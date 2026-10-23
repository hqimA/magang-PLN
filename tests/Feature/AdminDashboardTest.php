<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\ServiceReminderSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_actual_fleet_and_maintenance_summaries(): void
    {
        $this->travelTo('2026-09-28 12:00:00');
        $admin = User::factory()->create(['peran' => 'ADMIN']);
        $manager = User::factory()->create(['peran' => 'PENGELOLA']);
        $vehicleInService = Kendaraan::factory()->for($manager, 'pengelola')->create([
            'status_perawatan' => 'PERLU_SERVIS',
            'kategori_penggunaan' => 'PEJABAT',
            'kilometer_terakhir' => 5900,
        ]);
        $vehicleOperational = Kendaraan::factory()->for($manager, 'pengelola')->create([
            'status_perawatan' => 'BAIK',
            'kategori_penggunaan' => 'TEKNISI',
            'kilometer_terakhir' => 1000,
        ]);
        Kendaraan::factory()->for($manager, 'pengelola')->create([
            'status_perawatan' => 'BAIK',
            'tanggal_kir_berlaku_sampai' => '2026-09-27',
            'kilometer_terakhir' => 1000,
        ]);

        DB::table('pengajuan_servis')->insert([
            [
                'id_kendaraan' => $vehicleInService->id,
                'id_pengaju' => $manager->id,
                'jenis_pengajuan' => 'RUTIN',
                'deskripsi_keluhan' => 'Servis berkala',
                'status_persetujuan' => 'MENUNGGU',
                'dibuat_pada' => '2026-09-20 09:00:00',
            ],
            [
                'id_kendaraan' => $vehicleOperational->id,
                'id_pengaju' => $manager->id,
                'jenis_pengajuan' => 'RUTIN',
                'deskripsi_keluhan' => 'Servis berkala',
                'status_persetujuan' => 'DISETUJUI',
                'dibuat_pada' => '2026-09-21 09:00:00',
            ],
        ]);

        DB::table('komponen_kendaraan')->insert([
            'id_kendaraan' => $vehicleOperational->id,
            'nama_komponen' => 'Filter oli',
            'kategori' => 'KOMPONEN_DASAR_MESIN',
            'status' => 'JATUH_TEMPO',
            'is_aktif' => true,
        ]);

        $activeReportId = DB::table('laporan_kerusakan')->insertGetId([
            'id_kendaraan' => $vehicleInService->id,
            'id_pelapor' => $manager->id,
            'tanggal_kejadian' => '2026-09-20 09:00:00',
            'lokasi_kejadian' => 'Kantor PLN',
            'deskripsi_kerusakan' => 'Kendaraan memerlukan pemeriksaan',
            'tingkat_kerusakan' => 'RINGAN',
            'status_penanganan' => 'DILAPORKAN',
        ]);
        $completedReportId = DB::table('laporan_kerusakan')->insertGetId([
            'id_kendaraan' => $vehicleOperational->id,
            'id_pelapor' => $manager->id,
            'tanggal_kejadian' => '2026-09-10 09:00:00',
            'lokasi_kejadian' => 'Kantor PLN',
            'deskripsi_kerusakan' => 'Kerusakan selesai ditangani',
            'tingkat_kerusakan' => 'RINGAN',
            'status_penanganan' => 'SELESAI',
        ]);

        DB::table('riwayat_servis')->insert([
            [
                'id_kendaraan' => $vehicleInService->id,
                'tanggal_servis' => '2026-09-15',
                'kilometer_servis' => 1000,
                'nama_bengkel' => 'Bengkel PLN',
                'total_biaya' => 1250000,
                'id_pembuat' => $manager->id,
            ],
            [
                'id_kendaraan' => $vehicleInService->id,
                'tanggal_servis' => '2026-08-15',
                'kilometer_servis' => 500,
                'nama_bengkel' => 'Bengkel PLN',
                'total_biaya' => 900000,
                'id_pembuat' => $manager->id,
            ],
        ]);

        DB::table('riwayat_perbaikan')->insert([
            [
                'id_laporan_kerusakan' => $activeReportId,
                'tanggal_perbaikan' => '2026-09-22',
                'nama_bengkel' => 'Bengkel PLN',
                'ringkasan_perbaikan' => 'Penggantian komponen',
                'total_biaya_perbaikan' => 750000,
                'id_pembuat' => $manager->id,
            ],
            [
                'id_laporan_kerusakan' => $completedReportId,
                'tanggal_perbaikan' => '2026-08-22',
                'nama_bengkel' => 'Bengkel PLN',
                'ringkasan_perbaikan' => 'Perbaikan sebelumnya',
                'total_biaya_perbaikan' => 350000,
                'id_pembuat' => $manager->id,
            ],
        ]);

        DB::table('notifikasi')->insert([
            'id_pengguna' => $admin->id,
            'judul' => 'Pengajuan baru',
            'pesan' => 'Ada pengajuan maintenance untuk ditinjau.',
            'sudah_dibaca' => false,
            'tipe_referensi' => 'pengajuan_servis:1',
            'dibuat_pada' => '2026-09-22 10:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertSee('admin-dashboard-page', false)
            ->assertSee(route('maintenance.index', ['tab' => 'pengajuan']), false)
            ->assertSee(route('maintenance.show', 1), false)
            ->assertSee('Pengajuan baru')
            ->assertSee('Service Reminder')
            ->assertSee('Selamat datang, '.$admin->name)
            ->assertDontSee('Approval Maintenance')
            ->assertDontSee('Riwayat Maintenance');

        $stats = $response->viewData('stats');
        $this->assertSame(3, $stats['totalVehicles']);
        $this->assertSame(1, $stats['activeMaintenance']);
        $this->assertSame(1, $stats['completedMaintenance']);
        $this->assertSame(1, $stats['serviceReminders']);

        $this->assertSame([0.0, 0.0, 0.0, 0.0, 1250000.0, 2000000.0], $response->viewData('expenseChart')['values']);
        $this->assertTrue($response->viewData('hasExpenseData'));
        $response->assertDontSee('Status kendaraan')
            ->assertDontSee('vehicle-status-chart');
        $this->assertSame([0, 0, 0, 0, 1, 1], $response->viewData('maintenanceChart')['values']);
        $this->assertSame([
            'MENUNGGU' => 1,
            'DISETUJUI' => 1,
            'DITOLAK' => 0,
        ], $response->viewData('approvalSummary'));
        $this->assertSame(1, $response->viewData('unreadNotificationsCount'));
        $this->assertSame('Pengajuan baru', $response->viewData('latestNotifications')->first()->judul);
    }

    public function test_admin_dashboard_shows_empty_states_when_no_records_exist(): void
    {
        $admin = User::factory()->create(['peran' => 'ADMIN']);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Belum ada data biaya maintenance pada periode ini.')
            ->assertSee('Belum ada riwayat servis pada periode ini.')
            ->assertSee('Belum ada pengajuan maintenance.')
            ->assertSee('Belum ada notifikasi.');

        $this->assertSame([
            'totalVehicles' => 0,
            'activeMaintenance' => 0,
            'completedMaintenance' => 0,
            'serviceReminders' => 0,
        ], $response->viewData('stats'));
        $this->assertFalse($response->viewData('hasExpenseData'));
    }

    public function test_admin_dashboard_service_reminder_count_is_zero_when_reminders_are_disabled(): void
    {
        $admin = User::factory()->create(['peran' => 'ADMIN']);
        $manager = User::factory()->create(['peran' => 'PENGELOLA']);
        Kendaraan::factory()->for($manager, 'pengelola')->create([
            'kilometer_terakhir' => 5100,
            'tanggal_pembelian' => '2026-01-01',
        ]);
        ServiceReminderSetting::create(['is_active' => false]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $this->assertSame(0, $response->viewData('stats')['serviceReminders']);
    }

    public function test_pengelola_receives_the_manager_dashboard(): void
    {
        $manager = User::factory()->create(['peran' => 'PENGELOLA']);

        $response = $this->actingAs($manager)->get(route('dashboard'));

        $response->assertOk()
            ->assertViewIs('pengelola.dashboard')
            ->assertSee('Dashboard Pengelola');
    }

    public function test_admin_dashboard_displays_monthly_fleet_kpis(): void
    {
        $this->travelTo('2026-09-28 12:00:00');
        $admin = User::factory()->create(['peran' => 'ADMIN']);
        $manager = User::factory()->create(['peran' => 'PENGELOLA']);
        $vehicleInService = Kendaraan::factory()->for($manager, 'pengelola')->create([
            'status_perawatan' => 'PERLU_SERVIS',
            'kategori_penggunaan' => 'PEJABAT',
        ]);
        $vehicleOperational = Kendaraan::factory()->for($manager, 'pengelola')->create([
            'status_perawatan' => 'BAIK',
            'kategori_penggunaan' => 'TEKNISI',
        ]);

        DB::table('pengajuan_servis')->insert([
            [
                'id_kendaraan' => $vehicleInService->id,
                'id_pengaju' => $manager->id,
                'jenis_pengajuan' => 'RUTIN',
                'deskripsi_keluhan' => 'Servis berkala',
                'status_persetujuan' => 'MENUNGGU',
            ],
            [
                'id_kendaraan' => $vehicleOperational->id,
                'id_pengaju' => $manager->id,
                'jenis_pengajuan' => 'RUTIN',
                'deskripsi_keluhan' => 'Servis berkala',
                'status_persetujuan' => 'DISETUJUI',
            ],
        ]);

        $activeReportId = DB::table('laporan_kerusakan')->insertGetId([
            'id_kendaraan' => $vehicleInService->id,
            'id_pelapor' => $manager->id,
            'tanggal_kejadian' => '2026-09-20 09:00:00',
            'lokasi_kejadian' => 'Kantor PLN',
            'deskripsi_kerusakan' => 'Kendaraan memerlukan pemeriksaan',
            'tingkat_kerusakan' => 'RINGAN',
            'status_penanganan' => 'DILAPORKAN',
        ]);
        $completedReportId = DB::table('laporan_kerusakan')->insertGetId([
            'id_kendaraan' => $vehicleOperational->id,
            'id_pelapor' => $manager->id,
            'tanggal_kejadian' => '2026-09-10 09:00:00',
            'lokasi_kejadian' => 'Kantor PLN',
            'deskripsi_kerusakan' => 'Kerusakan selesai ditangani',
            'tingkat_kerusakan' => 'RINGAN',
            'status_penanganan' => 'SELESAI',
        ]);

        DB::table('riwayat_servis')->insert([
            [
                'id_kendaraan' => $vehicleInService->id,
                'tanggal_servis' => '2026-09-15',
                'kilometer_servis' => 1000,
                'nama_bengkel' => 'Bengkel PLN',
                'total_biaya' => 1250000,
                'id_pembuat' => $manager->id,
            ],
            [
                'id_kendaraan' => $vehicleInService->id,
                'tanggal_servis' => '2026-08-15',
                'kilometer_servis' => 500,
                'nama_bengkel' => 'Bengkel PLN',
                'total_biaya' => 900000,
                'id_pembuat' => $manager->id,
            ],
        ]);

        DB::table('riwayat_perbaikan')->insert([
            [
                'id_laporan_kerusakan' => $activeReportId,
                'tanggal_perbaikan' => '2026-09-22',
                'nama_bengkel' => 'Bengkel PLN',
                'ringkasan_perbaikan' => 'Penggantian komponen',
                'total_biaya_perbaikan' => 750000,
                'id_pembuat' => $manager->id,
            ],
            [
                'id_laporan_kerusakan' => $completedReportId,
                'tanggal_perbaikan' => '2026-08-22',
                'nama_bengkel' => 'Bengkel PLN',
                'ringkasan_perbaikan' => 'Perbaikan sebelumnya',
                'total_biaya_perbaikan' => 350000,
                'id_pembuat' => $manager->id,
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertSee(route('maintenance.index', ['tab' => 'usai']), false);

        $kpiData = $response->viewData('kpiData');

        $this->assertSame(2, $kpiData['totalKendaraan']);
        $this->assertSame(1, $kpiData['kendaraanBermasalah']);
        $this->assertSame(1, $kpiData['pendingApproval']);
        $this->assertSame(1, $kpiData['laporanKerusakanAktif']);
        $this->assertSame(2000000.0, $kpiData['totalPengeluaranBulanIni']);
        $this->assertEqualsCanonicalizing(
            [
                ['kategori_penggunaan' => 'PEJABAT', 'jumlah' => 1],
                ['kategori_penggunaan' => 'TEKNISI', 'jumlah' => 1],
            ],
            collect($kpiData['kategoriBreakdown'])
                ->map(fn (object $category): array => [
                    'kategori_penggunaan' => $category->kategori_penggunaan,
                    'jumlah' => (int) $category->jumlah,
                ])
                ->all(),
        );
    }

    public function test_non_admin_keeps_the_general_dashboard(): void
    {
        $manager = User::factory()->create(['peran' => 'PENGELOLA']);

        $response = $this->actingAs($manager)->get(route('dashboard'));

        $response->assertOk()->assertViewIs('dashboard');
    }

}
