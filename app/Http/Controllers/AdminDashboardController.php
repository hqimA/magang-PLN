<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\LaporanKerusakan;
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
        $serviceReminderStatuses = collect();
        $serviceReminderSetting = ServiceReminderSetting::query()->first();

        if (! $serviceReminderSetting || $serviceReminderSetting->is_active) {
            $serviceReminderStatuses = Kendaraan::query()
                ->with('riwayatServisTerbaru')
                ->get()
                ->map(fn (Kendaraan $vehicle): string => $vehicle->serviceReminder()['status']);
            $serviceReminderCount = $serviceReminderStatuses
                ->filter(fn (string $status): bool => $status !== 'TERJADWAL')
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

        $currentMonth = $today->copy();
        $serviceCostThisMonth = RiwayatServis::query()
            ->whereYear('tanggal_servis', $currentMonth->year)
            ->whereMonth('tanggal_servis', $currentMonth->month)
            ->sum('total_biaya');
        $repairCostThisMonth = RiwayatPerbaikan::query()
            ->whereYear('tanggal_perbaikan', $currentMonth->year)
            ->whereMonth('tanggal_perbaikan', $currentMonth->month)
            ->sum('total_biaya_perbaikan');
        $serviceCostTotal = RiwayatServis::query()->sum('total_biaya');
        $repairCostTotal = RiwayatPerbaikan::query()->sum('total_biaya_perbaikan');
        $pendingApprovals = PengajuanServis::query()
            ->with(['kendaraan:id,plat_nomor,merk_tipe', 'pengaju:id,name'])
            ->where('status_persetujuan', 'MENUNGGU')
            ->orderBy('dibuat_pada')
            ->get();
        $currentYear = (int) $today->year;
        $lifecycleData = [
            'baru' => Kendaraan::query()->whereRaw("({$currentYear} - tahun_pembuatan) <= 1")->count(),
            'normal' => Kendaraan::query()->whereRaw("({$currentYear} - tahun_pembuatan) BETWEEN 2 AND 3")->count(),
            'perhatian' => Kendaraan::query()->whereRaw("({$currentYear} - tahun_pembuatan) = 4")->count(),
            'prioritas' => Kendaraan::query()->whereRaw("({$currentYear} - tahun_pembuatan) >= 5")->count(),
        ];
        $categoryBreakdown = Kendaraan::query()
            ->select('kategori_penggunaan')
            ->selectRaw('COUNT(*) as jumlah')
            ->groupBy('kategori_penggunaan')
            ->orderBy('kategori_penggunaan')
            ->get();
        $fuelBreakdown = Kendaraan::query()
            ->select('jenis_bbm')
            ->selectRaw('COUNT(*) as jumlah')
            ->groupBy('jenis_bbm')
            ->orderBy('jenis_bbm')
            ->get();
        $activeDamageReports = LaporanKerusakan::query()
            ->whereIn('status_penanganan', ['DILAPORKAN', 'SEDANG_DIPERBAIKI'])
            ->count();
        $problemVehicles = Kendaraan::query()
            ->whereIn('status_perawatan', ['PERLU_SERVIS', 'SEDANG_SERVIS', 'RUSAK'])
            ->count();
        $recentLogs = collect();
        Kendaraan::query()
            ->with('pengelola')
            ->latest('dibuat_pada')
            ->limit(5)
            ->get()
            ->each(function (Kendaraan $vehicle) use ($recentLogs): void {
                $recentLogs->push((object) [
                    'title' => 'Admin menambahkan kendaraan',
                    'description' => "{$vehicle->merk_tipe} - {$vehicle->plat_nomor}",
                    'timestamp' => $vehicle->dibuat_pada,
                    'type' => 'kendaraan',
                ]);
            });
        PengajuanServis::query()
            ->with(['kendaraan', 'pengaju'])
            ->latest('dibuat_pada')
            ->limit(5)
            ->get()
            ->each(function (PengajuanServis $requestItem) use ($recentLogs): void {
                $recentLogs->push((object) [
                    'title' => 'Pengajuan biaya '.strtolower($requestItem->jenis_pengajuan),
                    'description' => "{$requestItem->kendaraan->merk_tipe} - {$requestItem->kendaraan->plat_nomor}",
                    'timestamp' => $requestItem->dibuat_pada,
                    'type' => 'pengajuan',
                ]);
            });
        RiwayatServis::query()
            ->with(['kendaraan', 'pembuat'])
            ->orderByDesc('tanggal_servis')
            ->limit(5)
            ->get()
            ->each(function (RiwayatServis $serviceHistory) use ($recentLogs): void {
                $recentLogs->push((object) [
                    'title' => 'Maintenance selesai dicatat',
                    'description' => "{$serviceHistory->kendaraan->merk_tipe} di {$serviceHistory->nama_bengkel}",
                    'timestamp' => Carbon::parse($serviceHistory->tanggal_servis)->startOfDay(),
                    'type' => 'riwayat',
                ]);
            });
        $recentLogs = $recentLogs->sortByDesc('timestamp')->take(5)->values();
        $kpiData = [
            'totalKendaraan' => (int) Kendaraan::query()->count(),
            'kendaraanBermasalah' => $problemVehicles,
            'pendingApproval' => $approvalSummary['MENUNGGU'],
            'laporanKerusakanAktif' => $activeDamageReports,
            'totalPengeluaranBulanIni' => round((float) $serviceCostThisMonth + (float) $repairCostThisMonth, 2),
            'totalMaintenance' => (int) RiwayatServis::query()->count(),
            'totalBiayaMaintenance' => (float) $serviceCostTotal,
            'totalPengeluaranLengkap' => round((float) $serviceCostTotal + (float) $repairCostTotal, 2),
            'kategoriBreakdown' => $categoryBreakdown,
            'bbmBreakdown' => $fuelBreakdown,
            'lifecycleData' => $lifecycleData,
            'pengajuanMenungguList' => $pendingApprovals,
            'alerts' => [
                'terlambat' => $serviceReminderStatuses->filter(fn (string $status): bool => $status === 'TERLAMBAT_SERVIS')->count(),
                'mendekati' => $serviceReminderStatuses->filter(fn (string $status): bool => $status === 'MENDEKATI_SERVIS')->count(),
                'pengajuan' => $approvalSummary['MENUNGGU'],
                'maintenance' => $activeDamageReports,
            ],
            'recentLogs' => $recentLogs,
        ];

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
            'kpiData' => $kpiData,
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
