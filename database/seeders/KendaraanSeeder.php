<?php

namespace Database\Seeders;

use App\Models\Kendaraan;
use App\Models\TemplateJadwalServis;
use App\Models\User;
use Illuminate\Database\Seeder;

class KendaraanSeeder extends Seeder
{
    public function run(): void
    {
        $pengelola1 = User::where('email', 'pengelola@pln.co.id')->first()
            ?? User::firstOrCreate(['email' => 'pengelola@pln.co.id'], [
                'name' => 'Budi Santoso',
                'password' => bcrypt('password'),
                'peran' => 'PENGELOLA',
            ]);

        $pengelola2 = User::where('email', 'ahmad.teknisi@pln.co.id')->first()
            ?? User::firstOrCreate(['email' => 'ahmad.teknisi@pln.co.id'], [
                'name' => 'Ahmad Hidayat',
                'password' => bcrypt('password'),
                'peran' => 'PENGELOLA',
            ]);

        $kendaraans = [
            [
                'plat_nomor' => 'B 1234 PLN',
                'merk_tipe' => 'Toyota Innova Zenix 2.0 V',
                'tahun_pembuatan' => 2023,
                'transmisi' => 'OTOMATIS',
                'jenis_bbm' => 'BENSIN',
                'kategori_penggunaan' => 'PEJABAT',
                'kilometer_terakhir' => 18500,
                'interval_servis_km' => 10000,
                'interval_servis_bulan' => 6,
                'threshold_servis_km' => 500,
                'threshold_servis_hari' => 14,
                'tanggal_pembelian' => '2023-03-15',
                'status_perawatan' => 'BAIK',
                'foto_kendaraan' => null,
                'id_pengelola' => $pengelola1->id,
            ],
            [
                'plat_nomor' => 'B 2345 PLN',
                'merk_tipe' => 'Toyota Hilux D-Cab 2.4 4x4',
                'tahun_pembuatan' => 2021,
                'transmisi' => 'MANUAL',
                'jenis_bbm' => 'SOLAR',
                'kategori_penggunaan' => 'TEKNISI',
                'kilometer_terakhir' => 64200,
                'interval_servis_km' => 5000,
                'interval_servis_bulan' => 3,
                'threshold_servis_km' => 200,
                'threshold_servis_hari' => 10,
                'tanggal_pembelian' => '2021-06-20',
                'status_perawatan' => 'PERLU_SERVIS',
                'foto_kendaraan' => null,
                'id_pengelola' => $pengelola2->id,
            ],
            [
                'plat_nomor' => 'B 3456 PLN',
                'merk_tipe' => 'Isuzu D-Max Derek Operasional',
                'tahun_pembuatan' => 2020,
                'transmisi' => 'MANUAL',
                'jenis_bbm' => 'DIESEL',
                'kategori_penggunaan' => 'ANGKUT_BARANG',
                'kilometer_terakhir' => 98700,
                'interval_servis_km' => 5000,
                'interval_servis_bulan' => 3,
                'threshold_servis_km' => 250,
                'threshold_servis_hari' => 10,
                'tanggal_pembelian' => '2020-01-10',
                'status_perawatan' => 'SEDANG_SERVIS',
                'foto_kendaraan' => null,
                'id_pengelola' => $pengelola2->id,
            ],
            [
                'plat_nomor' => 'B 4567 PLN',
                'merk_tipe' => 'Hyundai Ioniq 5 Signature Long Range',
                'tahun_pembuatan' => 2023,
                'transmisi' => 'OTOMATIS',
                'jenis_bbm' => 'LISTRIK',
                'kategori_penggunaan' => 'PEJABAT',
                'kilometer_terakhir' => 12300,
                'interval_servis_km' => 15000,
                'interval_servis_bulan' => 12,
                'threshold_servis_km' => 1000,
                'threshold_servis_hari' => 30,
                'tanggal_pembelian' => '2023-08-01',
                'status_perawatan' => 'BAIK',
                'foto_kendaraan' => null,
                'id_pengelola' => $pengelola1->id,
            ],
            [
                'plat_nomor' => 'B 5678 PLN',
                'merk_tipe' => 'Honda CB150R Streetfire Operasional',
                'tahun_pembuatan' => 2022,
                'transmisi' => 'MANUAL',
                'jenis_bbm' => 'BENSIN',
                'kategori_penggunaan' => 'MOTOR_OPERASIONAL',
                'kilometer_terakhir' => 28400,
                'interval_servis_km' => 3000,
                'interval_servis_bulan' => 2,
                'threshold_servis_km' => 200,
                'threshold_servis_hari' => 7,
                'tanggal_pembelian' => '2022-04-12',
                'status_perawatan' => 'BAIK',
                'foto_kendaraan' => null,
                'id_pengelola' => $pengelola2->id,
            ],
            [
                'plat_nomor' => 'B 6789 PLN',
                'merk_tipe' => 'Mitsubishi Triton Tanggap Darurat',
                'tahun_pembuatan' => 2022,
                'transmisi' => 'MANUAL',
                'jenis_bbm' => 'SOLAR',
                'kategori_penggunaan' => 'TEKNISI',
                'kilometer_terakhir' => 53100,
                'interval_servis_km' => 5000,
                'interval_servis_bulan' => 3,
                'threshold_servis_km' => 300,
                'threshold_servis_hari' => 10,
                'tanggal_pembelian' => '2022-02-18',
                'status_perawatan' => 'RUSAK',
                'foto_kendaraan' => null,
                'id_pengelola' => $pengelola2->id,
            ],
            [
                'plat_nomor' => 'B 7890 PLN',
                'merk_tipe' => 'Daihatsu Gran Max Blind Van Logistik',
                'tahun_pembuatan' => 2021,
                'transmisi' => 'MANUAL',
                'jenis_bbm' => 'BENSIN',
                'kategori_penggunaan' => 'ANGKUT_BARANG',
                'kilometer_terakhir' => 72000,
                'interval_servis_km' => 5000,
                'interval_servis_bulan' => 3,
                'threshold_servis_km' => 300,
                'threshold_servis_hari' => 10,
                'tanggal_pembelian' => '2021-09-05',
                'status_perawatan' => 'BAIK',
                'foto_kendaraan' => null,
                'id_pengelola' => $pengelola1->id,
            ],
        ];

        foreach ($kendaraans as $data) {
            $data['id_template'] = $this->resolveTemplate(
                $data['jenis_bbm'],
                $data['transmisi'],
                $data['kategori_penggunaan']
            );

            Kendaraan::updateOrCreate(
                ['plat_nomor' => $data['plat_nomor']],
                $data
            );
        }
    }

    /**
     * Auto-assign template berdasarkan jenis_bbm + transmisi + kategori_penggunaan.
     * Urutan pengecekan:
     * 1. Motor → kategori_penggunaan = MOTOR_OPERASIONAL
     * 2. EV    → jenis_bbm = LISTRIK
     * 3. Diesel Manual / Diesel Otomatis → jenis_bbm = SOLAR/DIESEL
     * 4. Bensin Manual / Bensin Otomatis → jenis_bbm = BENSIN
     */
    private function resolveTemplate(string $jenis_bbm, string $transmisi, string $kategori): ?int
    {
        $nama = match (true) {
            $kategori === 'MOTOR_OPERASIONAL' => 'Motor',
            $jenis_bbm === 'LISTRIK' => 'EV',
            in_array($jenis_bbm, ['SOLAR', 'DIESEL']) && $transmisi === 'MANUAL' => 'Diesel Manual',
            in_array($jenis_bbm, ['SOLAR', 'DIESEL']) && $transmisi === 'OTOMATIS' => 'Diesel Otomatis',
            $jenis_bbm === 'BENSIN' && $transmisi === 'MANUAL' => 'Bensin Manual',
            $jenis_bbm === 'BENSIN' && $transmisi === 'OTOMATIS' => 'Bensin Otomatis',
            default => null,
        };

        if (! $nama) {
            return null;
        }

        return TemplateJadwalServis::where('nama', $nama)->value('id');
    }
}
