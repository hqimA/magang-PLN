<?php

namespace Database\Seeders;

use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@fleet-pln.test'],
            User::factory()->raw([
                'name' => 'Administrator Fleet',
                'peran' => 'ADMIN',
            ]),
        );

        $managers = collect([
            ['name' => 'Budi Santoso', 'email' => 'budi@fleet-pln.test'],
            ['name' => 'Siti Aminah', 'email' => 'siti@fleet-pln.test'],
            ['name' => 'Rudi Hartono', 'email' => 'rudi@fleet-pln.test'],
        ])->map(function (array $manager): User {
            return User::query()->firstOrCreate(
                ['email' => $manager['email']],
                User::factory()->raw($manager + ['peran' => 'PENGELOLA']),
            );
        });

        $vehicleDefinitions = [
            ['owner' => 0, 'plat_nomor' => 'B 1234 PLN', 'merk_tipe' => 'Toyota Innova', 'tahun_pembuatan' => 2021, 'transmisi' => 'OTOMATIS', 'jenis_bbm' => 'DIESEL', 'kategori_penggunaan' => 'PEJABAT', 'kilometer_terakhir' => 45200, 'tanggal_pembelian' => '2021-03-15', 'status_perawatan' => 'BAIK'],
            ['owner' => 0, 'plat_nomor' => 'B 5678 PLN', 'merk_tipe' => 'Toyota Hilux', 'tahun_pembuatan' => 2020, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'DIESEL', 'kategori_penggunaan' => 'ANGKUT_BARANG', 'kilometer_terakhir' => 88300, 'tanggal_pembelian' => '2020-06-20', 'status_perawatan' => 'PERLU_SERVIS'],
            ['owner' => 0, 'plat_nomor' => 'B 9012 PLN', 'merk_tipe' => 'Honda CR-V', 'tahun_pembuatan' => 2022, 'transmisi' => 'OTOMATIS', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'PEJABAT', 'kilometer_terakhir' => 29100, 'tanggal_pembelian' => '2022-01-10', 'status_perawatan' => 'BAIK'],
            ['owner' => 1, 'plat_nomor' => 'B 2345 PLN', 'merk_tipe' => 'Mitsubishi Xpander', 'tahun_pembuatan' => 2019, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'TEKNISI', 'kilometer_terakhir' => 112500, 'tanggal_pembelian' => '2019-08-12', 'status_perawatan' => 'SEDANG_SERVIS'],
            ['owner' => 1, 'plat_nomor' => 'B 6789 PLN', 'merk_tipe' => 'Suzuki Carry', 'tahun_pembuatan' => 2018, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'ANGKUT_BARANG', 'kilometer_terakhir' => 146700, 'tanggal_pembelian' => '2018-02-05', 'status_perawatan' => 'RUSAK'],
            ['owner' => 1, 'plat_nomor' => 'B 3456 PLN', 'merk_tipe' => 'Honda Beat', 'tahun_pembuatan' => 2023, 'transmisi' => 'OTOMATIS', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'MOTOR_OPERASIONAL', 'kilometer_terakhir' => 18700, 'tanggal_pembelian' => '2023-04-22', 'status_perawatan' => 'BAIK'],
            ['owner' => 2, 'plat_nomor' => 'B 7890 PLN', 'merk_tipe' => 'Toyota Avanza', 'tahun_pembuatan' => 2021, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'TEKNISI', 'kilometer_terakhir' => 67400, 'tanggal_pembelian' => '2021-09-17', 'status_perawatan' => 'PERLU_SERVIS'],
            ['owner' => 2, 'plat_nomor' => 'B 4567 PLN', 'merk_tipe' => 'Mitsubishi L300', 'tahun_pembuatan' => 2020, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'SOLAR', 'kategori_penggunaan' => 'ANGKUT_BARANG', 'kilometer_terakhir' => 125600, 'tanggal_pembelian' => '2020-11-03', 'status_perawatan' => 'BAIK'],
            ['owner' => 0, 'plat_nomor' => 'B 1001 PLN', 'merk_tipe' => 'Toyota Avanza', 'tahun_pembuatan' => 2022, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'TEKNISI', 'kilometer_terakhir' => 32100, 'tanggal_pembelian' => '2022-02-14', 'status_perawatan' => 'BAIK'],
            ['owner' => 0, 'plat_nomor' => 'B 1002 PLN', 'merk_tipe' => 'Honda Brio', 'tahun_pembuatan' => 2023, 'transmisi' => 'OTOMATIS', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'MOTOR_OPERASIONAL', 'kilometer_terakhir' => 15900, 'tanggal_pembelian' => '2023-05-11', 'status_perawatan' => 'BAIK'],
            ['owner' => 0, 'plat_nomor' => 'B 1003 PLN', 'merk_tipe' => 'Isuzu Panther', 'tahun_pembuatan' => 2017, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'DIESEL', 'kategori_penggunaan' => 'ANGKUT_BARANG', 'kilometer_terakhir' => 173400, 'tanggal_pembelian' => '2017-07-19', 'status_perawatan' => 'PERLU_SERVIS'],
            ['owner' => 0, 'plat_nomor' => 'B 1004 PLN', 'merk_tipe' => 'Toyota Rush', 'tahun_pembuatan' => 2021, 'transmisi' => 'OTOMATIS', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'PEJABAT', 'kilometer_terakhir' => 48900, 'tanggal_pembelian' => '2021-10-08', 'status_perawatan' => 'BAIK'],
            ['owner' => 0, 'plat_nomor' => 'B 1005 PLN', 'merk_tipe' => 'Daihatsu Gran Max', 'tahun_pembuatan' => 2019, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'ANGKUT_BARANG', 'kilometer_terakhir' => 98700, 'tanggal_pembelian' => '2019-04-26', 'status_perawatan' => 'SEDANG_SERVIS'],
            ['owner' => 0, 'plat_nomor' => 'B 1006 PLN', 'merk_tipe' => 'Honda Vario', 'tahun_pembuatan' => 2022, 'transmisi' => 'OTOMATIS', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'MOTOR_OPERASIONAL', 'kilometer_terakhir' => 22400, 'tanggal_pembelian' => '2022-08-30', 'status_perawatan' => 'BAIK'],
            ['owner' => 0, 'plat_nomor' => 'B 1007 PLN', 'merk_tipe' => 'Mitsubishi Triton', 'tahun_pembuatan' => 2020, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'DIESEL', 'kategori_penggunaan' => 'TEKNISI', 'kilometer_terakhir' => 110200, 'tanggal_pembelian' => '2020-12-12', 'status_perawatan' => 'PERLU_SERVIS'],
            ['owner' => 0, 'plat_nomor' => 'B 1008 PLN', 'merk_tipe' => 'Toyota Kijang Innova', 'tahun_pembuatan' => 2023, 'transmisi' => 'OTOMATIS', 'jenis_bbm' => 'DIESEL', 'kategori_penggunaan' => 'PEJABAT', 'kilometer_terakhir' => 12100, 'tanggal_pembelian' => '2023-09-04', 'status_perawatan' => 'BAIK'],
            ['owner' => 1, 'plat_nomor' => 'B 1009 PLN', 'merk_tipe' => 'Suzuki Ertiga', 'tahun_pembuatan' => 2021, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'TEKNISI', 'kilometer_terakhir' => 56200, 'tanggal_pembelian' => '2021-01-21', 'status_perawatan' => 'BAIK'],
            ['owner' => 1, 'plat_nomor' => 'B 1010 PLN', 'merk_tipe' => 'Toyota Hilux', 'tahun_pembuatan' => 2019, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'DIESEL', 'kategori_penggunaan' => 'ANGKUT_BARANG', 'kilometer_terakhir' => 132800, 'tanggal_pembelian' => '2019-10-15', 'status_perawatan' => 'PERLU_SERVIS'],
            ['owner' => 1, 'plat_nomor' => 'B 1011 PLN', 'merk_tipe' => 'Honda Mobilio', 'tahun_pembuatan' => 2020, 'transmisi' => 'OTOMATIS', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'PEJABAT', 'kilometer_terakhir' => 74300, 'tanggal_pembelian' => '2020-03-09', 'status_perawatan' => 'BAIK'],
            ['owner' => 1, 'plat_nomor' => 'B 1012 PLN', 'merk_tipe' => 'Yamaha NMAX', 'tahun_pembuatan' => 2022, 'transmisi' => 'OTOMATIS', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'MOTOR_OPERASIONAL', 'kilometer_terakhir' => 31800, 'tanggal_pembelian' => '2022-11-18', 'status_perawatan' => 'BAIK'],
            ['owner' => 1, 'plat_nomor' => 'B 1013 PLN', 'merk_tipe' => 'Isuzu ELF', 'tahun_pembuatan' => 2018, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'SOLAR', 'kategori_penggunaan' => 'ANGKUT_BARANG', 'kilometer_terakhir' => 189500, 'tanggal_pembelian' => '2018-06-07', 'status_perawatan' => 'RUSAK'],
            ['owner' => 1, 'plat_nomor' => 'B 1014 PLN', 'merk_tipe' => 'Toyota Fortuner', 'tahun_pembuatan' => 2021, 'transmisi' => 'OTOMATIS', 'jenis_bbm' => 'DIESEL', 'kategori_penggunaan' => 'PEJABAT', 'kilometer_terakhir' => 63700, 'tanggal_pembelian' => '2021-12-01', 'status_perawatan' => 'BAIK'],
            ['owner' => 1, 'plat_nomor' => 'B 1015 PLN', 'merk_tipe' => 'Daihatsu Luxio', 'tahun_pembuatan' => 2019, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'TEKNISI', 'kilometer_terakhir' => 104600, 'tanggal_pembelian' => '2019-05-14', 'status_perawatan' => 'SEDANG_SERVIS'],
            ['owner' => 1, 'plat_nomor' => 'B 1016 PLN', 'merk_tipe' => 'Honda Supra X', 'tahun_pembuatan' => 2020, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'MOTOR_OPERASIONAL', 'kilometer_terakhir' => 45100, 'tanggal_pembelian' => '2020-07-23', 'status_perawatan' => 'PERLU_SERVIS'],
            ['owner' => 1, 'plat_nomor' => 'B 1017 PLN', 'merk_tipe' => 'Toyota Calya', 'tahun_pembuatan' => 2022, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'TEKNISI', 'kilometer_terakhir' => 28700, 'tanggal_pembelian' => '2022-04-16', 'status_perawatan' => 'BAIK'],
            ['owner' => 2, 'plat_nomor' => 'B 1018 PLN', 'merk_tipe' => 'Mitsubishi Pajero Sport', 'tahun_pembuatan' => 2020, 'transmisi' => 'OTOMATIS', 'jenis_bbm' => 'DIESEL', 'kategori_penggunaan' => 'PEJABAT', 'kilometer_terakhir' => 79200, 'tanggal_pembelian' => '2020-09-28', 'status_perawatan' => 'BAIK'],
            ['owner' => 2, 'plat_nomor' => 'B 1019 PLN', 'merk_tipe' => 'Toyota Dyna', 'tahun_pembuatan' => 2018, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'DIESEL', 'kategori_penggunaan' => 'ANGKUT_BARANG', 'kilometer_terakhir' => 201300, 'tanggal_pembelian' => '2018-01-17', 'status_perawatan' => 'RUSAK'],
            ['owner' => 2, 'plat_nomor' => 'B 1020 PLN', 'merk_tipe' => 'Honda BR-V', 'tahun_pembuatan' => 2023, 'transmisi' => 'OTOMATIS', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'PEJABAT', 'kilometer_terakhir' => 9800, 'tanggal_pembelian' => '2023-07-06', 'status_perawatan' => 'BAIK'],
            ['owner' => 2, 'plat_nomor' => 'B 1021 PLN', 'merk_tipe' => 'Suzuki Carry', 'tahun_pembuatan' => 2019, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'ANGKUT_BARANG', 'kilometer_terakhir' => 118400, 'tanggal_pembelian' => '2019-11-22', 'status_perawatan' => 'PERLU_SERVIS'],
            ['owner' => 2, 'plat_nomor' => 'B 1022 PLN', 'merk_tipe' => 'Yamaha NMAX', 'tahun_pembuatan' => 2021, 'transmisi' => 'OTOMATIS', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'MOTOR_OPERASIONAL', 'kilometer_terakhir' => 36900, 'tanggal_pembelian' => '2021-06-13', 'status_perawatan' => 'BAIK'],
            ['owner' => 2, 'plat_nomor' => 'B 1023 PLN', 'merk_tipe' => 'Toyota Rush', 'tahun_pembuatan' => 2020, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'TEKNISI', 'kilometer_terakhir' => 85600, 'tanggal_pembelian' => '2020-02-27', 'status_perawatan' => 'SEDANG_SERVIS'],
            ['owner' => 2, 'plat_nomor' => 'B 1024 PLN', 'merk_tipe' => 'Isuzu Traga', 'tahun_pembuatan' => 2022, 'transmisi' => 'MANUAL', 'jenis_bbm' => 'DIESEL', 'kategori_penggunaan' => 'ANGKUT_BARANG', 'kilometer_terakhir' => 47700, 'tanggal_pembelian' => '2022-06-25', 'status_perawatan' => 'BAIK'],
            ['owner' => 2, 'plat_nomor' => 'B 1025 PLN', 'merk_tipe' => 'Honda PCX', 'tahun_pembuatan' => 2023, 'transmisi' => 'OTOMATIS', 'jenis_bbm' => 'BENSIN', 'kategori_penggunaan' => 'MOTOR_OPERASIONAL', 'kilometer_terakhir' => 14300, 'tanggal_pembelian' => '2023-10-12', 'status_perawatan' => 'BAIK'],
        ];

        $vehicles = collect($vehicleDefinitions)->mapWithKeys(function (array $definition) use ($managers): array {
            $manager = $managers->values()->get($definition['owner']);
            unset($definition['owner']);

            $vehicle = Kendaraan::query()->updateOrCreate(
                ['plat_nomor' => $definition['plat_nomor']],
                $definition + ['id_pengelola' => $manager->id],
            );

            return [$vehicle->plat_nomor => $vehicle];
        });

        if (DB::table('notifikasi')->where('id_pengguna', $admin->id)->where('judul', 'Laporan kerusakan baru')->exists()) {
            return;
        }

        $insertIfMissing = function (string $table, array $conditions, array $values): int {
            $existingId = DB::table($table)->where($conditions)->value('id');

            if ($existingId !== null) {
                return (int) $existingId;
            }

            return (int) DB::table($table)->insertGetId($values);
        };

        $laporanSelesai = $insertIfMissing('laporan_kerusakan', [
            'id_kendaraan' => $vehicles->get('B 1234 PLN')->id,
            'status_penanganan' => 'SELESAI',
        ], [
            'id_kendaraan' => $vehicles->get('B 1234 PLN')->id,
            'id_pelapor' => $managers->values()->get(0)->id,
            'tanggal_kejadian' => now()->subDays(45),
            'lokasi_kejadian' => 'Gardu Induk Cawang',
            'deskripsi_kerusakan' => 'AC kabin tidak mengeluarkan udara dingin.',
            'tingkat_kerusakan' => 'RINGAN',
            'status_penanganan' => 'SELESAI',
        ]);

        $laporanBerat = $insertIfMissing('laporan_kerusakan', [
            'id_kendaraan' => $vehicles->get('B 6789 PLN')->id,
            'status_penanganan' => 'SEDANG_DIPERBAIKI',
        ], [
            'id_kendaraan' => $vehicles->get('B 6789 PLN')->id,
            'id_pelapor' => $managers->values()->get(1)->id,
            'tanggal_kejadian' => now()->subDays(4),
            'lokasi_kejadian' => 'Area Operasional Depok',
            'deskripsi_kerusakan' => 'Mesin kendaraan kehilangan tenaga dan mengeluarkan suara kasar.',
            'tingkat_kerusakan' => 'BERAT',
            'status_penanganan' => 'SEDANG_DIPERBAIKI',
        ]);

        $laporanAktif = $insertIfMissing('laporan_kerusakan', [
            'id_kendaraan' => $vehicles->get('B 7890 PLN')->id,
            'status_penanganan' => 'DILAPORKAN',
        ], [
            'id_kendaraan' => $vehicles->get('B 7890 PLN')->id,
            'id_pelapor' => $managers->values()->get(2)->id,
            'tanggal_kejadian' => now()->subDay(),
            'lokasi_kejadian' => 'UP3 Jakarta Selatan',
            'deskripsi_kerusakan' => 'Rem belakang terasa kurang pakem saat digunakan.',
            'tingkat_kerusakan' => 'SEDANG',
            'status_penanganan' => 'DILAPORKAN',
        ]);

        $pengajuanDisetujui = $insertIfMissing('pengajuan_servis', [
            'id_kendaraan' => $vehicles->get('B 5678 PLN')->id,
            'status_persetujuan' => 'DISETUJUI',
        ], [
            'id_kendaraan' => $vehicles->get('B 5678 PLN')->id,
            'id_pengaju' => $managers->values()->get(0)->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Servis berkala dan penggantian oli mesin.',
            'estimasi_biaya' => 1850000,
            'status_persetujuan' => 'DISETUJUI',
            'id_disetujui_oleh' => $admin->id,
            'dibuat_pada' => now()->subDays(10),
        ]);

        $pendingRequest = [
            'id_kendaraan' => $vehicles->get('B 2345 PLN')->id,
            'id_pengaju' => $managers->values()->get(1)->id,
            'jenis_pengajuan' => 'DARURAT',
            'deskripsi_keluhan' => 'Kendaraan mengalami overheating saat perjalanan dinas.',
            'estimasi_biaya' => 4200000,
            'status_persetujuan' => 'MENUNGGU',
            'alasan_penolakan' => null,
            'id_disetujui_oleh' => null,
            'dibuat_pada' => now()->subHours(8),
        ];

        if (! DB::table('pengajuan_servis')->where('id_kendaraan', $pendingRequest['id_kendaraan'])->where('status_persetujuan', 'MENUNGGU')->exists()) {
            DB::table('pengajuan_servis')->insert($pendingRequest);
        }

        $rejectedRequest = [
            'id_kendaraan' => $vehicles->get('B 3456 PLN')->id,
            'id_pengaju' => $managers->values()->get(1)->id,
            'jenis_pengajuan' => 'RUTIN',
            'deskripsi_keluhan' => 'Pemeriksaan rutin kendaraan operasional.',
            'estimasi_biaya' => 650000,
            'status_persetujuan' => 'DITOLAK',
            'alasan_penolakan' => 'Jadwal servis kendaraan masih aktif.',
            'id_disetujui_oleh' => $admin->id,
            'dibuat_pada' => now()->subDays(2),
        ];

        if (! DB::table('pengajuan_servis')->where('id_kendaraan', $rejectedRequest['id_kendaraan'])->where('status_persetujuan', 'DITOLAK')->exists()) {
            DB::table('pengajuan_servis')->insert($rejectedRequest);
        }

        $riwayatServis = DB::table('riwayat_servis')->insertGetId([
            'id_pengajuan' => $pengajuanDisetujui,
            'id_kendaraan' => $vehicles->get('B 5678 PLN')->id,
            'tanggal_servis' => now()->subDays(3)->toDateString(),
            'kilometer_servis' => 88300,
            'nama_bengkel' => 'Bengkel Mitra PLN Jakarta',
            'total_biaya' => 1850000,
            'target_kilometer_berikutnya' => 93300,
            'id_pembuat' => $admin->id,
        ]);

        DB::table('rincian_sparepart')->insert([
            [
                'id_riwayat_servis' => $riwayatServis,
                'nama_sparepart' => 'Oli Mesin Diesel 5W-40',
                'jumlah' => 7,
                'harga_satuan' => 125000,
                'subtotal' => 875000,
            ],
            [
                'id_riwayat_servis' => $riwayatServis,
                'nama_sparepart' => 'Filter Oli',
                'jumlah' => 1,
                'harga_satuan' => 175000,
                'subtotal' => 175000,
            ],
        ]);

        DB::table('riwayat_perbaikan')->insert([
            'id_laporan_kerusakan' => $laporanSelesai,
            'tanggal_perbaikan' => now()->subDays(40)->toDateString(),
            'nama_bengkel' => 'Bengkel AC Mobil Cawang',
            'ringkasan_perbaikan' => 'Penggantian filter kabin dan pengisian ulang freon.',
            'total_biaya_perbaikan' => 450000,
            'id_pembuat' => $admin->id,
        ]);

        DB::table('notifikasi')->insert([
            [
                'id_pengguna' => $admin->id,
                'judul' => 'Laporan kerusakan baru',
                'pesan' => 'Ada laporan kerusakan baru yang menunggu ditinjau.',
                'sudah_dibaca' => false,
                'tipe_referensi' => 'laporan_kerusakan',
                'dibuat_pada' => now()->subDay(),
            ],
            [
                'id_pengguna' => $managers->values()->get(0)->id,
                'judul' => 'Pengajuan servis disetujui',
                'pesan' => 'Pengajuan servis kendaraan B 5678 PLN telah disetujui.',
                'sudah_dibaca' => true,
                'tipe_referensi' => 'pengajuan_servis',
                'dibuat_pada' => now()->subDays(9),
            ],
            [
                'id_pengguna' => $managers->values()->get(2)->id,
                'judul' => 'Laporan kerusakan diterima',
                'pesan' => 'Laporan kerusakan kendaraan B 7890 PLN sedang menunggu penanganan.',
                'sudah_dibaca' => false,
                'tipe_referensi' => 'laporan_kerusakan',
                'dibuat_pada' => now()->subDay(),
            ],
        ]);
    }
}
