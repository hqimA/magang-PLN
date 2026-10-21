<?php

namespace Database\Factories;

use App\Models\Kendaraan;
use App\Models\KendaraanFoto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KendaraanFoto>
 */
class KendaraanFotoFactory extends Factory
{
    protected $model = KendaraanFoto::class;

    public function definition(): array
    {
        return [
            'id_kendaraan' => Kendaraan::factory(),
            'path' => 'kendaraan/'.Str::uuid().'.webp',
            'is_utama' => false,
            'urutan' => 0,
        ];
    }

    public function utama(): static
    {
        return $this->state(['is_utama' => true]);
    }
}
