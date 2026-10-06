<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\Mileage;
use App\Models\PengajuanServis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_tab_pengajuan_menampilkan_kilometer_akhir_dari_table_mileage(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'plat_nomor' => 'B 1234 PLN',
            'kilometer_terakhir' => 5000,
        ]);

        Mileage::create([
            'id_kendaraan' => $kendaraan->id,
            'tanggal_perjalanan' => now()->subDays(2)->toDateString(),
            'kilometer_awal' => 5000,
            'kilometer_akhir' => 7500,
            'status_perjalanan' => 'SELESAI',
            'id_pencatat' => $pengelola->id,
        ]);

        PengajuanServis::create([
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengelola->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Servis berkala',
            'kilometer_pengajuan' => 7500,
            'status_persetujuan' => 'MENUNGGU',
            'dibuat_pada' => now(),
        ]);

        $response = $this->actingAs($pengelola)->get(route('maintenance.index', ['tab' => 'pengajuan']));

        $response->assertOk();
        $response->assertSee('KM');
        $response->assertSee('7,500 km');
    }

    public function test_tab_aktif_menampilkan_kilometer_akhir_dari_table_mileage(): void
    {
        $admin = User::factory()->admin()->create();
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'plat_nomor' => 'B 5678 PLN',
            'kilometer_terakhir' => 10000,
        ]);

        Mileage::create([
            'id_kendaraan' => $kendaraan->id,
            'tanggal_perjalanan' => now()->subDay()->toDateString(),
            'kilometer_awal' => 10000,
            'kilometer_akhir' => 12500,
            'status_perjalanan' => 'SELESAI',
            'id_pencatat' => $pengelola->id,
        ]);

        PengajuanServis::create([
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengelola->id,
            'id_disetujui_oleh' => $admin->id,
            'jenis_pengajuan' => 'DARURAT',
            'deskripsi_keluhan' => 'Rem bunyi',
            'kilometer_pengajuan' => 12500,
            'status_persetujuan' => 'DISETUJUI',
            'dibuat_pada' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('maintenance.index', ['tab' => 'aktif']));

        $response->assertOk();
        $response->assertSee('KM');
        $response->assertSee('12,500 km');
    }

    public function test_pengelola_melihat_tombol_ajukan_maintenance(): void
    {
        $pengelola = User::factory()->pengelola()->create();

        $response = $this->actingAs($pengelola)->get(route('maintenance.index', ['tab' => 'pengajuan']));

        $response->assertOk();
        $response->assertSee('Ajukan Maintenance');
        $response->assertSee(route('maintenance.create'));
    }

    public function test_admin_tidak_melihat_tombol_ajukan_maintenance(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('maintenance.index', ['tab' => 'pengajuan']));

        $response->assertOk();
        $response->assertDontSee('+ Ajukan Maintenance');
    }

    public function test_pengelola_dapat_membuat_pengajuan_maintenance(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'kilometer_terakhir' => 3000,
        ]);

        Mileage::create([
            'id_kendaraan' => $kendaraan->id,
            'tanggal_perjalanan' => now()->toDateString(),
            'kilometer_awal' => 3000,
            'kilometer_akhir' => 4500,
            'status_perjalanan' => 'SELESAI',
            'id_pencatat' => $pengelola->id,
        ]);

        $response = $this->actingAs($pengelola)->post(route('maintenance.store'), [
            'id_kendaraan' => $kendaraan->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Ganti oli rutin',
            'estimasi_biaya' => 500000,
        ]);

        $response->assertRedirect(route('maintenance.index', ['tab' => 'pengajuan']));
        $this->assertDatabaseHas('pengajuan_servis', [
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengelola->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Ganti oli rutin',
            'status_persetujuan' => 'MENUNGGU',
            'kilometer_pengajuan' => 4500,
        ]);
    }

    public function test_selesaikan_maintenance_aktif_menginput_ke_tabel_riwayat_servis(): void
    {
        $admin = User::factory()->admin()->create();
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'kilometer_terakhir' => 5000,
            'interval_servis_km' => 5000,
            'status_perawatan' => 'SEDANG_SERVIS',
        ]);

        $pengajuan = PengajuanServis::create([
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengelola->id,
            'id_disetujui_oleh' => $admin->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Servis berkala',
            'status_persetujuan' => 'DISETUJUI',
            'estimasi_biaya' => 750000,
            'dibuat_pada' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('maintenance.index', ['tab' => 'aktif']))
            ->post(route('riwayat-servis.store'), [
                'id_pengajuan' => $pengajuan->id,
                'id_kendaraan' => $kendaraan->id,
                'tanggal_servis' => now()->toDateString(),
                'kilometer_servis' => 5500,
                'nama_bengkel' => 'Bengkel Resmi PLN',
                'total_biaya' => 800000,
            ]);

        $response->assertRedirect(route('maintenance.index', ['tab' => 'aktif']));
        $this->assertDatabaseHas('riwayat_servis', [
            'id_pengajuan' => $pengajuan->id,
            'id_kendaraan' => $kendaraan->id,
            'kilometer_servis' => 5500,
            'nama_bengkel' => 'Bengkel Resmi PLN',
            'total_biaya' => 800000,
            'target_kilometer_berikutnya' => 10500,
            'id_pembuat' => $admin->id,
        ]);

        $kendaraan->refresh();
        $this->assertEquals(5500, $kendaraan->kilometer_terakhir);
        $this->assertEquals('BAIK', $kendaraan->status_perawatan);

        // Setelah selesai, maintenance tidak lagi muncul di tab aktif
        $aktifResponse = $this->actingAs($admin)->get(route('maintenance.index', ['tab' => 'aktif']));
        $aktifResponse->assertDontSee('Bengkel Resmi PLN');
    }
}
