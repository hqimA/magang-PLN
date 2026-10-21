<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\Mileage;
use App\Models\PengajuanServis;
use App\Models\RiwayatServis;
use App\Models\User;
use App\Services\ImageStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\Fakes\FakeImageStorageService;
use Tests\TestCase;

class MaintenanceIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->app->instance(ImageStorageService::class, new FakeImageStorageService);
    }

    public function test_tab_pengajuan_menampilkan_kilometer_terakhir_kendaraan(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'plat_nomor' => 'B 1234 PLN',
            'kilometer_terakhir' => 5000,
        ]);

        Mileage::create([
            'id_kendaraan' => $kendaraan->id,
            'tanggal_perjalanan' => now()->subDays(2)->toDateString(),
            'kilometer_awal' => 5000,
            'kilometer_akhir' => 7500,
            'status_perjalanan' => 'SELESAI',
            'id_pencatat' => $pengelola->id,
        ]);

        PengajuanServis::create([
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengelola->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Servis berkala',
            'kilometer_pengajuan' => 7500,
            'status_persetujuan' => 'MENUNGGU',
            'dibuat_pada' => now(),
        ]);

        $response = $this->actingAs($pengelola)->get(route('maintenance.index', ['tab' => 'pengajuan']));

        $response->assertOk();
        $response->assertSee('KM');
        $response->assertSee('5,000 km');
        $response->assertSee(route('maintenance.index', ['tab' => 'pengajuan']), false)
            ->assertSee(route('maintenance.index', ['tab' => 'proses']), false)
            ->assertSee(route('maintenance.index', ['tab' => 'usai']), false)
            ->assertSee(route('maintenance.index', ['tab' => 'ditolak']), false);
    }

    public function test_tab_aktif_menampilkan_kilometer_terakhir_kendaraan(): void
    {
        $admin = User::factory()->admin()->create();
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'plat_nomor' => 'B 5678 PLN',
            'kilometer_terakhir' => 10000,
        ]);

        Mileage::create([
            'id_kendaraan' => $kendaraan->id,
            'tanggal_perjalanan' => now()->subDay()->toDateString(),
            'kilometer_awal' => 10000,
            'kilometer_akhir' => 12500,
            'status_perjalanan' => 'SELESAI',
            'id_pencatat' => $pengelola->id,
        ]);

        PengajuanServis::create([
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengelola->id,
            'id_disetujui_oleh' => $admin->id,
            'jenis_pengajuan' => 'DARURAT',
            'deskripsi_keluhan' => 'Rem bunyi',
            'kilometer_pengajuan' => 12500,
            'status_persetujuan' => 'DISETUJUI',
            'dibuat_pada' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('maintenance.index', ['tab' => 'proses']));

        $response->assertOk();
        $response->assertSee('KM');
        $response->assertSee('10,000 km');
        $response->assertSee('kilometer: 10000', false);
    }

    public function test_pengelola_melihat_tombol_ajukan_maintenance(): void
    {
        $pengelola = User::factory()->pengelola()->create();

        $response = $this->actingAs($pengelola)->get(route('maintenance.index', ['tab' => 'pengajuan']));

        $response->assertOk();
        $response->assertSee('Ajukan Maintenance');
        $response->assertSee(route('maintenance.create'));
    }

    public function test_pengelola_hanya_mendapat_tombol_batal_untuk_pengajuan_menunggu_atau_ditolak(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraanMenunggu = Kendaraan::factory()->for($pengelola, 'pengelola')->create();
        $kendaraanDitolak = Kendaraan::factory()->for($pengelola, 'pengelola')->create();
        $kendaraanDisetujui = Kendaraan::factory()->for($pengelola, 'pengelola')->create();
        $kendaraanDibatalkan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'merk_tipe' => 'Kendaraan yang Dibatalkan',
            'plat_nomor' => 'B 9999 XYZ',
        ]);
        $pengajuanMenunggu = $this->createPengajuan($pengelola, $kendaraanMenunggu, 'MENUNGGU');
        $pengajuanDitolak = $this->createPengajuan($pengelola, $kendaraanDitolak, 'DITOLAK');
        $pengajuanDitolak->update(['alasan_penolakan' => 'Bukti belum lengkap.']);
        $pengajuanDisetujui = $this->createPengajuan($pengelola, $kendaraanDisetujui, 'DISETUJUI');
        $pengajuanDibatalkan = $this->createPengajuan($pengelola, $kendaraanDibatalkan, 'DIBATALKAN');

        $response = $this->actingAs($pengelola)
            ->get(route('maintenance.index', ['tab' => 'pengajuan']));

        $response->assertOk()
            ->assertSee(route('maintenance.cancel', $pengajuanMenunggu), false)
            ->assertDontSee(route('maintenance.cancel', $pengajuanDitolak), false)
            ->assertDontSee(route('maintenance.cancel', $pengajuanDisetujui), false)
            ->assertDontSee(route('maintenance.cancel', $pengajuanDibatalkan), false)
            ->assertDontSee('B 9999 XYZ')
            ->assertDontSee('Kendaraan yang Dibatalkan');

        $rejectedResponse = $this->actingAs($pengelola)
            ->get(route('maintenance.index', ['tab' => 'ditolak']));

        $rejectedResponse->assertOk()
            ->assertSee('Maintenance Ditolak')
            ->assertSee('Bukti belum lengkap.')
            ->assertSee(route('maintenance.cancel', $pengajuanDitolak), false)
            ->assertDontSee(route('maintenance.cancel', $pengajuanMenunggu), false)
            ->assertDontSee(route('maintenance.cancel', $pengajuanDisetujui), false)
            ->assertDontSee('B 9999 XYZ')
            ->assertDontSee('Kendaraan yang Dibatalkan');
    }

    public function test_pengelola_hanya_melihat_maintenance_usai_dari_unit_pln_nya(): void
    {
        $pengelolaUp2d = User::factory()->pengelola()->create(['unit_pln' => 'UP2D']);
        $pengelolaUp3 = User::factory()->pengelola()->create(['unit_pln' => 'UP3']);
        $kendaraanUp2d = Kendaraan::factory()->for($pengelolaUp2d, 'pengelola')->create([
            'unit_pln' => 'UP2D',
            'plat_nomor' => 'B 1200 UP2',
        ]);
        $kendaraanUp3 = Kendaraan::factory()->for($pengelolaUp3, 'pengelola')->create([
            'unit_pln' => 'UP3',
            'plat_nomor' => 'B 1300 UP3',
        ]);

        RiwayatServis::create([
            'id_kendaraan' => $kendaraanUp2d->id,
            'tanggal_servis' => '2026-10-01',
            'kilometer_servis' => 1000,
            'nama_bengkel' => 'Bengkel Unit UP2D',
            'total_biaya' => 100000,
            'id_pembuat' => $pengelolaUp2d->id,
        ]);
        RiwayatServis::create([
            'id_kendaraan' => $kendaraanUp3->id,
            'tanggal_servis' => '2026-10-02',
            'kilometer_servis' => 2000,
            'nama_bengkel' => 'Bengkel Unit UP3',
            'total_biaya' => 200000,
            'id_pembuat' => $pengelolaUp3->id,
        ]);

        $response = $this->actingAs($pengelolaUp2d)
            ->get(route('maintenance.index', ['tab' => 'usai']));

        $response->assertOk()
            ->assertSee('Bengkel Unit UP2D')
            ->assertSee('B 1200 UP2')
            ->assertDontSee('Bengkel Unit UP3')
            ->assertDontSee('B 1300 UP3');
    }

    public function test_admin_tidak_melihat_tombol_ajukan_maintenance(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('maintenance.index', ['tab' => 'pengajuan']));

        $response->assertOk();
        $response->assertDontSee('+ Ajukan Maintenance');
    }

    public function test_pengelola_dapat_membuat_pengajuan_maintenance(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'kilometer_terakhir' => 3000,
        ]);

        Mileage::create([
            'id_kendaraan' => $kendaraan->id,
            'tanggal_perjalanan' => now()->toDateString(),
            'kilometer_awal' => 3000,
            'kilometer_akhir' => 4500,
            'status_perjalanan' => 'SELESAI',
            'id_pencatat' => $pengelola->id,
        ]);

        $response = $this->actingAs($pengelola)->post(route('maintenance.store'), [
            'id_kendaraan' => $kendaraan->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Ganti oli rutin',
            'estimasi_biaya' => 500000,
            'kilometer_pengajuan' => 999999,
            'foto_bukti_odometer' => UploadedFile::fake()->image('odometer.jpg'),
        ]);

        $response->assertRedirect(route('maintenance.index', ['tab' => 'pengajuan']));
        $this->assertDatabaseHas('pengajuan_servis', [
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengelola->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Ganti oli rutin',
            'status_persetujuan' => 'MENUNGGU',
            'kilometer_pengajuan' => 3000,
        ]);

        $pengajuan = PengajuanServis::query()->where('id_kendaraan', $kendaraan->id)->firstOrFail();

        $this->assertStringStartsWith(
            'bukti_odometer/fake-',
            $pengajuan->foto_bukti_odometer,
        );

        $this->actingAs($pengelola)
            ->get(route('maintenance.show', $pengajuan))
            ->assertOk()
            ->assertSee('Foto Bukti Odometer')
            ->assertSee('Lihat foto bukti');
    }

    public function test_form_pengajuan_mengubah_label_foto_sesuai_jenis_dan_menyembunyikan_komponen_untuk_darurat(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        Kendaraan::factory()->for($pengelola, 'pengelola')->create();

        $this->actingAs($pengelola)
            ->withSession(['_old_input' => ['jenis_pengajuan' => 'DARURAT']])
            ->get(route('maintenance.create'))
            ->assertOk()
            ->assertSee('id="photo-label"', false)
            ->assertSee("isEmergency ? 'Foto Bukti Kerusakan' : 'Foto Bukti Odometer'", false)
            ->assertSee("'Pilih kendaraan, unggah foto kerusakan, dan isi estimasi biaya.'", false)
            ->assertSee("'Isi rincian servis rutin dan unggah foto bukti odometer.'", false)
            ->assertSee('id="component-selection" class="hidden', false)
            ->assertSee('id="kilometer-field"', false)
            ->assertSee('id="description-field"', false)
            ->assertSee('costInput.required = isEmergency', false)
            ->assertSee('descriptionInput.required = !isEmergency', false);
    }

    public function test_pengajuan_maintenance_tanpa_foto_bukti_odometer_ditolak(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create();

        $this->actingAs($pengelola)->post(route('maintenance.store'), [
            'id_kendaraan' => $kendaraan->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Ganti oli rutin',
        ])->assertSessionHasErrors('foto_bukti_odometer');

        $this->assertDatabaseMissing('pengajuan_servis', [
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengelola->id,
        ]);
    }

    public function test_selesaikan_maintenance_aktif_menginput_ke_tabel_riwayat_servis(): void
    {
        $admin = User::factory()->admin()->create();
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'kilometer_terakhir' => 5000,
            'interval_servis_km' => 5000,
            'status_perawatan' => 'SEDANG_SERVIS',
        ]);

        $pengajuan = PengajuanServis::create([
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengelola->id,
            'id_disetujui_oleh' => $admin->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Servis berkala',
            'status_persetujuan' => 'DISETUJUI',
            'estimasi_biaya' => 750000,
            'dibuat_pada' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('maintenance.index', ['tab' => 'proses']))
            ->post(route('riwayat-servis.store'), [
                'id_pengajuan' => $pengajuan->id,
                'id_kendaraan' => $kendaraan->id,
                'tanggal_servis' => now()->toDateString(),
                'kilometer_servis' => 5500,
                'nama_bengkel' => 'Bengkel Resmi PLN',
                'total_biaya' => 800000,
                'foto_nota' => UploadedFile::fake()->image('nota-servis.jpg'),
            ]);

        $response->assertRedirect(route('maintenance.index', ['tab' => 'usai']));
        $this->assertDatabaseHas('riwayat_servis', [
            'id_pengajuan' => $pengajuan->id,
            'id_kendaraan' => $kendaraan->id,
            'kilometer_servis' => 5000,
            'nama_bengkel' => 'Bengkel Resmi PLN',
            'total_biaya' => 800000,
            'target_kilometer_berikutnya' => 10000,
            'id_pembuat' => $admin->id,
        ]);
        $serviceHistory = RiwayatServis::query()->where('id_pengajuan', $pengajuan->id)->firstOrFail();
        $this->assertNotNull($serviceHistory->foto_nota);
        $this->assertStringStartsWith(
            'nota_servis/',
            $serviceHistory->foto_nota,
        );

        $kendaraan->refresh();
        $this->assertEquals(5000, $kendaraan->kilometer_terakhir);
        $this->assertEquals('BAIK', $kendaraan->status_perawatan);

        // Setelah selesai, data berpindah dari tab proses ke tab usai.
        $processResponse = $this->actingAs($admin)->get(route('maintenance.index', ['tab' => 'proses']));
        $processResponse->assertDontSee('Bengkel Resmi PLN');

        $completedResponse = $this->actingAs($admin)->get(route('maintenance.index', ['tab' => 'usai']));
        $completedResponse->assertSee('Bengkel Resmi PLN')
            ->assertSee('Usai Maintenance')
            ->assertSee(route('service-history.show', $pengajuan->riwayatServis->id), false);
    }

    public function test_maintenance_cannot_be_completed_without_service_receipt_photo(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->pengelola()->create();
        $vehicle = Kendaraan::factory()->for($manager, 'pengelola')->create([
            'status_perawatan' => 'SEDANG_SERVIS',
        ]);
        $request = PengajuanServis::create([
            'id_kendaraan' => $vehicle->id,
            'id_pengaju' => $manager->id,
            'id_disetujui_oleh' => $admin->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Servis berkala',
            'status_persetujuan' => 'DISETUJUI',
            'dibuat_pada' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('maintenance.index', ['tab' => 'proses']))
            ->assertOk()
            ->assertSee('Upload Foto Nota / Dokumentasi Servis *')
            ->assertSee('id="foto_nota" name="foto_nota" type="file" accept="image/*,.pdf" required', false);

        $this->actingAs($admin)
            ->from(route('maintenance.index', ['tab' => 'proses']))
            ->post(route('riwayat-servis.store'), [
                'id_pengajuan' => $request->id,
                'id_kendaraan' => $vehicle->id,
                'tanggal_servis' => now()->toDateString(),
                'nama_bengkel' => 'Bengkel PLN',
                'total_biaya' => 100000,
            ])
            ->assertRedirect(route('maintenance.index', ['tab' => 'proses']))
            ->assertSessionHasErrors([
                'foto_nota' => 'Foto nota wajib diunggah untuk menyelesaikan maintenance.',
            ]);

        $this->assertDatabaseCount('riwayat_servis', 0);
        $this->assertDatabaseHas('pengajuan_servis', [
            'id' => $request->id,
            'status_persetujuan' => 'DISETUJUI',
        ]);
        $this->assertDatabaseHas('kendaraan', [
            'id' => $vehicle->id,
            'status_perawatan' => 'SEDANG_SERVIS',
        ]);
    }

    public function test_form_create_dan_ajax_menggunakan_kilometer_terakhir_jika_belum_ada_mileage(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'kilometer_terakhir' => 8500,
        ]);

        // Cek halaman create menampilkan kilometer_terakhir di json map
        $createResponse = $this->actingAs($pengelola)->get(route('maintenance.create'));
        $createResponse->assertOk();
        $createResponse->assertSee('"'.$kendaraan->id.'":8500', false);

        // Cek endpoint AJAX mileage-kendaraan
        $ajaxResponse = $this->actingAs($pengelola)->getJson(route('maintenance.mileage-kendaraan', $kendaraan));
        $ajaxResponse->assertOk();
        $ajaxResponse->assertJson(['kilometer_akhir' => 8500]);

        // Cek store menggunakan fallback kilometer_terakhir
        $storeResponse = $this->actingAs($pengelola)->post(route('maintenance.store'), [
            'id_kendaraan' => $kendaraan->id,
            'jenis_pengajuan' => 'DARURAT',
            'estimasi_biaya' => 300000,
            'foto_bukti_odometer' => UploadedFile::fake()->image('odometer.jpg'),
        ]);

        $storeResponse->assertRedirect(route('maintenance.index', ['tab' => 'pengajuan']));
        $this->assertDatabaseHas('pengajuan_servis', [
            'id_kendaraan' => $kendaraan->id,
            'kilometer_pengajuan' => 8500,
        ]);
    }

    private function createPengajuan(User $pengelola, Kendaraan $kendaraan, string $status): PengajuanServis
    {
        return PengajuanServis::create([
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengelola->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Servis berkala',
            'status_persetujuan' => $status,
            'dibuat_pada' => now(),
        ]);
    }
}
