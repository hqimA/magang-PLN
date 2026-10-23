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

    public function test_pengelola_melihat_semua_kendaraan_dalam_unit_pln_yang_sama(): void
    {
        $pengelolaA = User::factory()->create([
            'name' => 'Pengelola A',
            'peran' => 'PENGELOLA',
            'unit_pln' => 'UP2D',
        ]);
        $pengelolaB = User::factory()->create([
            'name' => 'Pengelola B',
            'peran' => 'PENGELOLA',
            'unit_pln' => 'UP2D',
        ]);

        $kendaraanA = Kendaraan::factory()->create([
            'plat_nomor' => 'B 1111 AAA',
            'unit_pln' => 'UP2D',
            'id_pengelola' => $pengelolaA->id,
        ]);
        $kendaraanB = Kendaraan::factory()->create([
            'plat_nomor' => 'B 2222 BBB',
            'unit_pln' => 'UP2D',
            'id_pengelola' => $pengelolaB->id,
        ]);
        $kendaraanPlnLain = Kendaraan::factory()->create([
            'plat_nomor' => 'B 3333 CCC',
            'unit_pln' => 'UP3',
        ]);

        $response = $this->actingAs($pengelolaA)->get(route('pengelola.kendaraan'));

        $response->assertOk();
        $response->assertSee($kendaraanA->plat_nomor);
        $response->assertSee($kendaraanB->plat_nomor);
        $response->assertDontSee($kendaraanPlnLain->plat_nomor);
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
            'unit_pln' => 'UP3',
        ]);

        $kendaraan = Kendaraan::factory()->create([
            'plat_nomor' => 'B 9999 PLN',
            'unit_pln' => 'UP3',
            'id_pengelola' => $pengelola->id,
        ]);

        $response = $this->actingAs($admin)->get(route('pengelola.kendaraan', ['pengelola' => $pengelola->id]));

        $response->assertOk();
        $response->assertSee('Mode Admin');
        $response->assertSee('Pilih Pengelola / Unit PLN:');
        $response->assertSee($kendaraan->plat_nomor);
    }

    public function test_pengelola_dashboard_links_to_unit_operations(): void
    {
        $pengelola = User::factory()->create([
            'peran' => 'PENGELOLA',
            'unit_pln' => 'UP2D',
        ]);

        $response = $this->actingAs($pengelola)->get(route('dashboard'));

        $response->assertOk()
            ->assertViewIs('pengelola.dashboard')
            ->assertSee(route('kendaraan.index'))
            ->assertSee(route('maintenance.create'))
            ->assertSee(route('service-reminder.index'));
    }

}
