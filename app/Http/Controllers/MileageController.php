<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\Mileage;
use App\Models\User;
use App\Services\ImageStorageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class MileageController extends Controller
{
    /**
     * Halaman Mileage Tracker: daftar kendaraan, detail kendaraan terpilih, dan form pencatatan.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $kendaraans = $this->kendaraanTerpakai($user)->orderBy('plat_nomor')->get();

        $selectedId = (int) $request->query('kendaraan', 0);
        $selected = $kendaraans->firstWhere('id', $selectedId) ?? $kendaraans->first();

        return view('mileage.index', compact('kendaraans', 'selected'));
    }

    /**
     * Trip history untuk seluruh kendaraan (opsional filter kendaraan & tanggal).
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $kendaraanId = (int) $request->query('kendaraan', 0);
        $dari = (string) $request->query('dari', '');
        $sampai = (string) $request->query('sampai', '');

        $riwayat = $this->riwayatTerpakai($user)
            ->when($kendaraanId > 0, fn ($query) => $query->where('id_kendaraan', $kendaraanId))
            ->when($dari !== '', fn ($query) => $query->whereDate('tanggal_perjalanan', '>=', $dari))
            ->when($sampai !== '', fn ($query) => $query->whereDate('tanggal_perjalanan', '<=', $sampai))
            ->orderByDesc('tanggal_perjalanan')
            ->orderByDesc('id')
            ->get();

        $kendaraans = $this->kendaraanTerpakai($user)->orderBy('plat_nomor')->get();

        return view('mileage.history', compact('riwayat', 'kendaraans', 'kendaraanId', 'dari', 'sampai'));
    }

    /**
     * Form input odometer manual.
     */
    public function odometer(Request $request)
    {
        $user = $request->user();
        $kendaraans = $this->kendaraanTerpakai($user)->orderBy('plat_nomor')->get();

        $selectedId = (int) $request->query('kendaraan', 0);
        $selected = $kendaraans->firstWhere('id', $selectedId) ?? $kendaraans->first();

        $riwayatTerbaru = $selected
            ? $selected->mileages()->orderByDesc('tanggal_perjalanan')->orderByDesc('id')->limit(10)->get()
            : collect();

        return view('mileage.odometer', compact('kendaraans', 'selected', 'riwayatTerbaru'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_kendaraan' => ['required', 'exists:kendaraan,id'],
            'odometer_baru' => ['required', 'integer', 'min:0'],
            'tanggal_perjalanan' => ['required', 'date'],
            'status_perjalanan' => ['sometimes', 'in:SELESAI,BERJALAN,TERBATAS'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'foto_bukti' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [], [
            'id_kendaraan' => 'kendaraan',
            'odometer_baru' => 'odometer',
            'foto_bukti' => 'foto bukti odometer',
        ]);

        $kendaraan = Kendaraan::findOrFail($validated['id_kendaraan']);
        $this->authorizeKendaraan($request->user(), $kendaraan);

        $this->simpanMileage($request, $kendaraan, (int) $validated['odometer_baru'], [
            'tanggal_perjalanan' => $validated['tanggal_perjalanan'],
            'status_perjalanan' => $validated['status_perjalanan'] ?? 'SELESAI',
            'keterangan' => $validated['keterangan'] ?? null,
            'id_pencatat' => $request->user()?->id,
        ], 'odometer_baru');

        return redirect()
            ->route('mileage.odometer', ['kendaraan' => $kendaraan->id])
            ->with('success', 'Odometer dan foto bukti berhasil disimpan.');
    }

    public function storeTrip(Request $request)
    {
        $validated = $request->validate([
            'id_kendaraan' => ['required', 'exists:kendaraan,id'],
            'tanggal_perjalanan' => ['required', 'date'],
            'kilometer_akhir' => ['required', 'integer', 'min:0'],
            'status_perjalanan' => ['sometimes', 'in:SELESAI,BERJALAN,TERBATAS'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'foto_bukti' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [], [
            'kilometer_akhir' => 'kilometer akhir',
            'foto_bukti' => 'foto bukti odometer',
        ]);

        $kendaraan = Kendaraan::findOrFail($validated['id_kendaraan']);
        $this->authorizeKendaraan($request->user(), $kendaraan);

        $this->simpanMileage($request, $kendaraan, (int) $validated['kilometer_akhir'], [
            'tanggal_perjalanan' => $validated['tanggal_perjalanan'],
            'status_perjalanan' => $validated['status_perjalanan'] ?? 'SELESAI',
            'keterangan' => $validated['keterangan'] ?? null,
            'id_pencatat' => $request->user()?->id,
        ]);

        return redirect()
            ->route('mileage.index', ['kendaraan' => $kendaraan->id])
            ->with('success', 'Perjalanan dan foto bukti berhasil dicatat.');
    }

    /**
     * Admin melihat seluruh kendaraan, Pengelola hanya kendaraannya sendiri.
     */
    private function kendaraanTerpakai(User $user): Builder
    {
        return Kendaraan::query()
            ->with('fotos')
            ->when(! $user->isAdmin(), fn ($query) => $query->where('id_pengelola', $user->id));
    }

    private function riwayatTerpakai(User $user): Builder
    {
        return Mileage::with(['kendaraan', 'pencatat'])
            ->when(
                ! $user->isAdmin(),
                fn ($query) => $query->whereHas(
                    'kendaraan',
                    fn ($k) => $k->where('id_pengelola', $user->id),
                ),
            );
    }

    /**
     * Save a mileage record using the vehicle's current kilometer as the start value.
     *
     * @param  array{tanggal_perjalanan: string, status_perjalanan: string, keterangan: ?string, id_pencatat: ?int}  $attributes
     */
    private function simpanMileage(
        Request $request,
        Kendaraan $kendaraan,
        int $kilometerAkhir,
        array $attributes,
        string $kilometerAkhirField = 'kilometer_akhir',
    ): Mileage {
        $fotoPath = app(ImageStorageService::class)->store($request->file('foto_bukti'), 'mileage');

        try {
            return DB::transaction(function () use (
                $request,
                $kendaraan,
                $kilometerAkhir,
                $attributes,
                $kilometerAkhirField,
                $fotoPath,
            ): Mileage {
                $kendaraan = Kendaraan::query()
                    ->lockForUpdate()
                    ->findOrFail($kendaraan->id);
                $this->authorizeKendaraan($request->user(), $kendaraan);

                $kilometerAwal = (int) ($kendaraan->kilometer_terakhir ?? 0);
                if ($kilometerAkhir < $kilometerAwal) {
                    throw ValidationException::withMessages([
                        $kilometerAkhirField => "Kilometer akhir tidak boleh lebih kecil dari kilometer awal ({$kilometerAwal} km).",
                    ]);
                }

                $mileage = Mileage::query()->create([
                    'id_kendaraan' => $kendaraan->id,
                    'kilometer_awal' => $kilometerAwal,
                    'kilometer_akhir' => $kilometerAkhir,
                    ...$attributes,
                    'foto_bukti' => $fotoPath,
                ]);

                $kendaraan->update(['kilometer_terakhir' => $kilometerAkhir]);

                return $mileage;
            });
        } catch (Throwable $exception) {
            app(ImageStorageService::class)->deleteSafely($fotoPath);

            throw $exception;
        }
    }

    private function authorizeKendaraan(User $user, Kendaraan $kendaraan): void
    {
        abort_unless(
            $user->isAdmin() || (int) $kendaraan->id_pengelola === (int) $user->id,
            403,
        );
    }
}
