<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\Mileage;
use App\Models\PengajuanKomponen;
use App\Models\PengajuanServis;
use App\Services\KomponenServisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'pengajuan');
        $user = $request->user();

        $pengajuanMaintenance = collect();
        $maintenanceAktif = collect();

        if ($tab === 'pengajuan') {
            if ($user->isAdmin()) {
                // Admin: semua pengajuan yang masih MENUNGGU
                $pengajuanMaintenance = PengajuanServis::with(['kendaraan.mileageTerbaru', 'pengaju'])
                    ->where('status_persetujuan', 'MENUNGGU')
                    ->orderBy('dibuat_pada', 'desc')
                    ->get();
            } else {
                // Pengelola: hanya pengajuan yang dibuat oleh dirinya sendiri (semua status)
                $pengajuanMaintenance = PengajuanServis::with(['kendaraan.mileageTerbaru', 'pengaju'])
                    ->where('id_pengaju', $user->id)
                    ->orderBy('dibuat_pada', 'desc')
                    ->get();
            }
        } elseif ($tab === 'aktif') {
            $maintenanceAktif = PengajuanServis::with(['kendaraan.mileageTerbaru', 'pengaju', 'disetujuiOleh'])
                ->where('status_persetujuan', 'DISETUJUI')
                ->doesntHave('riwayatServis')
                ->when(! $user->isAdmin(), fn ($q) => $q->whereHas('kendaraan', fn ($k) => $k->where('id_pengelola', $user->id)))
                ->orderBy('dibuat_pada', 'desc')
                ->get();
        }

        return view('maintenance.index', compact('tab', 'pengajuanMaintenance', 'maintenanceAktif'));
    }

    public function create(Request $request): View
    {
        abort_if($request->user()->isAdmin(), 403, 'Admin tidak dapat membuat pengajuan maintenance.');

        $kendaraanList = Kendaraan::where('id_pengelola', $request->user()->id)
            ->with('template')
            ->orderBy('merk_tipe')
            ->get();

        // Peta kilometer_akhir terbaru per kendaraan (fallback ke kilometer_terakhir kendaraan jika belum ada catatan trip mileage)
        $mileageMap = [];
        foreach ($kendaraanList as $k) {
            $latest = Mileage::where('id_kendaraan', $k->id)
                ->orderByDesc('tanggal_perjalanan')
                ->orderByDesc('id')
                ->first();
            $mileageMap[$k->id] = $latest?->kilometer_akhir ?? $k->kilometer_terakhir;
        }

        return view('maintenance.create', compact('kendaraanList', 'mileageMap'));
    }

    /** AJAX: return kilometer_akhir terbaru untuk kendaraan tertentu. */
    public function mileageKendaraan(Request $request, Kendaraan $kendaraan): JsonResponse
    {
        abort_if($request->user()->isAdmin(), 403);
        abort_unless((int) $kendaraan->id_pengelola === (int) $request->user()->id, 403);

        $latest = Mileage::where('id_kendaraan', $kendaraan->id)
            ->orderByDesc('tanggal_perjalanan')
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'kilometer_akhir' => $latest?->kilometer_akhir ?? $kendaraan->kilometer_terakhir,
        ]);
    }

    /** AJAX: return rekomendasi dan daftar komponen servis kendaraan. */
    public function komponenKendaraan(Request $request, Kendaraan $kendaraan, KomponenServisService $komponenService): JsonResponse
    {
        abort_if($request->user()->isAdmin(), 403);
        abort_unless((int) $kendaraan->id_pengelola === (int) $request->user()->id, 403);

        $data = $komponenService->getKomponenKendaraan($kendaraan);

        return response()->json($data);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if($request->user()->isAdmin(), 403, 'Admin tidak dapat membuat pengajuan maintenance.');

        $validated = $request->validate([
            'id_kendaraan' => ['required', 'exists:kendaraan,id'],
            'jenis_pengajuan' => ['required', 'in:RUTIN,DARURAT'],
            'deskripsi_keluhan' => ['required', 'string', 'max:2000'],
            'estimasi_biaya' => ['nullable', 'numeric', 'min:0'],
            'komponen' => ['nullable', 'array'],
            'komponen.*.id_komponen_kendaraan' => ['required_with:komponen', 'exists:komponen_kendaraan,id'],
            'komponen.*.jenis_aksi' => ['required_with:komponen', 'in:P,G'],
            'komponen.*.catatan' => ['nullable', 'string', 'max:500'],
        ]);

        // Pastikan kendaraan milik Pengelola yang login
        $kendaraan = Kendaraan::where('id', $validated['id_kendaraan'])
            ->where('id_pengelola', $request->user()->id)
            ->firstOrFail();

        // Ambil snapshot kilometer dari Mileage terbaru, fallback ke kilometer_terakhir kendaraan
        $latestMileage = Mileage::where('id_kendaraan', $kendaraan->id)
            ->orderByDesc('tanggal_perjalanan')
            ->orderByDesc('id')
            ->first();

        DB::transaction(function () use ($kendaraan, $validated, $latestMileage, $request) {
            $pengajuan = PengajuanServis::create([
                'id_kendaraan' => $kendaraan->id,
                'id_pengaju' => $request->user()->id,
                'jenis_pengajuan' => $validated['jenis_pengajuan'],
                'deskripsi_keluhan' => $validated['deskripsi_keluhan'],
                'estimasi_biaya' => $validated['estimasi_biaya'] ?? null,
                'kilometer_pengajuan' => $latestMileage?->kilometer_akhir ?? $kendaraan->kilometer_terakhir,
                'status_persetujuan' => 'MENUNGGU',
            ]);

            if (! empty($validated['komponen'])) {
                foreach ($validated['komponen'] as $item) {
                    PengajuanKomponen::create([
                        'id_pengajuan' => $pengajuan->id,
                        'id_komponen_kendaraan' => $item['id_komponen_kendaraan'],
                        'jenis_aksi' => $item['jenis_aksi'],
                        'catatan' => $item['catatan'] ?? null,
                    ]);
                }
            }
        });

        return redirect()->route('maintenance.index', ['tab' => 'pengajuan'])
            ->with('success', 'Pengajuan maintenance berhasil diajukan dan sedang menunggu persetujuan Admin.');
    }

    public function show(Request $request, PengajuanServis $pengajuan): View
    {
        $user = $request->user();

        if (! $user->isAdmin() && (int) $pengajuan->id_pengaju !== (int) $user->id) {
            abort(403);
        }

        $pengajuan->load([
            'kendaraan.mileageTerbaru',
            'pengaju',
            'disetujuiOleh',
            'riwayatServis',
            'komponen.komponenKendaraan',
        ]);

        return view('maintenance.show', compact('pengajuan'));
    }

    public function approve(Request $request, PengajuanServis $pengajuan): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        if ($pengajuan->status_persetujuan !== 'MENUNGGU') {
            return back()->with('error', 'Status pengajuan sudah diproses.');
        }

        $pengajuan->update([
            'status_persetujuan' => 'DISETUJUI',
            'id_disetujui_oleh' => $request->user()->id,
        ]);

        return back()->with('success', 'Pengajuan berhasil disetujui dan masuk ke Maintenance Aktif.');
    }

    public function reject(Request $request, PengajuanServis $pengajuan): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

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

        return back()->with('success', 'Pengajuan telah ditolak.');
    }
}
