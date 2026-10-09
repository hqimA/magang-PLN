<?php

namespace Database\Seeders;

use App\Models\Kendaraan;
use App\Models\StatusKomponenKendaraan;
use App\Models\TemplateKomponen;
use Illuminate\Database\Seeder;

class StatusKomponenKendaraanSeeder extends Seeder
{
    public function run(): void
    {
        $kendaraans = Kendaraan::whereNotNull('id_template')->get();

        foreach ($kendaraans as $kendaraan) {
            $komponens = TemplateKomponen::where('id_template', $kendaraan->id_template)
                ->where('is_aktif', true)
                ->get();

            foreach ($komponens as $komponen) {
                StatusKomponenKendaraan::firstOrCreate(
                    [
                        'id_kendaraan' => $kendaraan->id,
                        'id_template_komponen' => $komponen->id,
                    ],
                    [
                        'km_terakhir_servis' => null,
                        'tgl_terakhir_servis' => null,
                        'aksi_terakhir' => null,
                        'km_jatuh_tempo' => null,
                        'tgl_jatuh_tempo' => null,
                        'status' => 'BELUM_DATA',
                    ]
                );
            }
        }
    }
}
