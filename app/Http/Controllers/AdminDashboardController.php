<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\LaporanKerusakan;
use App\Models\PengajuanServis;
use App\Models\RiwayatPerbaikan;
use App\Models\RiwayatServis;
use App\Models\ServiceReminderSetting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->peran !== 'ADMIN') {
            return redirect()->route('pengelola.kendaraan');
        }

        $bulanIni = now();
        $totalKendaraan = Kendaraan::query()->count();
        $kendaraanBermasalah = Kendaraan::query()
            ->whereIn('status_perawatan', ['PERLU_SERVIS', 'SEDANG_SERVIS', 'RUSAK'])
            ->count();
        $pengajuanMenungguList = PengajuanServis::query()
            ->with(['kendaraan', 'pengaju'])
            ->where('status_persetujuan', 'MENUNGGU')
            ->orderBy('dibuat_pada', 'asc')
            ->get();

        $pendingApproval = $pengajuanMenungguList->count();

        $laporanKerusakanAktif = LaporanKerusakan::query()
            ->whereIn('status_penanganan', ['DILAPORKAN', 'SEDANG_DIPERBAIKI'])
            ->count();
        $totalBiayaServisBulanIni = RiwayatServis::query()
            ->whereYear('tanggal_servis', $bulanIni->year)
            ->whereMonth('tanggal_servis', $bulanIni->month)
            ->sum('total_biaya');
        $totalBiayaPerbaikanBulanIni = RiwayatPerbaikan::query()
            ->whereYear('tanggal_perbaikan', $bulanIni->year)
            ->whereMonth('tanggal_perbaikan', $bulanIni->month)
            ->sum('total_biaya_perbaikan');
        $totalPengeluaranBulanIni = round(
            (float) $totalBiayaServisBulanIni + (float) $totalBiayaPerbaikanBulanIni,
            2,
        );
        $totalMaintenance = RiwayatServis::query()->count();
        $totalBiayaMaintenance = RiwayatServis::query()->sum('total_biaya');
        $totalBiayaPerbaikan = RiwayatPerbaikan::query()->sum('total_biaya_perbaikan');
        $totalPengeluaranLengkap = round((float) $totalBiayaMaintenance + (float) $totalBiayaPerbaikan, 2);

        $kategoriBreakdown = Kendaraan::query()
            ->select('kategori_penggunaan')
            ->selectRaw('COUNT(*) as jumlah')
            ->groupBy('kategori_penggunaan')
            ->orderBy('kategori_penggunaan')
            ->get();

        $bbmBreakdown = Kendaraan::query()
            ->select('jenis_bbm')
            ->selectRaw('COUNT(*) as jumlah')
            ->groupBy('jenis_bbm')
            ->orderBy('jenis_bbm')
            ->get();

        $tahunSekarang = (int) now()->year;
        $lifecycleData = [
            'baru' => Kendaraan::query()->whereRaw("($tahunSekarang - tahun_pembuatan) <= 1")->count(),
            'normal' => Kendaraan::query()->whereRaw("($tahunSekarang - tahun_pembuatan) BETWEEN 2 AND 3")->count(),
            'perhatian' => Kendaraan::query()->whereRaw("($tahunSekarang - tahun_pembuatan) = 4")->count(),
            'prioritas' => Kendaraan::query()->whereRaw("($tahunSekarang - tahun_pembuatan) >= 5")->count(),
        ];

        // LOGIC FOR ALERTS
        $vehicles = Kendaraan::with(['riwayatServisTerbaru'])->get();
        $setting = ServiceReminderSetting::first();
        if ($setting) {
            foreach ($vehicles as $v) {
                $v->threshold_servis_hari = $setting->reminder_days_before;
            }
        }

        $terlambatServisCount = 0;
        $mendekatiServisCount = 0;
        foreach ($vehicles as $v) {
            $status = $v->serviceReminder()['status'];
            if ($status === 'TERLAMBAT_SERVIS') {
                $terlambatServisCount++;
            } elseif ($status === 'MENDEKATI_SERVIS') {
                $mendekatiServisCount++;
            }
        }

        $alerts = [
            'terlambat' => $terlambatServisCount,
            'mendekati' => $mendekatiServisCount,
            'pengajuan' => $pendingApproval,
            'maintenance' => $laporanKerusakanAktif,
        ];

        // LOGIC FOR RECENT LOGS
        $recentLogs = collect();
        Kendaraan::with('pengelola')->latest('dibuat_pada')->take(5)->get()->each(function ($k) use ($recentLogs) {
            $recentLogs->push((object) [
                'title' => 'Admin menambahkan kendaraan',
                'description' => "{$k->merk_tipe} - {$k->plat_nomor}",
                'timestamp' => $k->dibuat_pada,
                'type' => 'kendaraan',
            ]);
        });
        PengajuanServis::with(['kendaraan', 'pengaju'])->latest('dibuat_pada')->take(5)->get()->each(function ($p) use ($recentLogs) {
            $recentLogs->push((object) [
                'title' => 'Pengajuan biaya '.strtolower($p->jenis_pengajuan),
                'description' => "{$p->kendaraan->merk_tipe} - {$p->kendaraan->plat_nomor}",
                'timestamp' => $p->dibuat_pada,
                'type' => 'pengajuan',
            ]);
        });
        RiwayatServis::with(['kendaraan', 'pembuat'])->orderByDesc('tanggal_servis')->take(5)->get()->each(function ($r) use ($recentLogs) {
            $recentLogs->push((object) [
                'title' => 'Maintenance selesai dicatat',
                'description' => "{$r->kendaraan->merk_tipe} di {$r->nama_bengkel}",
                'timestamp' => Carbon::parse($r->tanggal_servis)->startOfDay(),
                'type' => 'riwayat',
            ]);
        });
        $recentLogs = $recentLogs->sortByDesc('timestamp')->take(5)->values();

        $kpiData = compact(
            'totalKendaraan',
            'kendaraanBermasalah',
            'pendingApproval',
            'laporanKerusakanAktif',
            'totalPengeluaranBulanIni',
            'totalMaintenance',
            'totalBiayaMaintenance',
            'totalPengeluaranLengkap',
            'kategoriBreakdown',
            'bbmBreakdown',
            'lifecycleData',
            'pengajuanMenungguList',
            'alerts',
            'recentLogs'
        );

        return view('admin.dashboard', ['kpiData' => $kpiData]);
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
