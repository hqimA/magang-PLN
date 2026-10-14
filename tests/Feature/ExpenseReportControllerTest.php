<?php

namespace Tests\Feature;

use App\Exports\ExpenseReportExport;
use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExpenseReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_monthly_service_and_repair_expenses_per_vehicle(): void
    {
        $admin = User::factory()->create(['peran' => 'ADMIN']);
        $manager = User::factory()->create(['peran' => 'PENGELOLA']);
        $kendaraanDenganBiaya = Kendaraan::factory()->for($manager, 'pengelola')->create([
            'plat_nomor' => 'B 1234 PLN',
            'merk_tipe' => 'Toyota Avanza',
            'status_perawatan' => 'BAIK',
        ]);
        $kendaraanTanpaBiaya = Kendaraan::factory()->for($manager, 'pengelola')->create([
            'plat_nomor' => 'B 5678 PLN',
            'merk_tipe' => 'Mitsubishi Xpander',
            'status_perawatan' => 'BAIK',
        ]);

        DB::table('riwayat_servis')->insert([
            [
                'id_kendaraan' => $kendaraanDenganBiaya->id,
                'tanggal_servis' => '2026-09-05',
                'kilometer_servis' => 1000,
                'nama_bengkel' => 'Bengkel PLN',
                'total_biaya' => 125000,
                'id_pembuat' => $manager->id,
            ],
            [
                'id_kendaraan' => $kendaraanDenganBiaya->id,
                'tanggal_servis' => '2026-08-05',
                'kilometer_servis' => 900,
                'nama_bengkel' => 'Bengkel PLN',
                'total_biaya' => 90000,
                'id_pembuat' => $manager->id,
            ],
        ]);

        $laporanId = DB::table('laporan_kerusakan')->insertGetId([
            'id_kendaraan' => $kendaraanDenganBiaya->id,
            'id_pelapor' => $manager->id,
            'tanggal_kejadian' => '2026-09-04 09:00:00',
            'lokasi_kejadian' => 'Kantor PLN',
            'deskripsi_kerusakan' => 'Perlu perbaikan',
            'tingkat_kerusakan' => 'RINGAN',
        ]);
        DB::table('riwayat_perbaikan')->insert([
            'id_laporan_kerusakan' => $laporanId,
            'tanggal_perbaikan' => '2026-09-10',
            'nama_bengkel' => 'Bengkel PLN',
            'ringkasan_perbaikan' => 'Penggantian komponen',
            'total_biaya_perbaikan' => 75000,
            'id_pembuat' => $manager->id,
        ]);

        $response = $this->actingAs($admin)->get(route('laporan-pengeluaran.index', [
            'bulan' => 9,
            'tahun' => 2026,
        ]));

        $response->assertOk()->assertViewIs('admin.reports.expense');
        $this->assertSame([
            [
                'plat_nomor' => 'B 1234 PLN',
                'merk_tipe' => 'Toyota Avanza',
                'pengelola' => $manager->name,
                'total_biaya_servis' => 125000.0,
                'total_biaya_perbaikan' => 75000.0,
                'grand_total_pengeluaran' => 200000.0,
            ],
            [
                'plat_nomor' => 'B 5678 PLN',
                'merk_tipe' => 'Mitsubishi Xpander',
                'pengelola' => $manager->name,
                'total_biaya_servis' => 0.0,
                'total_biaya_perbaikan' => 0.0,
                'grand_total_pengeluaran' => 0.0,
            ],
        ], $response->viewData('rekap'));
        $this->assertSame(9, $response->viewData('bulan'));
        $this->assertSame(2026, $response->viewData('tahun'));
    }

    public function test_pengelola_cannot_access_expense_report(): void
    {
        $manager = User::factory()->pengelola()->create();

        $response = $this->actingAs($manager)->get(route('laporan-pengeluaran.index'));

        $response->assertForbidden();
    }

    public function test_admin_report_includes_imported_vehicles_without_a_manager(): void
    {
        $admin = User::factory()->admin()->create();
        $kendaraan = Kendaraan::factory()->state([
            'id_pengelola' => null,
            'plat_nomor' => 'B 4321 PLN',
        ])->create();

        $response = $this->actingAs($admin)->get(route('laporan-pengeluaran.index'));

        $response->assertOk();
        $this->assertSame([
            [
                'plat_nomor' => 'B 4321 PLN',
                'merk_tipe' => $kendaraan->merk_tipe,
                'pengelola' => '-',
                'total_biaya_servis' => 0.0,
                'total_biaya_perbaikan' => 0.0,
                'grand_total_pengeluaran' => 0.0,
            ],
        ], $response->viewData('rekap'));
    }

    public function test_guest_is_redirected_to_login_from_expense_report(): void
    {
        $response = $this->get(route('laporan-pengeluaran.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_download_excel_expense_report(): void
    {
        $admin = User::factory()->create(['peran' => 'ADMIN']);
        $manager = User::factory()->create(['peran' => 'PENGELOLA']);
        $kendaraan = Kendaraan::factory()->for($manager, 'pengelola')->create([
            'plat_nomor' => 'B 9001 PLN',
            'merk_tipe' => 'Toyota Innova',
            'status_perawatan' => 'BAIK',
        ]);
        DB::table('riwayat_servis')->insert([
            'id_kendaraan' => $kendaraan->id,
            'tanggal_servis' => '2026-09-05',
            'kilometer_servis' => 1000,
            'nama_bengkel' => 'Bengkel PLN',
            'total_biaya' => 125000,
            'id_pembuat' => $manager->id,
        ]);
        $laporanId = DB::table('laporan_kerusakan')->insertGetId([
            'id_kendaraan' => $kendaraan->id,
            'id_pelapor' => $manager->id,
            'tanggal_kejadian' => '2026-09-04 09:00:00',
            'lokasi_kejadian' => 'Kantor PLN',
            'deskripsi_kerusakan' => 'Perlu perbaikan',
            'tingkat_kerusakan' => 'RINGAN',
        ]);
        DB::table('riwayat_perbaikan')->insert([
            'id_laporan_kerusakan' => $laporanId,
            'tanggal_perbaikan' => '2026-09-10',
            'nama_bengkel' => 'Bengkel PLN',
            'ringkasan_perbaikan' => 'Penggantian komponen',
            'total_biaya_perbaikan' => 75000,
            'id_pembuat' => $manager->id,
        ]);
        Excel::fake();

        $response = $this->actingAs($admin)->get(route('laporan-pengeluaran.excel', [
            'bulan' => 9,
            'tahun' => 2026,
        ]));

        $response->assertOk();
        Excel::assertDownloaded('laporan-pengeluaran-2026-09.xlsx', function (ExpenseReportExport $export) use ($manager): bool {
            return $export->collection()->all() === [[
                'B 9001 PLN',
                'Toyota Innova',
                $manager->name,
                125000.0,
                75000.0,
                200000.0,
            ]];
        });
    }

    public function test_admin_can_download_full_history_archive_and_receipt_is_recorded_for_that_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($manager, 'pengelola')->create();
        $this->createReportHistory($kendaraan, $manager);
        Excel::fake();

        $response = $this->actingAs($admin)->get(route('laporan-pengeluaran.archive'));

        $response->assertOk();
        Excel::assertDownloaded('arsip-seluruh-data-laporan.xlsx', function ($export): bool {
            return count($export->sheets()) === 5;
        });
        $this->assertDatabaseHas('expense_report_export_receipts', [
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_archive_route_generates_a_real_excel_download(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('laporan-pengeluaran.archive'));

        $response->assertOk();
        $this->assertDatabaseHas('expense_report_export_receipts', [
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_cannot_delete_report_history_before_exporting_full_archive(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($manager, 'pengelola')->create();
        $this->createReportHistory($kendaraan, $manager);

        $response = $this->from(route('laporan-pengeluaran.index'))
            ->actingAs($admin)
            ->delete(route('laporan-pengeluaran.destroy-all'));

        $response->assertRedirect(route('laporan-pengeluaran.index'))
            ->assertSessionHasErrors('export');
        $this->assertDatabaseCount('laporan_kerusakan', 1);
        $this->assertDatabaseCount('riwayat_servis', 1);
        $this->assertDatabaseCount('riwayat_perbaikan', 1);
    }

    public function test_admin_can_delete_all_report_history_after_exporting_unchanged_full_archive(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($manager, 'pengelola')->create();
        $this->createReportHistory($kendaraan, $manager);
        Excel::fake();
        $this->actingAs($admin)->get(route('laporan-pengeluaran.archive'))->assertOk();
        Excel::assertDownloaded('arsip-seluruh-data-laporan.xlsx');

        $response = $this->from(route('laporan-pengeluaran.index'))
            ->actingAs($admin)
            ->delete(route('laporan-pengeluaran.destroy-all'));

        $response->assertRedirect(route('laporan-pengeluaran.index'))
            ->assertSessionHas('success', 'Semua data riwayat servis, perbaikan, dan laporan kerusakan berhasil dihapus.');
        $this->assertDatabaseCount('laporan_kerusakan', 0);
        $this->assertDatabaseCount('riwayat_servis', 0);
        $this->assertDatabaseCount('riwayat_perbaikan', 0);
        $this->assertDatabaseCount('rincian_sparepart', 0);
        $this->assertDatabaseMissing('expense_report_export_receipts', ['user_id' => $admin->id]);
        $this->assertModelExists($kendaraan);
    }

    public function test_admin_cannot_delete_report_history_using_another_admins_export_receipt(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $manager = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($manager, 'pengelola')->create();
        $this->createReportHistory($kendaraan, $manager);
        Excel::fake();
        $this->actingAs($admin)->get(route('laporan-pengeluaran.archive'))->assertOk();
        Excel::assertDownloaded('arsip-seluruh-data-laporan.xlsx');

        $response = $this->from(route('laporan-pengeluaran.index'))
            ->actingAs($otherAdmin)
            ->delete(route('laporan-pengeluaran.destroy-all'));

        $response->assertRedirect(route('laporan-pengeluaran.index'))
            ->assertSessionHasErrors('export');
        $this->assertDatabaseCount('laporan_kerusakan', 1);
        $this->assertDatabaseCount('riwayat_servis', 1);
    }

    public function test_admin_must_reexport_full_archive_if_report_history_changes(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($manager, 'pengelola')->create();
        $this->createReportHistory($kendaraan, $manager);
        Excel::fake();
        $this->actingAs($admin)->get(route('laporan-pengeluaran.archive'))->assertOk();
        Excel::assertDownloaded('arsip-seluruh-data-laporan.xlsx');
        DB::table('riwayat_servis')->update(['total_biaya' => 999999]);

        $response = $this->from(route('laporan-pengeluaran.index'))
            ->actingAs($admin)
            ->delete(route('laporan-pengeluaran.destroy-all'));

        $response->assertRedirect(route('laporan-pengeluaran.index'))
            ->assertSessionHasErrors('export');
        $this->assertDatabaseCount('riwayat_servis', 1);
        $this->assertDatabaseHas('riwayat_servis', ['total_biaya' => 999999]);
    }

    public function test_admin_report_page_keeps_export_actions_without_import_controls(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('laporan-pengeluaran.index'));

        $response->assertOk()
            ->assertSee('Ekspor Excel')
            ->assertSee('Ekspor PDF')
            ->assertSee('Hapus Semua Data Laporan')
            ->assertSee('Sudah ekspor arsip lengkap laporan?')
            ->assertSee('Belum, ekspor dulu')
            ->assertSee(route('laporan-pengeluaran.archive'))
            ->assertDontSee('confirm-delete-report-data')
            ->assertDontSee('Impor Riwayat dari Excel')
            ->assertDontSee('Unduh template impor');
    }

    public function test_pengelola_cannot_download_expense_excel_or_pdf(): void
    {
        $manager = User::factory()->pengelola()->create();

        $this->actingAs($manager)->get(route('laporan-pengeluaran.excel'))->assertForbidden();
        $this->actingAs($manager)->get(route('laporan-pengeluaran.pdf'))->assertForbidden();
        $this->actingAs($manager)->get(route('laporan-pengeluaran.archive'))->assertForbidden();
        $this->actingAs($manager)
            ->delete(route('laporan-pengeluaran.destroy-all'))
            ->assertForbidden();
    }

    public function test_admin_can_download_pdf_expense_report(): void
    {
        $admin = User::factory()->create(['peran' => 'ADMIN']);

        $response = $this->actingAs($admin)->get(route('laporan-pengeluaran.pdf', [
            'bulan' => 9,
            'tahun' => 2026,
        ]));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    private function createReportHistory(Kendaraan $kendaraan, User $manager): void
    {
        DB::table('riwayat_servis')->insert([
            'id_kendaraan' => $kendaraan->id,
            'tanggal_servis' => '2026-09-05',
            'kilometer_servis' => 1000,
            'nama_bengkel' => 'Bengkel PLN',
            'total_biaya' => 125000,
            'id_pembuat' => $manager->id,
        ]);
        $serviceHistoryId = (int) DB::table('riwayat_servis')->latest('id')->value('id');
        DB::table('rincian_sparepart')->insert([
            'id_riwayat_servis' => $serviceHistoryId,
            'nama_sparepart' => 'Filter oli',
            'jumlah' => 1,
            'harga_satuan' => 25000,
            'subtotal' => 25000,
        ]);
        $damageReportId = DB::table('laporan_kerusakan')->insertGetId([
            'id_kendaraan' => $kendaraan->id,
            'id_pelapor' => $manager->id,
            'tanggal_kejadian' => '2026-09-04 09:00:00',
            'lokasi_kejadian' => 'Kantor PLN',
            'deskripsi_kerusakan' => 'Perlu perbaikan',
            'tingkat_kerusakan' => 'RINGAN',
        ]);
        DB::table('riwayat_perbaikan')->insert([
            'id_laporan_kerusakan' => $damageReportId,
            'tanggal_perbaikan' => '2026-09-10',
            'nama_bengkel' => 'Bengkel PLN',
            'ringkasan_perbaikan' => 'Penggantian komponen',
            'total_biaya_perbaikan' => 75000,
            'id_pembuat' => $manager->id,
        ]);
    }
}
