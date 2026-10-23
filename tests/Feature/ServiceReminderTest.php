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
        $user = User::factory()->pengelola()->create();
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

    public function test_vehicle_is_not_reminded_when_only_the_date_threshold_is_near(): void
    {
        $this->travelTo('2026-09-15 12:00:00');
        $user = User::factory()->pengelola()->create();
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

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_vehicle_is_not_overdue_when_only_the_service_date_has_passed(): void
    {
        $this->travelTo('2026-09-25 12:00:00');
        $user = User::factory()->pengelola()->create();
        $vehicle = Kendaraan::factory()->for($user, 'pengelola')->create([
            'kilometer_terakhir' => 1000,
            'tanggal_pembelian' => '2026-01-01',
        ]);
        RiwayatServis::create([
            'id_kendaraan' => $vehicle->id,
            'tanggal_servis' => '2026-01-01',
            'kilometer_servis' => 0,
            'nama_bengkel' => 'Bengkel PLN',
            'id_pembuat' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('service-reminders.index'));

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_service_reminder_page_displays_distance_overdue_instead_of_days(): void
    {
        $this->travelTo('2026-09-25 12:00:00');
        $user = User::factory()->pengelola()->create();
        $vehicle = Kendaraan::factory()->for($user, 'pengelola')->create([
            'kilometer_terakhir' => 5100,
            'tanggal_pembelian' => '2026-01-01',
        ]);
        RiwayatServis::create([
            'id_kendaraan' => $vehicle->id,
            'tanggal_servis' => '2026-08-25',
            'kilometer_servis' => 0,
            'target_kilometer_berikutnya' => 5000,
            'nama_bengkel' => 'Bengkel PLN',
            'id_pembuat' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('service-reminder.index'));

        $response->assertOk()
            ->assertSee('100 km terlewat')
            ->assertSee('Target KM Servis')
            ->assertDontSee('Sisa Hari');
    }

    public function test_service_reminder_page_shows_all_reminders_without_filter_buttons(): void
    {
        $this->travelTo('2026-09-25 12:00:00');
        $user = User::factory()->pengelola()->create();
        $nearServiceVehicle = Kendaraan::factory()->for($user, 'pengelola')->create([
            'plat_nomor' => 'B 1111 PLN',
            'kilometer_terakhir' => 4800,
            'tanggal_pembelian' => '2026-01-01',
        ]);
        $overdueVehicle = Kendaraan::factory()->for($user, 'pengelola')->create([
            'plat_nomor' => 'B 2222 PLN',
            'kilometer_terakhir' => 6000,
            'tanggal_pembelian' => '2026-01-01',
        ]);

        RiwayatServis::create([
            'id_kendaraan' => $nearServiceVehicle->id,
            'tanggal_servis' => '2026-08-25',
            'kilometer_servis' => 0,
            'target_kilometer_berikutnya' => 5000,
            'nama_bengkel' => 'Bengkel PLN',
            'id_pembuat' => $user->id,
        ]);
        RiwayatServis::create([
            'id_kendaraan' => $overdueVehicle->id,
            'tanggal_servis' => '2026-08-25',
            'kilometer_servis' => 0,
            'target_kilometer_berikutnya' => 5000,
            'nama_bengkel' => 'Bengkel PLN',
            'id_pembuat' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('service-reminder.index'))
            ->assertOk()
            ->assertSee('B 1111 PLN')
            ->assertSee('B 2222 PLN')
            ->assertSee('Akan Servis')
            ->assertSee('Terlambat')
            ->assertDontSee("filter = 'all'", false)
            ->assertDontSee("filter = 'akan_servis'", false)
            ->assertDontSee("filter = 'terlambat'", false)
            ->assertDontSee('x-show="filter', false);
    }

    public function test_service_reminder_response_includes_mileage_based_service_prediction(): void
    {
        $this->travelTo('2026-09-20 12:00:00');
        $user = User::factory()->pengelola()->create();
        $vehicle = Kendaraan::factory()->for($user, 'pengelola')->create([
            'kilometer_terakhir' => 4500,
            'interval_servis_km' => 5000,
            'threshold_servis_hari' => 10,
            'threshold_servis_km' => 500,
            'interval_servis_bulan' => 0,
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

    public function test_service_prediction_uses_kilometer_threshold_without_service_history(): void
    {
        $this->travelTo('2026-09-20 12:00:00');
        $user = User::factory()->pengelola()->create();
        $vehicle = Kendaraan::factory()->for($user, 'pengelola')->create([
            'kilometer_terakhir' => 300,
            'interval_servis_km' => 1000,
            'threshold_servis_km' => 700,
            'interval_servis_bulan' => 0,
            'tanggal_pembelian' => '2026-09-10',
        ]);

        $response = $this->actingAs($user)->getJson(route('service-reminders.index'));

        $response->assertOk()
            ->assertJsonPath('data.0.prediksi.target_kilometer_servis', 1000)
            ->assertJsonPath('data.0.prediksi.rata_rata_km_harian', 30)
            ->assertJsonPath('data.0.prediksi.sisa_kilometer', 700)
            ->assertJsonPath('data.0.prediksi.sisa_hari_prediksi', 24)
            ->assertJsonPath('data.0.prediksi.tanggal_prediksi_servis', '2026-10-14')
            ->assertJsonPath('data.0.prediksi.status_prediksi', 'WARNING');
    }

    public function test_service_prediction_marks_vehicle_overdue_when_target_mileage_is_reached(): void
    {
        $this->travelTo('2026-09-20 12:00:00');
        $user = User::factory()->pengelola()->create();
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
        $user = User::factory()->pengelola()->create(['unit_pln' => 'UP2D']);
        $otherUser = User::factory()->pengelola()->create(['unit_pln' => 'UP3']);
        Kendaraan::factory()->for($otherUser, 'pengelola')->create([
            'kilometer_terakhir' => 4800,
            'unit_pln' => 'UP3',
        ]);

        $response = $this->actingAs($user)->getJson(route('service-reminders.index'));

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_vehicle_is_marked_as_overdue_when_a_threshold_is_exceeded(): void
    {
        $this->travelTo('2026-09-25 12:00:00');
        $user = User::factory()->pengelola()->create();
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

    public function test_service_reminder_is_cleared_after_an_overdue_vehicle_is_serviced(): void
    {
        $this->travelTo('2026-10-21 12:00:00');
        $user = User::factory()->pengelola()->create();
        $vehicle = Kendaraan::factory()->for($user, 'pengelola')->create([
            'kilometer_terakhir' => 5100,
            'interval_servis_km' => 5000,
            'threshold_servis_km' => 500,
            'status_perawatan' => 'PERLU_SERVIS',
        ]);
        RiwayatServis::create([
            'id_kendaraan' => $vehicle->id,
            'tanggal_servis' => '2026-10-21',
            'kilometer_servis' => 0,
            'target_kilometer_berikutnya' => 5000,
            'nama_bengkel' => 'Servis sebelumnya',
            'id_pembuat' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('service-reminder.index'))
            ->assertSee($vehicle->plat_nomor)
            ->assertSee('data-testid="service-reminder-badge"', false);

        $this->actingAs($user)
            ->post(route('riwayat-servis.store'), [
                'id_kendaraan' => $vehicle->id,
                'tanggal_servis' => '2026-10-21',
                'nama_bengkel' => 'Servis terbaru',
                'total_biaya' => 250000,
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->getJson(route('service-reminders.index'))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($user)
            ->get(route('service-reminder.index'))
            ->assertOk()
            ->assertSee('Tidak ada kendaraan yang membutuhkan reminder servis saat ini.')
            ->assertDontSee($vehicle->plat_nomor)
            ->assertDontSee('data-testid="service-reminder-badge"', false)
            ->assertDontSee('Servis sebelumnya');

        $this->assertDatabaseHas('riwayat_servis', [
            'id_kendaraan' => $vehicle->id,
            'nama_bengkel' => 'Servis terbaru',
            'target_kilometer_berikutnya' => 10100,
        ]);
    }
}
