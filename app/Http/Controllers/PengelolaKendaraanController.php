<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengelolaKendaraanController extends Controller
{
    /**
     * Halaman daftar kendaraan berdasarkan pengelola.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $pengelolas = collect();
        if ($isAdmin) {
            $pengelolas = User::query()
                ->where('peran', 'PENGELOLA')
                ->orWhereHas('kendaraan')
                ->orderBy('name')
                ->get();

            if ($pengelolas->isEmpty()) {
                $pengelolas = User::query()->orderBy('name')->get();
            }

            $selectedPengelolaId = (int) $request->query('pengelola', $pengelolas->first()?->id ?? $user->id);
            $currentPengelola = $pengelolas->firstWhere('id', $selectedPengelolaId) ?? $pengelolas->first() ?? $user;
        } else {
            $currentPengelola = $user;
        }

        $kendaraans = Kendaraan::query()
            ->where('id_pengelola', $currentPengelola->id)
            ->with(['pengelola', 'riwayatServisTerbaru'])
            ->orderBy('plat_nomor')
            ->get();

        $selectedId = (int) $request->query('kendaraan', 0);
        $selected = $kendaraans->firstWhere('id', $selectedId) ?? $kendaraans->first();

        $riwayatServis = collect();
        $riwayatPerjalanan = collect();
        $serviceReminder = null;

        if ($selected) {
            $riwayatServis = $selected->riwayatServis()
                ->orderByDesc('tanggal_servis')
                ->limit(5)
                ->get();

            $riwayatPerjalanan = $selected->mileages()
                ->with('pencatat')
                ->orderByDesc('tanggal_perjalanan')
                ->orderByDesc('id')
                ->limit(5)
                ->get();

            $serviceReminder = $selected->serviceReminder();
        }

        return view('pengelola.index', compact(
            'isAdmin',
            'pengelolas',
            'currentPengelola',
            'kendaraans',
            'selected',
            'riwayatServis',
            'riwayatPerjalanan',
            'serviceReminder'
        ));
    }
}
