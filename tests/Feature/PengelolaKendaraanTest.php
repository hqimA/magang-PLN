<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengelolaKendaraanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_pengelola_hanya_melihat_kendaraan_miliknya_sendiri(): void
    {
        $pengelolaA = User::factory()->create([
            'name' => 'Pengelola A',
            'peran' => 'PENGELOLA',
        ]);
        $pengelolaB = User::factory()->create([
            'name' => 'Pengelola B',
            'peran' => 'PENGELOLA',
        ]);

        $kendaraanA = Kendaraan::factory()->create([
            'plat_nomor' => 'B 1111 AAA',
            'id_pengelola' => $pengelolaA->id,
        ]);
        $kendaraanB = Kendaraan::factory()->create([
            'plat_nomor' => 'B 2222 BBB',
            'id_pengelola' => $pengelolaB->id,
        ]);

        $response = $this->actingAs($pengelolaA)->get(route('pengelola.kendaraan'));

        $response->assertOk();
        $response->assertSee($kendaraanA->plat_nomor);
        $response->assertDontSee($kendaraanB->plat_nomor);
        $response->assertDontSee('Pilih Pengelola:');
    }

    public function test_admin_dapat_melihat_dan_memilih_pengelola_lain(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin PLN',
            'peran' => 'ADMIN',
        ]);
        $pengelola = User::factory()->create([
            'name' => 'Pengelola Cabang',
            'peran' => 'PENGELOLA',
        ]);

        $kendaraan = Kendaraan::factory()->create([
            'plat_nomor' => 'B 9999 PLN',
            'id_pengelola' => $pengelola->id,
        ]);

        $response = $this->actingAs($admin)->get(route('pengelola.kendaraan', ['pengelola' => $pengelola->id]));

        $response->assertOk();
        $response->assertSee('Mode Admin');
        $response->assertSee('Pilih Pengelola:');
        $response->assertSee($kendaraan->plat_nomor);
    }

    public function test_pengelola_diarahkan_dari_dashboard_ke_halaman_kendaraan_pengelola(): void
    {
        $pengelola = User::factory()->create([
            'peran' => 'PENGELOLA',
        ]);

        $response = $this->actingAs($pengelola)->get(route('dashboard'));

        $response->assertRedirect(route('pengelola.kendaraan'));
    }
}
