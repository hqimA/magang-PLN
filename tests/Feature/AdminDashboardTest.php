<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

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

        $response->assertOk()->assertViewIs('admin.dashboard');

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
