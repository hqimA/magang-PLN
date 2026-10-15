<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FakeCloudinaryService;
use Tests\TestCase;

class KendaraanTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_multiple_vehicles(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $firstVehicle = Kendaraan::factory()->for($user, 'pengelola')->create();
        $secondVehicle = Kendaraan::factory()->for($user, 'pengelola')->create();
        Kendaraan::factory()->for($otherUser, 'pengelola')->create();

        $vehicles = $user->kendaraan;

        $this->assertSame([$firstVehicle->id, $secondVehicle->id], $vehicles->modelKeys());
    }

    public function test_vehicle_resolves_its_manager(): void
    {
        $user = User::factory()->create();
        $vehicle = Kendaraan::factory()->for($user, 'pengelola')->create();

        $manager = $vehicle->pengelola;

        $this->assertSame($user->id, $manager->id);
    }

    public function test_admin_can_delete_only_selected_vehicles(): void
    {
        $admin = User::factory()->admin()->create();
        $cloudinary = new FakeCloudinaryService;
        $this->app->instance(CloudinaryService::class, $cloudinary);
        $firstVehicleUrl = 'https://res.cloudinary.com/demo/image/upload/v123/kendaraan/first.webp';
        $secondVehicleUrl = 'https://res.cloudinary.com/demo/image/upload/v123/kendaraan/second.webp';
        $firstVehicle = Kendaraan::factory()->create(['foto_kendaraan' => $firstVehicleUrl]);
        $secondVehicle = Kendaraan::factory()->create(['foto_kendaraan' => $secondVehicleUrl]);
        $remainingVehicle = Kendaraan::factory()->create();

        $response = $this->actingAs($admin)
            ->delete(route('kendaraan.destroy-selected'), [
                'kendaraan_ids' => [$firstVehicle->id, $secondVehicle->id],
            ]);

        $response->assertRedirect(route('kendaraan.index'))
            ->assertSessionHas('success', '2 kendaraan terpilih berhasil dihapus.');
        $this->assertDatabaseMissing('kendaraan', ['id' => $firstVehicle->id]);
        $this->assertDatabaseMissing('kendaraan', ['id' => $secondVehicle->id]);
        $this->assertModelExists($remainingVehicle);
        $this->assertSame([$firstVehicleUrl, $secondVehicleUrl], $cloudinary->deletedUrls);
    }

    public function test_non_admin_cannot_delete_selected_vehicles(): void
    {
        $manager = User::factory()->pengelola()->create();
        $vehicle = Kendaraan::factory()->for($manager, 'pengelola')->create();

        $this->actingAs($manager)
            ->delete(route('kendaraan.destroy-selected'), [
                'kendaraan_ids' => [$vehicle->id],
            ])
            ->assertForbidden();

        $this->assertModelExists($vehicle);
    }

    public function test_admin_vehicle_index_renders_bulk_selection_controls(): void
    {
        $admin = User::factory()->admin()->create();
        $vehicle = Kendaraan::factory()->create();

        $response = $this->actingAs($admin)->get(route('kendaraan.index'));

        $response->assertSee('Hapus Kendaraan Terpilih')
            ->assertSee(route('kendaraan.destroy-selected'))
            ->assertSee('name="kendaraan_ids[]"', false)
            ->assertSee('value="'.$vehicle->id.'"', false)
            ->assertSee('Pilih semua kendaraan yang ditampilkan')
            ->assertSee('+ Tambah Kendaraan');
    }

    public function test_admin_must_select_at_least_one_vehicle_to_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $vehicle = Kendaraan::factory()->create();

        $response = $this->actingAs($admin)
            ->from(route('kendaraan.index'))
            ->delete(route('kendaraan.destroy-selected'));

        $response->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('kendaraan_ids');
        $this->assertModelExists($vehicle);
    }
}
