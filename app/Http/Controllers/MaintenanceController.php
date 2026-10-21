<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\KomponenKendaraan;
use App\Models\PengajuanKomponen;
use App\Models\PengajuanServis;
use App\Services\ImageStorageService;
use App\Services\KomponenServisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

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
                // Pengelola: tampilkan semua pengajuan aktif/memerlukan tindak lanjut, bukan yang sudah dibatalkan.
                $pengajuanMaintenance = PengajuanServis::with(['kendaraan.mileageTerbaru', 'pengaju'])
                    ->where('id_pengaju', $user->id)
                    ->where('status_persetujuan', '!=', 'DIBATALKAN')
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

        $mileageMap = $kendaraanList->mapWithKeys(
            fn (Kendaraan $kendaraan): array => [$kendaraan->id => $kendaraan->kilometer_terakhir],
        )->all();

        return view('maintenance.create', compact('kendaraanList', 'mileageMap'));
    }

    /** AJAX: return kilometer_terakhir kendaraan tertentu. */
    public function mileageKendaraan(Request $request, Kendaraan $kendaraan): JsonResponse
    {
        abort_if($request->user()->isAdmin(), 403);
        abort_unless((int) $kendaraan->id_pengelola === (int) $request->user()->id, 403);

        return response()->json([
            'kilometer_akhir' => $kendaraan->kilometer_terakhir,
        ]);
    }

    /** AJAX: return rekomendasi dan daftar komponen servis kendaraan. */
    public function komponenKendaraan(Request $request, Kendaraan $kendaraan, KomponenServisService $komponenService): JsonResponse
    {
        abort_if($request->user()->isAdmin(), 403);
        abort_unless((int) $kendaraan->id_pengelola === (int) $request->user()->id, 403);

        $validated = $request->validate([
            'jenis_pengajuan' => ['nullable', 'in:RUTIN,DARURAT'],
        ]);

        $currentKm = (int) ($kendaraan->kilometer_terakhir ?? 0);

        $data = $komponenService->getKomponenKendaraan(
            $kendaraan,
            $currentKm,
            $validated['jenis_pengajuan'] ?? 'RUTIN',
        );

        return response()->json($data);
    }

    public function store(Request $request, KomponenServisService $komponenService): RedirectResponse
    {
        abort_if($request->user()->isAdmin(), 403, 'Admin tidak dapat membuat pengajuan maintenance.');

        $validated = $request->validate([
            'id_kendaraan' => ['required', 'exists:kendaraan,id'],
            'jenis_pengajuan' => ['required', 'in:RUTIN,DARURAT'],
            'deskripsi_keluhan' => [
                Rule::requiredIf($request->input('jenis_pengajuan') === 'RUTIN'),
                'nullable',
                'string',
                'max:2000',
            ],
            'estimasi_biaya' => [
                Rule::requiredIf($request->input('jenis_pengajuan') === 'DARURAT'),
                'nullable',
                'numeric',
                'min:0',
            ],
            'foto_bukti_odometer' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [], [
            'foto_bukti_odometer' => $request->input('jenis_pengajuan') === 'RUTIN'
                ? 'foto bukti odometer'
                : 'foto bukti kerusakan',
        ]);

        // Pastikan kendaraan milik Pengelola yang login
        $kendaraan = Kendaraan::where('id', $validated['id_kendaraan'])
            ->where('id_pengelola', $request->user()->id)
            ->firstOrFail();

        $currentKm = (int) ($kendaraan->kilometer_terakhir ?? 0);
        $usesRoutineTemplate = $validated['jenis_pengajuan'] === 'RUTIN'
            && $kendaraan->templateRekomendasi() !== null;

        if ($usesRoutineTemplate) {
            $recommendations = $komponenService->getKomponenKendaraan($kendaraan, $currentKm);
            $dueTemplateComponentIds = collect($recommendations['komponen'])
                ->filter(fn (array $item): bool => $item['is_rekomendasi'])
                ->pluck('id_template_komponen')
                ->all();

            $validatedComponents = $request->validate([
                'komponen' => ['nullable', 'array'],
                'komponen.*.id_template_komponen' => [
                    'required',
                    'integer',
                    'distinct',
                    Rule::in($dueTemplateComponentIds),
                ],
                'komponen.*.catatan' => ['nullable', 'string', 'max:500'],
            ], [
                'komponen.*.id_template_komponen.in' => 'Komponen yang dipilih tidak termasuk jadwal servis rutin kendaraan pada kilometer saat ini.',
            ]);
        } elseif ($validated['jenis_pengajuan'] === 'DARURAT') {
            $validatedComponents = ['komponen' => []];
        } else {
            $validatedComponents = $request->validate([
                'komponen' => ['nullable', 'array'],
                'komponen.*.id_komponen_kendaraan' => [
                    'required',
                    'integer',
                    'distinct',
                    Rule::in(KomponenKendaraan::query()
                        ->where('id_kendaraan', $kendaraan->id)
                        ->where('is_aktif', true)
                        ->pluck('id')
                        ->all()),
                ],
                'komponen.*.jenis_aksi' => ['required', 'in:P,G'],
                'komponen.*.catatan' => ['nullable', 'string', 'max:500'],
            ]);
        }

        $fotoPath = null;
        $fotoFolder = $validated['jenis_pengajuan'] === 'RUTIN'
            ? 'bukti_odometer'
            : 'bukti_kerusakan';

        $fotoPath = app(ImageStorageService::class)->store($request->file('foto_bukti_odometer'), $fotoFolder);

        try {
            DB::transaction(function () use (
                $kendaraan,
                $validated,
                $validatedComponents,
                $usesRoutineTemplate,
                $komponenService,
                $request,
                $fotoPath,
            ): void {
                $kendaraan = Kendaraan::query()
                    ->lockForUpdate()
                    ->findOrFail($kendaraan->id);
                $currentKm = (int) ($kendaraan->kilometer_terakhir ?? 0);

                $pengajuan = PengajuanServis::create([
                    'id_kendaraan' => $kendaraan->id,
                    'id_pengaju' => $request->user()->id,
                    'jenis_pengajuan' => $validated['jenis_pengajuan'],
                    'deskripsi_keluhan' => $validated['deskripsi_keluhan']
                        ?? 'Pengajuan darurat tanpa keterangan tambahan.',
                    'estimasi_biaya' => $validated['estimasi_biaya'] ?? null,
                    'kilometer_pengajuan' => $currentKm,
                    'foto_bukti_odometer' => $fotoPath,
                    'status_persetujuan' => 'MENUNGGU',
                ]);

                if ($usesRoutineTemplate) {
                    $komponenService->applyRecommendedTemplateToKendaraan($kendaraan);

                    $dueComponents = collect($komponenService->getKomponenKendaraan($kendaraan, $currentKm)['komponen'])
                        ->filter(fn (array $item): bool => $item['is_rekomendasi'])
                        ->values();

                    foreach ($dueComponents as $index => $recommendation) {
                        $vehicleComponent = $komponenService->createKomponenKendaraanFromTemplate(
                            $kendaraan,
                            (int) $recommendation['id_template_komponen'],
                            $currentKm,
                        );

                        PengajuanKomponen::create([
                            'id_pengajuan' => $pengajuan->id,
                            'id_komponen_kendaraan' => $vehicleComponent->id,
                            'jenis_aksi' => $recommendation['aksi_rekomendasi'],
                            'catatan' => $validatedComponents['komponen'][$index]['catatan'] ?? null,
                        ]);
                    }
                } else {
                    foreach ($validatedComponents['komponen'] ?? [] as $item) {
                        $vehicleComponent = KomponenKendaraan::query()
                            ->where('id_kendaraan', $kendaraan->id)
                            ->findOrFail($item['id_komponen_kendaraan']);

                        PengajuanKomponen::create([
                            'id_pengajuan' => $pengajuan->id,
                            'id_komponen_kendaraan' => $vehicleComponent->id,
                            'jenis_aksi' => $item['jenis_aksi'],
                            'catatan' => $item['catatan'] ?? null,
                        ]);
                    }
                }
            });
        } catch (Throwable $exception) {
            app(ImageStorageService::class)->deleteSafely($fotoPath);

            throw $exception;
        }

        $message = $validated['jenis_pengajuan'] === 'DARURAT'
            ? 'Pengajuan darurat beserta foto kerusakan dan estimasi biaya berhasil diajukan.'
            : 'Pengajuan servis rutin beserta komponen dan foto odometer berhasil diajukan.';

        return redirect()->route('maintenance.index', ['tab' => 'pengajuan'])
            ->with('success', $message.' Pengajuan sedang menunggu persetujuan Admin.');
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

    public function cancel(Request $request, PengajuanServis $pengajuan): RedirectResponse
    {
        abort_if($request->user()->isAdmin(), 403);

        $wasCancelled = DB::transaction(function () use ($request, $pengajuan): bool {
            $ownedRequest = PengajuanServis::query()
                ->where('id', $pengajuan->id)
                ->where('id_pengaju', $request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($ownedRequest->status_persetujuan, ['MENUNGGU', 'DITOLAK'], true)) {
                return false;
            }

            $ownedRequest->update(['status_persetujuan' => 'DIBATALKAN']);

            return true;
        });

        if (! $wasCancelled) {
            return back()->with('error', 'Pengajuan hanya dapat dibatalkan jika statusnya Menunggu atau Ditolak.');
        }

        return back()->with('success', 'Pengajuan maintenance berhasil dibatalkan.');
    }
}
