<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\RiwayatServis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class RiwayatServisController extends Controller
{
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
