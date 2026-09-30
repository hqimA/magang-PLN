<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\OdometerLog;
use App\Models\User;
use Tests\TestCase;

class OdometerLogTest extends TestCase
{
    public function test_guest_is_redirected_from_odometer_log_submission(): void
    {
        $response = $this->post(route('odometer-log.store'));

        $response->assertRedirect(route('login'));
    }

    public function test_manager_can_create_odometer_log_and_update_vehicle_mileage(): void
    {
        $user = User::factory()->create(['peran' => 'PENGELOLA']);
        $vehicle = Kendaraan::factory()->for($user, 'pengelola')->create([
            'kilometer_terakhir' => 1200,
        ]);

        $response = $this->actingAs($user)->post(route('odometer-log.store'), [
            'id_kendaraan' => $vehicle->id,
            'kilometer' => 1350,
            'tanggal_pencatatan' => '2026-09-28',
            'keterangan' => 'Pencatatan rutin',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('odometer_logs', [
            'id_kendaraan' => $vehicle->id,
            'kilometer' => 1350,
            'tanggal_pencatatan' => '2026-09-28',
            'keterangan' => 'Pencatatan rutin',
            'id_penginput' => $user->id,
        ]);
        $this->assertDatabaseHas('kendaraan', [
            'id' => $vehicle->id,
            'kilometer_terakhir' => 1350,
        ]);
    }

    public function test_manager_cannot_submit_lower_mileage(): void
    {
        $user = User::factory()->create(['peran' => 'PENGELOLA']);
        $vehicle = Kendaraan::factory()->for($user, 'pengelola')->create([
            'kilometer_terakhir' => 1200,
        ]);

        $response = $this->actingAs($user)->from('/dashboard')->post(route('odometer-log.store'), [
            'id_kendaraan' => $vehicle->id,
            'kilometer' => 1199,
            'tanggal_pencatatan' => '2026-09-28',
        ]);

        $response->assertRedirect('/dashboard')
            ->assertSessionHasErrors('kilometer');
        $this->assertDatabaseCount('odometer_logs', 0);
        $this->assertDatabaseHas('kendaraan', [
            'id' => $vehicle->id,
            'kilometer_terakhir' => 1200,
        ]);
    }

    public function test_manager_cannot_log_another_managers_vehicle(): void
    {
        $user = User::factory()->create(['peran' => 'PENGELOLA']);
        $otherUser = User::factory()->create(['peran' => 'PENGELOLA']);
        $vehicle = Kendaraan::factory()->for($otherUser, 'pengelola')->create([
            'kilometer_terakhir' => 1200,
        ]);

        $response = $this->actingAs($user)->post(route('odometer-log.store'), [
            'id_kendaraan' => $vehicle->id,
            'kilometer' => 1350,
            'tanggal_pencatatan' => '2026-09-28',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('odometer_logs', 0);
        $this->assertDatabaseHas('kendaraan', [
            'id' => $vehicle->id,
            'kilometer_terakhir' => 1200,
        ]);
    }

    public function test_odometer_log_resolves_its_vehicle_and_inputter(): void
    {
        $user = User::factory()->create(['peran' => 'PENGELOLA']);
        $vehicle = Kendaraan::factory()->for($user, 'pengelola')->create();
        $log = OdometerLog::query()->create([
            'id_kendaraan' => $vehicle->id,
            'kilometer' => 100,
            'tanggal_pencatatan' => '2026-09-28',
            'id_penginput' => $user->id,
        ]);

        $this->assertSame($vehicle->id, $log->kendaraan->id);
        $this->assertSame($user->id, $log->penginput->id);
        $this->assertSame($log->id, $vehicle->odometerLogs()->first()->id);
    }
}
