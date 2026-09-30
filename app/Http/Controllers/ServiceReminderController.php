<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
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
}
