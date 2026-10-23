<?php

namespace Tests\Feature;

use App\Exports\MaintenanceExport;
use App\Models\Kendaraan;
use App\Models\PengajuanServis;
use App\Models\RiwayatServis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class MaintenanceExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_completed_maintenance_for_one_unit(): void
    {
        $admin = User::factory()->admin()->create();
        $managerUp2d = User::factory()->pengelola()->create(['unit_pln' => 'UP2D']);
        $managerUp3 = User::factory()->pengelola()->create(['unit_pln' => 'UP3']);
        $vehicleUp2d = Kendaraan::factory()->for($managerUp2d, 'pengelola')->create([
            'unit_pln' => 'UP2D',
            'plat_nomor' => 'B 1200 UP2',
        ]);
        $vehicleUp3 = Kendaraan::factory()->for($managerUp3, 'pengelola')->create([
            'unit_pln' => 'UP3',
            'plat_nomor' => 'B 1300 UP3',
        ]);
        $this->createServiceHistory($vehicleUp2d, $managerUp2d, 'Bengkel UP2D');
        $this->createServiceHistory($vehicleUp3, $managerUp3, 'Bengkel UP3');
        Excel::fake();

        $response = $this->actingAs($admin)->get(route('maintenance.export.completed', [
            'unit_pln' => 'UP3',
        ]));

        $response->assertOk();
        Excel::assertDownloaded(
            'maintenance-usai-UP3.xlsx',
            function (MaintenanceExport $export) use ($vehicleUp3): bool {
                $row = $export->query()->first();
                $this->assertNotNull($row);
                $this->assertSame([
                    '2026-10-02 00:00:00',
                    'UP3',
                    'B 1300 UP3',
                    $vehicleUp3->merk_tipe,
                    'UMUM',
                    'Bengkel UP3',
                    2000,
                    '200000.00',
                ], $export->map($row));

                return $export->query()->get()->pluck('plat_nomor')->all() === ['B 1300 UP3'];
            },
        );
    }

    public function test_admin_can_export_rejected_requests_for_all_units(): void
    {
        $admin = User::factory()->admin()->create();
        $managerUp2d = User::factory()->pengelola()->create(['unit_pln' => 'UP2D']);
        $managerUp3 = User::factory()->pengelola()->create(['unit_pln' => 'UP3']);
        $vehicleUp2d = Kendaraan::factory()->for($managerUp2d, 'pengelola')->create([
            'unit_pln' => 'UP2D',
            'plat_nomor' => 'B 2200 UP2',
        ]);
        $vehicleUp3 = Kendaraan::factory()->for($managerUp3, 'pengelola')->create([
            'unit_pln' => 'UP3',
            'plat_nomor' => 'B 2300 UP3',
        ]);
        $this->createRejectedRequest($vehicleUp2d, $managerUp2d, 'Bukti tidak lengkap.');
        $this->createRejectedRequest($vehicleUp3, $managerUp3, 'Biaya tidak disetujui.');
        $this->createRequest($vehicleUp3, $managerUp3, 'MENUNGGU', null);
        Excel::fake();

        $response = $this->actingAs($admin)->get(route('maintenance.export.rejected', [
            'unit_pln' => 'SEMUA',
        ]));

        $response->assertOk();
        Excel::assertDownloaded(
            'maintenance-ditolak-seluruh-unit.xlsx',
            fn (MaintenanceExport $export): bool => $export->query()->get()->pluck('plat_nomor')->all() === [
                'B 2200 UP2',
                'B 2300 UP3',
            ] && $export->headings() === [
                'Tanggal Pengajuan',
                'Unit PLN',
                'Nomor Polisi',
                'Nama Kendaraan',
                'Jenis Maintenance',
                'Pengaju',
                'Alasan Penolakan',
                'Estimasi Biaya',
                'Odometer (KM)',
            ],
        );
    }

    public function test_pengelola_can_only_export_their_unit_and_cannot_select_all_units(): void
    {
        $managerUp2d = User::factory()->pengelola()->create(['unit_pln' => 'UP2D']);
        $managerUp3 = User::factory()->pengelola()->create(['unit_pln' => 'UP3']);
        $vehicleUp2d = Kendaraan::factory()->for($managerUp2d, 'pengelola')->create([
            'unit_pln' => 'UP2D',
            'plat_nomor' => 'B 3200 UP2',
        ]);
        $vehicleUp3 = Kendaraan::factory()->for($managerUp3, 'pengelola')->create([
            'unit_pln' => 'UP3',
            'plat_nomor' => 'B 3300 UP3',
        ]);
        $this->createRejectedRequest($vehicleUp2d, $managerUp2d, 'Alasan UP2D.');
        $this->createRejectedRequest($vehicleUp3, $managerUp3, 'Alasan UP3.');
        Excel::fake();

        $response = $this->actingAs($managerUp2d)->get(route('maintenance.export.rejected'));

        $response->assertOk();
        Excel::assertDownloaded(
            'maintenance-ditolak-UP2D.xlsx',
            fn (MaintenanceExport $export): bool => $export->query()->get()->pluck('plat_nomor')->all() === ['B 3200 UP2'],
        );

        $this->actingAs($managerUp2d)
            ->get(route('maintenance.export.rejected', ['unit_pln' => 'UP3']))
            ->assertForbidden();
        $this->actingAs($managerUp2d)
            ->get(route('maintenance.export.rejected', ['unit_pln' => 'SEMUA']))
            ->assertForbidden();
    }

    public function test_pengelola_without_a_unit_cannot_export_maintenance(): void
    {
        $manager = User::factory()->pengelola()->create(['unit_pln' => null]);

        $this->actingAs($manager)
            ->get(route('maintenance.export.completed'))
            ->assertForbidden();
    }

    public function test_admin_can_select_the_export_unit_from_both_maintenance_tabs(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('maintenance.index', ['tab' => 'usai']))
            ->assertOk()
            ->assertSee(route('maintenance.export.completed'), false)
            ->assertSee('value="SEMUA"', false)
            ->assertSee('value="UP2D"', false)
            ->assertSee('value="UP3"', false);

        $this->actingAs($admin)
            ->get(route('maintenance.index', ['tab' => 'ditolak']))
            ->assertOk()
            ->assertSee(route('maintenance.export.rejected'), false)
            ->assertSee('value="SEMUA"', false)
            ->assertSee('value="UP2D"', false)
            ->assertSee('value="UP3"', false);
    }

    public function test_pengelola_export_forms_show_only_their_unit(): void
    {
        $manager = User::factory()->pengelola()->create(['unit_pln' => 'UP2D']);

        $this->actingAs($manager)
            ->get(route('maintenance.index', ['tab' => 'usai']))
            ->assertOk()
            ->assertSee(route('maintenance.export.completed'), false)
            ->assertSee('name="unit_pln" value="UP2D"', false)
            ->assertDontSee('value="SEMUA"', false)
            ->assertDontSee('value="UP3"', false);

        $this->actingAs($manager)
            ->get(route('maintenance.index', ['tab' => 'ditolak']))
            ->assertOk()
            ->assertSee(route('maintenance.export.rejected'), false)
            ->assertSee('name="unit_pln" value="UP2D"', false)
            ->assertDontSee('value="SEMUA"', false)
            ->assertDontSee('value="UP3"', false);
    }

    public function test_guest_is_redirected_to_login_from_maintenance_exports(): void
    {
        $this->get(route('maintenance.export.completed'))
            ->assertRedirect(route('login'));
        $this->get(route('maintenance.export.rejected'))
            ->assertRedirect(route('login'));
    }

    private function createServiceHistory(Kendaraan $vehicle, User $creator, string $workshop): RiwayatServis
    {
        return RiwayatServis::query()->create([
            'id_kendaraan' => $vehicle->id,
            'tanggal_servis' => '2026-10-02',
            'kilometer_servis' => 2000,
            'nama_bengkel' => $workshop,
            'total_biaya' => 200000,
            'id_pembuat' => $creator->id,
        ]);
    }

    private function createRejectedRequest(Kendaraan $vehicle, User $requester, string $reason): PengajuanServis
    {
        return $this->createRequest($vehicle, $requester, 'DITOLAK', $reason);
    }

    private function createRequest(
        Kendaraan $vehicle,
        User $requester,
        string $status,
        ?string $reason,
    ): PengajuanServis {
        return PengajuanServis::query()->create([
            'id_kendaraan' => $vehicle->id,
            'id_pengaju' => $requester->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Servis berkala',
            'estimasi_biaya' => 250000,
            'kilometer_pengajuan' => 1500,
            'status_persetujuan' => $status,
            'alasan_penolakan' => $reason,
            'dibuat_pada' => '2026-10-01 10:00:00',
        ]);
    }
}
