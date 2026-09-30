<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        ]);
        $kendaraanTanpaBiaya = Kendaraan::factory()->for($manager, 'pengelola')->create([
            'plat_nomor' => 'B 5678 PLN',
            'merk_tipe' => 'Mitsubishi Xpander',
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

    public function test_non_admin_is_forbidden_from_expense_report(): void
    {
        $manager = User::factory()->create(['peran' => 'PENGELOLA']);

        $response = $this->actingAs($manager)->get(route('laporan-pengeluaran.index'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_from_expense_report(): void
    {
        $response = $this->get(route('laporan-pengeluaran.index'));

        $response->assertRedirect(route('login'));
    }
}
