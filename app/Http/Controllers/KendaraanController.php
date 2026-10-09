<?php

namespace App\Http\Controllers;

use App\Enums\KategoriKomponen;
use App\Models\Kendaraan;
use App\Models\TemplateJadwalServis;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KendaraanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $kendaraans = Kendaraan::with('pengelola')
            ->when(! $user->isAdmin(), fn ($query) => $query->where('id_pengelola', $user->id))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('plat_nomor', 'like', "%{$search}%")
                        ->orWhere('merk_tipe', 'like', "%{$search}%")
                        ->orWhereHas('pengelola', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(in_array($status, ['BAIK', 'PERLU_SERVIS', 'SEDANG_SERVIS', 'RUSAK'], true), function ($query) use ($status) {
                $query->where('status_perawatan', $status);
            })
            ->orderBy('plat_nomor')
            ->get();

        return view('kendaraan.index', compact('kendaraans', 'search', 'status'));
    }

    public function create(Request $request)
    {
        $user = $request->user();

        $pengelolas = $user->isAdmin()
            ? User::orderBy('name')->get()
            : User::whereKey($user->id)->get();

        return view('kendaraan.create', compact('pengelolas'));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'plat_nomor' => 'required|string|max:15|unique:kendaraan,plat_nomor',
            'merk_tipe' => 'required|string|max:100',
            'tahun_pembuatan' => 'required|integer',
            'transmisi' => 'required|in:MANUAL,OTOMATIS',
            'jenis_bbm' => 'required|in:BENSIN,SOLAR,DIESEL,LISTRIK',
            'kategori_penggunaan' => 'required|in:PEJABAT,TEKNISI,ANGKUT_BARANG,MOTOR_OPERASIONAL',
            'kilometer_terakhir' => 'required|integer',
            'tanggal_pembelian' => 'required|date',
            'status_perawatan' => 'required|in:BAIK,PERLU_SERVIS,SEDANG_SERVIS,RUSAK',
            'id_pengelola' => 'required|exists:users,id',
            'foto_kendaraan' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Pengelola hanya dapat membuat kendaraan atas namanya sendiri.
        if (! $user->isAdmin()) {
            $validated['id_pengelola'] = $user->id;
        }

        if ($request->hasFile('foto_kendaraan')) {
            $validated['foto_kendaraan'] = $request->file('foto_kendaraan')->store('kendaraan', 'public');
        }

        Kendaraan::create($validated);

        return redirect()->route('kendaraan.index')->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $kendaraan = Kendaraan::with([
            'pengelola',
            'template',
            'komponen' => fn ($q) => $q->orderBy('nomor_urut'),
            'mileageTerbaru',
        ])->findOrFail($id);

        $this->authorizeKendaraan($user, $kendaraan);

        $rekomendasiTemplate = $kendaraan->templateRekomendasi();
        $allTemplates = TemplateJadwalServis::where('is_aktif', true)->orderBy('nama')->get();
        $kategoriList = KategoriKomponen::cases();

        return view('kendaraan.show', compact('kendaraan', 'rekomendasiTemplate', 'allTemplates', 'kategoriList'));
    }

    public function edit(Request $request, string $id)
    {
        $user = $request->user();
        $kendaraan = Kendaraan::findOrFail($id);

        $this->authorizeKendaraan($user, $kendaraan);

        $pengelolas = $user->isAdmin()
            ? User::orderBy('name')->get()
            : User::whereKey($user->id)->get();

        return view('kendaraan.edit', compact('kendaraan', 'pengelolas'));
    }

    public function update(Request $request, string $id)
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
            'kilometer_terakhir' => 'required|integer',
            'tanggal_pembelian' => 'required|date',
            'status_perawatan' => 'required|in:BAIK,PERLU_SERVIS,SEDANG_SERVIS,RUSAK',
            'id_pengelola' => 'required|exists:users,id',
            'foto_kendaraan' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('foto_kendaraan')) {
            // Hapus foto lama jika ada
            if ($kendaraan->foto_kendaraan) {
                Storage::disk('public')->delete($kendaraan->foto_kendaraan);
            }
            $validated['foto_kendaraan'] = $request->file('foto_kendaraan')->store('kendaraan', 'public');
        }

        // Pengelola tidak dapat memindahkan kendaraan ke pengelola lain.
        if (! $user->isAdmin()) {
            $validated['id_pengelola'] = $kendaraan->id_pengelola;
        }

        $kendaraan->update($validated);

        return redirect()->route('kendaraan.index')->with('success', 'Kendaraan berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        // Penghapusan kendaraan hanya untuk Admin.
        abort_unless($request->user()->isAdmin(), 403);

        $kendaraan = Kendaraan::findOrFail($id);

        // Hapus foto jika ada
        if ($kendaraan->foto_kendaraan) {
            Storage::disk('public')->delete($kendaraan->foto_kendaraan);
        }

        $kendaraan->delete();

        return redirect()->route('kendaraan.index')->with('success', 'Kendaraan berhasil dihapus.');
    }

    /**
     * Pengelola hanya boleh mengakses kendaraan yang menjadi tanggung jawabnya.
     */
    private function authorizeKendaraan(User $user, Kendaraan $kendaraan): void
    {
        abort_unless(
            $user->isAdmin() || (int) $kendaraan->id_pengelola === (int) $user->id,
            403,
        );
    }
}
