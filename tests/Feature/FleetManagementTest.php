<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengelola_tidak_bisa_akses_halaman_admin(): void
    {
        $pengelola = $this->createUserWithRole('PENGELOLA');

        $response = $this->actingAs($pengelola)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_admin_bisa_akses_halaman_admin(): void
    {
        $admin = $this->createUserWithRole('ADMIN');

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
    }

    public function test_pengelola_bisa_membuat_pengajuan_servis(): void
    {
        $pengelola = $this->createUserWithRole('PENGELOLA');
        $kendaraan = Kendaraan::factory()
            ->for($pengelola, 'pengelola')
            ->create();

        $this->actingAs($pengelola)->post('/pengajuan-servis', [
            'id_kendaraan' => $kendaraan->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Servis berkala kendaraan.',
        ]);

        $this->assertDatabaseHas('pengajuan_servis', [
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengelola->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Servis berkala kendaraan.',
            'status_persetujuan' => 'MENUNGGU',
        ]);
    }

    public function test_admin_bisa_mencatat_riwayat_servis_dan_update_status_kendaraan(): void
    {
        $admin = $this->createUserWithRole('ADMIN');
        $pengelola = $this->createUserWithRole('PENGELOLA');
        $kendaraan = Kendaraan::factory()
            ->for($pengelola, 'pengelola')
            ->create([
                'status_perawatan' => 'SEDANG_SERVIS',
            ]);

        $this->actingAs($admin)->post('/riwayat-servis', [
            'id_kendaraan' => $kendaraan->id,
            'tanggal_servis' => '2026-09-29',
            'kilometer_servis' => 2200,
            'nama_bengkel' => 'Bengkel PLN',
            'total_biaya' => 450000,
        ]);

        $this->assertDatabaseHas('riwayat_servis', [
            'id_kendaraan' => $kendaraan->id,
            'kilometer_servis' => 2200,
            'id_pembuat' => $admin->id,
        ]);
        $this->assertDatabaseHas('kendaraan', [
            'id' => $kendaraan->id,
            'status_perawatan' => 'BAIK',
        ]);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['peran' => $role])->save();

        return $user;
    }
}
