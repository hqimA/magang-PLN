<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\Mileage;
use App\Models\User;
use App\Services\ImageStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\Fakes\FakeImageStorageService;
use Tests\TestCase;

class MileageTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->app->instance(ImageStorageService::class, new FakeImageStorageService);
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

    public function test_mileage_form_mengunci_kilometer_awal_dan_memungkinkan_kilometer_akhir(): void
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
        $response->assertSee('id="kilometer_awal"', false);
        $response->assertSee('name="kilometer_awal"', false);
        $response->assertSee('readonly', false);
        $response->assertSee('name="kilometer_akhir"', false);
        $response->assertSee('name="foto_bukti"', false);
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
            ->assertSee('name="odometer_baru"', false)
            ->assertDontSee('Status Perjalanan');
    }

    public function test_odometer_post_menyimpan_angka_baru_dengan_km_awal_dari_kendaraan(): void
    {
        $user = $this->user();
        $kendaraan = $this->kendaraan(['kilometer_terakhir' => 5000]);

        $response = $this->actingAs($user)->post(route('mileage.odometer.store'), [
            'id_kendaraan' => $kendaraan->id,
            'odometer_baru' => 5500,
            'tanggal_perjalanan' => now()->toDateString(),
            'status_perjalanan' => 'SELESAI',
            'foto_bukti' => UploadedFile::fake()->image('odometer.jpg'),
        ]);

        $response->assertRedirect(route('mileage.odometer', ['kendaraan' => $kendaraan->id]));
        $this->assertDatabaseHas('mileage', [
            'id_kendaraan' => $kendaraan->id,
            'kilometer_awal' => 5000,
            'kilometer_akhir' => 5500,
        ]);
        $this->assertSame(5500, $kendaraan->fresh()->kilometer_terakhir);
    }

    public function test_odometer_menolak_angka_di_bawah_kilometer_terakhir(): void
    {
        $user = $this->user();
        $kendaraan = $this->kendaraan(['kilometer_terakhir' => 5000]);

        $this->actingAs($user)->post(route('mileage.odometer.store'), [
            'id_kendaraan' => $kendaraan->id,
            'odometer_baru' => 4000,
            'tanggal_perjalanan' => now()->toDateString(),
            'status_perjalanan' => 'SELESAI',
            'foto_bukti' => UploadedFile::fake()->image('odometer.jpg'),
        ])->assertSessionHasErrors('odometer_baru');

        $this->assertDatabaseMissing('mileage', ['id_kendaraan' => $kendaraan->id]);
        $this->assertSame(5000, $kendaraan->fresh()->kilometer_terakhir);
    }

    public function test_odometer_mewajibkan_foto_bukti(): void
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
    }

    public function test_mileage_tracker_membaca_odometer_terkini_dari_database(): void
    {
        $user = $this->user();
        $kendaraan = $this->kendaraan(['kilometer_terakhir' => 1250]);

        $this->actingAs($user)
            ->get(route('mileage.index', ['kendaraan' => $kendaraan->id]))
            ->assertOk()
            ->assertSee('1,250 km');
    }

    public function test_trip_menolak_kilometer_akhir_di_bawah_awal(): void
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

    public function test_trip_menggunakan_km_awal_kendaraan_meski_request_dimanipulasi(): void
    {
        $user = $this->user();
        $kendaraan = $this->kendaraan(['kilometer_terakhir' => 1000]);
        $fotoBukti = UploadedFile::fake()->image('odometer.jpg');

        $response = $this->actingAs($user)->post(route('mileage.trip.store'), [
            'id_kendaraan' => $kendaraan->id,
            'tanggal_perjalanan' => now()->toDateString(),
            'kilometer_awal' => 25,
            'kilometer_akhir' => 1150,
            'foto_bukti' => $fotoBukti,
        ]);

        $response->assertRedirect(route('mileage.index', ['kendaraan' => $kendaraan->id]));
        $this->assertDatabaseHas('mileage', [
            'id_kendaraan' => $kendaraan->id,
            'kilometer_awal' => 1000,
            'kilometer_akhir' => 1150,
        ]);
        $this->assertSame(1150, $kendaraan->fresh()->kilometer_terakhir);
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

    public function test_detail_kendaraan_hanya_menampilkan_tombol_catat_kilometer(): void
    {
        $user = $this->user();
        $kendaraan = $this->kendaraan(['kilometer_terakhir' => 1250]);

        $this->actingAs($user)
            ->get(route('kendaraan.show', $kendaraan->id))
            ->assertOk()
            ->assertSee('Catat Kilometer')
            ->assertSee(route('mileage.odometer', ['kendaraan' => $kendaraan->id]), false)
            ->assertSee('Kembali')
            ->assertDontSee('Catat Perjalanan')
            ->assertDontSee('name="keterangan"', false);
    }

}
