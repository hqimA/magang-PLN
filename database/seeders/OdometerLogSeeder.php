<?php

namespace Database\Seeders;

use App\Models\Kendaraan;
use App\Models\OdometerLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class OdometerLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $penginput1 = User::where('email', 'pengelola@pln.co.id')->first() ?? User::first();
        $penginput2 = User::where('email', 'ahmad.teknisi@pln.co.id')->first() ?? User::first();

        $innova = Kendaraan::where('plat_nomor', 'B 1234 PLN')->first();
        $hilux = Kendaraan::where('plat_nomor', 'B 2345 PLN')->first();
        $ioniq = Kendaraan::where('plat_nomor', 'B 4567 PLN')->first();

        if (! $innova || ! $hilux || ! $ioniq) {
            return;
        }

        $logs = [
            // Innova logs
            [
                'id_kendaraan' => $innova->id,
                'kilometer' => 15000,
                'tanggal_pencatatan' => now()->subDays(30)->toDateString(),
                'keterangan' => 'Pencatatan rutin awal bulan',
                'id_penginput' => $penginput1->id,
            ],
            [
                'id_kendaraan' => $innova->id,
                'kilometer' => 16800,
                'tanggal_pencatatan' => now()->subDays(15)->toDateString(),
                'keterangan' => 'Perjalanan dinas pimpinan ke Bandung',
                'id_penginput' => $penginput1->id,
            ],
            [
                'id_kendaraan' => $innova->id,
                'kilometer' => 18500,
                'tanggal_pencatatan' => now()->subDays(2)->toDateString(),
                'keterangan' => 'Pencatatan kilometer saat ini',
                'id_penginput' => $penginput1->id,
            ],

            // Hilux logs
            [
                'id_kendaraan' => $hilux->id,
                'kilometer' => 60000,
                'tanggal_pencatatan' => now()->subDays(25)->toDateString(),
                'keterangan' => 'Inspeksi gardu transmisi Banten',
                'id_penginput' => $penginput2->id,
            ],
            [
                'id_kendaraan' => $hilux->id,
                'kilometer' => 62100,
                'tanggal_pencatatan' => now()->subDays(12)->toDateString(),
                'keterangan' => 'Patroli jaringan kabel udara',
                'id_penginput' => $penginput2->id,
            ],
            [
                'id_kendaraan' => $hilux->id,
                'kilometer' => 64200,
                'tanggal_pencatatan' => now()->subDay()->toDateString(),
                'keterangan' => 'Update odometer sebelum pengajuan servis',
                'id_penginput' => $penginput2->id,
            ],

            // Ioniq logs
            [
                'id_kendaraan' => $ioniq->id,
                'kilometer' => 10000,
                'tanggal_pencatatan' => now()->subDays(20)->toDateString(),
                'keterangan' => 'Pencatatan kilometer sebelum servis berkala EV',
                'id_penginput' => $penginput1->id,
            ],
            [
                'id_kendaraan' => $ioniq->id,
                'kilometer' => 12300,
                'tanggal_pencatatan' => now()->subDays(3)->toDateString(),
                'keterangan' => 'Operasional kedinasan kantor pusat',
                'id_penginput' => $penginput1->id,
            ],
        ];

        foreach ($logs as $log) {
            OdometerLog::firstOrCreate(
                [
                    'id_kendaraan' => $log['id_kendaraan'],
                    'kilometer' => $log['kilometer'],
                    'tanggal_pencatatan' => $log['tanggal_pencatatan'],
                ],
                $log
            );
        }
    }
}
