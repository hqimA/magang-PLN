<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
