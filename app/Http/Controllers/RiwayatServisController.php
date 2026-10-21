<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\RiwayatServis;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
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
                fn ($query) => $query->whereHas('kendaraan', fn ($vehicleQuery) => $vehicleQuery->visibleTo($user))
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
            ->visibleTo($user)
            ->orderBy('plat_nomor')
            ->get(['id', 'plat_nomor', 'merk_tipe']);

        return view('riwayat-servis.index', compact('riwayatServis', 'kendaraanList'));
    }

    public function show(RiwayatServis $riwayatServis): View
    {
        $user = auth()->user();

        if (! Kendaraan::query()->visibleTo($user)->whereKey($riwayatServis->id_kendaraan)->exists()) {
            abort(403);
        }

        $riwayatServis->load(['kendaraan.pengelola', 'rincianSparepart', 'pengajuan', 'pembuat']);

        return view('riwayat-servis.show', compact('riwayatServis'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_pengajuan' => ['nullable', 'integer', 'exists:pengajuan_servis,id'],
            'id_kendaraan' => ['required', 'integer', 'exists:kendaraan,id'],
            'tanggal_servis' => ['required', 'date'],
            'nama_bengkel' => ['required', 'string', 'max:150'],
            'total_biaya' => ['required', 'numeric', 'min:0'],
            'foto_nota' => [
                Rule::requiredIf(filled($request->input('id_pengajuan'))),
                'file',
                'mimes:jpeg,png,jpg,webp,pdf',
                'max:10240',
            ],
            'sparepart' => ['nullable', 'array'],
            'sparepart.*.nama_sparepart' => ['required', 'string', 'max:150'],
            'sparepart.*.jumlah' => ['required', 'integer', 'min:1'],
            'sparepart.*.harga_satuan' => ['required', 'numeric', 'min:0'],
        ], [
            'foto_nota.required' => 'Foto nota wajib diunggah untuk menyelesaikan maintenance.',
        ]);

        $path = null;

        try {
            DB::transaction(function () use ($request, $validated, &$path): void {
                $kendaraan = Kendaraan::query()
                    ->lockForUpdate()
                    ->findOrFail($validated['id_kendaraan']);
                $user = $request->user();

                if (! Kendaraan::query()->visibleTo($user)->whereKey($kendaraan->id)->exists()) {
                    abort(403);
                }

                $kilometerServis = $kendaraan->kilometer_terakhir;

                if ($request->hasFile('foto_nota')) {
                    $path = app(CloudinaryService::class)->upload($request->file('foto_nota'), 'nota_servis');
                }

                $riwayatServis = RiwayatServis::query()->create([
                    'id_pengajuan' => $validated['id_pengajuan'] ?? null,
                    'id_kendaraan' => $kendaraan->id,
                    'tanggal_servis' => $validated['tanggal_servis'],
                    'kilometer_servis' => $kilometerServis,
                    'nama_bengkel' => $validated['nama_bengkel'],
                    'total_biaya' => $validated['total_biaya'],
                    'foto_nota' => $path,
                    'target_kilometer_berikutnya' => $kilometerServis + $kendaraan->interval_servis_km,
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

                $kendaraan->status_perawatan = 'BAIK';
                $kendaraan->save();
            });
        } catch (Throwable $exception) {
            if (is_string($path)) {
                app(CloudinaryService::class)->deleteSafely($path);
            }

            throw $exception;
        }

        $response = filled($validated['id_pengajuan'] ?? null)
            ? redirect()->route('maintenance.index', ['tab' => 'usai'])
            : back();

        return $response
            ->with('status', 'riwayat-servis-created')
            ->with('success', 'Maintenance berhasil diselesaikan dan dicatat ke Riwayat Servis.');
    }
}
