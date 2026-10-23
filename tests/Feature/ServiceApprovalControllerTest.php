<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\PengajuanServis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceApprovalControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approval_updates_request_vehicle_and_notifies_requester(): void
    {
        $admin = User::factory()->create(['peran' => 'ADMIN']);
        $pengaju = User::factory()->create(['peran' => 'PENGELOLA']);
        $kendaraan = Kendaraan::factory()
            ->for($pengaju, 'pengelola')
            ->create(['status_perawatan' => 'PERLU_SERVIS']);
        $pengajuan = PengajuanServis::query()->create([
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengaju->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Perlu servis berkala',
            'status_persetujuan' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($admin)
            ->from('/dashboard')
            ->post(route('pengajuan-servis.approve', $pengajuan->id));

        $response->assertRedirect('/dashboard')
            ->assertSessionHas('status', 'pengajuan-servis-approved');

        $this->assertDatabaseHas('pengajuan_servis', [
            'id' => $pengajuan->id,
            'status_persetujuan' => 'DISETUJUI',
            'id_disetujui_oleh' => $admin->id,
        ]);
        $this->assertDatabaseHas('kendaraan', [
            'id' => $kendaraan->id,
            'status_perawatan' => 'SEDANG_SERVIS',
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'id_pengguna' => $pengaju->id,
            'judul' => 'Pengajuan Servis Disetujui',
            'pesan' => sprintf('Pengajuan servis #%d telah disetujui.', $pengajuan->id),
            'tipe_referensi' => 'pengajuan_servis',
        ]);
    }

    public function test_admin_rejection_saves_reason_and_notifies_requester(): void
    {
        $admin = User::factory()->create(['peran' => 'ADMIN']);
        $pengaju = User::factory()->create(['peran' => 'PENGELOLA']);
        $kendaraan = Kendaraan::factory()
            ->for($pengaju, 'pengelola')
            ->create(['status_perawatan' => 'PERLU_SERVIS']);
        $pengajuan = PengajuanServis::query()->create([
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengaju->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Perlu servis berkala',
            'status_persetujuan' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($admin)
            ->from('/dashboard')
            ->post(route('pengajuan-servis.reject', $pengajuan->id), [
                'alasan_penolakan' => 'Dokumen pendukung belum lengkap.',
            ]);

        $response->assertRedirect('/dashboard')
            ->assertSessionHas('status', 'pengajuan-servis-rejected');

        $this->assertDatabaseHas('pengajuan_servis', [
            'id' => $pengajuan->id,
            'status_persetujuan' => 'DITOLAK',
            'alasan_penolakan' => 'Dokumen pendukung belum lengkap.',
            'id_disetujui_oleh' => $admin->id,
        ]);
        $this->assertDatabaseHas('kendaraan', [
            'id' => $kendaraan->id,
            'status_perawatan' => 'PERLU_SERVIS',
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'id_pengguna' => $pengaju->id,
            'judul' => 'Pengajuan Servis Ditolak',
            'pesan' => sprintf(
                'Pengajuan servis #%d ditolak. Alasan: Dokumen pendukung belum lengkap.',
                $pengajuan->id,
            ),
            'tipe_referensi' => 'pengajuan_servis',
        ]);
    }

    public function test_rejection_requires_a_reason_without_changing_records(): void
    {
        $admin = User::factory()->create(['peran' => 'ADMIN']);
        $pengaju = User::factory()->create(['peran' => 'PENGELOLA']);
        $kendaraan = Kendaraan::factory()
            ->for($pengaju, 'pengelola')
            ->create(['status_perawatan' => 'BAIK']);
        $pengajuan = PengajuanServis::query()->create([
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengaju->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Perlu servis berkala',
            'status_persetujuan' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($admin)
            ->from('/dashboard')
            ->post(route('pengajuan-servis.reject', $pengajuan->id));

        $response->assertRedirect('/dashboard')
            ->assertSessionHasErrors('alasan_penolakan');

        $this->assertDatabaseHas('pengajuan_servis', [
            'id' => $pengajuan->id,
            'status_persetujuan' => 'MENUNGGU',
            'alasan_penolakan' => null,
            'id_disetujui_oleh' => null,
        ]);
        $this->assertDatabaseCount('notifikasi', 0);
    }

    public function test_rejection_requires_a_string_reason(): void
    {
        $admin = User::factory()->create(['peran' => 'ADMIN']);
        $pengaju = User::factory()->create(['peran' => 'PENGELOLA']);
        $kendaraan = Kendaraan::factory()
            ->for($pengaju, 'pengelola')
            ->create(['status_perawatan' => 'BAIK']);
        $pengajuan = PengajuanServis::query()->create([
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengaju->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Perlu servis berkala',
            'status_persetujuan' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($admin)
            ->from('/dashboard')
            ->post(route('pengajuan-servis.reject', $pengajuan->id), [
                'alasan_penolakan' => ['alasan berupa array'],
            ]);

        $response->assertRedirect('/dashboard')
            ->assertSessionHasErrors('alasan_penolakan');

        $this->assertDatabaseHas('pengajuan_servis', [
            'id' => $pengajuan->id,
            'status_persetujuan' => 'MENUNGGU',
        ]);
        $this->assertDatabaseCount('notifikasi', 0);
    }

    public function test_guest_is_redirected_to_login_when_approving_request(): void
    {
        $response = $this->post(route('pengajuan-servis.approve', 1));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_approve_request(): void
    {
        $pengaju = User::factory()->create(['peran' => 'PENGELOLA']);
        $kendaraan = Kendaraan::factory()
            ->for($pengaju, 'pengelola')
            ->create(['status_perawatan' => 'BAIK']);
        $pengajuan = PengajuanServis::query()->create([
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengaju->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Perlu servis berkala',
            'status_persetujuan' => 'MENUNGGU',
        ]);

        $response = $this->actingAs($pengaju)
            ->post(route('pengajuan-servis.approve', $pengajuan->id));

        $response->assertForbidden();

        $this->assertDatabaseHas('pengajuan_servis', [
            'id' => $pengajuan->id,
            'status_persetujuan' => 'MENUNGGU',
        ]);
        $this->assertDatabaseCount('notifikasi', 0);
    }
}
