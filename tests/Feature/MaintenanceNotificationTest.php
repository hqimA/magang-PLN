<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\Notifikasi;
use App\Models\PengajuanServis;
use App\Models\RiwayatServis;
use App\Models\User;
use App\Services\ImageStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\Fakes\FakeImageStorageService;
use Tests\TestCase;

class MaintenanceNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->app->instance(ImageStorageService::class, new FakeImageStorageService);
    }

    public function test_new_maintenance_request_notifies_each_admin_and_can_be_marked_read(): void
    {
        $admin = User::factory()->admin()->create();
        $anotherAdmin = User::factory()->admin()->create();
        $manager = User::factory()->pengelola()->create(['unit_pln' => 'UP2D']);
        $vehicle = Kendaraan::factory()->for($manager, 'pengelola')->create([
            'unit_pln' => 'UP2D',
            'plat_nomor' => 'B 1234 PLN',
        ]);

        $response = $this->actingAs($manager)->post(route('maintenance.store'), [
            'id_kendaraan' => $vehicle->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Servis berkala',
            'foto_bukti_odometer' => UploadedFile::fake()->image('odometer.jpg'),
        ]);

        $response->assertRedirect(route('maintenance.index', ['tab' => 'pengajuan']));
        $serviceRequest = PengajuanServis::query()->firstOrFail();
        $this->assertDatabaseCount('notifikasi', 2);
        $this->assertDatabaseHas('notifikasi', [
            'id_pengguna' => $admin->id,
            'judul' => 'Pengajuan Maintenance Baru',
            'pesan' => "Pengajuan maintenance #{$serviceRequest->id} untuk kendaraan B 1234 PLN dari unit UP2D menunggu persetujuan.",
            'sudah_dibaca' => false,
            'tipe_referensi' => 'pengajuan_servis:'.$serviceRequest->id,
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'id_pengguna' => $anotherAdmin->id,
            'tipe_referensi' => 'pengajuan_servis:'.$serviceRequest->id,
            'sudah_dibaca' => false,
        ]);

        $notification = Notifikasi::query()->where('id_pengguna', $admin->id)->firstOrFail();
        $this->actingAs($admin)
            ->get(route('maintenance.index', ['tab' => 'pengajuan']))
            ->assertDontSee('Pengajuan Maintenance Baru')
            ->assertSee('B 1234 PLN')
            ->assertDontSee('data-testid="maintenance-active-badge"', false);

        $this->actingAs($admin)
            ->patch(route('maintenance.notifications.read', $notification))
            ->assertRedirect(route('maintenance.index', ['tab' => 'pengajuan']));

        $this->assertDatabaseHas('notifikasi', [
            'id' => $notification->id,
            'sudah_dibaca' => true,
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'id_pengguna' => $anotherAdmin->id,
            'sudah_dibaca' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('maintenance.index', ['tab' => 'pengajuan']))
            ->assertDontSee('data-testid="maintenance-active-badge"', false);
    }

    public function test_maintenance_badge_counts_active_requests_for_the_users_unit(): void
    {
        $manager = User::factory()->pengelola()->create(['unit_pln' => 'UP2D']);
        $otherManager = User::factory()->pengelola()->create(['unit_pln' => 'UP3']);
        $activeVehicle = Kendaraan::factory()->for($manager, 'pengelola')->create(['unit_pln' => 'UP2D']);
        $servicedVehicle = Kendaraan::factory()->for($manager, 'pengelola')->create(['unit_pln' => 'UP2D']);
        $otherUnitVehicle = Kendaraan::factory()->for($otherManager, 'pengelola')->create(['unit_pln' => 'UP3']);
        $pendingVehicle = Kendaraan::factory()->for($manager, 'pengelola')->create(['unit_pln' => 'UP2D']);

        $activeRequest = PengajuanServis::query()->create([
            'id_kendaraan' => $activeVehicle->id,
            'id_pengaju' => $manager->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Servis sedang diproses',
            'status_persetujuan' => 'DISETUJUI',
        ]);
        $servicedRequest = PengajuanServis::query()->create([
            'id_kendaraan' => $servicedVehicle->id,
            'id_pengaju' => $manager->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Servis sudah selesai',
            'status_persetujuan' => 'DISETUJUI',
        ]);
        PengajuanServis::query()->create([
            'id_kendaraan' => $otherUnitVehicle->id,
            'id_pengaju' => $otherManager->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Unit lain',
            'status_persetujuan' => 'DISETUJUI',
        ]);
        PengajuanServis::query()->create([
            'id_kendaraan' => $pendingVehicle->id,
            'id_pengaju' => $manager->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Menunggu persetujuan',
            'status_persetujuan' => 'MENUNGGU',
        ]);
        RiwayatServis::query()->create([
            'id_pengajuan' => $servicedRequest->id,
            'id_kendaraan' => $servicedVehicle->id,
            'tanggal_servis' => '2026-10-21',
            'kilometer_servis' => 1000,
            'nama_bengkel' => 'Bengkel PLN',
            'id_pembuat' => $manager->id,
        ]);

        $response = $this->actingAs($manager)->get(route('maintenance.index'));

        $response->assertSee('data-testid="maintenance-active-badge"', false);
        $this->assertSame(
            1,
            preg_match('/data-testid="maintenance-active-badge"[^>]*>\s*1\s*<\/span>/', $response->getContent()),
        );

        RiwayatServis::query()->create([
            'id_pengajuan' => $activeRequest->id,
            'id_kendaraan' => $activeVehicle->id,
            'tanggal_servis' => '2026-10-21',
            'kilometer_servis' => 1000,
            'nama_bengkel' => 'Bengkel PLN',
            'id_pembuat' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('maintenance.index'))
            ->assertDontSee('data-testid="maintenance-active-badge"', false);
    }

    public function test_admin_cannot_mark_another_admins_maintenance_notification_as_read(): void
    {
        $admin = User::factory()->admin()->create();
        $anotherAdmin = User::factory()->admin()->create();
        $notification = Notifikasi::query()->create([
            'id_pengguna' => $anotherAdmin->id,
            'judul' => 'Pengajuan Maintenance Baru',
            'pesan' => 'Pengajuan baru.',
            'sudah_dibaca' => false,
            'tipe_referensi' => 'pengajuan_servis:1',
            'dibuat_pada' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('maintenance.notifications.read', $notification))
            ->assertNotFound();

        $this->assertDatabaseHas('notifikasi', [
            'id' => $notification->id,
            'sudah_dibaca' => false,
        ]);
    }
}
