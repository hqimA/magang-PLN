<?php

namespace Database\Factories;

use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kendaraan>
 */
class KendaraanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plat_nomor' => strtoupper(fake()->unique()->bothify('B #### ???')),
            'merk_tipe' => fake()->randomElement(['Toyota Avanza', 'Mitsubishi Xpander', 'Honda CR-V']),
            'tahun_pembuatan' => fake()->numberBetween(2018, 2025),
            'transmisi' => fake()->randomElement(['MANUAL', 'OTOMATIS']),
            'jenis_bbm' => fake()->randomElement(['BENSIN', 'SOLAR', 'DIESEL', 'LISTRIK']),
            'kategori_penggunaan' => fake()->randomElement(['PEJABAT', 'TEKNISI', 'ANGKUT_BARANG', 'MOTOR_OPERASIONAL']),
            'kilometer_terakhir' => fake()->numberBetween(0, 200000),
            'tanggal_pembelian' => fake()->date(),
            'status_perawatan' => fake()->randomElement(['BAIK', 'PERLU_SERVIS', 'SEDANG_SERVIS', 'RUSAK']),
            'id_pengelola' => User::factory(),
        ];
    }
}
