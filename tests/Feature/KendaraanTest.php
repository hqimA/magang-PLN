<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\KomponenKendaraan;
use App\Models\TemplateJadwalDetail;
use App\Models\TemplateJadwalServis;
use App\Models\TemplateKomponen;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FakeCloudinaryService;
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

    public function test_admin_can_delete_only_selected_vehicles(): void
    {
        $admin = User::factory()->admin()->create();
        $cloudinary = new FakeCloudinaryService;
        $this->app->instance(CloudinaryService::class, $cloudinary);
        $firstVehicleUrl = 'https://res.cloudinary.com/demo/image/upload/v123/kendaraan/first.webp';
        $secondVehicleUrl = 'https://res.cloudinary.com/demo/image/upload/v123/kendaraan/second.webp';
        $firstVehicle = Kendaraan::factory()->create(['foto_kendaraan' => $firstVehicleUrl]);
        $secondVehicle = Kendaraan::factory()->create(['foto_kendaraan' => $secondVehicleUrl]);
        $remainingVehicle = Kendaraan::factory()->create();

        $response = $this->actingAs($admin)
            ->delete(route('kendaraan.destroy-selected'), [
                'kendaraan_ids' => [$firstVehicle->id, $secondVehicle->id],
            ]);

        $response->assertRedirect(route('kendaraan.index'))
            ->assertSessionHas('success', '2 kendaraan terpilih berhasil dihapus.');
        $this->assertDatabaseMissing('kendaraan', ['id' => $firstVehicle->id]);
        $this->assertDatabaseMissing('kendaraan', ['id' => $secondVehicle->id]);
        $this->assertModelExists($remainingVehicle);
        $this->assertSame([$firstVehicleUrl, $secondVehicleUrl], $cloudinary->deletedUrls);
    }

    public function test_non_admin_cannot_delete_selected_vehicles(): void
    {
        $manager = User::factory()->pengelola()->create();
        $vehicle = Kendaraan::factory()->for($manager, 'pengelola')->create();

        $this->actingAs($manager)
            ->delete(route('kendaraan.destroy-selected'), [
                'kendaraan_ids' => [$vehicle->id],
            ])
            ->assertForbidden();

        $this->assertModelExists($vehicle);
    }

    public function test_admin_vehicle_index_renders_bulk_selection_controls(): void
    {
        $admin = User::factory()->admin()->create();
        $vehicle = Kendaraan::factory()->create();

        $response = $this->actingAs($admin)->get(route('kendaraan.index'));

        $response->assertSee('Hapus Kendaraan Terpilih')
            ->assertSee(route('kendaraan.destroy-selected'))
            ->assertSee('name="kendaraan_ids[]"', false)
            ->assertSee('value="'.$vehicle->id.'"', false)
            ->assertSee('Pilih semua kendaraan yang ditampilkan')
            ->assertSee('+ Tambah Kendaraan');
    }

    public function test_admin_must_select_at_least_one_vehicle_to_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $vehicle = Kendaraan::factory()->create();

        $response = $this->actingAs($admin)
            ->from(route('kendaraan.index'))
            ->delete(route('kendaraan.destroy-selected'));

        $response->assertRedirect(route('kendaraan.index'))
            ->assertSessionHasErrors('kendaraan_ids');
        $this->assertModelExists($vehicle);
    }

    public function test_vehicle_edit_displays_mileage_as_read_only_and_ignores_submitted_mileage(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->pengelola()->create();
        $vehicle = Kendaraan::factory()->for($manager, 'pengelola')->create([
            'kilometer_terakhir' => 4500,
        ]);
        $this->withoutVite();

        $this->actingAs($admin)
            ->get(route('kendaraan.edit', $vehicle))
            ->assertOk()
            ->assertSee('4,500 km')
            ->assertDontSee('name="kilometer_terakhir"', false);

        $this->actingAs($admin)->put(route('kendaraan.update', $vehicle), [
            'plat_nomor' => $vehicle->plat_nomor,
            'merk_tipe' => 'Toyota Avanza Update',
            'tahun_pembuatan' => $vehicle->tahun_pembuatan,
            'transmisi' => $vehicle->transmisi,
            'jenis_bbm' => $vehicle->jenis_bbm,
            'kategori_penggunaan' => $vehicle->kategori_penggunaan,
            'kilometer_terakhir' => 999999,
            'tanggal_pembelian' => $vehicle->tanggal_pembelian,
            'status_perawatan' => $vehicle->status_perawatan,
            'id_pengelola' => $manager->id,
        ])->assertRedirect(route('kendaraan.index'));

        $this->assertSame(4500, $vehicle->fresh()->kilometer_terakhir);
        $this->assertSame('Toyota Avanza Update', $vehicle->fresh()->merk_tipe);
    }

    public function test_admin_created_vehicle_receives_its_recommended_template_components(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->pengelola()->create();
        $template = TemplateJadwalServis::create([
            'nama' => 'Bensin Manual',
            'jenis_bbm' => 'BENSIN',
            'transmisi' => 'MANUAL',
            'is_aktif' => true,
        ]);
        $component = $this->createTemplateComponent($template, 'Oli mesin', 10000, 'P');

        $response = $this->actingAs($admin)->post(route('kendaraan.store'), [
            'plat_nomor' => 'B 5555 PLN',
            'merk_tipe' => 'Toyota Avanza',
            'tahun_pembuatan' => 2022,
            'transmisi' => 'MANUAL',
            'jenis_bbm' => 'BENSIN',
            'kategori_penggunaan' => 'TEKNISI',
            'kilometer_terakhir' => 10000,
            'tanggal_pembelian' => '2022-01-01',
            'status_perawatan' => 'BAIK',
            'id_pengelola' => $manager->id,
        ]);

        $response->assertRedirect(route('kendaraan.index'));
        $kendaraan = Kendaraan::query()->where('plat_nomor', 'B 5555 PLN')->firstOrFail();
        $this->assertSame($template->id, $kendaraan->id_template);
        $this->assertDatabaseHas('komponen_kendaraan', [
            'id_kendaraan' => $kendaraan->id,
            'id_template_komponen' => $component->id,
            'jenis_aksi_default' => 'P',
            'km_jatuh_tempo' => 10000,
        ]);
    }

    public function test_vehicle_component_template_is_refreshed_when_fuel_or_transmission_changes(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->pengelola()->create();
        $oldTemplate = TemplateJadwalServis::create([
            'nama' => 'Bensin Manual',
            'jenis_bbm' => 'BENSIN',
            'transmisi' => 'MANUAL',
            'is_aktif' => true,
        ]);
        $newTemplate = TemplateJadwalServis::create([
            'nama' => 'Solar Otomatis',
            'jenis_bbm' => 'SOLAR',
            'transmisi' => 'OTOMATIS',
            'is_aktif' => true,
        ]);
        $this->createTemplateComponent($oldTemplate, 'Oli mesin', 10000, 'G');
        $newComponent = $this->createTemplateComponent($newTemplate, 'Filter solar', 10000, 'P');
        $kendaraan = Kendaraan::factory()->for($manager, 'pengelola')->create([
            'jenis_bbm' => 'BENSIN',
            'transmisi' => 'MANUAL',
            'kilometer_terakhir' => 5000,
        ]);

        $response = $this->actingAs($admin)->put(route('kendaraan.update', $kendaraan), [
            'plat_nomor' => $kendaraan->plat_nomor,
            'merk_tipe' => $kendaraan->merk_tipe,
            'tahun_pembuatan' => $kendaraan->tahun_pembuatan,
            'transmisi' => 'OTOMATIS',
            'jenis_bbm' => 'SOLAR',
            'kategori_penggunaan' => $kendaraan->kategori_penggunaan,
            'tanggal_pembelian' => $kendaraan->tanggal_pembelian->toDateString(),
            'status_perawatan' => $kendaraan->status_perawatan,
            'id_pengelola' => $manager->id,
        ]);

        $response->assertRedirect(route('kendaraan.index'));
        $this->assertSame($newTemplate->id, $kendaraan->fresh()->id_template);
        $this->assertDatabaseHas('komponen_kendaraan', [
            'id_kendaraan' => $kendaraan->id,
            'id_template_komponen' => $newComponent->id,
            'jenis_aksi_default' => 'P',
        ]);
        $this->assertSame(
            1,
            KomponenKendaraan::query()->where('id_kendaraan', $kendaraan->id)->where('is_aktif', true)->count(),
        );
    }

    public function test_vehicle_detail_populates_components_from_template_matching_fuel_and_transmission(): void
    {
        $manager = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($manager, 'pengelola')->create([
            'jenis_bbm' => 'BENSIN',
            'transmisi' => 'MANUAL',
            'kilometer_terakhir' => 15000,
        ]);
        $this->createTemplate('Template umum', 'SEMUA', 'SEMUA');
        $matchingTemplate = $this->createTemplate('Bensin Manual', 'BENSIN', 'MANUAL');
        $matchingComponent = $this->createTemplateComponent($matchingTemplate, 'Oli mesin manual', 10000, 'G');

        $response = $this->actingAs($manager)
            ->get(route('kendaraan.show', $kendaraan));

        $response->assertOk()
            ->assertSee('Oli mesin manual')
            ->assertSee('Bensin Manual');
        $this->assertSame($matchingTemplate->id, $kendaraan->fresh()->id_template);
        $this->assertDatabaseHas('komponen_kendaraan', [
            'id_kendaraan' => $kendaraan->id,
            'id_template_komponen' => $matchingComponent->id,
            'nama_komponen' => 'Oli mesin manual',
            'jenis_aksi_default' => 'G',
        ]);
        $this->assertSame(1, KomponenKendaraan::query()->where('id_kendaraan', $kendaraan->id)->count());
    }

    private function createTemplate(string $name, string $fuel, string $transmission): TemplateJadwalServis
    {
        return TemplateJadwalServis::create([
            'nama' => $name,
            'jenis_bbm' => $fuel,
            'transmisi' => $transmission,
            'is_aktif' => true,
        ]);
    }

    private function createTemplateComponent(
        TemplateJadwalServis $template,
        string $name,
        int $intervalKm,
        string $action,
    ): TemplateKomponen {
        $component = TemplateKomponen::create([
            'id_template' => $template->id,
            'nomor_urut' => 1,
            'nama_komponen' => $name,
            'kategori' => 'KOMPONEN_DASAR_MESIN',
            'is_aktif' => true,
        ]);

        TemplateJadwalDetail::create([
            'id_template_komponen' => $component->id,
            'interval_km' => $intervalKm,
            'interval_bulan' => 6,
            'jenis_aksi' => $action,
        ]);

        return $component;
    }
}
