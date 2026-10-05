<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
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
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $kendaraans = Kendaraan::with('pengelola')
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

    public function create()
    {
        $pengelolas = User::all();

        return view('kendaraan.create', compact('pengelolas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'plat_nomor' => 'required|string|max:15|unique:kendaraan,plat_nomor',
            'merk_tipe' => 'required|string|max:100',
            'tahun_pembuatan' => 'required|integer',
            'transmisi' => 'required|in:MANUAL,OTOMATIS',
            'jenis_bbm' => 'required|in:BENSIN,SOLAR,DIESEL,LISTRIK',
            'kategori_penggunaan' => 'required|in:PEJABAT,TEKNISI,ANGKUT_BARANG,MOTOR_OPERASIONAL',
            'kilometer_terakhir' => 'required|integer',
            'tanggal_pembelian' => 'required|date',
            ...$this->documentExpiryDateRules(),
            'status_perawatan' => 'required|in:BAIK,PERLU_SERVIS,SEDANG_SERVIS,RUSAK',
            'id_pengelola' => 'required|exists:users,id',
            'foto_kendaraan' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('foto_kendaraan')) {
            $validated['foto_kendaraan'] = $request->file('foto_kendaraan')->store('kendaraan', 'public');
        }

        Kendaraan::create($validated);

        return redirect()->route('kendaraan.index')->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $kendaraan = Kendaraan::findOrFail($id);
        $pengelolas = User::all();

        return view('kendaraan.edit', compact('kendaraan', 'pengelolas'));
    }

    public function update(Request $request, string $id)
    {
        $kendaraan = Kendaraan::findOrFail($id);

        $validated = $request->validate([
            'plat_nomor' => 'required|string|max:15|unique:kendaraan,plat_nomor,'.$kendaraan->id,
            'merk_tipe' => 'required|string|max:100',
            'tahun_pembuatan' => 'required|integer',
            'transmisi' => 'required|in:MANUAL,OTOMATIS',
            'jenis_bbm' => 'required|in:BENSIN,SOLAR,DIESEL,LISTRIK',
            'kategori_penggunaan' => 'required|in:PEJABAT,TEKNISI,ANGKUT_BARANG,MOTOR_OPERASIONAL',
            'kilometer_terakhir' => 'required|integer',
            'tanggal_pembelian' => 'required|date',
            ...$this->documentExpiryDateRules(),
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

        $kendaraan->update($validated);

        return redirect()->route('kendaraan.index')->with('success', 'Kendaraan berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $kendaraan = Kendaraan::findOrFail($id);

        // Hapus foto jika ada
        if ($kendaraan->foto_kendaraan) {
            Storage::disk('public')->delete($kendaraan->foto_kendaraan);
        }

        $kendaraan->delete();

        return redirect()->route('kendaraan.index')->with('success', 'Kendaraan berhasil dihapus.');
    }

    private function documentExpiryDateRules(): array
    {
        $maximumDate = today()->addYears(5)->toDateString();

        return [
            'tanggal_stnk_berlaku_sampai' => ['nullable', 'date', 'before_or_equal:'.$maximumDate],
            'tanggal_kir_berlaku_sampai' => ['nullable', 'date', 'before_or_equal:'.$maximumDate],
        ];
    }
}
