<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\OdometerLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OdometerLogController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_kendaraan' => ['required', 'integer', 'exists:kendaraan,id'],
            'kilometer' => ['required', 'integer', 'min:0'],
            'tanggal_pencatatan' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);
        $user = $request->user();

        DB::transaction(function () use ($validated, $user): void {
            $kendaraan = Kendaraan::query()
                ->lockForUpdate()
                ->findOrFail($validated['id_kendaraan']);

            if ($user->peran !== 'ADMIN' && (int) $kendaraan->id_pengelola !== (int) $user->id) {
                abort(403);
            }

            if ($validated['kilometer'] < $kendaraan->kilometer_terakhir) {
                throw ValidationException::withMessages([
                    'kilometer' => 'Kilometer baru tidak boleh lebih kecil dari kilometer terakhir kendaraan.',
                ]);
            }

            OdometerLog::query()->create([
                'id_kendaraan' => $kendaraan->id,
                'kilometer' => $validated['kilometer'],
                'tanggal_pencatatan' => $validated['tanggal_pencatatan'],
                'keterangan' => $validated['keterangan'] ?? null,
                'id_penginput' => Auth::id(),
            ]);

            $kendaraan->kilometer_terakhir = $validated['kilometer'];
            $kendaraan->save();
        });

        return back()->with('status', 'odometer-log-created');
    }
}
