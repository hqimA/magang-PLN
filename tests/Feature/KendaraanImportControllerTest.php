<?php

namespace Tests\Feature;

use App\Exports\KendaraanImportTemplateExport;
use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class KendaraanImportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_import_vehicles_without_assigning_a_manager(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $file = $this->createImportFile([
            $this->vehicleRow('B 1234 PLN'),
        ]);

        $response = $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file]);

        $response->assertRedirect(route('kendaraan.index'))
            ->assertSessionHas('success', 'Import berhasil: 1 kendaraan ditambahkan.');
        $this->assertDatabaseHas('kendaraan', [
            'plat_nomor' => 'B 1234 PLN',
            'merk_tipe' => 'Toyota Avanza',
            'id_pengelola' => null,
            'kilometer_terakhir' => 10000,
            'interval_servis_km' => 5000,
            'interval_servis_bulan' => 3,
            'status_perawatan' => 'BAIK',
        ]);
    }

    public function test_admin_can_import_and_store_an_embedded_vehicle_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $file = $this->createImportFile([
            $this->vehicleRow('B 1235 PLN'),
        ], includePhoto: true);

        $this->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'));

        $kendaraan = Kendaraan::query()->where('plat_nomor', 'B 1235 PLN')->firstOrFail();
        $this->assertNotNull($kendaraan->foto_kendaraan);
        Storage::disk('public')->assertExists($kendaraan->foto_kendaraan);
    }

    public function test_import_accepts_excel_date_cells_and_converts_them_to_vehicle_dates(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->createImportFile([
            $this->vehicleRow('B 1237 PLN'),
        ], excelDateCells: true);

        $this->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'));

        $this->assertDatabaseHas('kendaraan', [
            'plat_nomor' => 'B 1237 PLN',
            'tanggal_pembelian' => '2022-01-01 00:00:00',
        ]);
    }

    public function test_import_accepts_day_month_year_dates_and_converts_them_to_vehicle_dates(): void
    {
        $admin = User::factory()->admin()->create();
        $row = $this->vehicleRow('B 1238 PLN');
        $row[7] = '01/01/2022';
        $row[8] = '01/01/2025';
        $row[9] = '01/01/2025';
        $file = $this->createImportFile([$row]);

        $this->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'));

        $this->assertDatabaseHas('kendaraan', [
            'plat_nomor' => 'B 1238 PLN',
            'tanggal_pembelian' => '2022-01-01 00:00:00',
            'tanggal_stnk_berlaku_sampai' => '2025-01-01 00:00:00',
            'tanggal_kir_berlaku_sampai' => '2025-01-01 00:00:00',
        ]);
    }

    public function test_pengelola_sees_an_imported_vehicle_only_after_admin_assigns_it(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->pengelola()->create();
        $file = $this->createImportFile([
            $this->vehicleRow('B 1236 PLN'),
        ]);

        $this->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'));

        $kendaraan = Kendaraan::query()->where('plat_nomor', 'B 1236 PLN')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('kendaraan.edit', $kendaraan->id))
            ->assertOk()
            ->assertSee($manager->name)
            ->assertDontSee($admin->name);

        $this->actingAs($manager)
            ->get(route('kendaraan.index'))
            ->assertOk()
            ->assertDontSee($kendaraan->plat_nomor);

        $this->actingAs($admin)
            ->put(route('kendaraan.update', $kendaraan->id), $this->vehicleUpdateData($kendaraan, $admin->id))
            ->assertSessionHasErrors('id_pengelola');

        $this->actingAs($admin)
            ->put(route('kendaraan.update', $kendaraan->id), $this->vehicleUpdateData($kendaraan, $manager->id))
            ->assertRedirect(route('kendaraan.index'));

        $this->actingAs($manager)
            ->get(route('kendaraan.index'))
            ->assertOk()
            ->assertSee($kendaraan->plat_nomor);
    }

    public function test_import_rejects_existing_plate_without_partially_creating_vehicles(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->pengelola()->create();
        Kendaraan::factory()->for($manager, 'pengelola')->create([
            'plat_nomor' => 'b 2000 pln',
        ]);
        $file = $this->createImportFile([
            $this->vehicleRow('B 2001 PLN'),
            $this->vehicleRow('B 2000 PLN'),
        ]);

        $response = $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file]);

        $response->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('kendaraan', 1);
        $this->assertDatabaseMissing('kendaraan', ['plat_nomor' => 'B 2001 PLN']);
    }

    public function test_import_rejects_duplicate_plates_in_file_without_creating_vehicles(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->createImportFile([
            $this->vehicleRow('B 3000 PLN'),
            $this->vehicleRow('b 3000 pln'),
        ]);

        $response = $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file]);

        $response->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('kendaraan', 0);
    }

    public function test_import_rejects_a_workbook_with_headers_out_of_template_order(): void
    {
        $admin = User::factory()->admin()->create();
        $headings = $this->headings();
        [$headings[0], $headings[1]] = [$headings[1], $headings[0]];
        $file = $this->createImportFile([
            $this->vehicleRow('B 4000 PLN'),
        ], headings: $headings);

        $response = $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file]);

        $response->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('kendaraan', 0);
    }

    public function test_import_rejects_invalid_vehicle_data_without_partially_importing_rows(): void
    {
        $admin = User::factory()->admin()->create();
        $invalidRow = $this->vehicleRow('B 5001 PLN');
        $invalidRow[3] = -1;
        $invalidRow[4] = 'AUTOMATIC';
        $file = $this->createImportFile([
            $this->vehicleRow('B 5000 PLN'),
            $invalidRow,
        ]);

        $response = $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file]);

        $response->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('kendaraan', 0);
    }

    public function test_import_rejects_vehicle_document_expiry_more_than_five_years_after_purchase(): void
    {
        $admin = User::factory()->admin()->create();
        $row = $this->vehicleRow('B 5002 PLN');
        $row[8] = '2028-01-01';
        $file = $this->createImportFile([$row]);

        $response = $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file]);

        $response->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('kendaraan', 0);
    }

    public function test_import_rejects_a_photo_reference_without_a_matching_file_in_the_zip(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->createImportFile([
            $this->vehicleRow('B 6000 PLN'),
        ], photoText: 'kendaraan.jpg');

        $response = $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file]);

        $response->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('kendaraan', 0);
    }

    public function test_import_rejects_formula_cells_instead_of_vehicle_values(): void
    {
        $admin = User::factory()->admin()->create();
        $row = $this->vehicleRow('B 6001 PLN');
        $row[1] = '=1+1';
        $file = $this->createImportFile([$row]);

        $response = $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file]);

        $response->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('kendaraan', 0);
    }

    public function test_import_rejects_files_that_are_not_excel_workbooks(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->createWithContent('kendaraan.txt', 'bukan file Excel');

        $response = $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file]);

        $response->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('kendaraan', 0);
    }

    public function test_admin_can_download_template_with_the_exact_vehicle_columns(): void
    {
        Excel::fake();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('kendaraan.import.template'))
            ->assertOk();
        Excel::assertDownloaded('template-impor-kendaraan.xlsx', function (KendaraanImportTemplateExport $export): bool {
            return $export->headings() === $this->headings();
        });

        $this->get(route('kendaraan.index'))
            ->assertOk()
            ->assertSee('Impor Kendaraan dari Excel')
            ->assertSee('Foto Kendaraan')
            ->assertSee('Pilih file Excel')
            ->assertSee(route('kendaraan.import'));
    }

    public function test_pengelola_can_only_view_assigned_vehicles_without_mutation_controls(): void
    {
        $manager = User::factory()->pengelola()->create();
        $otherManager = User::factory()->pengelola()->create();
        $vehicle = Kendaraan::factory()->for($manager, 'pengelola')->create([
            'plat_nomor' => 'B 7000 PLN',
        ]);
        $otherVehicle = Kendaraan::factory()->for($otherManager, 'pengelola')->create([
            'plat_nomor' => 'B 7001 PLN',
        ]);

        $this->actingAs($manager)
            ->get(route('kendaraan.index'))
            ->assertOk()
            ->assertSee($vehicle->plat_nomor)
            ->assertDontSee(route('kendaraan.create'))
            ->assertDontSee(route('kendaraan.edit', $vehicle->id))
            ->assertDontSee('Impor Kendaraan dari Excel');

        $this->get(route('kendaraan.show', $vehicle->id))
            ->assertOk()
            ->assertDontSee('Edit Kendaraan')
            ->assertDontSee('Terapkan Template')
            ->assertDontSee('Tambah Suku Cadang Khusus')
            ->assertDontSee(route('kendaraan.komponen.store', $vehicle->id));

        $this->get(route('kendaraan.show', $otherVehicle->id))->assertForbidden();
    }

    public function test_pengelola_cannot_create_edit_or_import_vehicles(): void
    {
        $manager = User::factory()->pengelola()->create();
        $vehicle = Kendaraan::factory()->for($manager, 'pengelola')->create();
        $file = $this->createImportFile([
            $this->vehicleRow('B 8000 PLN'),
        ]);

        $this->actingAs($manager)->get(route('kendaraan.create'))->assertForbidden();
        $this->post(route('kendaraan.store'), [])->assertForbidden();
        $this->get(route('kendaraan.edit', $vehicle->id))->assertForbidden();
        $this->put(route('kendaraan.update', $vehicle->id), [])->assertForbidden();
        $this->post(route('kendaraan.komponen.store', $vehicle->id), [])->assertForbidden();
        $this->get(route('kendaraan.import.template'))->assertForbidden();
        $this->post(route('kendaraan.import'), ['file' => $file])->assertForbidden();

        $this->assertDatabaseCount('kendaraan', 1);
    }

    /**
     * @return array<int, mixed>
     */
    private function vehicleRow(string $plate): array
    {
        return [
            $plate,
            'Toyota Avanza',
            2022,
            10000,
            'MANUAL',
            'BENSIN',
            'TEKNISI',
            '2022-01-01',
            null,
            null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function vehicleUpdateData(Kendaraan $kendaraan, int $managerId): array
    {
        return [
            'plat_nomor' => $kendaraan->plat_nomor,
            'merk_tipe' => $kendaraan->merk_tipe,
            'tahun_pembuatan' => $kendaraan->tahun_pembuatan,
            'transmisi' => $kendaraan->transmisi,
            'jenis_bbm' => $kendaraan->jenis_bbm,
            'kategori_penggunaan' => $kendaraan->kategori_penggunaan,
            'kilometer_terakhir' => $kendaraan->kilometer_terakhir,
            'tanggal_pembelian' => '2022-01-01',
            'status_perawatan' => $kendaraan->status_perawatan,
            'id_pengelola' => $managerId,
        ];
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, string>|null  $headings
     */
    private function createImportFile(
        array $rows,
        ?array $headings = null,
        bool $includePhoto = false,
        ?string $photoText = null,
        bool $excelDateCells = false,
    ): UploadedFile {
        $spreadsheet = new Spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->fromArray($headings ?? $this->headings(), null, 'A1');

        foreach ($rows as $index => $row) {
            $worksheet->fromArray($row, null, 'A'.($index + 2));
        }

        if ($excelDateCells) {
            $worksheet->setCellValue('H2', 44562);
            $worksheet->getStyle('H2')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        }

        if ($photoText !== null) {
            $worksheet->setCellValue('K2', $photoText);
        }

        $photoPath = null;
        if ($includePhoto) {
            $photoPath = tempnam(sys_get_temp_dir(), 'kendaraan-photo-');
            file_put_contents($photoPath, base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jN2sAAAAASUVORK5CYII=',
                true,
            ));
            $drawing = new Drawing;
            $drawing->setPath($photoPath);
            $drawing->setCoordinates('K2');
            $drawing->setWorksheet($worksheet);
        }

        $workbookPath = tempnam(sys_get_temp_dir(), 'kendaraan-import-');

        try {
            (new Xlsx($spreadsheet))->save($workbookPath);
            $spreadsheet->disconnectWorksheets();

            return UploadedFile::fake()->createWithContent('kendaraan.xlsx', file_get_contents($workbookPath));
        } finally {
            $spreadsheet->disconnectWorksheets();

            if (is_file($workbookPath)) {
                unlink($workbookPath);
            }

            if ($photoPath !== null && is_file($photoPath)) {
                unlink($photoPath);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function headings(): array
    {
        return [
            'Nomor Polisi',
            'Nama Kendaraan',
            'Tahun Pembuatan',
            'Kilometer',
            'Transmisi',
            'Jenis BBM',
            'Kategori Penggunaan',
            'Tanggal Pembelian',
            'STNK Berlaku Sampai',
            'KIR Berlaku Sampai',
            'Foto Kendaraan',
        ];
    }
}
