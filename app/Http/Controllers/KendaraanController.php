<?php

namespace App\Http\Controllers;

use App\Enums\KategoriKomponen;
use App\Enums\UnitPln;
use App\Models\Kendaraan;
use App\Models\TemplateJadwalServis;
use App\Models\User;
use App\Services\ImageStorageService;
use App\Services\KomponenServisService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class KendaraanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));

        $kendaraans = Kendaraan::query()
            ->visibleTo($user)
            ->with(['pengelola', 'fotos'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('plat_nomor', 'like', "%{$search}%")
                        ->orWhere('merk_tipe', 'like', "%{$search}%")
                        ->orWhereHas('pengelola', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('plat_nomor')
            ->get();

        $isAdmin = $user->isAdmin();

        return view('kendaraan.index', compact('kendaraans', 'isAdmin', 'search'));
    }

    public function create(Request $request)
    {
        $user = $request->user();

        $pengelolas = $user->isAdmin()
            ? User::query()
                ->where('peran', 'PENGELOLA')
                ->whereIn('unit_pln', array_column(UnitPln::cases(), 'value'))
                ->orderBy('name')
                ->get()
            : User::whereKey($user->id)->get();

        $unitPln = UnitPln::cases();

        return view('kendaraan.create', compact('pengelolas', 'unitPln'));
    }

    public function store(Request $request, KomponenServisService $komponenService)
    {
        $user = $request->user();

        $validated = $request->validate([
            'plat_nomor' => 'required|string|max:15|unique:kendaraan,plat_nomor',
            'merk_tipe' => 'required|string|max:100',
            'tahun_pembuatan' => 'required|integer',
            'transmisi' => 'required|in:MANUAL,OTOMATIS',
            'jenis_bbm' => 'required|in:BENSIN,SOLAR,DIESEL,LISTRIK',
            'kategori_penggunaan' => 'required|in:PEJABAT,TEKNISI,ANGKUT_BARANG,MOTOR_OPERASIONAL',
            'unit_pln' => ['required', Rule::in(array_column(UnitPln::cases(), 'value'))],
            'kilometer_terakhir' => 'required|integer',
            'tanggal_pembelian' => 'required|date',
            ...$this->documentExpiryDateRules($request),
            'id_pengelola' => [
                'required',
                Rule::exists('users', 'id')
                    ->where('peran', 'PENGELOLA')
                    ->where('unit_pln', $request->input('unit_pln')),
            ],
        ]);

        $this->validateManagerUnit($validated);
        $validated['status_perawatan'] = 'BAIK';

        // Pengelola hanya dapat membuat kendaraan atas namanya sendiri.
        if (! $user->isAdmin()) {
            $validated['id_pengelola'] = $user->id;
        }

        try {
            DB::transaction(function () use ($validated, $komponenService): void {
                $kendaraan = Kendaraan::create($validated);
                $komponenService->applyRecommendedTemplateToKendaraan($kendaraan);
            });
        } catch (Throwable $exception) {
            throw $exception;
        }

        return redirect()->route('kendaraan.index')->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function show(Request $request, string $id, KomponenServisService $komponenService)
    {
        $user = $request->user();
        $kendaraan = Kendaraan::findOrFail($id);

        $this->authorizeKendaraan($user, $kendaraan);

        DB::transaction(function () use ($kendaraan, $komponenService): void {
            $kendaraan = Kendaraan::query()
                ->lockForUpdate()
                ->findOrFail($kendaraan->id);

            $komponenService->applyRecommendedTemplateToKendaraan($kendaraan);
        });

        $kendaraan->load([
            'fotos',
            'pengelola',
            'template',
            'komponen' => fn ($query) => $query->orderBy('nomor_urut'),
            'mileageTerbaru',
        ]);

        $rekomendasiTemplate = $kendaraan->templateRekomendasi();
        $allTemplates = TemplateJadwalServis::where('is_aktif', true)->orderBy('nama')->get();
        $kategoriList = KategoriKomponen::cases();

        $isAdmin = $user->isAdmin();

        return view('kendaraan.show', compact('kendaraan', 'rekomendasiTemplate', 'allTemplates', 'kategoriList', 'isAdmin'));
    }

    public function edit(Request $request, string $id)
    {
        $user = $request->user();
        $kendaraan = Kendaraan::findOrFail($id);

        $this->authorizeKendaraan($user, $kendaraan);

        $pengelolas = $user->isAdmin()
            ? User::where('peran', 'PENGELOLA')->orderBy('name')->get()
            : User::whereKey($user->id)->get();

        $unitPln = UnitPln::cases();
        $kendaraan->load('fotos');

        return view('kendaraan.edit', compact('kendaraan', 'pengelolas', 'unitPln'));
    }

    public function update(Request $request, string $id, KomponenServisService $komponenService)
    {
        $user = $request->user();
        $kendaraan = Kendaraan::findOrFail($id);

        $this->authorizeKendaraan($user, $kendaraan);

        $validated = $request->validate([
            'plat_nomor' => 'required|string|max:15|unique:kendaraan,plat_nomor,'.$kendaraan->id,
            'merk_tipe' => 'required|string|max:100',
            'tahun_pembuatan' => 'required|integer',
            'transmisi' => 'required|in:MANUAL,OTOMATIS',
            'jenis_bbm' => 'required|in:BENSIN,SOLAR,DIESEL,LISTRIK',
            'kategori_penggunaan' => 'required|in:PEJABAT,TEKNISI,ANGKUT_BARANG,MOTOR_OPERASIONAL',
            'unit_pln' => ['required', Rule::in(array_column(UnitPln::cases(), 'value'))],
            'tanggal_pembelian' => 'required|date',
            ...$this->documentExpiryDateRules($request),
            'id_pengelola' => [
                'required',
                Rule::exists('users', 'id')
                    ->where('peran', 'PENGELOLA'),
            ],
        ]);

        // Pengelola tidak dapat memindahkan kendaraan ke pengelola lain.
        if (! $user->isAdmin()) {
            $validated['id_pengelola'] = $kendaraan->id_pengelola;
        }

        $specificationsChanged = $kendaraan->jenis_bbm !== $validated['jenis_bbm']
            || $kendaraan->transmisi !== $validated['transmisi'];

        try {
            DB::transaction(function () use ($kendaraan, $validated, $specificationsChanged, $komponenService): void {
                $manager = User::query()
                    ->where('peran', 'PENGELOLA')
                    ->lockForUpdate()
                    ->findOrFail($validated['id_pengelola']);

                $this->assignManagerUnitIfUnassigned($manager, $validated['unit_pln']);
                $kendaraan->update($validated);

                if ($specificationsChanged) {
                    $komponenService->applyRecommendedTemplateToKendaraan($kendaraan, true);
                }
            });
        } catch (Throwable $exception) {
            throw $exception;
        }

        return redirect()->route('kendaraan.index')->with('success', 'Kendaraan berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        // Penghapusan kendaraan hanya untuk Admin.
        abort_unless($request->user()->isAdmin(), 403);

        $kendaraan = Kendaraan::findOrFail($id);

        // Kumpulkan path foto sebelum delete agar bisa dihapus dari storage.
        $fotoPaths = $kendaraan->fotos()->pluck('path')->all();

        $kendaraan->delete();

        foreach ($fotoPaths as $path) {
            app(ImageStorageService::class)->deleteSafely($path);
        }

        return redirect()->route('kendaraan.index')->with('success', 'Kendaraan berhasil dihapus.');
    }

    public function destroySelected(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'kendaraan_ids' => ['required', 'array', 'min:1'],
            'kendaraan_ids.*' => ['required', 'integer', 'distinct', 'exists:kendaraan,id'],
        ]);
        $vehicleIds = array_map('intval', $validated['kendaraan_ids']);
        $deletedCount = 0;
        $photosToDelete = [];

        DB::transaction(function () use ($vehicleIds, &$deletedCount, &$photosToDelete): void {
            Kendaraan::query()
                ->whereKey($vehicleIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->chunkById(100, function (Collection $kendaraans) use (&$deletedCount, &$photosToDelete): void {
                    foreach ($kendaraans as $kendaraan) {
                        $fotoPaths = $kendaraan->fotos()->pluck('path')->all();
                        array_push($photosToDelete, ...$fotoPaths);
                        $kendaraan->delete();
                        $deletedCount++;
                    }
                });
        });

        foreach ($photosToDelete as $photo) {
            app(ImageStorageService::class)->deleteSafely($photo);
        }

        return redirect()->route('kendaraan.index')->with(
            'success',
            $deletedCount > 0
                ? "{$deletedCount} kendaraan terpilih berhasil dihapus."
                : 'Kendaraan yang dipilih tidak ditemukan.',
        );
    }

    /**
     * Validasi masa berlaku STNK dan KIR tidak boleh melebihi lima tahun sejak tanggal pembelian.
     */
    private function documentExpiryDateRules(Request $request): array
    {
        $purchaseDate = $request->input('tanggal_pembelian');
        $maximumExpiryDate = $purchaseDate
            ? Carbon::parse($purchaseDate)->addYears(5)->toDateString()
            : null;

        return [
            'tanggal_stnk_berlaku_sampai' => [
                'nullable',
                'date',
                'after_or_equal:tanggal_pembelian',
                ...($maximumExpiryDate ? ['before_or_equal:'.$maximumExpiryDate] : []),
            ],
            'tanggal_kir_berlaku_sampai' => [
                'nullable',
                'date',
                'after_or_equal:tanggal_pembelian',
                ...($maximumExpiryDate ? ['before_or_equal:'.$maximumExpiryDate] : []),
            ],
        ];
    }

    /**
     * Pengelola hanya boleh mengakses kendaraan yang menjadi tanggung jawabnya.
     */
    private function authorizeKendaraan(User $user, Kendaraan $kendaraan): void
    {
        abort_unless(
            $user->isAdmin()
                || ($user->unit_pln !== null && $kendaraan->unit_pln === $user->unit_pln),
            403,
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validateManagerUnit(array $validated): void
    {
        $manager = User::query()->findOrFail($validated['id_pengelola']);

        if ($manager->unit_pln?->value !== $validated['unit_pln']) {
            throw ValidationException::withMessages([
                'id_pengelola' => 'Pengelola harus berasal dari Unit PLN kendaraan yang dipilih.',
            ]);
        }
    }

    private function assignManagerUnitIfUnassigned(User $manager, string $unitPln): void
    {
        if ($manager->unit_pln !== null && $manager->unit_pln->value !== $unitPln) {
            throw ValidationException::withMessages([
                'id_pengelola' => 'Pengelola harus berasal dari Unit PLN kendaraan yang dipilih.',
            ]);
        }

        if ($manager->unit_pln === null) {
            $manager->unit_pln = $unitPln;
            $manager->save();
        }
    }
}
