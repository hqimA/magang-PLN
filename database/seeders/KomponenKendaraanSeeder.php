<?php

namespace Database\Seeders;

use App\Models\Kendaraan;
use App\Services\KomponenServisService;
use Illuminate\Database\Seeder;

class KomponenKendaraanSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(KomponenServisService::class);
        $kendaraans = Kendaraan::whereNotNull('id_template')->with('template')->get();

        foreach ($kendaraans as $kendaraan) {
            if ($kendaraan->template) {
                $service->applyTemplateToKendaraan($kendaraan, $kendaraan->template, true);
            }
        }
    }
}
