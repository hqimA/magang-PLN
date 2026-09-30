<?php

namespace Database\Seeders;

use App\Models\Kendaraan;
use App\Models\LaporanKerusakan;
use App\Models\User;
use Illuminate\Database\Seeder;

class LaporanKerusakanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pelapor1 = User::where('email', 'ahmad.teknisi@pln.co.id')->first() ?? User::first();
        $pelapor2 = User::where('email', 'siti.aminah@pln.co.id')->first() ?? User::first();

        $mobilTriton = Kendaraan::where('plat_nomor', 'B 6789 PLN')->first();
        $mobilHilux = Kendaraan::where('plat_nomor', 'B 2345 PLN')->first();
        $mobilDmax = Kendaraan::where('plat_nomor', 'B 3456 PLN')->first();

        if (! $mobilTriton || ! $mobilHilux || ! $mobilDmax) {
            return;
        }

        $laporans = [
            [
                'id_kendaraan' => $mobilTriton->id,
                'id_pelapor' => $pelapor1->id,
                'tanggal_kejadian' => now()->subDays(3)->format('Y-m-d H:i:s'),
                'lokasi_kejadian' => 'Jl. Gatot Subroto No. 12, Jakarta Selatan',
                'deskripsi_kerusakan' => 'Pedal kopling ambles dan bau sangit kampas kopling menyengat saat bertugas penanganan gangguan listrik.',
                'foto_bukti' => null,
                'tingkat_kerusakan' => 'BERAT',
                'status_penanganan' => 'SEDANG_DIPERBAIKI',
            ],
            [
                'id_kendaraan' => $mobilHilux->id,
                'id_pelapor' => $pelapor2->id,
                'tanggal_kejadian' => now()->subDay()->format('Y-m-d H:i:s'),
                'lokasi_kejadian' => 'Area Parkir Kantor PLN Distribusi Jakarta Raya',
                'deskripsi_kerusakan' => 'Pengereman terasa bergetar keras dan timbul bunyi decit saat pengereman kecepatan rendah.',
                'foto_bukti' => null,
                'tingkat_kerusakan' => 'SEDANG',
                'status_penanganan' => 'DILAPORKAN',
            ],
            [
                'id_kendaraan' => $mobilDmax->id,
                'id_pelapor' => $pelapor1->id,
                'tanggal_kejadian' => now()->subDays(14)->format('Y-m-d H:i:s'),
                'lokasi_kejadian' => 'Gardu Induk Gambir, Jakarta Pusat',
                'deskripsi_kerusakan' => 'Lampu rotator atas derek mati total dan indikator aki menyala saat mesin menyala.',
                'foto_bukti' => null,
                'tingkat_kerusakan' => 'RINGAN',
                'status_penanganan' => 'SELESAI',
            ],
        ];

        foreach ($laporans as $data) {
            LaporanKerusakan::firstOrCreate(
                [
                    'id_kendaraan' => $data['id_kendaraan'],
                    'deskripsi_kerusakan' => $data['deskripsi_kerusakan'],
                ],
                $data
            );
        }
    }
}
