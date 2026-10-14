<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\Mileage;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\Fakes\FakeCloudinaryService;
use Tests\TestCase;

class MileageTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Manifest Vite tidak diperlukan untuk pengujian output Blade.
        $this->withoutVite();
        $this->app->instance(CloudinaryService::class, new FakeCloudinaryService);
    }

    private ?User $user = null;

    private function user(): User
    {
        return $this->user ??= User::factory()->pengelola()->create();
    }

    private function kendaraan(array $attributes = []): Kendaraan
    {
        return Kendaraan::factory()->for($this->user(), 'pengelola')->create($attributes);
    }

    public function test_mileage_index_menampilkan_kendaraan_dan_form_tanpa_histori_perjalanan(): void
    {
        $user = $this->user();
        $kendaraan = $this->kendaraan();

        Mileage::create([
            'id_kendaraan' => $kendaraan->id,
            'tanggal_perjalanan' => now()->subDay()->toDateString(),
            'kilometer_awal' => 1000,
            'kilometer_akhir' => 1150,
            'status_perjalanan' => 'SELESAI',
            'keterangan' => 'Perjalanan uji',
            'foto_bukti' => 'mileage/evidence.jpg',
            'id_pencatat' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('mileage.index'));

        $response->assertOk();
        $response->assertSee($kendaraan->plat_nomor);
        $response->assertSee('Catat Perjalanan');
        $response->assertDontSee('Perjalanan uji');
        $response->assertDontSee('storage/mileage/evidence.jpg');
        $response->assertDontSee('Histori Perjalanan');
        $response->assertDontSee('Status Perjalanan');
    }

    public function test_halaman_odometer_menampilkan_odometer_terakhir_kendaraan(): void
    {
        $user = $this->user();
        $kendaraan = $this->kendaraan(['kilometer_terakhir' => 5000]);

        $this->actingAs($user)
            ->get(route('mileage.odometer', ['kendaraan' => $kendaraan->id]))
            ->assertOk()
            ->assertSee('5,000 km')
            ->assertDontSee('Status Perjalanan');
    }

    public function test_odometer_baru_kurang_dari_sebelumnya_ditolak(): void
    {
        $user = $this->user();
        $kendaraan = $this->kendaraan(['kilometer_terakhir' => 5000]);

        $response = $this->actingAs($user)->post(route('mileage.odometer.store'), [
            'id_kendaraan' => $kendaraan->id,
            'odometer_baru' => 4000,
            'tanggal_perjalanan' => now()->toDateString(),
            'status_perjalanan' => 'SELESAI',
            'foto_bukti' => UploadedFile::fake()->image('odometer.jpg'),
        ]);

        $response->assertSessionHasErrors('odometer_baru');
        $this->assertDatabaseMissing('mileage', ['id_kendaraan' => $kendaraan->id]);
        $this->assertSame(5000, $kendaraan->fresh()->kilometer_terakhir);
    }

    public function test_odometer_wajib_disi_dan_valid(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('mileage.odometer.store'), [])
            ->assertSessionHasErrors(['id_kendaraan', 'odometer_baru', 'tanggal_perjalanan', 'foto_bukti']);
    }

    public function test_odometer_tanpa_foto_bukti_ditolak(): void
    {
        $user = $this->user();
        $kendaraan = $this->kendaraan(['kilometer_terakhir' => 5000]);

        $this->actingAs($user)->post(route('mileage.odometer.store'), [
            'id_kendaraan' => $kendaraan->id,
            'odometer_baru' => 5320,
            'tanggal_perjalanan' => now()->toDateString(),
            'status_perjalanan' => 'SELESAI',
        ])->assertSessionHasErrors('foto_bukti');

        $this->assertDatabaseMissing('mileage', ['id_kendaraan' => $kendaraan->id]);
        $this->assertSame(5000, $kendaraan->fresh()->kilometer_terakhir);
    }

    public function test_odometer_menolak_file_bukan_gambar(): void
    {
        $user = $this->user();
        $kendaraan = $this->kendaraan(['kilometer_terakhir' => 5000]);

        $this->actingAs($user)->post(route('mileage.odometer.store'), [
            'id_kendaraan' => $kendaraan->id,
            'odometer_baru' => 5320,
            'tanggal_perjalanan' => now()->toDateString(),
            'status_perjalanan' => 'SELESAI',
            'foto_bukti' => UploadedFile::fake()->create('bukti.txt', 5, 'text/plain'),
        ])->assertSessionHasErrors('foto_bukti');

        $this->assertDatabaseMissing('mileage', ['id_kendaraan' => $kendaraan->id]);
    }

    public function test_menyimpan_odometer_baru_memperbarui_kilometer_terakhir_kendaraan(): void
    {
        $user = $this->user();
        $kendaraan = $this->kendaraan(['kilometer_terakhir' => 5000]);

        $response = $this->actingAs($user)->post(route('mileage.odometer.store'), [
            'id_kendaraan' => $kendaraan->id,
            'odometer_baru' => 5320,
            'tanggal_perjalanan' => now()->toDateString(),
            'keterangan' => 'Cek rutin',
            'foto_bukti' => UploadedFile::fake()->image('odometer.jpg'),
        ]);

        $response->assertRedirect(route('mileage.odometer', ['kendaraan' => $kendaraan->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('mileage', [
            'id_kendaraan' => $kendaraan->id,
            'kilometer_awal' => 5000,
            'kilometer_akhir' => 5320,
            'status_perjalanan' => 'SELESAI',
        ]);

        $mileage = Mileage::query()->where('id_kendaraan', $kendaraan->id)->firstOrFail();

        $this->assertStringStartsWith(
            'https://res.cloudinary.com/test/image/upload/v123/mileage/',
            $mileage->foto_bukti,
        );
        $this->assertStringEndsWith('.webp', $mileage->foto_bukti);
        $this->assertSame(5320, $kendaraan->fresh()->kilometer_terakhir);
    }

    public function test_mileage_tracker_membaca_odometer_terkini_dari_database(): void
    {
        $user = $this->user();
        $kendaraan = $this->kendaraan(['kilometer_terakhir' => 1000]);

        $this->actingAs($user)->post(route('mileage.odometer.store'), [
            'id_kendaraan' => $kendaraan->id,
            'odometer_baru' => 1250,
            'tanggal_perjalanan' => now()->toDateString(),
            'status_perjalanan' => 'SELESAI',
            'foto_bukti' => UploadedFile::fake()->image('odometer.jpg'),
        ]);

        $this->actingAs($user)
            ->get(route('mileage.index', ['kendaraan' => $kendaraan->id]))
            ->assertOk()
            ->assertSee('1,250 km');
    }

    public function test_trip_baru_tidak_boleh_kilometer_akhir_kurang_dari_awal(): void
    {
        $user = $this->user();
        $kendaraan = $this->kendaraan(['kilometer_terakhir' => 1000]);

        $this->actingAs($user)->post(route('mileage.trip.store'), [
            'id_kendaraan' => $kendaraan->id,
            'tanggal_perjalanan' => now()->toDateString(),
            'kilometer_awal' => 1000,
            'kilometer_akhir' => 900,
            'status_perjalanan' => 'SELESAI',
            'foto_bukti' => UploadedFile::fake()->image('odometer.jpg'),
        ])->assertSessionHasErrors('kilometer_akhir');

        $this->assertDatabaseMissing('mileage', ['id_kendaraan' => $kendaraan->id]);
    }

    public function test_trip_menyimpan_foto_bukti_dan_memperbarui_kilometer_terakhir(): void
    {
        $user = $this->user();
        $kendaraan = $this->kendaraan(['kilometer_terakhir' => 1000]);
        $fotoBukti = UploadedFile::fake()->image('odometer.jpg');

        $response = $this->actingAs($user)->post(route('mileage.trip.store'), [
            'id_kendaraan' => $kendaraan->id,
            'tanggal_perjalanan' => now()->toDateString(),
            'kilometer_awal' => 1000,
            'kilometer_akhir' => 1150,
            'foto_bukti' => $fotoBukti,
        ]);

        $response->assertRedirect(route('mileage.index', ['kendaraan' => $kendaraan->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('mileage', [
            'id_kendaraan' => $kendaraan->id,
            'status_perjalanan' => 'SELESAI',
        ]);

        $mileage = Mileage::query()->where('id_kendaraan', $kendaraan->id)->firstOrFail();

        $this->assertSame('SELESAI', $mileage->status_perjalanan);
        $this->assertSame(1150, $kendaraan->fresh()->kilometer_terakhir);
        $this->assertStringStartsWith(
            'https://res.cloudinary.com/test/image/upload/v123/mileage/',
            $mileage->foto_bukti,
        );
    }

    public function test_halaman_history_dapat_disaring(): void
    {
        $user = $this->user();
        $kendaraan = $this->kendaraan();

        Mileage::create([
            'id_kendaraan' => $kendaraan->id,
            'tanggal_perjalanan' => now()->subWeek()->toDateString(),
            'kilometer_awal' => 100,
            'kilometer_akhir' => 180,
            'status_perjalanan' => 'SELESAI',
            'id_pencatat' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('mileage.history', ['kendaraan' => $kendaraan->id]))
            ->assertOk()
            ->assertSee($kendaraan->plat_nomor);

        $this->actingAs($user)
            ->get(route('mileage.history', ['dari' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Data perjalanan tidak ditemukan.');
    }
}
