<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentExpiryAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_deduplicated_notifications_for_stnk_and_kir_due_in_30_days(): void
    {
        $this->travelTo('2026-09-30 09:00:00');
        $manager = User::factory()->create();
        $vehicle = Kendaraan::factory()->for($manager, 'pengelola')->create([
            'status_perawatan' => 'BAIK',
            'tanggal_stnk_berlaku_sampai' => '2026-10-30',
            'tanggal_kir_berlaku_sampai' => '2026-10-30',
        ]);
        Kendaraan::factory()->for($manager, 'pengelola')->create([
            'status_perawatan' => 'BAIK',
            'tanggal_stnk_berlaku_sampai' => '2026-10-31',
        ]);

        $this->artisan('kendaraan:alert-masa-berlaku')->assertSuccessful();
        $this->artisan('kendaraan:alert-masa-berlaku')->assertSuccessful();

        $this->assertDatabaseCount('notifikasi', 2);
        $this->assertDatabaseHas('notifikasi', [
            'id_pengguna' => $manager->id,
            'tipe_referensi' => 'kendaraan:'.$vehicle->id.':stnk:20261030',
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'id_pengguna' => $manager->id,
            'tipe_referensi' => 'kendaraan:'.$vehicle->id.':kir:20261030',
        ]);
    }

    public function test_vehicle_expiry_dates_cannot_exceed_five_years(): void
    {
        $this->travelTo('2026-09-30 09:00:00');
        $manager = User::factory()->create();

        $response = $this->actingAs($manager)->post(route('kendaraan.store'), [
            'plat_nomor' => 'B 1234 TEST',
            'merk_tipe' => 'Toyota Avanza',
            'tahun_pembuatan' => 2020,
            'transmisi' => 'MANUAL',
            'jenis_bbm' => 'BENSIN',
            'kategori_penggunaan' => 'TEKNISI',
            'kilometer_terakhir' => 1000,
            'tanggal_pembelian' => '2020-01-01',
            'tanggal_stnk_berlaku_sampai' => '2031-10-01',
            'status_perawatan' => 'BAIK',
            'id_pengelola' => $manager->id,
        ]);

        $response->assertSessionHasErrors('tanggal_stnk_berlaku_sampai');
    }
}
