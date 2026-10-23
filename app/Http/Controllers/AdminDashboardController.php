<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\Mileage;
use App\Models\Notifikasi;
use App\Models\PengajuanServis;
use App\Models\RiwayatPerbaikan;
use App\Models\RiwayatServis;
use App\Models\ServiceReminderSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $admin = $request->user();

        if ($admin->isPengelola()) {
            return $this->pengelolaDashboard($request);
        }

        if ($admin->peran !== 'ADMIN') {
            return redirect()->route('pengelola.kendaraan');
        }

        $today = now();
        $todayDate = $today->toDateString();
        $periodStart = $today->copy()->startOfMonth()->subMonths(5);
        $periodStartDate = $periodStart->toDateString();
        $databaseDriver = DB::connection()->getDriverName();
        $monthExpression = $databaseDriver === 'sqlite'
            ? "strftime('%Y-%m', %s)"
            : "DATE_FORMAT(%s, '%Y-%m')";
        $serviceMonthExpression = str_replace('%s', 'tanggal_servis', $monthExpression);
        $repairMonthExpression = str_replace('%s', 'tanggal_perbaikan', $monthExpression);

        $monthPeriods = collect(range(5, 0))
            ->map(fn (int $monthsAgo): Carbon => $today->copy()->startOfMonth()->subMonths($monthsAgo));
        $monthKeys = $monthPeriods->map(fn (Carbon $month): string => $month->format('Y-m'));

        $serviceCostRows = RiwayatServis::query()
            ->whereBetween('tanggal_servis', [$periodStartDate, $todayDate])
            ->selectRaw("{$serviceMonthExpression} as month, SUM(total_biaya) as total")
            ->groupBy('month')
            ->get();
        $repairCostRows = RiwayatPerbaikan::query()
            ->whereBetween('tanggal_perbaikan', [$periodStartDate, $todayDate])
            ->selectRaw("{$repairMonthExpression} as month, SUM(total_biaya_perbaikan) as total")
            ->groupBy('month')
            ->get();
        $serviceCostsByMonth = $serviceCostRows->mapWithKeys(
            fn (object $row): array => [$row->month => (float) $row->total],
        );
        $repairCostsByMonth = $repairCostRows->mapWithKeys(
            fn (object $row): array => [$row->month => (float) $row->total],
        );
        $completedByMonth = RiwayatServis::query()
            ->whereBetween('tanggal_servis', [$periodStartDate, $todayDate])
            ->selectRaw("{$serviceMonthExpression} as month, COUNT(*) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $expenseChart = [
            'labels' => $monthPeriods
                ->map(fn (Carbon $month): string => $month->locale('id')->translatedFormat('M Y'))
                ->all(),
            'values' => $monthKeys
                ->map(fn (string $month): float => round(
                    (float) $serviceCostsByMonth->get($month, 0)
                    + (float) $repairCostsByMonth->get($month, 0),
                    2,
                ))
                ->all(),
        ];
        $maintenanceChart = [
            'labels' => $monthPeriods
                ->map(fn (Carbon $month): string => $month->locale('id')->translatedFormat('M Y'))
                ->all(),
            'values' => $monthKeys
                ->map(fn (string $month): int => (int) $completedByMonth->get($month, 0))
                ->all(),
        ];

        $activeMaintenanceCount = PengajuanServis::query()
            ->where('status_persetujuan', 'DISETUJUI')
            ->whereDoesntHave('riwayatServis')
            ->count();
        $completedMaintenanceCount = RiwayatServis::query()
            ->whereBetween('tanggal_servis', [
                $today->copy()->subDays(29)->toDateString(),
                $todayDate,
            ])
            ->count();
        $serviceReminderCount = 0;
        $serviceReminderSetting = ServiceReminderSetting::query()->first();

        if (! $serviceReminderSetting || $serviceReminderSetting->is_active) {
            $serviceReminderCount = Kendaraan::query()
                ->with('riwayatServisTerbaru')
                ->get()
                ->filter(fn (Kendaraan $vehicle): bool => $vehicle->serviceReminder()['status'] !== 'TERJADWAL')
                ->count();
        }

        $approvalCountsByStatus = PengajuanServis::query()
            ->select('status_persetujuan')
            ->selectRaw('COUNT(*) as total')
            ->whereIn('status_persetujuan', ['MENUNGGU', 'DISETUJUI', 'DITOLAK'])
            ->groupBy('status_persetujuan')
            ->pluck('total', 'status_persetujuan');
        $approvalSummary = [
            'MENUNGGU' => (int) $approvalCountsByStatus->get('MENUNGGU', 0),
            'DISETUJUI' => (int) $approvalCountsByStatus->get('DISETUJUI', 0),
            'DITOLAK' => (int) $approvalCountsByStatus->get('DITOLAK', 0),
        ];

        $latestRequests = PengajuanServis::query()
            ->with([
                'kendaraan:id,plat_nomor,merk_tipe',
                'pengaju:id,name',
            ])
            ->select([
                'id',
                'id_kendaraan',
                'id_pengaju',
                'jenis_pengajuan',
                'status_persetujuan',
                'dibuat_pada',
            ])
            ->orderByDesc('dibuat_pada')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $unreadNotificationsCount = Notifikasi::query()
            ->where('id_pengguna', $admin->id)
            ->where('sudah_dibaca', false)
            ->count();
        $latestNotifications = Notifikasi::query()
            ->where('id_pengguna', $admin->id)
            ->select(['id', 'judul', 'pesan', 'sudah_dibaca', 'dibuat_pada'])
            ->orderByDesc('dibuat_pada')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'admin' => $admin,
            'stats' => [
                'totalVehicles' => Kendaraan::query()->count(),
                'activeMaintenance' => $activeMaintenanceCount,
                'completedMaintenance' => $completedMaintenanceCount,
                'serviceReminders' => $serviceReminderCount,
            ],
            'expenseChart' => $expenseChart,
            'hasExpenseData' => $serviceCostRows->isNotEmpty() || $repairCostRows->isNotEmpty(),
            'maintenanceChart' => $maintenanceChart,
            'latestRequests' => $latestRequests,
            'approvalSummary' => $approvalSummary,
            'latestNotifications' => $latestNotifications,
            'unreadNotificationsCount' => $unreadNotificationsCount,
        ]);
    }

    private function pengelolaDashboard(Request $request): View
    {
        $pengelola = $request->user();
        $visibleVehicles = Kendaraan::query()->visibleTo($pengelola);
        $vehicleCount = (clone $visibleVehicles)->count();
        $requestsForUnit = fn (): Builder => PengajuanServis::query()
            ->whereHas('kendaraan', fn (Builder $query): Builder => $query->visibleTo($pengelola));

        $pendingRequestsCount = $requestsForUnit()
            ->where('status_persetujuan', 'MENUNGGU')
            ->count();
        $activeMaintenanceCount = $requestsForUnit()
            ->where('status_persetujuan', 'DISETUJUI')
            ->whereDoesntHave('riwayatServis')
            ->count();
        $unitVehicles = (clone $visibleVehicles)
            ->select(['id', 'plat_nomor', 'merk_tipe', 'tahun_pembuatan', 'kilometer_terakhir'])
            ->orderBy('plat_nomor')
            ->limit(5)
            ->get();
        $recentMileage = Mileage::query()
            ->with('kendaraan:id,plat_nomor,merk_tipe')
            ->whereHas('kendaraan', fn (Builder $query): Builder => $query->visibleTo($pengelola))
            ->orderByDesc('tanggal_perjalanan')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $serviceReminderSetting = ServiceReminderSetting::query()->first();
        $serviceReminders = collect();

        if (! $serviceReminderSetting || $serviceReminderSetting->is_active) {
            $serviceReminders = (clone $visibleVehicles)
                ->select([
                    'id',
                    'plat_nomor',
                    'merk_tipe',
                    'kilometer_terakhir',
                    'interval_servis_km',
                    'interval_servis_bulan',
                    'threshold_servis_km',
                    'tanggal_pembelian',
                ])
                ->with('riwayatServisTerbaru')
                ->orderBy('plat_nomor')
                ->get()
                ->map(fn (Kendaraan $vehicle): array => [
                    'vehicle' => $vehicle,
                    'reminder' => $vehicle->serviceReminder(),
                ])
                ->filter(fn (array $item): bool => $item['reminder']['status'] !== 'TERJADWAL')
                ->sortBy(fn (array $item): int => $item['reminder']['sisa_kilometer'])
                ->values();
        }

        $latestRequests = $requestsForUnit()
            ->with('kendaraan:id,plat_nomor,merk_tipe')
            ->select([
                'id',
                'id_kendaraan',
                'jenis_pengajuan',
                'status_persetujuan',
                'dibuat_pada',
            ])
            ->orderByDesc('dibuat_pada')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('pengelola.dashboard', [
            'pengelola' => $pengelola,
            'stats' => [
                'totalVehicles' => $vehicleCount,
                'pendingRequests' => $pendingRequestsCount,
                'activeMaintenance' => $activeMaintenanceCount,
                'serviceReminders' => $serviceReminders->count(),
            ],
            'unitVehicles' => $unitVehicles,
            'recentMileage' => $recentMileage,
            'serviceReminders' => $serviceReminders->take(5),
            'serviceRemindersEnabled' => ! $serviceReminderSetting || $serviceReminderSetting->is_active,
            'latestRequests' => $latestRequests,
        ]);
    }

    public function approvePengajuan(Request $request, PengajuanServis $pengajuan): RedirectResponse
    {
        if ($pengajuan->status_persetujuan !== 'MENUNGGU') {
            return back()->with('error', 'Status pengajuan sudah diproses.');
        }

        $pengajuan->update([
            'status_persetujuan' => 'DISETUJUI',
            'id_disetujui_oleh' => $request->user()->id,
        ]);

        return back()->with('success', 'Pengajuan biaya berhasil disetujui.');
    }

    public function rejectPengajuan(Request $request, PengajuanServis $pengajuan): RedirectResponse
    {
        if ($pengajuan->status_persetujuan !== 'MENUNGGU') {
            return back()->with('error', 'Status pengajuan sudah diproses.');
        }

        $request->validate([
            'alasan_penolakan' => ['nullable', 'string', 'max:500'],
        ]);

        $pengajuan->update([
            'status_persetujuan' => 'DITOLAK',
            'id_disetujui_oleh' => $request->user()->id,
            'alasan_penolakan' => $request->input('alasan_penolakan'),
        ]);

        return back()->with('success', 'Pengajuan biaya berhasil ditolak.');
    }
}
