<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\ServiceReminderSetting;
use App\Services\MileagePredictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceReminderController extends Controller
{
    public function index(Request $request, MileagePredictionService $mileagePrediction): JsonResponse
    {
        $user = $request->user();
        $vehicles = Kendaraan::query()
            ->with(['riwayatServisTerbaru', 'pengelola'])
            ->when($user->peran !== 'ADMIN', fn ($query) => $query->whereBelongsTo($user, 'pengelola'))
            ->orderBy('plat_nomor')
            ->get()
            ->map(function (Kendaraan $vehicle) use ($mileagePrediction): array {
                return [
                    'id' => $vehicle->id,
                    'plat_nomor' => $vehicle->plat_nomor,
                    'merk_tipe' => $vehicle->merk_tipe,
                    'pengelola' => $vehicle->pengelola?->name,
                    'reminder' => $vehicle->serviceReminder(),
                    'prediksi' => $mileagePrediction->predictNextService($vehicle),
                ];
            })
            ->filter(fn (array $vehicle): bool => $vehicle['reminder']['status'] !== 'TERJADWAL')
            ->values();

        return response()->json(['data' => $vehicles]);
    }

    public function viewIndex(Request $request)
    {
        $user = $request->user();
        $setting = ServiceReminderSetting::first();
        $isActive = $setting ? $setting->is_active : true;

        $vehicles = collect();

        if ($isActive) {
            $vehicles = Kendaraan::query()
                ->with(['riwayatServisTerbaru', 'pengelola'])
                ->when($user->peran !== 'ADMIN', fn ($query) => $query->whereBelongsTo($user, 'pengelola'))
                ->orderBy('plat_nomor')
                ->get()
                ->map(function (Kendaraan $vehicle) use ($setting) {
                    if ($setting) {
                        $vehicle->threshold_servis_hari = $setting->reminder_days_before;
                    }

                    return [
                        'id' => $vehicle->id,
                        'plat_nomor' => $vehicle->plat_nomor,
                        'merk_tipe' => $vehicle->merk_tipe,
                        'foto' => $vehicle->foto_kendaraan,
                        'kilometer_terakhir' => $vehicle->kilometer_terakhir,
                        'pengelola' => $vehicle->pengelola?->name,
                        'reminder' => $vehicle->serviceReminder(),
                    ];
                })
                ->filter(fn (array $vehicle): bool => $vehicle['reminder']['status'] !== 'TERJADWAL')
                ->values();
        }

        return view('service-reminder.index', [
            'vehicles' => $vehicles,
            'setting' => $setting ?? new ServiceReminderSetting(['is_active' => true, 'reminder_days_before' => 7]),
        ]);
    }

    public function updateSettings(Request $request)
    {
        // Pengaturan global service reminder hanya dapat diubah Admin.
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'is_active' => 'boolean',
            'reminder_days_before' => 'required|integer|min:1|max:365',
        ]);

        $setting = ServiceReminderSetting::first() ?? new ServiceReminderSetting;
        $setting->is_active = $request->has('is_active');
        $setting->reminder_days_before = $validated['reminder_days_before'];
        $setting->save();

        return back()->with('success', 'Pengaturan notifikasi berhasil disimpan.');
    }
}
