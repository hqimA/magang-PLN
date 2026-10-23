<?php

namespace App\Http\Controllers;

use App\Enums\UnitPln;
use App\Exports\ExpenseReportArchiveExport;
use App\Exports\ExpenseReportExport;
use App\Models\LaporanKerusakan;
use App\Models\RiwayatServis;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
                $row['unit_pln'],
                $row['merk_tipe'],
                $row['pengelola'],
                $row['total_biaya_servis'],
                $row['total_biaya_perbaikan'],
                $row['grand_total_pengeluaran'],
            ])
            ->all();

        return Excel::download(
            new ExpenseReportExport($rows),
            sprintf(
                'laporan-pengeluaran-%04d-%02d-%s.xlsx',
                $report['tahun'],
                $report['bulan'],
                $this->reportUnitFileName($report['unitPlnSelected']),
            )
        );
    }

    public function exportPdf(Request $request): Response
    {
        $report = $this->getReportData($request);

        return Pdf::loadView('pdf.expense_report', $report)
            ->download(sprintf(
                'laporan-pengeluaran-%04d-%02d-%s.pdf',
                $report['tahun'],
                $report['bulan'],
                $this->reportUnitFileName($report['unitPlnSelected']),
            ));
    }

    public function exportArchive(Request $request): BinaryFileResponse
    {
        $archiveData = $this->getReportArchiveData();
        $response = Excel::download(
            new ExpenseReportArchiveExport($archiveData),
            'arsip-seluruh-data-laporan.xlsx',
        );

        DB::table('expense_report_export_receipts')->updateOrInsert(
            ['user_id' => $request->user()->id],
            [
                'snapshot_hash' => $this->archiveSnapshotHash($archiveData),
                'exported_at' => now(),
            ],
        );

        return $response;
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $receipt = DB::table('expense_report_export_receipts')
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if ($receipt === null) {
                throw ValidationException::withMessages([
                    'export' => 'Unduh arsip lengkap seluruh data laporan sebelum menghapus data.',
                ]);
            }

            $archiveData = $this->getReportArchiveData();

            if (! hash_equals($receipt->snapshot_hash, $this->archiveSnapshotHash($archiveData))) {
                throw ValidationException::withMessages([
                    'export' => 'Data laporan berubah sejak arsip terakhir dibuat. Unduh arsip terbaru sebelum menghapus data.',
                ]);
            }

            if (! $this->archiveHasRows($archiveData)) {
                throw ValidationException::withMessages([
                    'export' => 'Tidak ada data riwayat laporan yang dapat dihapus.',
                ]);
            }

            RiwayatServis::query()->orderBy('id')->chunkById(100, function ($riwayatServis): void {
                $riwayatServis->each->delete();
            });
            DB::table('riwayat_perbaikan')->delete();
            LaporanKerusakan::query()->orderBy('id')->chunkById(100, function ($laporanKerusakan): void {
                $laporanKerusakan->each->delete();
            });
            DB::table('expense_report_export_receipts')
                ->where('user_id', $request->user()->id)
                ->delete();
        });

        return redirect()->route('laporan-pengeluaran.index')
            ->with('success', 'Semua data riwayat servis, perbaikan, dan laporan kerusakan berhasil dihapus.');
    }

    /**
     * @return array<string, array{headings: array<int, string>, rows: array<int, array<int, mixed>>}>
     */
    private function getReportArchiveData(): array
    {
        $columnsByTable = [
            'laporan_kerusakan' => [
                'id',
                'id_kendaraan',
                'id_pelapor',
                'tanggal_kejadian',
                'lokasi_kejadian',
                'deskripsi_kerusakan',
                'foto_bukti',
                'tingkat_kerusakan',
                'status_penanganan',
            ],
            'riwayat_servis' => [
                'id',
                'id_pengajuan',
                'id_kendaraan',
                'tanggal_servis',
                'kilometer_servis',
                'nama_bengkel',
                'total_biaya',
                'foto_nota',
                'target_kilometer_berikutnya',
                'id_pembuat',
            ],
            'riwayat_perbaikan' => [
                'id',
                'id_laporan_kerusakan',
                'tanggal_perbaikan',
                'nama_bengkel',
                'ringkasan_perbaikan',
                'total_biaya_perbaikan',
                'id_pembuat',
            ],
            'rincian_sparepart' => [
                'id',
                'id_riwayat_servis',
                'nama_sparepart',
                'jumlah',
                'harga_satuan',
                'subtotal',
            ],
            'detail_komponen_servis' => [
                'id',
                'id_riwayat_servis',
                'id_komponen_kendaraan',
                'jenis_aksi_dilakukan',
                'catatan',
                'dibuat_pada',
            ],
        ];
        $sheetTitles = [
            'laporan_kerusakan' => 'Laporan Kerusakan',
            'riwayat_servis' => 'Riwayat Servis',
            'riwayat_perbaikan' => 'Riwayat Perbaikan',
            'rincian_sparepart' => 'Rincian Sparepart',
            'detail_komponen_servis' => 'Detail Komponen Servis',
        ];
        $archiveData = [];

        foreach ($columnsByTable as $table => $columns) {
            $archiveData[$sheetTitles[$table]] = [
                'headings' => $columns,
                'rows' => DB::table($table)
                    ->select($columns)
                    ->orderBy('id')
                    ->get()
                    ->map(fn (object $row): array => array_values((array) $row))
                    ->all(),
            ];
        }

        return $archiveData;
    }

    /**
     * @param  array<string, array{headings: array<int, string>, rows: array<int, array<int, mixed>>}>  $archiveData
     */
    private function archiveSnapshotHash(array $archiveData): string
    {
        return hash('sha256', json_encode($archiveData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param  array<string, array{headings: array<int, string>, rows: array<int, array<int, mixed>>}>  $archiveData
     */
    private function archiveHasRows(array $archiveData): bool
    {
        foreach ($archiveData as $sheet) {
            if ($sheet['rows'] !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{rekap: array<int, array{plat_nomor: string, unit_pln: string, merk_tipe: string, pengelola: string, total_biaya_servis: float, total_biaya_perbaikan: float, grand_total_pengeluaran: float}>, bulan: int, tahun: int, unitPlnSelected: string, unitPln: array<int, UnitPln>}
     */
    private function getReportData(Request $request): array
    {
        $user = $request->user();
        $filters = $request->validate([
            'bulan' => ['sometimes', 'integer', 'between:1,12'],
            'tahun' => ['sometimes', 'integer', 'between:1000,9999'],
            'unit_pln' => ['sometimes', 'string', Rule::in(['SEMUA', ...array_column(UnitPln::cases(), 'value')])],
        ]);

        $bulan = (int) ($filters['bulan'] ?? now()->month);
        $tahun = (int) ($filters['tahun'] ?? now()->year);
        $unitPlnSelected = $filters['unit_pln'] ?? 'SEMUA';
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
            ->leftJoin('users', 'kendaraan.id_pengelola', '=', 'users.id')
            ->when(
                ! $user->isAdmin(),
                fn ($query) => $query->where('kendaraan.id_pengelola', $user->id),
            )
            ->when(
                $unitPlnSelected !== 'SEMUA',
                fn ($query) => $query->where('kendaraan.unit_pln', $unitPlnSelected),
            )
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
                'kendaraan.unit_pln',
                'kendaraan.merk_tipe',
                DB::raw("COALESCE(users.name, '-') AS pengelola"),
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
                'unit_pln' => $kendaraan->unit_pln ?? '-',
                'merk_tipe' => $kendaraan->merk_tipe,
                'pengelola' => $kendaraan->pengelola,
                'total_biaya_servis' => (float) $kendaraan->total_biaya_servis,
                'total_biaya_perbaikan' => (float) $kendaraan->total_biaya_perbaikan,
                'grand_total_pengeluaran' => (float) $kendaraan->grand_total_pengeluaran,
            ])
            ->all();

        $unitPln = UnitPln::cases();

        return compact('rekap', 'bulan', 'tahun', 'unitPlnSelected', 'unitPln');
    }

    private function reportUnitFileName(string $unitPln): string
    {
        return $unitPln === 'SEMUA' ? 'seluruh-unit' : $unitPln;
    }
}
