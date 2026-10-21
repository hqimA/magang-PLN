<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\Notifikasi;
use App\Models\PengajuanServis;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\Fakes\FakeCloudinaryService;
use Tests\TestCase;

class MaintenanceNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->app->instance(CloudinaryService::class, new FakeCloudinaryService);
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
            ->assertSee('Pengajuan Maintenance Baru')
            ->assertSee('B 1234 PLN')
            ->assertSee(route('maintenance.show', ['pengajuan' => $serviceRequest->id, 'tab' => 'pengajuan']), false)
            ->assertSee(route('maintenance.notifications.read', $notification), false)
            ->assertSee('data-testid="maintenance-request-badge"', false)
            ->assertSee('bg-red-600 rounded-full ml-2', false)
            ->assertSee('>1</span>', false);

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
            ->assertDontSee('data-testid="maintenance-request-badge"', false);
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
