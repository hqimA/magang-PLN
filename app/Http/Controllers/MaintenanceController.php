<?php

namespace App\Http\Controllers;

use App\Enums\UnitPln;
use App\Exports\MaintenanceExport;
use App\Models\Kendaraan;
use App\Models\KomponenKendaraan;
use App\Models\Notifikasi;
use App\Models\PengajuanKomponen;
use App\Models\PengajuanServis;
use App\Models\RiwayatServis;
use App\Models\User;
use App\Services\ImageStorageService;
use App\Services\KomponenServisService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class MaintenanceController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'pengajuan');

        if ($tab === 'aktif') {
            $tab = 'proses';
        }

        $tabs = ['pengajuan', 'proses', 'usai', 'ditolak'];

        if (! in_array($tab, $tabs, true)) {
            $tab = 'pengajuan';
        }

        $user = $request->user();

        $pengajuanMaintenance = collect();
        $maintenanceAktif = collect();
        $maintenanceUsai = collect();
        $maintenanceDitolak = collect();
        $incomingNotifications = collect();
        $newRequestNotificationCount = 0;

        if ($user->isAdmin()) {
            $notificationQuery = Notifikasi::query()
                ->where('id_pengguna', $user->id)
                ->where('tipe_referensi', 'like', 'pengajuan_servis:%')
                ->where('sudah_dibaca', false);

            $newRequestNotificationCount = (clone $notificationQuery)->count();

            if ($tab === 'pengajuan') {
                $incomingNotifications = $notificationQuery
                    ->orderByDesc('dibuat_pada')
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get()
                    ->map(function (Notifikasi $notification): Notifikasi {
                        $referenceId = substr((string) $notification->tipe_referensi, strlen('pengajuan_servis:'));
                        $notification->setAttribute(
                            'pengajuan_id',
                            ctype_digit($referenceId) ? (int) $referenceId : null,
                        );

                        return $notification;
                    });
            }
        }

        if ($tab === 'pengajuan') {
            $pengajuanMaintenance = PengajuanServis::with(['kendaraan.mileageTerbaru', 'pengaju'])
                ->where('status_persetujuan', 'MENUNGGU')
                ->whereHas('kendaraan', fn ($query) => $query->visibleTo($user))
                ->orderBy('dibuat_pada', 'desc')
                ->get();
        } elseif ($tab === 'proses') {
            $maintenanceAktif = PengajuanServis::with(['kendaraan.mileageTerbaru', 'pengaju', 'disetujuiOleh'])
                ->where('status_persetujuan', 'DISETUJUI')
                ->doesntHave('riwayatServis')
                ->whereHas('kendaraan', fn ($query) => $query->visibleTo($user))
                ->orderBy('dibuat_pada', 'desc')
                ->get();
        } elseif ($tab === 'usai') {
            $maintenanceUsai = RiwayatServis::query()
                ->with(['kendaraan', 'pengajuan'])
                ->whereHas('kendaraan', fn ($query) => $query->visibleTo($user))
                ->orderByDesc('tanggal_servis')
                ->paginate(15)
                ->withQueryString();
        } elseif ($tab === 'ditolak') {
            $maintenanceDitolak = PengajuanServis::with(['kendaraan', 'pengaju', 'disetujuiOleh'])
                ->where('status_persetujuan', 'DITOLAK')
                ->whereHas('kendaraan', fn ($query) => $query->visibleTo($user))
                ->orderBy('dibuat_pada', 'desc')
                ->get();
        }

        $unitPln = UnitPln::cases();

        return view('maintenance.index', compact(
            'tab',
            'pengajuanMaintenance',
            'maintenanceAktif',
            'maintenanceUsai',
            'maintenanceDitolak',
            'incomingNotifications',
            'newRequestNotificationCount',
            'unitPln',
        ));
    }

    public function exportCompleted(Request $request): BinaryFileResponse
    {
        $unit = $this->exportUnit($request);
        $user = $request->user();

        $query = RiwayatServis::query()
            ->join('kendaraan', 'kendaraan.id', '=', 'riwayat_servis.id_kendaraan')
            ->leftJoin('pengajuan_servis', 'pengajuan_servis.id', '=', 'riwayat_servis.id_pengajuan')
            ->whereHas('kendaraan', fn (Builder $vehicles) => $vehicles->visibleTo($user))
            ->when($unit !== null, fn (Builder $rows) => $rows->where('kendaraan.unit_pln', $unit))
            ->select([
                'riwayat_servis.id',
                'riwayat_servis.tanggal_servis as tanggal',
                'kendaraan.unit_pln',
                'kendaraan.plat_nomor',
                'kendaraan.merk_tipe',
                'pengajuan_servis.jenis_pengajuan',
                'riwayat_servis.nama_bengkel',
                'riwayat_servis.kilometer_servis',
                'riwayat_servis.total_biaya',
            ])
            ->orderBy('riwayat_servis.id');

        return Excel::download(
            new MaintenanceExport($query, 'usai'),
            'maintenance-usai-'.$this->exportUnitFileName($unit).'.xlsx',
        );
    }

    public function exportRejected(Request $request): BinaryFileResponse
    {
        $unit = $this->exportUnit($request);
        $user = $request->user();

        $query = PengajuanServis::query()
            ->join('kendaraan', 'kendaraan.id', '=', 'pengajuan_servis.id_kendaraan')
            ->leftJoin('users as pemohon', 'pemohon.id', '=', 'pengajuan_servis.id_pengaju')
            ->where('pengajuan_servis.status_persetujuan', 'DITOLAK')
            ->whereHas('kendaraan', fn (Builder $vehicles) => $vehicles->visibleTo($user))
            ->when($unit !== null, fn (Builder $rows) => $rows->where('kendaraan.unit_pln', $unit))
            ->select([
                'pengajuan_servis.id',
                'pengajuan_servis.dibuat_pada as tanggal',
                'kendaraan.unit_pln',
                'kendaraan.plat_nomor',
                'kendaraan.merk_tipe',
                'pengajuan_servis.jenis_pengajuan',
                'pemohon.name as pengaju',
                'pengajuan_servis.alasan_penolakan',
                'pengajuan_servis.estimasi_biaya',
                'pengajuan_servis.kilometer_pengajuan',
            ])
            ->orderBy('pengajuan_servis.id');

        return Excel::download(
            new MaintenanceExport($query, 'ditolak'),
            'maintenance-ditolak-'.$this->exportUnitFileName($unit).'.xlsx',
        );
    }

    public function markNotificationRead(Request $request, Notifikasi $notifikasi): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless(
            $notifikasi->id_pengguna === $request->user()->id
                && str_starts_with((string) $notifikasi->tipe_referensi, 'pengajuan_servis:'),
            404,
        );

        $notifikasi->update(['sudah_dibaca' => true]);

        return redirect()->route('maintenance.index', ['tab' => 'pengajuan']);
    }

    private function exportUnit(Request $request): ?string
    {
        $units = array_column(UnitPln::cases(), 'value');
        $selectedUnit = $request->validate([
            'unit_pln' => ['nullable', Rule::in([...$units, 'SEMUA'])],
        ])['unit_pln'] ?? null;
        $user = $request->user();

        if (! $user->isAdmin()) {
            abort_if($user->unit_pln === null, 403);
            abort_if($selectedUnit !== null && $selectedUnit !== $user->unit_pln->value, 403);

            return $user->unit_pln->value;
        }

        return $selectedUnit === null || $selectedUnit === 'SEMUA'
            ? null
            : $selectedUnit;
    }

    private function exportUnitFileName(?string $unit): string
    {
        return $unit ?? 'seluruh-unit';
    }

    public function create(Request $request): View
    {
        abort_if($request->user()->isAdmin(), 403, 'Admin tidak dapat membuat pengajuan maintenance.');

        $kendaraanList = Kendaraan::query()
            ->visibleTo($request->user())
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
        abort_unless(
            Kendaraan::query()->visibleTo($request->user())->whereKey($kendaraan->id)->exists(),
            403,
        );

        return response()->json([
            'kilometer_akhir' => $kendaraan->kilometer_terakhir,
        ]);
    }

    /** AJAX: return rekomendasi dan daftar komponen servis kendaraan. */
    public function komponenKendaraan(Request $request, Kendaraan $kendaraan, KomponenServisService $komponenService): JsonResponse
    {
        abort_if($request->user()->isAdmin(), 403);
        abort_unless(
            Kendaraan::query()->visibleTo($request->user())->whereKey($kendaraan->id)->exists(),
            403,
        );

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
        $kendaraan = Kendaraan::query()
            ->visibleTo($request->user())
            ->whereKey($validated['id_kendaraan'])
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

                $unitPln = $kendaraan->unit_pln?->value ?? 'Belum ditentukan';
                $notificationMessage = sprintf(
                    'Pengajuan maintenance #%d untuk kendaraan %s dari unit %s menunggu persetujuan.',
                    $pengajuan->id,
                    $kendaraan->plat_nomor,
                    $unitPln,
                );

                User::query()
                    ->where('peran', 'ADMIN')
                    ->pluck('id')
                    ->each(fn (int $adminId) => Notifikasi::query()->create([
                        'id_pengguna' => $adminId,
                        'judul' => 'Pengajuan Maintenance Baru',
                        'pesan' => $notificationMessage,
                        'sudah_dibaca' => false,
                        'tipe_referensi' => 'pengajuan_servis:'.$pengajuan->id,
                        'dibuat_pada' => now(),
                    ]));

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

        if (! $user->isAdmin() && ! Kendaraan::query()
            ->visibleTo($user)
            ->whereKey($pengajuan->id_kendaraan)
            ->exists()) {
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

        return back()->with('success', 'Pengajuan berhasil disetujui dan masuk ke Proses Maintenance.');
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
