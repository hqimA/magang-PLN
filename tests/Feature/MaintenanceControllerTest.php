<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\KomponenKendaraan;
use App\Models\PengajuanServis;
use App\Models\RiwayatServis;
use App\Models\TemplateJadwalDetail;
use App\Models\TemplateJadwalServis;
use App\Models\TemplateKomponen;
use App\Models\User;
use App\Services\ImageStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\Fakes\FakeImageStorageService;
use Tests\TestCase;

class MaintenanceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->app->instance(ImageStorageService::class, new FakeImageStorageService);
    }

    public function test_rutin_merekomendasikan_aksi_template_terakhir_sejak_servis_terakhir(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'jenis_bbm' => 'BENSIN',
            'transmisi' => 'MANUAL',
            'kilometer_terakhir' => 25000,
        ]);

        $this->createTemplate('Template umum', 'SEMUA', 'SEMUA');
        $template = $this->createTemplate('Bensin Manual', 'BENSIN', 'MANUAL');
        $oli = $this->createTemplateComponent($template, 'Oli mesin', [
            [10000, 'P'],
            [20000, 'G'],
            [30000, 'P'],
        ]);
        $rem = $this->createTemplateComponent($template, 'Kampas rem', [
            [10000, 'G'],
            [20000, 'P'],
        ]);
        $this->createServiceHistory($kendaraan, $pengelola, 10000, now()->subMonths(2)->toDateString());

        $response = $this->actingAs($pengelola)
            ->getJson(route('maintenance.komponen-kendaraan', $kendaraan));

        $response->assertOk()
            ->assertJsonPath('template_nama', 'Bensin Manual')
            ->assertJsonPath('rekomendasi_count', 2)
            ->assertJsonPath('komponen.0.id_template_komponen', $oli->id)
            ->assertJsonPath('komponen.0.aksi_rekomendasi', 'G')
            ->assertJsonPath('komponen.0.km_jatuh_tempo', 20000)
            ->assertJsonPath('komponen.1.id_template_komponen', $rem->id)
            ->assertJsonPath('komponen.1.aksi_rekomendasi', 'P');
    }

    public function test_diesel_mencocokkan_template_solar_yang_tersedia(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'jenis_bbm' => 'DIESEL',
            'transmisi' => 'MANUAL',
            'kilometer_terakhir' => 5000,
        ]);
        $template = $this->createTemplate('Diesel Manual', 'SOLAR', 'MANUAL');
        $this->createTemplateComponent($template, 'Filter solar', [
            [5000, 'G'],
        ]);

        $this->actingAs($pengelola)
            ->getJson(route('maintenance.komponen-kendaraan', $kendaraan))
            ->assertOk()
            ->assertJsonPath('template_nama', 'Diesel Manual')
            ->assertJsonPath('komponen.0.aksi_rekomendasi', 'G');
    }

    public function test_rutin_hanya_memilih_jadwal_di_atas_kilometer_servis_terakhir(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'jenis_bbm' => 'BENSIN',
            'transmisi' => 'MANUAL',
            'kilometer_terakhir' => 20000,
        ]);
        $template = $this->createTemplate('Bensin Manual', 'BENSIN', 'MANUAL');
        $this->createTemplateComponent($template, 'Oli mesin', [
            [10000, 'P'],
            [20000, 'G'],
        ]);
        $this->createServiceHistory($kendaraan, $pengelola, 20000, now()->toDateString());

        $this->actingAs($pengelola)
            ->getJson(route('maintenance.komponen-kendaraan', $kendaraan))
            ->assertOk()
            ->assertJsonPath('rekomendasi_count', 0);
    }

    public function test_rutin_menyimpan_aksi_template_dan_membuat_komponen_kendaraan(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'jenis_bbm' => 'BENSIN',
            'transmisi' => 'MANUAL',
            'kilometer_terakhir' => 25000,
        ]);
        $template = $this->createTemplate('Bensin Manual', 'BENSIN', 'MANUAL');
        $oli = $this->createTemplateComponent($template, 'Oli mesin', [
            [10000, 'P'],
            [20000, 'G'],
        ]);
        $this->createServiceHistory($kendaraan, $pengelola, 10000, now()->subMonths(2)->toDateString());

        $response = $this->actingAs($pengelola)
            ->from(route('maintenance.create'))
            ->post(route('maintenance.store'), [
                'id_kendaraan' => $kendaraan->id,
                'jenis_pengajuan' => 'RUTIN',
                'deskripsi_keluhan' => 'Servis berkala',
                'foto_bukti_odometer' => UploadedFile::fake()->image('odometer.jpg'),
                'komponen' => [
                    [
                        'id_template_komponen' => $oli->id,
                        'jenis_aksi' => 'P',
                    ],
                ],
            ]);

        $response->assertRedirect(route('maintenance.index', ['tab' => 'pengajuan']));
        $this->assertDatabaseHas('komponen_kendaraan', [
            'id_kendaraan' => $kendaraan->id,
            'id_template_komponen' => $oli->id,
            'nama_komponen' => 'Oli mesin',
        ]);

        $pengajuan = PengajuanServis::query()->where('id_kendaraan', $kendaraan->id)->firstOrFail();
        $komponenKendaraan = KomponenKendaraan::query()
            ->where('id_kendaraan', $kendaraan->id)
            ->where('id_template_komponen', $oli->id)
            ->firstOrFail();

        $this->assertDatabaseHas('pengajuan_komponen', [
            'id_pengajuan' => $pengajuan->id,
            'id_komponen_kendaraan' => $komponenKendaraan->id,
            'jenis_aksi' => 'G',
        ]);
    }

    public function test_rutin_hanya_menyimpan_komponen_template_yang_dipilih(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'jenis_bbm' => 'BENSIN',
            'transmisi' => 'MANUAL',
            'kilometer_terakhir' => 25000,
        ]);
        $template = $this->createTemplate('Bensin Manual', 'BENSIN', 'MANUAL');
        $oli = $this->createTemplateComponent($template, 'Oli mesin', [
            [10000, 'P'],
            [20000, 'G'],
        ]);
        $rem = $this->createTemplateComponent($template, 'Kampas rem', [
            [10000, 'G'],
            [20000, 'P'],
        ]);
        $this->createServiceHistory($kendaraan, $pengelola, 10000, now()->subMonths(2)->toDateString());

        $response = $this->actingAs($pengelola)
            ->post(route('maintenance.store'), [
                'id_kendaraan' => $kendaraan->id,
                'jenis_pengajuan' => 'RUTIN',
                'deskripsi_keluhan' => 'Servis berkala',
                'foto_bukti_odometer' => UploadedFile::fake()->image('odometer.jpg'),
                'komponen_selection_present' => '1',
                'komponen' => [
                    1 => [
                        'id_template_komponen' => $rem->id,
                        'catatan' => 'Periksa suara roda',
                    ],
                ],
            ]);

        $response->assertRedirect(route('maintenance.index', ['tab' => 'pengajuan']));
        $pengajuan = PengajuanServis::query()->where('id_kendaraan', $kendaraan->id)->firstOrFail();
        $remKendaraan = KomponenKendaraan::query()
            ->where('id_kendaraan', $kendaraan->id)
            ->where('id_template_komponen', $rem->id)
            ->firstOrFail();

        $this->assertDatabaseCount('pengajuan_komponen', 1);
        $this->assertDatabaseHas('pengajuan_komponen', [
            'id_pengajuan' => $pengajuan->id,
            'id_komponen_kendaraan' => $remKendaraan->id,
            'jenis_aksi' => 'P',
            'catatan' => 'Periksa suara roda',
        ]);
        $this->assertDatabaseMissing('pengajuan_komponen', [
            'id_pengajuan' => $pengajuan->id,
            'id_komponen_kendaraan' => KomponenKendaraan::query()
                ->where('id_kendaraan', $kendaraan->id)
                ->where('id_template_komponen', $oli->id)
                ->value('id'),
        ]);
    }

    public function test_rutin_menyimpan_semua_komponen_jatuh_tempo_meski_request_tidak_mengirim_pilihan(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'jenis_bbm' => 'BENSIN',
            'transmisi' => 'MANUAL',
            'kilometer_terakhir' => 25000,
        ]);
        $template = $this->createTemplate('Bensin Manual', 'BENSIN', 'MANUAL');
        $oli = $this->createTemplateComponent($template, 'Oli mesin', [
            [10000, 'P'],
            [20000, 'G'],
        ]);
        $filter = $this->createTemplateComponent($template, 'Filter udara', [
            [30000, 'P'],
        ]);
        $this->createServiceHistory($kendaraan, $pengelola, 10000, now()->subMonths(2)->toDateString());
        KomponenKendaraan::create([
            'id_kendaraan' => $kendaraan->id,
            'id_template_komponen' => $oli->id,
            'nama_komponen' => 'Oli mesin',
            'kategori' => 'KOMPONEN_DASAR_MESIN',
            'nomor_urut' => 1,
            'interval_km' => 10000,
            'interval_bulan' => 6,
            'jenis_aksi_default' => 'P',
            'km_jatuh_tempo' => 30000,
            'status' => 'AMAN',
            'is_aktif' => true,
        ]);

        $response = $this->actingAs($pengelola)
            ->post(route('maintenance.store'), [
                'id_kendaraan' => $kendaraan->id,
                'jenis_pengajuan' => 'RUTIN',
                'deskripsi_keluhan' => 'Servis berkala',
                'foto_bukti_odometer' => UploadedFile::fake()->image('odometer.jpg'),
            ]);

        $response->assertRedirect(route('maintenance.index', ['tab' => 'pengajuan']));
        $this->assertDatabaseHas('komponen_kendaraan', [
            'id_kendaraan' => $kendaraan->id,
            'id_template_komponen' => $oli->id,
            'jenis_aksi_default' => 'G',
            'km_jatuh_tempo' => 20000,
            'status' => 'JATUH_TEMPO',
        ]);
        $this->assertDatabaseHas('komponen_kendaraan', [
            'id_kendaraan' => $kendaraan->id,
            'id_template_komponen' => $filter->id,
        ]);

        $pengajuan = PengajuanServis::query()->where('id_kendaraan', $kendaraan->id)->firstOrFail();
        $oliVehicleComponent = KomponenKendaraan::query()
            ->where('id_kendaraan', $kendaraan->id)
            ->where('id_template_komponen', $oli->id)
            ->firstOrFail();

        $this->assertDatabaseHas('pengajuan_komponen', [
            'id_pengajuan' => $pengajuan->id,
            'id_komponen_kendaraan' => $oliVehicleComponent->id,
            'jenis_aksi' => 'G',
        ]);
        $this->assertDatabaseCount('pengajuan_komponen', 1);
    }

    public function test_rutin_menolak_komponen_di_luar_jadwal_kilometer_saat_ini(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create([
            'jenis_bbm' => 'BENSIN',
            'transmisi' => 'MANUAL',
            'kilometer_terakhir' => 15000,
        ]);
        $template = $this->createTemplate('Bensin Manual', 'BENSIN', 'MANUAL');
        $belumJatuhTempo = $this->createTemplateComponent($template, 'Filter udara', [
            [20000, 'G'],
        ]);

        $response = $this->actingAs($pengelola)
            ->from(route('maintenance.create'))
            ->post(route('maintenance.store'), [
                'id_kendaraan' => $kendaraan->id,
                'jenis_pengajuan' => 'RUTIN',
                'deskripsi_keluhan' => 'Servis berkala',
                'foto_bukti_odometer' => UploadedFile::fake()->image('odometer.jpg'),
                'komponen' => [
                    ['id_template_komponen' => $belumJatuhTempo->id],
                ],
            ]);

        $response->assertSessionHasErrors('komponen.0.id_template_komponen');
        $this->assertDatabaseCount('pengajuan_servis', 0);
    }

    public function test_darurat_mengabaikan_rincian_komponen_yang_dikirim(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create();
        $kendaraanLain = Kendaraan::factory()->for($pengelola, 'pengelola')->create();
        $komponenKendaraanLain = $this->createVehicleComponent($kendaraanLain, 'Aki');

        $this->actingAs($pengelola)
            ->from(route('maintenance.create'))
            ->post(route('maintenance.store'), [
                'id_kendaraan' => $kendaraan->id,
                'jenis_pengajuan' => 'DARURAT',
                'estimasi_biaya' => 250000,
                'foto_bukti_odometer' => UploadedFile::fake()->image('kerusakan.jpg'),
                'komponen' => [
                    [
                        'id_komponen_kendaraan' => $komponenKendaraanLain->id,
                        'jenis_aksi' => 'G',
                    ],
                ],
            ])
            ->assertRedirect(route('maintenance.index', ['tab' => 'pengajuan']));

        $pengajuan = PengajuanServis::query()->where('id_kendaraan', $kendaraan->id)->firstOrFail();
        $this->assertDatabaseCount('pengajuan_komponen', 0);
        $this->assertDatabaseHas('pengajuan_servis', [
            'id' => $pengajuan->id,
            'jenis_pengajuan' => 'DARURAT',
            'estimasi_biaya' => 250000,
            'deskripsi_keluhan' => 'Pengajuan darurat tanpa keterangan tambahan.',
        ]);
        $this->assertStringStartsWith(
            'bukti_kerusakan/fake-',
            $pengajuan->foto_bukti_odometer,
        );
    }

    public function test_darurat_mewajibkan_estimasi_biaya(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create();

        $this->actingAs($pengelola)
            ->from(route('maintenance.create'))
            ->post(route('maintenance.store'), [
                'id_kendaraan' => $kendaraan->id,
                'jenis_pengajuan' => 'DARURAT',
                'foto_bukti_odometer' => UploadedFile::fake()->image('kerusakan.jpg'),
            ])
            ->assertRedirect(route('maintenance.create'))
            ->assertSessionHasErrors('estimasi_biaya');

        $this->assertDatabaseMissing('pengajuan_servis', [
            'id_kendaraan' => $kendaraan->id,
        ]);
    }

    public function test_halaman_detail_darurat_tidak_merender_rincian_komponen(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create();
        $pengajuan = PengajuanServis::create([
            'id_kendaraan' => $kendaraan->id,
            'id_pengaju' => $pengelola->id,
            'jenis_pengajuan' => 'DARURAT',
            'deskripsi_keluhan' => 'Ban bocor',
            'status_persetujuan' => 'MENUNGGU',
            'dibuat_pada' => now(),
        ]);

        $this->actingAs($pengelola)
            ->get(route('maintenance.show', $pengajuan))
            ->assertOk()
            ->assertDontSee('Komponen & Suku Cadang yang Diajukan')
            ->assertSee('Foto Bukti Kerusakan');
    }

    public function test_pengelola_dapat_membatalkan_pengajuan_yang_menunggu(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create();
        $pengajuan = $this->createPengajuan($pengelola, $kendaraan, 'MENUNGGU');

        $response = $this->actingAs($pengelola)
            ->from(route('maintenance.index', ['tab' => 'pengajuan']))
            ->patch(route('maintenance.cancel', $pengajuan));

        $response->assertRedirect(route('maintenance.index', ['tab' => 'pengajuan']))
            ->assertSessionHas('success', 'Pengajuan maintenance berhasil dibatalkan.');
        $this->assertDatabaseHas('pengajuan_servis', [
            'id' => $pengajuan->id,
            'status_persetujuan' => 'DIBATALKAN',
        ]);
    }

    public function test_pengelola_dapat_membatalkan_pengajuan_yang_ditolak(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create();
        $pengajuan = $this->createPengajuan($pengelola, $kendaraan, 'DITOLAK');

        $this->actingAs($pengelola)
            ->patch(route('maintenance.cancel', $pengajuan))
            ->assertSessionHas('success', 'Pengajuan maintenance berhasil dibatalkan.');

        $this->assertDatabaseHas('pengajuan_servis', [
            'id' => $pengajuan->id,
            'status_persetujuan' => 'DIBATALKAN',
        ]);
    }

    public function test_pengelola_tidak_dapat_membatalkan_pengajuan_yang_sudah_disetujui(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create();
        $pengajuan = $this->createPengajuan($pengelola, $kendaraan, 'DISETUJUI');

        $this->actingAs($pengelola)
            ->from(route('maintenance.index', ['tab' => 'pengajuan']))
            ->patch(route('maintenance.cancel', $pengajuan))
            ->assertRedirect(route('maintenance.index', ['tab' => 'pengajuan']))
            ->assertSessionHas('error', 'Pengajuan hanya dapat dibatalkan jika statusnya Menunggu atau Ditolak.');

        $this->assertDatabaseHas('pengajuan_servis', [
            'id' => $pengajuan->id,
            'status_persetujuan' => 'DISETUJUI',
        ]);
    }

    public function test_pengelola_tidak_dapat_membatalkan_pengajuan_milik_pengelola_lain(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $pemilikPengajuan = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pemilikPengajuan, 'pengelola')->create();
        $pengajuan = $this->createPengajuan($pemilikPengajuan, $kendaraan, 'MENUNGGU');

        $this->actingAs($pengelola)
            ->patch(route('maintenance.cancel', $pengajuan))
            ->assertNotFound();

        $this->assertDatabaseHas('pengajuan_servis', [
            'id' => $pengajuan->id,
            'status_persetujuan' => 'MENUNGGU',
        ]);
    }

    public function test_admin_tidak_dapat_membatalkan_pengajuan_maintenance(): void
    {
        $admin = User::factory()->admin()->create();
        $pengelola = User::factory()->pengelola()->create();
        $kendaraan = Kendaraan::factory()->for($pengelola, 'pengelola')->create();
        $pengajuan = $this->createPengajuan($pengelola, $kendaraan, 'MENUNGGU');

        $this->actingAs($admin)
            ->patch(route('maintenance.cancel', $pengajuan))
            ->assertForbidden();

        $this->assertDatabaseHas('pengajuan_servis', [
            'id' => $pengajuan->id,
            'status_persetujuan' => 'MENUNGGU',
        ]);
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

    /**
     * @param  array<int, array{0: int, 1: string}>  $schedules
     */
    private function createTemplateComponent(
        TemplateJadwalServis $template,
        string $name,
        array $schedules,
    ): TemplateKomponen {
        $component = TemplateKomponen::create([
            'id_template' => $template->id,
            'nomor_urut' => $template->komponen()->count() + 1,
            'nama_komponen' => $name,
            'kategori' => 'KOMPONEN_DASAR_MESIN',
            'is_aktif' => true,
        ]);

        foreach ($schedules as [$intervalKm, $action]) {
            TemplateJadwalDetail::create([
                'id_template_komponen' => $component->id,
                'interval_km' => $intervalKm,
                'interval_bulan' => 6,
                'jenis_aksi' => $action,
            ]);
        }

        return $component;
    }

    private function createServiceHistory(
        Kendaraan $kendaraan,
        User $creator,
        int $kilometer,
        string $date,
    ): RiwayatServis {
        return RiwayatServis::create([
            'id_kendaraan' => $kendaraan->id,
            'tanggal_servis' => $date,
            'kilometer_servis' => $kilometer,
            'nama_bengkel' => 'Bengkel PLN',
            'total_biaya' => 0,
            'id_pembuat' => $creator->id,
        ]);
    }

    private function createVehicleComponent(Kendaraan $kendaraan, string $name): KomponenKendaraan
    {
        return KomponenKendaraan::create([
            'id_kendaraan' => $kendaraan->id,
            'nama_komponen' => $name,
            'kategori' => 'KOMPONEN_DASAR_MESIN',
            'nomor_urut' => 1,
            'interval_km' => 5000,
            'interval_bulan' => 6,
            'jenis_aksi_default' => 'G',
            'km_jatuh_tempo' => 5000,
            'status' => 'AMAN',
            'is_aktif' => true,
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
