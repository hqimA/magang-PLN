<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            // 1. Users dulu karena semua tabel lain FK ke users
            UserSeeder::class,

            // 2. Template sebelum kendaraan (kendaraan FK ke template)
            TemplateJadwalServisSeeder::class,
            TemplateKomponenSeeder::class,

            // 3. Kendaraan (sekarang sudah assign id_template otomatis)
            KendaraanSeeder::class,

            // 4. Status komponen per kendaraan (butuh kendaraan + template komponen)
            StatusKomponenKendaraanSeeder::class,

            // 5. Data operasional (butuh kendaraan + users)
            LaporanKerusakanSeeder::class,
            PengajuanServisSeeder::class,
            RiwayatServisSeeder::class,
            RiwayatPerbaikanSeeder::class,
            NotifikasiSeeder::class,
            OdometerLogSeeder::class,
        ]);
    }
}
