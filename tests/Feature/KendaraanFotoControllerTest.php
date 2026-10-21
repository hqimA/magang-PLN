<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\KendaraanFoto;
use App\Models\User;
use App\Services\ImageStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Fakes\FakeImageStorageService;
use Tests\TestCase;

class KendaraanFotoControllerTest extends TestCase
{
    use RefreshDatabase;

    private FakeImageStorageService $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = new FakeImageStorageService;
        $this->app->instance(ImageStorageService::class, $this->storage);
    }

    // ── store ─────────────────────────────────────────────────────────────────

    public function test_admin_can_upload_foto_kendaraan(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $kendaraan = Kendaraan::factory()->create();

        $response = $this->actingAs($admin)
            ->post(route('kendaraan.foto.store', $kendaraan->id), [
                'foto_kendaraan' => [UploadedFile::fake()->image('mobil.jpg')],
            ]);

        $response->assertRedirect(route('kendaraan.edit', $kendaraan));
        $this->assertDatabaseHas('kendaraan_foto', ['id_kendaraan' => $kendaraan->id, 'is_utama' => true]);
        $this->assertCount(1, $this->storage->storedFiles);
    }

    public function test_first_uploaded_foto_is_set_as_utama(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $kendaraan = Kendaraan::factory()->create();

        $this->actingAs($admin)->post(route('kendaraan.foto.store', $kendaraan->id), [
            'foto_kendaraan' => [UploadedFile::fake()->image('a.jpg')],
        ]);

        $this->assertSame(1, KendaraanFoto::where('id_kendaraan', $kendaraan->id)->where('is_utama', true)->count());
    }

    public function test_upload_sets_first_new_file_as_utama_if_vehicle_has_no_utama_foto(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $kendaraan = Kendaraan::factory()->create();
        // Ada foto existing tapi tidak ada yang is_utama = true
        KendaraanFoto::factory()->for($kendaraan, 'kendaraan')->create(['is_utama' => false, 'urutan' => 0]);

        $this->actingAs($admin)->post(route('kendaraan.foto.store', $kendaraan->id), [
            'foto_kendaraan' => [UploadedFile::fake()->image('new.jpg')],
        ]);

        $this->assertSame(1, KendaraanFoto::where('id_kendaraan', $kendaraan->id)->where('is_utama', true)->count());
    }

    public function test_pengelola_can_upload_foto_for_own_vehicle(): void
    {
        Storage::fake('public');
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create();

        $response = $this->actingAs($pengelola)
            ->post(route('kendaraan.foto.store', $kendaraan->id), [
                'foto_kendaraan' => [UploadedFile::fake()->image('mobil.png')],
            ]);

        $response->assertRedirect(route('kendaraan.edit', $kendaraan));
        $this->assertDatabaseHas('kendaraan_foto', ['id_kendaraan' => $kendaraan->id]);
    }

    public function test_pengelola_cannot_upload_foto_for_other_vehicle(): void
    {
        Storage::fake('public');
        $pengelola = User::factory()->pengelola()->create();
        $otherVehicle = Kendaraan::factory()->create();

        $this->actingAs($pengelola)
            ->post(route('kendaraan.foto.store', $otherVehicle->id), [
                'foto_kendaraan' => [UploadedFile::fake()->image('mobil.jpg')],
            ])
            ->assertForbidden();

        $this->assertDatabaseEmpty('kendaraan_foto');
    }

    public function test_upload_is_rejected_when_max_foto_reached(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $kendaraan = Kendaraan::factory()->create();
        KendaraanFoto::factory()->count(5)->for($kendaraan, 'kendaraan')->create();

        $response = $this->actingAs($admin)
            ->post(route('kendaraan.foto.store', $kendaraan->id), [
                'foto_kendaraan' => [UploadedFile::fake()->image('extra.jpg')],
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('foto_kendaraan');
        $this->assertSame(5, KendaraanFoto::where('id_kendaraan', $kendaraan->id)->count());
        $this->assertEmpty($this->storage->storedFiles);
    }

    public function test_upload_is_capped_when_extra_files_exceed_limit(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $kendaraan = Kendaraan::factory()->create();
        KendaraanFoto::factory()->count(4)->for($kendaraan, 'kendaraan')->create();

        $this->actingAs($admin)->post(route('kendaraan.foto.store', $kendaraan->id), [
            'foto_kendaraan' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ],
        ]);

        // Hanya 1 yang bisa masuk (sisa slot = 5 - 4 = 1).
        $this->assertSame(5, KendaraanFoto::where('id_kendaraan', $kendaraan->id)->count());
        $this->assertCount(1, $this->storage->storedFiles);
    }

    public function test_upload_requires_image_file(): void
    {
        $admin = User::factory()->admin()->create();
        $kendaraan = Kendaraan::factory()->create();

        $this->actingAs($admin)
            ->post(route('kendaraan.foto.store', $kendaraan->id), [
                'foto_kendaraan' => [UploadedFile::fake()->create('doc.txt', 100, 'text/plain')],
            ])
            ->assertSessionHasErrors('foto_kendaraan.*');
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function test_admin_can_delete_foto(): void
    {
        $admin = User::factory()->admin()->create();
        $kendaraan = Kendaraan::factory()->create();
        $foto = KendaraanFoto::factory()->for($kendaraan, 'kendaraan')->create(['is_utama' => true]);

        $response = $this->actingAs($admin)
            ->delete(route('kendaraan.foto.destroy', [$kendaraan->id, $foto->id]));

        $response->assertRedirect(route('kendaraan.edit', $kendaraan));
        $this->assertDatabaseMissing('kendaraan_foto', ['id' => $foto->id]);
        $this->assertSame([$foto->path], $this->storage->deletedPaths);
    }

    public function test_deleting_utama_promotes_next_foto(): void
    {
        $admin = User::factory()->admin()->create();
        $kendaraan = Kendaraan::factory()->create();
        $utama = KendaraanFoto::factory()->for($kendaraan, 'kendaraan')->create(['is_utama' => true, 'urutan' => 0]);
        $kedua = KendaraanFoto::factory()->for($kendaraan, 'kendaraan')->create(['is_utama' => false, 'urutan' => 1]);

        $this->actingAs($admin)
            ->delete(route('kendaraan.foto.destroy', [$kendaraan->id, $utama->id]));

        $this->assertDatabaseMissing('kendaraan_foto', ['id' => $utama->id]);
        $this->assertSame(true, $kedua->fresh()->is_utama);
    }

    public function test_pengelola_can_delete_own_vehicle_foto(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create();
        $foto = KendaraanFoto::factory()->for($kendaraan, 'kendaraan')->create(['is_utama' => true]);

        $this->actingAs($pengelola)
            ->delete(route('kendaraan.foto.destroy', [$kendaraan->id, $foto->id]))
            ->assertRedirect(route('kendaraan.edit', $kendaraan));

        $this->assertDatabaseMissing('kendaraan_foto', ['id' => $foto->id]);
    }

    public function test_pengelola_cannot_delete_other_vehicle_foto(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $otherVehicle = Kendaraan::factory()->create();
        $foto = KendaraanFoto::factory()->for($otherVehicle, 'kendaraan')->create(['is_utama' => true]);

        $this->actingAs($pengelola)
            ->delete(route('kendaraan.foto.destroy', [$otherVehicle->id, $foto->id]))
            ->assertForbidden();

        $this->assertModelExists($foto);
    }

    public function test_cannot_delete_foto_belonging_to_different_vehicle(): void
    {
        $admin = User::factory()->admin()->create();
        $vehicleA = Kendaraan::factory()->create();
        $vehicleB = Kendaraan::factory()->create();
        $foto = KendaraanFoto::factory()->for($vehicleB, 'kendaraan')->create(['is_utama' => true]);

        // Kirim foto milik vehicleB tapi kendaraan_id di URL adalah vehicleA.
        $this->actingAs($admin)
            ->delete(route('kendaraan.foto.destroy', [$vehicleA->id, $foto->id]))
            ->assertNotFound();

        $this->assertModelExists($foto);
    }

    // ── setUtama ─────────────────────────────────────────────────────────────

    public function test_admin_can_set_foto_as_utama(): void
    {
        $admin = User::factory()->admin()->create();
        $kendaraan = Kendaraan::factory()->create();
        $utama = KendaraanFoto::factory()->for($kendaraan, 'kendaraan')->create(['is_utama' => true, 'urutan' => 0]);
        $kedua = KendaraanFoto::factory()->for($kendaraan, 'kendaraan')->create(['is_utama' => false, 'urutan' => 1]);

        $response = $this->actingAs($admin)
            ->patch(route('kendaraan.foto.set-utama', [$kendaraan->id, $kedua->id]));

        $response->assertRedirect(route('kendaraan.edit', $kendaraan));
        $this->assertSame(false, $utama->fresh()->is_utama);
        $this->assertSame(true, $kedua->fresh()->is_utama);
        $this->assertSame(0, $kedua->fresh()->urutan);
        $this->assertSame(1, $utama->fresh()->urutan);
    }

    public function test_set_utama_places_photo_as_first_in_fotos_and_foto_cover(): void
    {
        $admin = User::factory()->admin()->create();
        $kendaraan = Kendaraan::factory()->create();
        $foto1 = KendaraanFoto::factory()->for($kendaraan, 'kendaraan')->create(['is_utama' => true, 'urutan' => 0]);
        $foto2 = KendaraanFoto::factory()->for($kendaraan, 'kendaraan')->create(['is_utama' => false, 'urutan' => 1]);
        $foto3 = KendaraanFoto::factory()->for($kendaraan, 'kendaraan')->create(['is_utama' => false, 'urutan' => 2]);

        // Jadikan foto ke-3 sebagai utama
        $this->actingAs($admin)
            ->patch(route('kendaraan.foto.set-utama', [$kendaraan->id, $foto3->id]));

        $kendaraan->refresh();
        $this->assertSame($foto3->id, $kendaraan->fotos()->first()->id);
        $this->assertSame($foto3->id, $kendaraan->fotos->first()->id);
        $this->assertSame($foto3->id, $kendaraan->fotoCover()->id);
    }

    public function test_pengelola_can_set_foto_utama_for_own_vehicle(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create();
        KendaraanFoto::factory()->for($kendaraan, 'kendaraan')->create(['is_utama' => true]);
        $kedua = KendaraanFoto::factory()->for($kendaraan, 'kendaraan')->create(['is_utama' => false]);

        $this->actingAs($pengelola)
            ->patch(route('kendaraan.foto.set-utama', [$kendaraan->id, $kedua->id]))
            ->assertRedirect(route('kendaraan.edit', $kendaraan));

        $this->assertSame(true, $kedua->fresh()->is_utama);
    }

    public function test_pengelola_cannot_set_utama_for_other_vehicle(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $otherVehicle = Kendaraan::factory()->create();
        $foto = KendaraanFoto::factory()->for($otherVehicle, 'kendaraan')->create(['is_utama' => false]);

        $this->actingAs($pengelola)
            ->patch(route('kendaraan.foto.set-utama', [$otherVehicle->id, $foto->id]))
            ->assertForbidden();
    }
}
