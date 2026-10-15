<?php

namespace Tests\Feature;

use App\Exports\KendaraanImportTemplateExport;
use App\Models\Kendaraan;
use App\Models\KomponenKendaraan;
use App\Models\TemplateJadwalDetail;
use App\Models\TemplateJadwalServis;
use App\Models\TemplateKomponen;
use App\Models\User;
use App\Services\CloudinaryService;
use App\Services\RemoteImageDownloader;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\Fakes\FakeCloudinaryService;
use Tests\TestCase;

class KendaraanImportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->app->instance(CloudinaryService::class, new FakeCloudinaryService);
    }

    public function test_admin_can_import_vehicles_without_assigning_a_manager(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->createImportFile([
            $this->vehicleRow('B 1234 PLN'),
        ]);

        $response = $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file]);

        $response->assertRedirect(route('kendaraan.index'))
            ->assertSessionHas('success', 'Import berhasil: 1 kendaraan ditambahkan.');

        $indexResponse = $this->actingAs($admin)->get(route('kendaraan.index'));
        $this->assertSame(
            1,
            substr_count($indexResponse->getContent(), 'Import berhasil: 1 kendaraan ditambahkan.'),
        );

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

    public function test_imported_vehicle_receives_components_and_actions_from_its_matching_template(): void
    {
        $admin = User::factory()->admin()->create();
        $template = TemplateJadwalServis::create([
            'nama' => 'Bensin Manual',
            'jenis_bbm' => 'BENSIN',
            'transmisi' => 'MANUAL',
            'is_aktif' => true,
        ]);
        $templateComponent = TemplateKomponen::create([
            'id_template' => $template->id,
            'nomor_urut' => 1,
            'nama_komponen' => 'Oli mesin',
            'kategori' => 'KOMPONEN_DASAR_MESIN',
            'is_aktif' => true,
        ]);
        TemplateJadwalDetail::create([
            'id_template_komponen' => $templateComponent->id,
            'interval_km' => 10000,
            'interval_bulan' => 6,
            'jenis_aksi' => 'G',
        ]);
        TemplateJadwalDetail::create([
            'id_template_komponen' => $templateComponent->id,
            'interval_km' => 20000,
            'interval_bulan' => 6,
            'jenis_aksi' => 'P',
        ]);
        $file = $this->createImportFile([
            $this->vehicleRow('B 1234 PLN'),
        ]);

        $this->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'));

        $kendaraan = Kendaraan::query()->where('plat_nomor', 'B 1234 PLN')->firstOrFail();
        $this->assertSame($template->id, $kendaraan->id_template);
        $this->assertDatabaseHas('komponen_kendaraan', [
            'id_kendaraan' => $kendaraan->id,
            'id_template_komponen' => $templateComponent->id,
            'nama_komponen' => 'Oli mesin',
            'jenis_aksi_default' => 'G',
            'km_jatuh_tempo' => 10000,
            'status' => 'JATUH_TEMPO',
        ]);
        $this->assertSame(1, KomponenKendaraan::query()->where('id_kendaraan', $kendaraan->id)->count());
    }

    public function test_admin_can_import_angku_barang_category_written_with_spaces(): void
    {
        $admin = User::factory()->admin()->create();
        $row = $this->vehicleRow('B 1233 PLN');
        $row[6] = 'Angkut Barang';
        $file = $this->createImportFile([$row]);

        $this->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'));

        $this->assertDatabaseHas('kendaraan', [
            'plat_nomor' => 'B 1233 PLN',
            'kategori_penggunaan' => 'ANGKUT_BARANG',
        ]);
    }

    public function test_admin_can_import_a_public_https_vehicle_photo_url(): void
    {
        $admin = User::factory()->admin()->create();
        $row = $this->vehicleRow('B 1235 PLN');
        $row[10] = 'https://8.8.8.8/vehicle.png';
        $this->useImageResponses([new Response(200, ['Content-Type' => 'image/png'], $this->validPng())]);
        $file = $this->createImportFile([$row]);

        $this->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'));

        $kendaraan = Kendaraan::query()->where('plat_nomor', 'B 1235 PLN')->firstOrFail();
        $this->assertNotNull($kendaraan->foto_kendaraan);
        $this->assertStringStartsWith(
            'https://res.cloudinary.com/test/image/upload/v123/kendaraan/',
            $kendaraan->foto_kendaraan,
        );
        $this->assertCount(1, $this->cloudinaryFake()->uploadedUrls);
    }

    public function test_import_rejects_non_https_photo_urls_without_saving_vehicles(): void
    {
        $admin = User::factory()->admin()->create();
        $row = $this->vehicleRow('B 1236 PLN');
        $row[10] = 'http://8.8.8.8/vehicle.png';
        $file = $this->createImportFile([$row]);

        $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('kendaraan', 0);
        $this->assertSame([], $this->cloudinaryFake()->uploadedUrls);
    }

    public function test_import_rejects_embedded_vehicle_photos(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->createImportFile([
            $this->vehicleRow('B 1239 PLN'),
        ], includePhoto: true);

        $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('kendaraan', 0);
    }

    public function test_import_rejects_redirects_to_private_addresses(): void
    {
        $admin = User::factory()->admin()->create();
        $row = $this->vehicleRow('B 1240 PLN');
        $row[10] = 'https://8.8.8.8/redirect.png';
        $this->useImageResponses([
            new Response(302, ['Location' => 'https://127.0.0.1/private.png']),
        ]);
        $file = $this->createImportFile([$row]);

        $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('kendaraan', 0);
    }

    public function test_import_rejects_photo_hosts_that_resolve_to_private_addresses(): void
    {
        $admin = User::factory()->admin()->create();
        $row = $this->vehicleRow('B 1243 PLN');
        $row[10] = 'https://images.example.test/vehicle.png';
        $this->useImageResponses(
            [new Response(200, ['Content-Type' => 'image/png'], $this->validPng())],
            '192.168.1.10',
        );
        $file = $this->createImportFile([$row]);

        $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('kendaraan', 0);
        $this->assertSame([], $this->cloudinaryFake()->uploadedUrls);
    }

    public function test_import_rejects_remote_responses_that_are_not_images(): void
    {
        $admin = User::factory()->admin()->create();
        $row = $this->vehicleRow('B 1244 PLN');
        $row[10] = 'https://8.8.8.8/vehicle.png';
        $this->useImageResponses([new Response(200, ['Content-Type' => 'image/png'], '<html>not an image</html>')]);
        $file = $this->createImportFile([$row]);

        $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('kendaraan', 0);
        $this->assertSame([], $this->cloudinaryFake()->uploadedUrls);
    }

    public function test_import_rejects_remote_images_larger_than_ten_megabytes(): void
    {
        $admin = User::factory()->admin()->create();
        $row = $this->vehicleRow('B 1245 PLN');
        $row[10] = 'https://8.8.8.8/vehicle.png';
        $this->useImageResponses([
            new Response(200, ['Content-Type' => 'image/png'], str_repeat('x', 10 * 1024 * 1024 + 1)),
        ]);
        $file = $this->createImportFile([$row]);

        $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('kendaraan', 0);
        $this->assertSame([], $this->cloudinaryFake()->uploadedUrls);
    }

    public function test_import_deletes_uploaded_photos_when_a_later_photo_fails(): void
    {
        $admin = User::factory()->admin()->create();
        $firstRow = $this->vehicleRow('B 1241 PLN');
        $firstRow[10] = 'https://8.8.8.8/first.png';
        $secondRow = $this->vehicleRow('B 1242 PLN');
        $secondRow[10] = 'https://8.8.8.8/missing.png';
        $this->useImageResponses([
            new Response(200, ['Content-Type' => 'image/png'], $this->validPng()),
            new Response(404),
        ]);
        $file = $this->createImportFile([$firstRow, $secondRow]);

        $this->from(route('kendaraan.index'))
            ->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('kendaraan', 0);
        $cloudinary = $this->cloudinaryFake();
        $this->assertCount(1, $cloudinary->uploadedUrls);
        $this->assertSame($cloudinary->uploadedUrls, $cloudinary->deletedUrls);
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

    public function test_imported_vehicle_is_assigned_to_the_manager_with_a_matching_category(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->pengelola()->create([
            'kategori_kendaraan' => 'TEKNISI',
        ]);
        $file = $this->createImportFile([
            $this->vehicleRow('B 1236 PLN'),
        ]);

        $this->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'));

        $kendaraan = Kendaraan::query()->where('plat_nomor', 'B 1236 PLN')->firstOrFail();

        $this->assertSame($manager->id, $kendaraan->id_pengelola);

        $this->actingAs($admin)
            ->get(route('kendaraan.edit', $kendaraan->id))
            ->assertOk()
            ->assertSee($manager->name);

        $this->actingAs($manager)
            ->get(route('kendaraan.index'))
            ->assertOk()
            ->assertSee($kendaraan->plat_nomor);
    }

    public function test_each_imported_category_is_assigned_to_its_configured_manager(): void
    {
        $admin = User::factory()->admin()->create();
        $managers = [
            'PEJABAT' => User::factory()->pengelola()->create([
                'email' => 'bahar@example.test',
                'kategori_kendaraan' => 'PEJABAT',
            ]),
            'MOTOR_OPERASIONAL' => User::factory()->pengelola()->create([
                'email' => 'motor@example.test',
                'kategori_kendaraan' => 'MOTOR_OPERASIONAL',
            ]),
            'ANGKUT_BARANG' => User::factory()->pengelola()->create([
                'email' => 'angkut@example.test',
                'kategori_kendaraan' => 'ANGKUT_BARANG',
            ]),
            'TEKNISI' => User::factory()->pengelola()->create([
                'email' => 'teknisi@example.test',
                'kategori_kendaraan' => 'TEKNISI',
            ]),
        ];
        $rows = [];

        foreach (array_keys($managers) as $index => $category) {
            $row = $this->vehicleRow('B 130'.$index.' PLN');
            $row[6] = $category;
            $rows[] = $row;
        }

        $this->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $this->createImportFile($rows)])
            ->assertRedirect(route('kendaraan.index'));

        foreach (array_values($managers) as $index => $manager) {
            $this->assertDatabaseHas('kendaraan', [
                'plat_nomor' => 'B 130'.$index.' PLN',
                'id_pengelola' => $manager->id,
            ]);
        }
    }

    public function test_imported_vehicle_stays_unassigned_when_no_manager_matches_its_category(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->pengelola()->create([
            'kategori_kendaraan' => 'PEJABAT',
        ]);
        $file = $this->createImportFile([
            $this->vehicleRow('B 1237 PLN'),
        ]);

        $this->actingAs($admin)
            ->post(route('kendaraan.import'), ['file' => $file])
            ->assertRedirect(route('kendaraan.index'));

        $this->assertDatabaseHas('kendaraan', [
            'plat_nomor' => 'B 1237 PLN',
            'id_pengelola' => null,
        ]);
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

    public function test_import_rejects_a_photo_filename_instead_of_a_url(): void
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
     * @param  array<int, Response>  $responses
     */
    private function useImageResponses(array $responses, string $resolvedAddress = '8.8.8.8'): void
    {
        $client = new Client([
            'handler' => HandlerStack::create(new MockHandler($responses)),
        ]);

        $this->app->instance(
            RemoteImageDownloader::class,
            new RemoteImageDownloader($client, static fn (string $host): array => [$resolvedAddress]),
        );
    }

    private function cloudinaryFake(): FakeCloudinaryService
    {
        return $this->app->make(CloudinaryService::class);
    }

    private function validPng(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jN2sAAAAASUVORK5CYII=',
            true,
        );
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
