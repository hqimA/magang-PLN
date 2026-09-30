<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExpenseReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'bulan' => ['sometimes', 'integer', 'between:1,12'],
            'tahun' => ['sometimes', 'integer', 'between:1000,9999'],
        ]);

        $bulan = (int) ($filters['bulan'] ?? now()->month);
        $tahun = (int) ($filters['tahun'] ?? now()->year);
        $tanggalMulai = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
        $tanggalSelesai = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();

        $biayaServis = DB::table('riwayat_servis')
            ->select('id_kendaraan')
            ->selectRaw('SUM(total_biaya) AS total_biaya_servis')
            ->whereBetween('tanggal_servis', [$tanggalMulai, $tanggalSelesai])
            ->groupBy('id_kendaraan');

        $biayaPerbaikan = DB::table('riwayat_perbaikan')
            ->join(
                'laporan_kerusakan',
                'riwayat_perbaikan.id_laporan_kerusakan',
                '=',
                'laporan_kerusakan.id'
            )
            ->select('laporan_kerusakan.id_kendaraan')
            ->selectRaw('SUM(riwayat_perbaikan.total_biaya_perbaikan) AS total_biaya_perbaikan')
            ->whereBetween('riwayat_perbaikan.tanggal_perbaikan', [$tanggalMulai, $tanggalSelesai])
            ->groupBy('laporan_kerusakan.id_kendaraan');

        $rekap = DB::table('kendaraan')
            ->join('users', 'kendaraan.id_pengelola', '=', 'users.id')
            ->leftJoinSub($biayaServis, 'biaya_servis', 'biaya_servis.id_kendaraan', '=', 'kendaraan.id')
            ->leftJoinSub(
                $biayaPerbaikan,
                'biaya_perbaikan',
                'biaya_perbaikan.id_kendaraan',
                '=',
                'kendaraan.id'
            )
            ->select([
                'kendaraan.plat_nomor',
                'kendaraan.merk_tipe',
                'users.name AS pengelola',
            ])
            ->selectRaw('COALESCE(biaya_servis.total_biaya_servis, 0) AS total_biaya_servis')
            ->selectRaw('COALESCE(biaya_perbaikan.total_biaya_perbaikan, 0) AS total_biaya_perbaikan')
            ->selectRaw(
                'COALESCE(biaya_servis.total_biaya_servis, 0) '
                    .' + COALESCE(biaya_perbaikan.total_biaya_perbaikan, 0) AS grand_total_pengeluaran'
            )
            ->orderBy('kendaraan.plat_nomor')
            ->get()
            ->map(fn (object $kendaraan): array => [
                'plat_nomor' => $kendaraan->plat_nomor,
                'merk_tipe' => $kendaraan->merk_tipe,
                'pengelola' => $kendaraan->pengelola,
                'total_biaya_servis' => (float) $kendaraan->total_biaya_servis,
                'total_biaya_perbaikan' => (float) $kendaraan->total_biaya_perbaikan,
                'grand_total_pengeluaran' => (float) $kendaraan->grand_total_pengeluaran,
            ])
            ->all();

        return view('admin.reports.expense', compact('rekap', 'bulan', 'tahun'));
    }
}
