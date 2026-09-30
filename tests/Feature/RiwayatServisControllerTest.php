<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\RiwayatServis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RiwayatServisControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_creating_service_history(): void
    {
        $response = $this->post(route('riwayat-servis.store'));

        $response->assertRedirect(route('login'));
    }

    public function test_valid_service_history_stores_receipt_parts_and_updates_vehicle(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['peran' => 'PENGELOLA']);
        $kendaraan = Kendaraan::factory()->for($user, 'pengelola')->create([
            'kilometer_terakhir' => 1500,
            'interval_servis_km' => 5000,
            'status_perawatan' => 'SEDANG SERVIS',
        ]);

        $response = $this->actingAs($user)
            ->from('/dashboard')
            ->post(route('riwayat-servis.store'), [
                'id_kendaraan' => $kendaraan->id,
                'tanggal_servis' => '2026-09-29',
                'kilometer_servis' => 2200,
                'nama_bengkel' => 'Bengkel PLN',
                'total_biaya' => 450000,
                'foto_nota' => UploadedFile::fake()->create('nota.pdf', 100, 'application/pdf'),
                'sparepart' => [
                    [
                        'nama_sparepart' => 'Oli mesin',
                        'jumlah' => 3,
                        'harga_satuan' => 100000,
                    ],
                ],
            ]);

        $response->assertRedirect('/dashboard')
            ->assertSessionHas('status', 'riwayat-servis-created');

        $riwayatServis = RiwayatServis::query()->firstOrFail();

        $this->assertDatabaseHas('riwayat_servis', [
            'id' => $riwayatServis->id,
            'id_pengajuan' => null,
            'id_kendaraan' => $kendaraan->id,
            'kilometer_servis' => 2200,
            'target_kilometer_berikutnya' => 7200,
            'id_pembuat' => $user->id,
        ]);
        $this->assertStringStartsWith('nota_servis/', $riwayatServis->foto_nota);
        Storage::disk('public')->assertExists($riwayatServis->foto_nota);

        $this->assertDatabaseHas('rincian_sparepart', [
            'id_riwayat_servis' => $riwayatServis->id,
            'nama_sparepart' => 'Oli mesin',
            'jumlah' => 3,
            'harga_satuan' => 100000,
            'subtotal' => 300000,
        ]);
        $this->assertDatabaseHas('kendaraan', [
            'id' => $kendaraan->id,
            'kilometer_terakhir' => 2200,
            'status_perawatan' => 'BAIK',
        ]);
    }

    public function test_manager_cannot_create_service_history_for_another_managers_vehicle(): void
    {
        $user = User::factory()->create(['peran' => 'PENGELOLA']);
        $otherUser = User::factory()->create(['peran' => 'PENGELOLA']);
        $kendaraan = Kendaraan::factory()->for($otherUser, 'pengelola')->create();

        $response = $this->actingAs($user)->post(route('riwayat-servis.store'), [
            'id_kendaraan' => $kendaraan->id,
            'tanggal_servis' => '2026-09-29',
            'kilometer_servis' => 2200,
            'nama_bengkel' => 'Bengkel PLN',
            'total_biaya' => 450000,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('riwayat_servis', 0);
        $this->assertDatabaseHas('kendaraan', [
            'id' => $kendaraan->id,
            'kilometer_terakhir' => $kendaraan->kilometer_terakhir,
            'status_perawatan' => $kendaraan->status_perawatan,
        ]);
    }

    public function test_required_service_history_fields_are_validated(): void
    {
        $user = User::factory()->create(['peran' => 'PENGELOLA']);

        $response = $this->actingAs($user)
            ->from('/dashboard')
            ->post(route('riwayat-servis.store'));

        $response->assertRedirect('/dashboard')
            ->assertSessionHasErrors([
                'id_kendaraan',
                'tanggal_servis',
                'kilometer_servis',
                'nama_bengkel',
                'total_biaya',
            ]);
        $this->assertDatabaseCount('riwayat_servis', 0);
    }
}
