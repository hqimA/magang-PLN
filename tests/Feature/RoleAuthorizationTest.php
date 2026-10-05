<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\RiwayatServis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_mengakses_seluruh_halaman(): void
    {
        $admin = User::factory()->admin()->create();
        $kendaraan = Kendaraan::factory()->create();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('kendaraan.index'))->assertOk();
        $this->actingAs($admin)->get(route('kendaraan.create'))->assertOk();
        $this->actingAs($admin)->get(route('mileage.index'))->assertOk();
        $this->actingAs($admin)->get(route('laporan-pengeluaran.index'))->assertOk();
        $this->actingAs($admin)->get(route('user.index'))->assertOk();
        $this->actingAs($admin)->get(route('user.create'))->assertOk();
        $this->actingAs($admin)->get(route('user.edit', $kendaraan->pengelola->id))->assertOk();
    }

    public function test_admin_menghapus_kendaraan(): void
    {
        $admin = User::factory()->admin()->create();
        $kendaraan = Kendaraan::factory()->create();

        $this->actingAs($admin)
            ->delete(route('kendaraan.destroy', $kendaraan->id))
            ->assertRedirect(route('kendaraan.index'));

        $this->assertDatabaseMissing('kendaraan', ['id' => $kendaraan->id]);
    }

    public function test_pengelola_tidak_boleh_menghapus_kendaraan(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create();

        $this->actingAs($pengelola)
            ->delete(route('kendaraan.destroy', $kendaraan->id))
            ->assertForbidden();

        $this->assertDatabaseHas('kendaraan', ['id' => $kendaraan->id]);
    }

    public function test_pengelola_tidak_bisa_mengakses_halaman_laporan(): void
    {
        $pengelola = User::factory()->pengelola()->create();

        $this->actingAs($pengelola)
            ->get(route('laporan-pengeluaran.index'))
            ->assertForbidden();
    }

    public function test_pengelola_tidak_bisa_mengakses_manajemen_user(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $target = User::factory()->pengelola()->create();

        $this->actingAs($pengelola)->get(route('user.index'))->assertForbidden();
        $this->actingAs($pengelola)->get(route('user.create'))->assertForbidden();
        $this->actingAs($pengelola)->get(route('user.edit', $target->id))->assertForbidden();
        $this->actingAs($pengelola)->post(route('user.store'), [])->assertForbidden();
        $this->actingAs($pengelola)->put(route('user.update', $target->id), [])->assertForbidden();
        $this->actingAs($pengelola)->delete(route('user.destroy', $target->id))->assertForbidden();
    }

    public function test_pengelola_tidak_bisa_mengubah_peran_user_lewat_request(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($pengelola)->put(route('user.update', $admin->id), [
            'name' => $admin->name,
            'email' => $admin->email,
            'peran' => 'PENGELOLA',
        ])->assertForbidden();

        $this->assertSame('ADMIN', $admin->fresh()->peran);
    }

    public function test_admin_dapat_mengelola_user_dan_peran(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->pengelola()->create();

        $this->actingAs($admin)->put(route('user.update', $target->id), [
            'name' => 'Nama Baru',
            'email' => $target->email,
            'peran' => 'ADMIN',
        ])->assertRedirect(route('user.index'));

        $this->assertSame('ADMIN', $target->fresh()->peran);
    }

    public function test_admin_tidak_bisa_menurunkan_peran_akun_sendiri(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('user.update', $admin->id), [
            'name' => $admin->name,
            'email' => $admin->email,
            'peran' => 'PENGELOLA',
        ])->assertForbidden();

        $this->assertSame('ADMIN', $admin->fresh()->peran);
    }

    public function test_admin_tidak_bisa_menghapus_akun_sendiri(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('user.destroy', $admin->id))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_sidebar_admin_menampilkan_laporan_dan_manajemen_user(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('laporan-pengeluaran.index'))
            ->assertSee(route('user.index'));
    }

    public function test_sidebar_pengelola_tidak_menampilkan_laporan_dan_manajemen_user(): void
    {
        $pengelola = User::factory()->pengelola()->create();

        $this->actingAs($pengelola)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('user.index'));
    }

    public function test_pengelola_hanya_melihat_kendaraan_miliknya(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $lainnya = User::factory()->pengelola()->create();

        $milikSendiri = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'plat_nomor' => 'B 1111 AAA',
        ]);
        Kendaraan::factory()->for($lainnya, 'pengelola')->create([
            'plat_nomor' => 'B 2222 BBB',
        ]);

        $response = $this->actingAs($pengelola)->get(route('kendaraan.index'));

        $response->assertOk()
            ->assertSee($milikSendiri->plat_nomor)
            ->assertDontSee('B 2222 BBB');
    }

    public function test_pengelola_tidak_bisa_mengedit_kendaraan_milik_orang_lain(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $lainnya = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($lainnya, 'pengelola')->create();

        $this->actingAs($pengelola)->get(route('kendaraan.edit', $kendaraan->id))->assertForbidden();
        $this->actingAs($pengelola)->put(route('kendaraan.update', $kendaraan->id), [])->assertForbidden();
    }

    public function test_pengelola_mencatat_mileage_hanya_kendaraan_miliknya(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $lainnya = User::factory()->pengelola()->create();
        $kendaraanLain = Kendaraan::factory()->for($lainnya, 'pengelola')->create([
            'kilometer_terakhir' => 1000,
        ]);

        $this->actingAs($pengelola)->post(route('mileage.trip.store'), [
            'id_kendaraan' => $kendaraanLain->id,
            'tanggal_perjalanan' => now()->toDateString(),
            'kilometer_awal' => 1000,
            'kilometer_akhir' => 1200,
            'status_perjalanan' => 'SELESAI',
        ])->assertForbidden();

        $this->assertDatabaseMissing('mileage', ['id_kendaraan' => $kendaraanLain->id]);
        $this->assertSame(1000, $kendaraanLain->fresh()->kilometer_terakhir);
    }

    public function test_pengelola_mencatat_mileage_kendaraan_miliknya(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'kilometer_terakhir' => 1000,
        ]);

        $this->actingAs($pengelola)->post(route('mileage.trip.store'), [
            'id_kendaraan' => $kendaraan->id,
            'tanggal_perjalanan' => now()->toDateString(),
            'kilometer_awal' => 1000,
            'kilometer_akhir' => 1200,
            'status_perjalanan' => 'SELESAI',
        ])->assertRedirect();

        $this->assertDatabaseHas('mileage', [
            'id_kendaraan' => $kendaraan->id,
            'kilometer_akhir' => 1200,
        ]);
    }

    public function test_pengelola_tidak_bisa_mengubah_pengaturan_service_reminder(): void
    {
        $pengelola = User::factory()->pengelola()->create();

        $this->actingAs($pengelola)->post(route('service-reminder.settings'), [
            'reminder_days_before' => 30,
        ])->assertForbidden();
    }

    public function test_admin_bisa_mengubah_pengaturan_service_reminder(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('service-reminder.index'))
            ->post(route('service-reminder.settings'), ['reminder_days_before' => 30])
            ->assertRedirect(route('service-reminder.index'));

        $this->assertDatabaseHas('service_reminder_settings', ['reminder_days_before' => 30]);
    }

    public function test_pengelola_dapat_mengajukan_maintenance(): void
    {
        $pengelola = User::factory()->pengelola()->create();

        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create();

        $this->actingAs($pengelola)
            ->post(route('riwayat-servis.store'), [
                'id_kendaraan' => $kendaraan->id,
                'tanggal_servis' => now()->toDateString(),
                'kilometer_servis' => 1500,
                'nama_bengkel' => 'Bengkel PLN',
                'total_biaya' => 250000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('riwayat_servis', [
            'id_kendaraan' => $kendaraan->id,
            'id_pembuat' => $pengelola->id,
        ]);
        $this->assertSame(1, RiwayatServis::query()->count());
    }

    public function test_guest_tidak_bisa_mengakses_halaman_admin(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('user.index'))->assertRedirect(route('login'));
    }
}
