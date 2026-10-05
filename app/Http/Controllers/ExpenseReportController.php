<?php

namespace App\Http\Controllers;

use App\Exports\ExpenseReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseReportController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.reports.expense', $this->getReportData($request));
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $report = $this->getReportData($request);
        $rows = collect($report['rekap'])
            ->map(fn (array $row): array => [
                $row['plat_nomor'],
                $row['merk_tipe'],
                $row['pengelola'],
                $row['total_biaya_servis'],
                $row['total_biaya_perbaikan'],
                $row['grand_total_pengeluaran'],
            ])
            ->all();

        return Excel::download(
            new ExpenseReportExport($rows),
            sprintf('laporan-pengeluaran-%04d-%02d.xlsx', $report['tahun'], $report['bulan'])
        );
    }

    public function exportPdf(Request $request): Response
    {
        $report = $this->getReportData($request);

        return Pdf::loadView('pdf.expense_report', $report)
            ->download(sprintf('laporan-pengeluaran-%04d-%02d.pdf', $report['tahun'], $report['bulan']));
    }

    /**
     * @return array{rekap: array<int, array{plat_nomor: string, merk_tipe: string, pengelola: string, total_biaya_servis: float, total_biaya_perbaikan: float, grand_total_pengeluaran: float}>, bulan: int, tahun: int}
     */
    private function getReportData(Request $request): array
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

        return compact('rekap', 'bulan', 'tahun');
    }
}
