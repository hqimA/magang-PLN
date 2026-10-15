<?php

namespace App\Services;

use App\Models\Kendaraan;
use App\Models\User;

class VehicleCategoryAssignmentService
{
    /**
     * @return array<string, int>
     */
    public function managerIdsByCategory(): array
    {
        return User::query()
            ->where('peran', 'PENGELOLA')
            ->whereNotNull('kategori_kendaraan')
            ->pluck('id', 'kategori_kendaraan')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    public function assignUnassignedVehiclesToManager(User $manager): int
    {
        if (! $manager->isPengelola() || $manager->kategori_kendaraan === null) {
            return 0;
        }

        return Kendaraan::query()
            ->whereNull('id_pengelola')
            ->where('kategori_penggunaan', $manager->kategori_kendaraan->value)
            ->update(['id_pengelola' => $manager->id]);
    }
}
