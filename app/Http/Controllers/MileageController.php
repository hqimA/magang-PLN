<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\Mileage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class MileageController extends Controller
{
    /**
     * Halaman Mileage Tracker: daftar kendaraan, detail kendaraan terpilih, dan trip history.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $kendaraans = $this->kendaraanTerpakai($user)->orderBy('plat_nomor')->get();

        $selectedId = (int) $request->query('kendaraan', 0);
        $selected = $kendaraans->firstWhere('id', $selectedId) ?? $kendaraans->first();

        $riwayat = collect();
        if ($selected) {
            $riwayat = $selected->mileages()
                ->with('pencatat')
                ->orderByDesc('tanggal_perjalanan')
                ->orderByDesc('id')
                ->get();
        }

        return view('mileage.index', compact('kendaraans', 'selected', 'riwayat'));
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

    /**
     * Simpan pencatatan odometer manual (juga menjadi trip baru).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_kendaraan' => 'required|exists:kendaraan,id',
            'odometer_baru' => 'required|integer|min:0',
            'tanggal_perjalanan' => 'required|date',
            'keterangan' => 'nullable|string|max:1000',
            'foto_bukti' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [], [
            'id_kendaraan' => 'kendaraan',
            'odometer_baru' => 'odometer',
            'foto_bukti' => 'foto bukti odometer',
        ]);

        $kendaraan = Kendaraan::findOrFail($validated['id_kendaraan']);

        $this->authorizeKendaraan($request->user(), $kendaraan);

        $odometerSebelumnya = $kendaraan->kilometer_terakhir ?? 0;

        if ((int) $validated['odometer_baru'] < (int) $odometerSebelumnya) {
            return back()
                ->withInput()
                ->withErrors([
                    'odometer_baru' => "Odometer baru tidak boleh lebih kecil dari odometer sebelumnya ({$odometerSebelumnya} km).",
                ]);
        }

        $this->simpanMileage($request, $kendaraan, [
            'tanggal_perjalanan' => $validated['tanggal_perjalanan'],
            'kilometer_awal' => $odometerSebelumnya,
            'kilometer_akhir' => $validated['odometer_baru'],
            'keterangan' => $validated['keterangan'] ?? null,
            'id_pencatat' => $request->user()?->id,
        ]);

        return redirect()
            ->route('mileage.odometer', ['kendaraan' => $kendaraan->id])
            ->with('success', 'Odometer dan foto bukti berhasil disimpan. Kilometer terakhir kendaraan kini '.number_format($validated['odometer_baru']).' km.');
    }

    /**
     * Simpan perjalanan/trip baru dari halaman Mileage Tracker.
     */
    public function storeTrip(Request $request)
    {
        $validated = $request->validate([
            'id_kendaraan' => 'required|exists:kendaraan,id',
            'tanggal_perjalanan' => 'required|date',
            'kilometer_awal' => 'required|integer|min:0',
            'kilometer_akhir' => 'required|integer|min:0',
            'keterangan' => 'nullable|string|max:1000',
            'foto_bukti' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [], [
            'kilometer_akhir' => 'kilometer akhir',
            'foto_bukti' => 'foto bukti odometer',
        ]);

        if ((int) $validated['kilometer_akhir'] < (int) $validated['kilometer_awal']) {
            return back()
                ->withInput()
                ->withErrors([
                    'kilometer_akhir' => 'Kilometer akhir tidak boleh lebih kecil dari kilometer awal.',
                ]);
        }

        $kendaraan = Kendaraan::findOrFail($validated['id_kendaraan']);

        $this->authorizeKendaraan($request->user(), $kendaraan);

        $this->simpanMileage($request, $kendaraan, [
            'tanggal_perjalanan' => $validated['tanggal_perjalanan'],
            'kilometer_awal' => $validated['kilometer_awal'],
            'kilometer_akhir' => $validated['kilometer_akhir'],
            'keterangan' => $validated['keterangan'] ?? null,
            'id_pencatat' => $request->user()?->id,
        ]);

        return redirect()
            ->route('mileage.index', ['kendaraan' => $kendaraan->id])
            ->with('success', 'Perjalanan dan foto bukti berhasil dicatat.');
    }

    /**
     * Simpan bukti dan catatan mileage secara atomik.
     *
     * @param array{
     *     tanggal_perjalanan: string,
     *     kilometer_awal: int|string,
     *     kilometer_akhir: int|string,
     *     keterangan: ?string,
     *     id_pencatat: ?int
     * } $attributes
     */
    private function simpanMileage(Request $request, Kendaraan $kendaraan, array $attributes): Mileage
    {
        $fotoPath = null;

        try {
            return DB::transaction(function () use ($request, $kendaraan, $attributes, &$fotoPath): Mileage {
                $fotoPath = $request->file('foto_bukti')->store('mileage', 'public');

                if (! is_string($fotoPath)) {
                    throw new RuntimeException('Foto bukti mileage gagal disimpan.');
                }

                $mileage = Mileage::create([
                    'id_kendaraan' => $kendaraan->id,
                    'status_perjalanan' => 'SELESAI',
                    ...$attributes,
                    'foto_bukti' => $fotoPath,
                ]);

                $kendaraan->update(['kilometer_terakhir' => $mileage->kilometer_akhir]);

                return $mileage;
            });
        } catch (Throwable $exception) {
            if (is_string($fotoPath)) {
                Storage::disk('public')->delete($fotoPath);
            }

            throw $exception;
        }
    }

    /**
     * Admin melihat seluruh kendaraan, Pengelola hanya kendaraannya sendiri.
     */
    private function kendaraanTerpakai(User $user): Builder
    {
        return Kendaraan::query()
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
     * Pengelola hanya boleh mencatat mileage kendaraan tanggung jawabnya.
     */
    private function authorizeKendaraan(User $user, Kendaraan $kendaraan): void
    {
        abort_unless(
            $user->isAdmin() || (int) $kendaraan->id_pengelola === (int) $user->id,
            403,
        );
    }
}
