<?php

namespace Database\Seeders;

use App\Models\Kendaraan;
use App\Models\RincianSparepart;
use App\Models\RiwayatServis;
use App\Models\User;
use Illuminate\Database\Seeder;

class RiwayatServisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pembuat1 = User::where('email', 'pengelola@pln.co.id')->first() ?? User::first();
        $pembuat2 = User::where('email', 'ahmad.teknisi@pln.co.id')->first() ?? User::first();

        $innova = Kendaraan::where('plat_nomor', 'B 1234 PLN')->first();
        $ioniq = Kendaraan::where('plat_nomor', 'B 4567 PLN')->first();
        $motor = Kendaraan::where('plat_nomor', 'B 5678 PLN')->first();
        $granMax = Kendaraan::where('plat_nomor', 'B 7890 PLN')->first();

        if (! $innova || ! $ioniq || ! $motor || ! $granMax) {
            return;
        }

        $servisList = [
            [
                'servis' => [
                    'id_pengajuan' => null,
                    'id_kendaraan' => $innova->id,
                    'tanggal_servis' => now()->subDays(5)->toDateString(),
                    'kilometer_servis' => 15000,
                    'nama_bengkel' => 'Auto2000 Saharjo Tebet',
                    'total_biaya' => 1850000.00,
                    'foto_nota' => null,
                    'target_kilometer_berikutnya' => 25000,
                    'id_pembuat' => $pembuat1->id,
                ],
                'sparepart' => [
                    ['nama_sparepart' => 'Oli Mesin TMO Full Synthetic 4L', 'jumlah' => 1, 'harga_satuan' => 580000.00, 'subtotal' => 580000.00],
                    ['nama_sparepart' => 'Filter Oli Original Toyota', 'jumlah' => 1, 'harga_satuan' => 95000.00, 'subtotal' => 95000.00],
                    ['nama_sparepart' => 'Filter Udara Mesin', 'jumlah' => 1, 'harga_satuan' => 175000.00, 'subtotal' => 175000.00],
                    ['nama_sparepart' => 'Jasa Servis Berkala & Tune Up', 'jumlah' => 1, 'harga_satuan' => 1000000.00, 'subtotal' => 1000000.00],
                ],
            ],
            [
                'servis' => [
                    'id_pengajuan' => null,
                    'id_kendaraan' => $ioniq->id,
                    'tanggal_servis' => now()->subDays(12)->toDateString(),
                    'kilometer_servis' => 10000,
                    'nama_bengkel' => 'Hyundai Simprug EV Official',
                    'total_biaya' => 950000.00,
                    'foto_nota' => null,
                    'target_kilometer_berikutnya' => 25000,
                    'id_pembuat' => $pembuat1->id,
                ],
                'sparepart' => [
                    ['nama_sparepart' => 'Filter AC Kabin Anti Bakteri EV', 'jumlah' => 1, 'harga_satuan' => 350000.00, 'subtotal' => 350000.00],
                    ['nama_sparepart' => 'Jasa Cek Baterai HV & Diagnosa Komputer', 'jumlah' => 1, 'harga_satuan' => 600000.00, 'subtotal' => 600000.00],
                ],
            ],
            [
                'servis' => [
                    'id_pengajuan' => null,
                    'id_kendaraan' => $motor->id,
                    'tanggal_servis' => now()->subMonth()->toDateString(),
                    'kilometer_servis' => 25000,
                    'nama_bengkel' => 'AHASS Tebet Raya',
                    'total_biaya' => 380000.00,
                    'foto_nota' => null,
                    'target_kilometer_berikutnya' => 28000,
                    'id_pembuat' => $pembuat2->id,
                ],
                'sparepart' => [
                    ['nama_sparepart' => 'Oli Mesin AHM SPX-1 1.2L', 'jumlah' => 1, 'harga_satuan' => 85000.00, 'subtotal' => 85000.00],
                    ['nama_sparepart' => 'Kampas Rem Depan Cakram', 'jumlah' => 1, 'harga_satuan' => 95000.00, 'subtotal' => 95000.00],
                    ['nama_sparepart' => 'Busi Standar Honda NGK', 'jumlah' => 1, 'harga_satuan' => 50000.00, 'subtotal' => 50000.00],
                    ['nama_sparepart' => 'Jasa Servis Lengkap', 'jumlah' => 1, 'harga_satuan' => 150000.00, 'subtotal' => 150000.00],
                ],
            ],
            [
                'servis' => [
                    'id_pengajuan' => null,
                    'id_kendaraan' => $granMax->id,
                    'tanggal_servis' => now()->subDays(8)->toDateString(),
                    'kilometer_servis' => 70000,
                    'nama_bengkel' => 'Astra Daihatsu Kalimalang',
                    'total_biaya' => 2450000.00,
                    'foto_nota' => null,
                    'target_kilometer_berikutnya' => 75000,
                    'id_pembuat' => $pembuat1->id,
                ],
                'sparepart' => [
                    ['nama_sparepart' => 'Oli Mesin Daihatsu Genuine 4L', 'jumlah' => 1, 'harga_satuan' => 450000.00, 'subtotal' => 450000.00],
                    ['nama_sparepart' => 'Filter Oli & Filter Udara Set', 'jumlah' => 1, 'harga_satuan' => 250000.00, 'subtotal' => 250000.00],
                    ['nama_sparepart' => 'Kampas Rem Depan Set', 'jumlah' => 1, 'harga_satuan' => 350000.00, 'subtotal' => 350000.00],
                    ['nama_sparepart' => 'Minyak Rem & Pengurasan', 'jumlah' => 2, 'harga_satuan' => 75000.00, 'subtotal' => 150000.00],
                    ['nama_sparepart' => 'Jasa Paket Servis Berkala 70rb KM', 'jumlah' => 1, 'harga_satuan' => 1250000.00, 'subtotal' => 1250000.00],
                ],
            ],
        ];

        foreach ($servisList as $item) {
            $servis = RiwayatServis::firstOrCreate(
                [
                    'id_kendaraan' => $item['servis']['id_kendaraan'],
                    'tanggal_servis' => $item['servis']['tanggal_servis'],
                    'kilometer_servis' => $item['servis']['kilometer_servis'],
                ],
                $item['servis']
            );

            foreach ($item['sparepart'] as $sparepart) {
                RincianSparepart::firstOrCreate(
                    [
                        'id_riwayat_servis' => $servis->id,
                        'nama_sparepart' => $sparepart['nama_sparepart'],
                    ],
                    array_merge($sparepart, ['id_riwayat_servis' => $servis->id])
                );
            }
        }
    }
}
