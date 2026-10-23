<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PengelolaDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengelola_dashboard_shows_live_data_scoped_to_its_pln_unit(): void
    {
        $this->travelTo('2026-10-22 09:00:00');
        $pengelola = User::factory()->pengelola()->create(['unit_pln' => 'UP2D']);
        $rekanUnit = User::factory()->pengelola()->create(['unit_pln' => 'UP2D']);
        $pengelolaUnitLain = User::factory()->pengelola()->create(['unit_pln' => 'UP3']);
        $vehicleNeedingService = Kendaraan::factory()
            ->for($pengelola, 'pengelola')
            ->create([
                'plat_nomor' => 'B 1001 PLN',
                'unit_pln' => 'UP2D',
                'kilometer_terakhir' => 10500,
                'interval_servis_km' => 10000,
                'interval_servis_bulan' => 6,
                'threshold_servis_km' => 1000,
                'tanggal_pembelian' => '2026-01-01',
            ]);
        $vehicleWithActiveMaintenance = Kendaraan::factory()
            ->for($rekanUnit, 'pengelola')
            ->create([
                'plat_nomor' => 'B 1002 PLN',
                'unit_pln' => 'UP2D',
                'kilometer_terakhir' => 0,
                'interval_servis_km' => 50000,
                'interval_servis_bulan' => 12,
                'threshold_servis_km' => 1000,
                'tanggal_pembelian' => '2026-09-01',
            ]);
        $vehicleInAnotherUnit = Kendaraan::factory()
            ->for($pengelolaUnitLain, 'pengelola')
            ->create([
                'plat_nomor' => 'B 3001 PLN',
                'unit_pln' => 'UP3',
                'kilometer_terakhir' => 10500,
                'interval_servis_km' => 10000,
                'interval_servis_bulan' => 6,
                'threshold_servis_km' => 1000,
                'tanggal_pembelian' => '2026-01-01',
            ]);

        DB::table('pengajuan_servis')->insert([
            [
                'id_kendaraan' => $vehicleNeedingService->id,
                'id_pengaju' => $pengelola->id,
                'jenis_pengajuan' => 'RUTIN',
                'deskripsi_keluhan' => 'Servis berkala',
                'status_persetujuan' => 'MENUNGGU',
                'dibuat_pada' => '2026-10-20 09:00:00',
            ],
            [
                'id_kendaraan' => $vehicleWithActiveMaintenance->id,
                'id_pengaju' => $rekanUnit->id,
                'jenis_pengajuan' => 'DARURAT',
                'deskripsi_keluhan' => 'Pemeriksaan kendaraan',
                'status_persetujuan' => 'DISETUJUI',
                'dibuat_pada' => '2026-10-21 09:00:00',
            ],
            [
                'id_kendaraan' => $vehicleInAnotherUnit->id,
                'id_pengaju' => $pengelolaUnitLain->id,
                'jenis_pengajuan' => 'RUTIN',
                'deskripsi_keluhan' => 'Servis berkala',
                'status_persetujuan' => 'MENUNGGU',
                'dibuat_pada' => '2026-10-22 08:00:00',
            ],
        ]);
        DB::table('mileage')->insert([
            [
                'id_kendaraan' => $vehicleWithActiveMaintenance->id,
                'tanggal_perjalanan' => '2026-10-21',
                'kilometer_awal' => 1200,
                'kilometer_akhir' => 1450,
                'status_perjalanan' => 'SELESAI',
                'keterangan' => 'Perjalanan operasional',
                'id_pencatat' => $rekanUnit->id,
            ],
            [
                'id_kendaraan' => $vehicleInAnotherUnit->id,
                'tanggal_perjalanan' => '2026-10-22',
                'kilometer_awal' => 500,
                'kilometer_akhir' => 900,
                'status_perjalanan' => 'SELESAI',
                'keterangan' => 'Perjalanan unit lain',
                'id_pencatat' => $pengelolaUnitLain->id,
            ],
        ]);

        $response = $this->actingAs($pengelola)->get(route('dashboard'));

        $response->assertOk()
            ->assertViewIs('pengelola.dashboard')
            ->assertSee('pengelola-dashboard-page', false)
            ->assertSee('Dashboard Pengelola')
            ->assertSee('UP2D')
            ->assertSee($vehicleNeedingService->plat_nomor)
            ->assertSee($vehicleWithActiveMaintenance->plat_nomor)
            ->assertDontSee($vehicleInAnotherUnit->plat_nomor)
            ->assertSee('Data kendaraan')
            ->assertSee(route('kendaraan.show', $vehicleNeedingService), false)
            ->assertSee('Data Mileage')
            ->assertSee(route('mileage.history', ['kendaraan' => $vehicleWithActiveMaintenance->id]), false)
            ->assertSee('+250 km')
            ->assertSee('Terlambat 500 km');

        $this->assertSame([
            'totalVehicles' => 2,
            'pendingRequests' => 1,
            'activeMaintenance' => 1,
            'serviceReminders' => 1,
        ], $response->viewData('stats'));
    }

    public function test_pengelola_dashboard_shows_empty_states_when_the_unit_has_no_vehicles(): void
    {
        $pengelola = User::factory()->pengelola()->create(['unit_pln' => 'UP2D']);

        $response = $this->actingAs($pengelola)->get(route('dashboard'));

        $response->assertOk()
            ->assertViewIs('pengelola.dashboard')
            ->assertSee('Belum ada kendaraan unit yang mendekati jadwal servis.')
            ->assertSee('Belum ada pengajuan maintenance untuk unit ini.');
        $this->assertSame([
            'totalVehicles' => 0,
            'pendingRequests' => 0,
            'activeMaintenance' => 0,
            'serviceReminders' => 0,
        ], $response->viewData('stats'));
    }

    public function test_pengelola_dashboard_shows_current_odometer_when_no_mileage_records_exist(): void
    {
        $pengelola = User::factory()->pengelola()->create(['unit_pln' => 'UP2D']);
        $vehicle = Kendaraan::factory()
            ->for($pengelola, 'pengelola')
            ->create([
                'plat_nomor' => 'B 1234 PLN',
                'unit_pln' => 'UP2D',
                'kilometer_terakhir' => 28500,
                'interval_servis_km' => 50000,
                'interval_servis_bulan' => 12,
                'threshold_servis_km' => 1000,
                'tanggal_pembelian' => '2026-09-01',
            ]);

        $response = $this->actingAs($pengelola)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Belum ada riwayat perjalanan. Berikut odometer terakhir yang tersimpan pada data kendaraan.')
            ->assertSee('B 1234 PLN')
            ->assertSee('Odometer 28.500 km')
            ->assertSee(route('kendaraan.show', $vehicle->id), false);
    }
}
