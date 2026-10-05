<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\RiwayatServis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class RiwayatServisController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = RiwayatServis::query()
            ->with(['kendaraan.pengelola', 'rincianSparepart', 'pengajuan'])
            ->when(
                $user->peran !== 'ADMIN',
                fn ($q) => $q->whereHas('kendaraan', fn ($k) => $k->where('id_pengelola', $user->id))
            );

        // Search by plat_nomor or nama_bengkel
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_bengkel', 'like', "%{$search}%")
                    ->orWhereHas('kendaraan', fn ($k) => $k->where('plat_nomor', 'like', "%{$search}%")
                        ->orWhere('merk_tipe', 'like', "%{$search}%"));
            });
        }

        // Filter by kendaraan
        if ($idKendaraan = $request->input('id_kendaraan')) {
            $query->where('id_kendaraan', $idKendaraan);
        }

        // Filter by date range
        if ($from = $request->input('dari')) {
            $query->whereDate('tanggal_servis', '>=', $from);
        }
        if ($to = $request->input('sampai')) {
            $query->whereDate('tanggal_servis', '<=', $to);
        }

        $riwayatServis = $query->orderByDesc('tanggal_servis')->paginate(15)->withQueryString();

        $kendaraanList = Kendaraan::query()
            ->when($user->peran !== 'ADMIN', fn ($q) => $q->where('id_pengelola', $user->id))
            ->orderBy('plat_nomor')
            ->get(['id', 'plat_nomor', 'merk_tipe']);

        return view('riwayat-servis.index', compact('riwayatServis', 'kendaraanList'));
    }

    public function show(RiwayatServis $riwayatServis): View
    {
        $user = auth()->user();

        // Authorization: non-admin can only see their own vehicles
        if ($user->peran !== 'ADMIN' && $riwayatServis->kendaraan->id_pengelola !== $user->id) {
            abort(403);
        }

        $riwayatServis->load(['kendaraan.pengelola', 'rincianSparepart', 'pengajuan', 'pembuat']);

        return view('riwayat-servis.show', compact('riwayatServis'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_kendaraan' => ['required', 'integer', 'exists:kendaraan,id'],
            'tanggal_servis' => ['required', 'date'],
            'kilometer_servis' => ['required', 'numeric'],
            'nama_bengkel' => ['required', 'string', 'max:150'],
            'total_biaya' => ['required', 'numeric', 'min:0'],
            'foto_nota' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'sparepart' => ['nullable', 'array'],
            'sparepart.*.nama_sparepart' => ['required', 'string', 'max:150'],
            'sparepart.*.jumlah' => ['required', 'integer', 'min:1'],
            'sparepart.*.harga_satuan' => ['required', 'numeric', 'min:0'],
        ]);

        $path = null;

        try {
            DB::transaction(function () use ($request, $validated, &$path): void {
                $kendaraan = Kendaraan::query()
                    ->lockForUpdate()
                    ->findOrFail($validated['id_kendaraan']);
                $user = $request->user();

                if ($user->peran !== 'ADMIN' && (int) $kendaraan->id_pengelola !== (int) $user->id) {
                    abort(403);
                }

                if ($request->hasFile('foto_nota')) {
                    $path = $request->file('foto_nota')->store('nota_servis', 'public');

                    if ($path === false) {
                        throw new RuntimeException('Nota servis gagal disimpan.');
                    }
                }

                $riwayatServis = RiwayatServis::query()->create([
                    'id_pengajuan' => null,
                    'id_kendaraan' => $kendaraan->id,
                    'tanggal_servis' => $validated['tanggal_servis'],
                    'kilometer_servis' => $validated['kilometer_servis'],
                    'nama_bengkel' => $validated['nama_bengkel'],
                    'total_biaya' => $validated['total_biaya'],
                    'foto_nota' => $path,
                    'target_kilometer_berikutnya' => $validated['kilometer_servis'] + $kendaraan->interval_servis_km,
                    'id_pembuat' => Auth::id(),
                ]);

                foreach ($validated['sparepart'] ?? [] as $sparepart) {
                    $riwayatServis->rincianSparepart()->create([
                        'nama_sparepart' => $sparepart['nama_sparepart'],
                        'jumlah' => $sparepart['jumlah'],
                        'harga_satuan' => $sparepart['harga_satuan'],
                        'subtotal' => number_format(
                            $sparepart['jumlah'] * $sparepart['harga_satuan'],
                            2,
                            '.',
                            ''
                        ),
                    ]);
                }

                $kendaraan->kilometer_terakhir = $validated['kilometer_servis'];
                $kendaraan->status_perawatan = 'BAIK';
                $kendaraan->save();
            });
        } catch (Throwable $exception) {
            if (is_string($path)) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        return back()->with('status', 'riwayat-servis-created');
    }
}
