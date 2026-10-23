<?php

namespace Database\Seeders;

use App\Models\TemplateJadwalDetail;
use App\Models\TemplateJadwalServis;
use App\Models\TemplateKomponen;
use Illuminate\Database\Seeder;

class TemplateKomponenSeeder extends Seeder
{
    /**
     * Peta interval km => bulan sesuai manual book.
     * Whichever comes first (km or bulan) akan trigger servis.
     */
    private array $intervalMap = [
        1000 => 1,
        10000 => 6,
        20000 => 12,
        30000 => 18,
        40000 => 24,
        50000 => 30,
        60000 => 36,
        70000 => 42,
        80000 => 48,
        90000 => 54,
        100000 => 60,
        160000 => 96, // khusus cairan pendingin: ganti pertama di 160.000km
    ];

    public function run(): void
    {
        // Komponen yang masuk tiap template (array nomor urut)
        $templateMap = [
            // Semua 30 komponen kecuali transmisi otomatis (no.25)
            'Bensin Manual' => array_values(array_diff(range(1, 30), [25])),

            // Semua kecuali pedal kopling (14), minyak kopling (19), transmisi manual (24)
            'Bensin Otomatis' => array_values(array_diff(range(1, 30), [14, 19, 24])),

            // Diesel tidak punya busi (8), tidak pakai transmisi otomatis (25)
            'Diesel Manual' => array_values(array_diff(range(1, 30), [8, 25])),

            // Diesel otomatis: tidak ada busi (8), kopling (14,19), trans manual (24)
            'Diesel Otomatis' => array_values(array_diff(range(1, 30), [8, 14, 19, 24])),

            // EV: hanya komponen chassis + pendingin + ban + lampu
            // Tidak ada: mesin (1,3,4), pengapian busi (8), bahan bakar (10-13),
            // kopling (14,19), transmisi (24,25), diferensial (26), exhaust (7)
            'EV' => [2, 5, 6, 9, 15, 16, 17, 18, 20, 21, 22, 23, 27, 28, 29, 30],

            // Motor: komponen dasar motor, rem, suspensi, ban, lampu
            'Motor' => [3, 4, 8, 9, 14, 15, 16, 17, 18, 27, 28, 29],
        ];

        $base = $this->baseKomponen();

        foreach ($templateMap as $templateNama => $nomorList) {
            $template = TemplateJadwalServis::where('nama', $templateNama)->first();
            if (! $template) {
                continue;
            }

            foreach ($nomorList as $nomor) {
                if (! isset($base[$nomor])) {
                    continue;
                }

                [$namaKomponen, $kategori, $jadwal] = $base[$nomor];

                $komponen = TemplateKomponen::firstOrCreate(
                    [
                        'id_template' => $template->id,
                        'nomor_urut' => $nomor,
                    ],
                    [
                        'nama_komponen' => $namaKomponen,
                        'kategori' => $kategori,
                        'is_aktif' => true,
                    ]
                );

                foreach ($jadwal as [$km, $aksi]) {
                    TemplateJadwalDetail::firstOrCreate(
                        [
                            'id_template_komponen' => $komponen->id,
                            'interval_km' => $km,
                        ],
                        [
                            'interval_bulan' => $this->intervalMap[$km] ?? 0,
                            'jenis_aksi' => $aksi,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Data komponen dasar dari manual book.
     * Format: nomor => [nama, kategori, [[interval_km, jenis_aksi], ...]]
     *
     * Sumber: Jadwal Servis Berkala (manual book)
     * P = Periksa dan perbaiki atau bila perlu ganti
     * G = Ganti, mengubah atau melumasi
     */
    private function baseKomponen(): array
    {
        return [
            // ── KOMPONEN DASAR MESIN ─────────────────────────────────────
            1 => ['Celah katup', 'KOMPONEN_DASAR_MESIN', [
                [80000, 'P'],
            ]],

            2 => ['Drive belt', 'KOMPONEN_DASAR_MESIN', [
                [10000, 'P'], [30000, 'P'], [50000, 'P'],
                [70000, 'P'], [90000, 'P'], [100000, 'P'],
            ]],

            3 => ['Oli mesin', 'KOMPONEN_DASAR_MESIN', [
                [1000,   'P'],
                [10000,  'G'], [20000, 'G'], [30000, 'G'], [40000, 'G'],
                [50000,  'G'], [60000, 'G'], [70000, 'G'], [80000, 'G'],
                [90000,  'G'], [100000, 'G'],
            ]],

            4 => ['Filter oli mesin', 'KOMPONEN_DASAR_MESIN', [
                [10000,  'G'], [20000, 'G'], [30000, 'G'], [40000, 'G'],
                [50000,  'G'], [60000, 'G'], [70000, 'G'], [80000, 'G'],
                [90000,  'G'], [100000, 'G'],
            ]],

            5 => ['Sistem pendingin dan pemanas', 'KOMPONEN_DASAR_MESIN', [
                [1000, 'P'], [40000, 'P'], [80000, 'P'], [100000, 'P'],
            ]],

            6 => ['Cairan pendingin mesin', 'KOMPONEN_DASAR_MESIN', [
                // Periksa di setiap interval
                [1000,   'P'], [10000,  'P'], [20000,  'P'], [30000,  'P'],
                [40000,  'P'], [50000,  'P'], [60000,  'P'], [70000,  'P'],
                [80000,  'P'], [90000,  'P'], [100000, 'P'],
                // Ganti pertama di 160.000km, selanjutnya tiap 80.000km
                [160000, 'G'],
            ]],

            7 => ['Pipa exhaust dan mounting', 'KOMPONEN_DASAR_MESIN', [
                [1000, 'P'], [20000, 'P'], [40000, 'P'],
                [60000, 'P'], [80000, 'P'], [100000, 'P'],
            ]],

            // ── SISTEM PENGAPIAN ─────────────────────────────────────────
            8 => ['Busi (Spark plug)', 'SISTEM_PENGAPIAN', [
                // Bergantian P dan G setiap 10.000km
                [10000, 'P'], [20000, 'G'],
                [30000, 'P'], [40000, 'G'],
                [50000, 'P'], [60000, 'G'],
                [70000, 'P'], [80000, 'G'],
                [90000, 'P'], [100000, 'G'],
            ]],

            9 => ['Baterai', 'SISTEM_PENGAPIAN', [
                [1000,   'P'], [10000,  'P'], [20000,  'P'], [30000,  'P'],
                [40000,  'P'], [50000,  'P'], [60000,  'P'], [70000,  'P'],
                [80000,  'P'], [90000,  'P'], [100000, 'P'],
            ]],

            // ── BAHAN BAKAR & EMISI ──────────────────────────────────────
            10 => ['Filter bahan bakar', 'BAHAN_BAKAR_EMISI', [
                [80000, 'G'],
            ]],

            11 => ['Filter pembersih udara', 'BAHAN_BAKAR_EMISI', [
                [10000, 'P'], [20000, 'P'], [30000, 'P'],
                [40000, 'G'],
                [50000, 'P'], [60000, 'P'], [70000, 'P'],
                [80000, 'G'],
                [90000, 'P'], [100000, 'P'],
            ]],

            12 => ['Tutup tangki & saluran bahan bakar', 'BAHAN_BAKAR_EMISI', [
                [40000, 'P'], [80000, 'P'], [100000, 'P'],
            ]],

            13 => ['Charcoal canister', 'BAHAN_BAKAR_EMISI', [
                [30000, 'P'], [80000, 'P'],
            ]],

            // ── CHASSIS DAN BODI ─────────────────────────────────────────
            14 => ['Pedal kopling', 'CHASSIS_BODI', [
                [1000,   'P'], [10000,  'P'], [20000,  'P'], [30000,  'P'],
                [40000,  'P'], [50000,  'P'], [60000,  'P'], [70000,  'P'],
                [80000,  'P'], [90000,  'P'], [100000, 'P'],
            ]],

            15 => ['Pedal rem dan rem parkir', 'CHASSIS_BODI', [
                [10000,  'P'], [20000,  'P'], [30000,  'P'], [40000,  'P'],
                [50000,  'P'], [60000,  'P'], [70000,  'P'], [80000,  'P'],
                [90000,  'P'], [100000, 'P'],
            ]],

            16 => ['Kanvas dan tromol rem', 'CHASSIS_BODI', [
                [10000,  'P'], [20000,  'P'], [30000,  'P'], [40000,  'P'],
                [50000,  'P'], [60000,  'P'], [70000,  'P'], [80000,  'P'],
                [90000,  'P'], [100000, 'P'],
            ]],

            17 => ['Pad dan piringan rem', 'CHASSIS_BODI', [
                [10000,  'P'], [20000,  'P'], [30000,  'P'], [40000,  'P'],
                [50000,  'P'], [60000,  'P'], [70000,  'P'], [80000,  'P'],
                [90000,  'P'], [100000, 'P'],
            ]],

            18 => ['Minyak rem', 'CHASSIS_BODI', [
                [1000,  'P'], [10000, 'P'], [20000, 'P'], [30000, 'P'],
                [40000, 'G'],
                // 50.000km: tidak ada aksi (sesuai manual)
                [60000, 'P'], [70000, 'P'],
                [80000, 'G'],
                [90000, 'P'], [100000, 'P'],
            ]],

            19 => ['Minyak kopling', 'CHASSIS_BODI', [
                [1000,  'P'], [10000, 'P'], [20000, 'P'], [30000, 'P'],
                [40000, 'G'],
                [60000, 'P'], [70000, 'P'],
                [80000, 'G'],
                [90000, 'P'], [100000, 'P'],
            ]],

            20 => ['Pipa-pipa dan selang rem', 'CHASSIS_BODI', [
                [20000, 'P'], [40000, 'P'], [60000, 'P'],
                [80000, 'P'], [100000, 'P'],
            ]],

            21 => ['Roda kemudi & steering gear box', 'CHASSIS_BODI', [
                [20000, 'P'], [40000, 'P'], [60000, 'P'],
                [80000, 'P'], [100000, 'P'],
            ]],

            22 => ['Alignment roda depan (toe-in)', 'CHASSIS_BODI', [
                [20000, 'P'], [40000, 'P'], [60000, 'P'],
                [80000, 'P'], [100000, 'P'],
            ]],

            23 => ['Suspension ball joint & penutup debu', 'CHASSIS_BODI', [
                [1000,   'P'], [10000,  'P'], [20000,  'P'], [30000,  'P'],
                [40000,  'P'], [50000,  'P'], [60000,  'P'], [70000,  'P'],
                [80000,  'P'], [90000,  'P'], [100000, 'P'],
            ]],

            24 => ['Minyak transmisi manual', 'CHASSIS_BODI', [
                [20000, 'P'], [40000, 'G'],
                [60000, 'P'], [80000, 'G'],
                [100000, 'P'],
            ]],

            25 => ['Minyak transmisi otomatis', 'CHASSIS_BODI', [
                [40000, 'P'],
                [80000, 'G'],
            ]],

            26 => ['Oli gigi diferensial', 'CHASSIS_BODI', [
                [20000, 'P'], [40000, 'G'],
                [60000, 'P'], [80000, 'G'],
                [100000, 'P'],
            ]],

            27 => ['Suspensi depan dan belakang', 'CHASSIS_BODI', [
                [1000, 'P'], [20000, 'P'], [40000, 'P'],
                [60000, 'P'], [80000, 'P'], [100000, 'P'],
            ]],

            28 => ['Ban dan tekanan pemompaan', 'CHASSIS_BODI', [
                [1000,   'P'], [10000,  'P'], [20000,  'P'], [30000,  'P'],
                [40000,  'P'], [50000,  'P'], [60000,  'P'], [70000,  'P'],
                [80000,  'P'], [90000,  'P'], [100000, 'P'],
            ]],

            29 => ['Lampu, klakson, wiper dan washer', 'CHASSIS_BODI', [
                [1000,   'P'], [10000,  'P'], [20000,  'P'], [30000,  'P'],
                [40000,  'P'], [50000,  'P'], [60000,  'P'], [70000,  'P'],
                [80000,  'P'], [90000,  'P'], [100000, 'P'],
            ]],

            30 => ['Jumlah refrigerant untuk AC', 'CHASSIS_BODI', [
                [1000, 'P'], [20000, 'P'], [40000, 'P'],
                [60000, 'P'], [80000, 'P'], [100000, 'P'],
            ]],
        ];
    }
}
