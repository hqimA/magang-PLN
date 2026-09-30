<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\RiwayatServis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_service_reminders(): void
    {
        $response = $this->get(route('service-reminders.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_vehicle_is_marked_as_nearing_service_by_kilometer_threshold(): void
    {
        $this->travelTo('2026-09-25 12:00:00');
        $user = User::factory()->create(['peran' => 'PENGELOLA']);
        $vehicle = Kendaraan::factory()->for($user, 'pengelola')->create([
            'kilometer_terakhir' => 4800,
            'tanggal_pembelian' => '2026-01-01',
        ]);
        RiwayatServis::create([
            'id_kendaraan' => $vehicle->id,
            'tanggal_servis' => '2026-08-25',
            'kilometer_servis' => 0,
            'nama_bengkel' => 'Bengkel PLN',
            'id_pembuat' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('service-reminders.index'));

        $response->assertOk()
            ->assertJsonPath('data.0.reminder.status', 'MENDEKATI_SERVIS')
            ->assertJsonPath('data.0.reminder.sisa_kilometer', 200);
    }

    public function test_vehicle_is_marked_as_nearing_service_by_time_threshold(): void
    {
        $this->travelTo('2026-09-15 12:00:00');
        $user = User::factory()->create(['peran' => 'PENGELOLA']);
        $vehicle = Kendaraan::factory()->for($user, 'pengelola')->create([
            'kilometer_terakhir' => 1000,
            'tanggal_pembelian' => '2026-01-01',
        ]);
        RiwayatServis::create([
            'id_kendaraan' => $vehicle->id,
            'tanggal_servis' => '2026-06-25',
            'kilometer_servis' => 0,
            'nama_bengkel' => 'Bengkel PLN',
            'id_pembuat' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('service-reminders.index'));

        $response->assertOk()
            ->assertJsonPath('data.0.reminder.status', 'MENDEKATI_SERVIS')
            ->assertJsonPath('data.0.reminder.sisa_hari', 10);
    }

    public function test_service_reminder_response_includes_mileage_based_service_prediction(): void
    {
        $this->travelTo('2026-09-20 12:00:00');
        $user = User::factory()->create(['peran' => 'PENGELOLA']);
        $vehicle = Kendaraan::factory()->for($user, 'pengelola')->create([
            'kilometer_terakhir' => 4500,
            'interval_servis_km' => 5000,
            'threshold_servis_hari' => 10,
            'tanggal_pembelian' => '2026-01-01',
        ]);
        RiwayatServis::create([
            'id_kendaraan' => $vehicle->id,
            'tanggal_servis' => '2026-09-10',
            'kilometer_servis' => 4000,
            'target_kilometer_berikutnya' => 5000,
            'nama_bengkel' => 'Bengkel PLN',
            'id_pembuat' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('service-reminders.index'));

        $response->assertOk()
            ->assertJsonPath('data.0.prediksi.plat_nomor', $vehicle->plat_nomor)
            ->assertJsonPath('data.0.prediksi.kilometer_saat_ini', 4500)
            ->assertJsonPath('data.0.prediksi.target_kilometer_servis', 5000)
            ->assertJsonPath('data.0.prediksi.sisa_kilometer', 500)
            ->assertJsonPath('data.0.prediksi.rata_rata_km_harian', 50)
            ->assertJsonPath('data.0.prediksi.sisa_hari_prediksi', 10)
            ->assertJsonPath('data.0.prediksi.tanggal_prediksi_servis', '2026-09-30')
            ->assertJsonPath('data.0.prediksi.status_prediksi', 'WARNING');
    }

    public function test_service_prediction_uses_purchase_date_and_interval_without_service_history(): void
    {
        $this->travelTo('2026-09-20 12:00:00');
        $user = User::factory()->create(['peran' => 'PENGELOLA']);
        $vehicle = Kendaraan::factory()->for($user, 'pengelola')->create([
            'kilometer_terakhir' => 300,
            'interval_servis_km' => 1000,
            'threshold_servis_hari' => 10,
            'tanggal_pembelian' => '2026-09-10',
        ]);

        $response = $this->actingAs($user)->getJson(route('service-reminders.index'));

        $response->assertOk()
            ->assertJsonPath('data.0.prediksi.target_kilometer_servis', 1000)
            ->assertJsonPath('data.0.prediksi.rata_rata_km_harian', 30)
            ->assertJsonPath('data.0.prediksi.sisa_kilometer', 700)
            ->assertJsonPath('data.0.prediksi.sisa_hari_prediksi', 24)
            ->assertJsonPath('data.0.prediksi.tanggal_prediksi_servis', '2026-10-14')
            ->assertJsonPath('data.0.prediksi.status_prediksi', 'SAFE');
    }

    public function test_service_prediction_marks_vehicle_overdue_when_target_mileage_is_reached(): void
    {
        $this->travelTo('2026-09-20 12:00:00');
        $user = User::factory()->create(['peran' => 'PENGELOLA']);
        $vehicle = Kendaraan::factory()->for($user, 'pengelola')->create([
            'kilometer_terakhir' => 5100,
            'interval_servis_km' => 5000,
            'tanggal_pembelian' => '2026-01-01',
        ]);
        RiwayatServis::create([
            'id_kendaraan' => $vehicle->id,
            'tanggal_servis' => '2026-09-20',
            'kilometer_servis' => 1000,
            'target_kilometer_berikutnya' => 5000,
            'nama_bengkel' => 'Bengkel PLN',
            'id_pembuat' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('service-reminders.index'));

        $response->assertOk()
            ->assertJsonPath('data.0.prediksi.sisa_kilometer', -100)
            ->assertJsonPath('data.0.prediksi.sisa_hari_prediksi', 0)
            ->assertJsonPath('data.0.prediksi.tanggal_prediksi_servis', '2026-09-20')
            ->assertJsonPath('data.0.prediksi.status_prediksi', 'OVERDUE');
    }

    public function test_manager_cannot_see_another_managers_service_reminder(): void
    {
        $user = User::factory()->create(['peran' => 'PENGELOLA']);
        $otherUser = User::factory()->create(['peran' => 'PENGELOLA']);
        Kendaraan::factory()->for($otherUser, 'pengelola')->create([
            'kilometer_terakhir' => 4800,
        ]);

        $response = $this->actingAs($user)->getJson(route('service-reminders.index'));

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_vehicle_is_marked_as_overdue_when_a_threshold_is_exceeded(): void
    {
        $this->travelTo('2026-09-25 12:00:00');
        $user = User::factory()->create(['peran' => 'PENGELOLA']);
        $vehicle = Kendaraan::factory()->for($user, 'pengelola')->create([
            'kilometer_terakhir' => 6000,
            'tanggal_pembelian' => '2026-01-01',
        ]);
        RiwayatServis::create([
            'id_kendaraan' => $vehicle->id,
            'tanggal_servis' => '2026-08-25',
            'kilometer_servis' => 0,
            'nama_bengkel' => 'Bengkel PLN',
            'id_pembuat' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('service-reminders.index'));

        $response->assertOk()
            ->assertJsonPath('data.0.reminder.status', 'TERLAMBAT_SERVIS')
            ->assertJsonPath('data.0.reminder.sisa_kilometer', -1000);
    }
}
