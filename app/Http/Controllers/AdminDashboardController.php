<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\LaporanKerusakan;
use App\Models\PengajuanServis;
use App\Models\RiwayatPerbaikan;
use App\Models\RiwayatServis;
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
        $pendingApproval = PengajuanServis::query()
            ->where('status_persetujuan', 'MENUNGGU')
            ->count();
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
        $kategoriBreakdown = Kendaraan::query()
            ->select('kategori_penggunaan')
            ->selectRaw('COUNT(*) as jumlah')
            ->groupBy('kategori_penggunaan')
            ->orderBy('kategori_penggunaan')
            ->get();

        $kpiData = compact(
            'totalKendaraan',
            'kendaraanBermasalah',
            'pendingApproval',
            'laporanKerusakanAktif',
            'totalPengeluaranBulanIni',
            'kategoriBreakdown',
        );

        return view('admin.dashboard', ['kpiData' => $kpiData]);
    }
}
