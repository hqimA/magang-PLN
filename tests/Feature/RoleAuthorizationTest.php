<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\RiwayatServis;
use App\Models\User;
use App\Services\ImageStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FakeImageStorageService;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->app->instance(ImageStorageService::class, new FakeImageStorageService);
    }

    public function test_admin_mengakses_seluruh_halaman(): void
    {
        $admin = User::factory()->admin()->create();
        $kendaraan = Kendaraan::factory()->create();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('kendaraan.index'))->assertOk();
        $this->actingAs($admin)->get(route('kendaraan.create'))->assertOk();
        $this->actingAs($admin)->get(route('kendaraan.show', $kendaraan))->assertOk();
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

    public function test_pengelola_cannot_access_expense_report(): void
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
            'unit_pln' => 'UP2D',
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
            'unit_pln' => 'UP2D',
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

    public function test_admin_assigning_a_pln_to_a_manager_grants_access_to_vehicles_in_that_unit(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->pengelola()->create();
        $up2dVehicle = Kendaraan::factory()->create([
            'plat_nomor' => 'B 3001 PLN',
            'unit_pln' => 'UP2D',
            'id_pengelola' => null,
        ]);
        $up3Vehicle = Kendaraan::factory()->create([
            'plat_nomor' => 'B 3002 PLN',
            'unit_pln' => 'UP3',
            'id_pengelola' => null,
        ]);

        $this->actingAs($admin)
            ->put(route('user.update', $manager), [
                'name' => $manager->name,
                'email' => $manager->email,
                'peran' => 'PENGELOLA',
                'unit_pln' => 'UP3',
            ])
            ->assertRedirect(route('user.index'));

        $manager = $manager->fresh();
        $this->assertSame('UP3', $manager->unit_pln->value);
        $this->actingAs($manager)
            ->get(route('kendaraan.index'))
            ->assertSee($up3Vehicle->plat_nomor)
            ->assertDontSee($up2dVehicle->plat_nomor);
    }

    public function test_managers_in_the_same_pln_can_share_access(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->pengelola()->create([
            'unit_pln' => 'UP3',
        ]);
        $manager = User::factory()->pengelola()->create();

        $this->actingAs($admin)
            ->put(route('user.update', $manager), [
                'name' => $manager->name,
                'email' => $manager->email,
                'peran' => 'PENGELOLA',
                'unit_pln' => 'UP3',
            ])
            ->assertRedirect(route('user.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('UP3', $manager->fresh()->unit_pln->value);
    }

    public function test_admin_cannot_create_a_manager_without_a_pln_unit(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('user.store'), [
                'name' => 'Pengelola Tanpa Unit',
                'email' => 'tanpa-unit@example.test',
                'peran' => 'PENGELOLA',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('unit_pln');

        $this->assertDatabaseMissing('users', [
            'email' => 'tanpa-unit@example.test',
        ]);
    }

    public function test_admin_can_create_multiple_managers_for_one_pln(): void
    {
        $admin = User::factory()->admin()->create();
        $vehicle = Kendaraan::factory()->create([
            'unit_pln' => 'UP2D',
            'id_pengelola' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('user.store'), [
                'name' => 'Pengelola UP2D',
                'email' => 'pengelola.up2d@example.test',
                'peran' => 'PENGELOLA',
                'unit_pln' => 'UP2D',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertRedirect(route('user.index'));

        $manager = User::query()->where('email', 'pengelola.up2d@example.test')->firstOrFail();

        $this->assertSame('UP2D', $manager->unit_pln->value);
        $this->assertNull($vehicle->fresh()->id_pengelola);
        $this->actingAs($manager)
            ->get(route('kendaraan.index'))
            ->assertSee($vehicle->plat_nomor);
    }

    public function test_existing_manager_accounts_receive_their_categories_and_matching_unassigned_vehicles(): void
    {
        $managerCategories = [
            'bahar@gmail.com' => 'PEJABAT',
            'pengelola@pln.co.id' => 'MOTOR_OPERASIONAL',
            'ahmad.teknisi@pln.co.id' => 'ANGKUT_BARANG',
            'twha@pln.co.id' => 'TEKNISI',
        ];
        $managers = [];

        foreach ($managerCategories as $email => $category) {
            $managers[$email] = User::factory()->pengelola()->create([
                'email' => $email,
                'kategori_kendaraan' => null,
            ]);

            Kendaraan::factory()->create([
                'kategori_penggunaan' => $category,
                'id_pengelola' => null,
            ]);
        }

        $migration = require database_path(
            'migrations/2026_10_15_111959_assign_vehicle_categories_to_existing_managers.php',
        );
        $migration->up();

        foreach ($managerCategories as $email => $category) {
            $manager = $managers[$email]->fresh();

            $this->assertSame($category, $manager->kategori_kendaraan);
            $this->assertSame(
                1,
                $manager->kendaraan()
                    ->where('kategori_penggunaan', $category)
                    ->count(),
            );
        }
    }

    public function test_sidebar_admin_menampilkan_laporan_dan_manajemen_user(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Administrator STI',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('laporan-pengeluaran.index'))
            ->assertSee(route('user.index'))
            ->assertSee('Administrator STI');
    }

    public function test_sidebar_pengelola_tidak_menampilkan_laporan_dan_manajemen_user(): void
    {
        $pengelola = User::factory()->pengelola()->create([
            'name' => 'Budi Santoso (motor)',
        ]);

        $this->actingAs($pengelola)
            ->get(route('pengelola.kendaraan'))
            ->assertOk()
            ->assertDontSee(route('laporan-pengeluaran.index'))
            ->assertDontSee(route('user.index'))
            ->assertSee('Budi Santoso (motor)');
    }

    public function test_pengelola_hanya_melihat_kendaraan_dalam_unit_pln_nya(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $lainnya = User::factory()->pengelola()->create();

        $milikSendiri = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'plat_nomor' => 'B 1111 AAA',
            'unit_pln' => 'UP2D',
        ]);
        $satuUnit = Kendaraan::factory()->for($lainnya, 'pengelola')->create([
            'plat_nomor' => 'B 2222 BBB',
            'unit_pln' => 'UP2D',
        ]);
        Kendaraan::factory()->for($lainnya, 'pengelola')->create([
            'plat_nomor' => 'B 3333 CCC',
            'unit_pln' => 'UP3',
        ]);

        $response = $this->actingAs($pengelola)->get(route('kendaraan.index'));

        $response->assertOk()
            ->assertSee($milikSendiri->plat_nomor)
            ->assertSee($satuUnit->plat_nomor)
            ->assertDontSee('B 3333 CCC');
    }

    public function test_pengelola_tidak_bisa_mengedit_kendaraan_milik_orang_lain(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $lainnya = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($lainnya, 'pengelola')->create();

        $this->actingAs($pengelola)->get(route('kendaraan.edit', $kendaraan->id))->assertForbidden();
        $this->actingAs($pengelola)->put(route('kendaraan.update', $kendaraan->id), [])->assertForbidden();
    }

    public function test_pengelola_tidak_bisa_melihat_kendaraan_dari_pln_lain(): void
    {
        $pengelola = User::factory()->pengelola()->create(['unit_pln' => 'UP2D']);
        $kendaraan = Kendaraan::factory()->create(['unit_pln' => 'UP3']);

        $this->actingAs($pengelola)
            ->get(route('kendaraan.show', $kendaraan))
            ->assertForbidden();
    }

    public function test_pengelola_tidak_bisa_mengubah_pengaturan_service_reminder(): void
    {
        $pengelola = User::factory()->pengelola()->create();

        $this->actingAs($pengelola)->post(route('service-reminder.settings'), [
            'is_active' => 1,
        ])->assertForbidden();
    }

    public function test_admin_bisa_mengubah_pengaturan_service_reminder(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('service-reminder.index'))
            ->post(route('service-reminder.settings'), [])
            ->assertRedirect(route('service-reminder.index'));

        $this->assertDatabaseHas('service_reminder_settings', ['is_active' => false]);
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
