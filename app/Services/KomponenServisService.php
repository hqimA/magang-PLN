<?php

namespace App\Services;

use App\Enums\JenisAksi;
use App\Enums\StatusKomponen;
use App\Models\Kendaraan;
use App\Models\KomponenKendaraan;
use App\Models\TemplateJadwalServis;
use App\Models\TemplateKomponen;
use Illuminate\Support\Facades\DB;

class KomponenServisService
{
    /**
     * Terapkan master template ke kendaraan (kloning seluruh komponen template menjadi komponen milik kendaraan).
     */
    public function applyTemplateToKendaraan(Kendaraan $kendaraan, TemplateJadwalServis $template, bool $overwrite = false): int
    {
        return DB::transaction(function () use ($kendaraan, $template, $overwrite) {
            if ($overwrite) {
                // Hapus komponen lama kendaraan ini yang belum terkait dengan riwayat servis
                $kendaraan->komponen()->doesntHave('detailKomponenServis')->delete();
            }

            $currentKm = $kendaraan->mileageTerbaru?->kilometer_akhir
                ?? $kendaraan->kilometer_terakhir
                ?? 0;

            $komponens = TemplateKomponen::where('id_template', $template->id)
                ->where('is_aktif', true)
                ->with(['jadwalDetail' => fn ($q) => $q->orderBy('interval_km', 'asc')])
                ->orderBy('nomor_urut', 'asc')
                ->get();

            $insertedCount = 0;

            foreach ($komponens as $tk) {
                // Jika tidak overwrite, lewati jika nama komponen sudah ada pada kendaraan ini
                if (! $overwrite && $kendaraan->komponen()->where('nama_komponen', $tk->nama_komponen)->exists()) {
                    continue;
                }

                $firstJadwal = $tk->jadwalDetail->first();
                $intervalKm = $firstJadwal?->interval_km ?? 10000;
                $intervalBulan = $firstJadwal?->interval_bulan ?? 6;
                $aksiDefault = $firstJadwal?->jenis_aksi instanceof JenisAksi
                    ? $firstJadwal->jenis_aksi
                    : ($firstJadwal?->jenis_aksi ?? JenisAksi::G);

                $targetKm = $currentKm + $intervalKm;
                $targetDate = now()->addMonths($intervalBulan)->toDateString();

                KomponenKendaraan::create([
                    'id_kendaraan' => $kendaraan->id,
                    'id_template_komponen' => $tk->id,
                    'nama_komponen' => $tk->nama_komponen,
                    'kategori' => $tk->kategori,
                    'nomor_urut' => $tk->nomor_urut,
                    'interval_km' => $intervalKm,
                    'interval_bulan' => $intervalBulan,
                    'jenis_aksi_default' => $aksiDefault,
                    'km_terakhir_servis' => null,
                    'tgl_terakhir_servis' => null,
                    'aksi_terakhir' => null,
                    'km_jatuh_tempo' => $targetKm,
                    'tgl_jatuh_tempo' => $targetDate,
                    'status' => StatusKomponen::AMAN,
                    'is_aktif' => true,
                ]);

                $insertedCount++;
            }

            // Simpan id_template rujukan pada kendaraan
            $kendaraan->update(['id_template' => $template->id]);

            return $insertedCount;
        });
    }

    /**
     * Dapatkan daftar komponen spesifik milik kendaraan beserta status kondisinya.
     *
     * @return array{
     *     has_komponen: bool,
     *     template_nama: ?string,
     *     rekomendasi_template: ?array{id: int, nama: string, deskripsi: ?string},
     *     current_km: int,
     *     rekomendasi_count: int,
     *     komponen: array<int, array>
     * }
     */
    public function getKomponenKendaraan(Kendaraan $kendaraan, ?int $currentKm = null): array
    {
        $currentKm = $currentKm
            ?? $kendaraan->mileageTerbaru?->kilometer_akhir
            ?? $kendaraan->kilometer_terakhir
            ?? 0;

        $komponens = $kendaraan->komponen()
            ->where('is_aktif', true)
            ->orderBy('nomor_urut', 'asc')
            ->get();

        $rekomendasiTemplate = $kendaraan->templateRekomendasi();

        // Jika kendaraan belum punya komponen sendiri sama sekali
        if ($komponens->isEmpty()) {
            return [
                'has_komponen' => false,
                'template_nama' => $kendaraan->template?->nama,
                'rekomendasi_template' => $rekomendasiTemplate ? [
                    'id' => $rekomendasiTemplate->id,
                    'nama' => $rekomendasiTemplate->nama,
                    'deskripsi' => $rekomendasiTemplate->deskripsi,
                ] : null,
                'current_km' => $currentKm,
                'rekomendasi_count' => 0,
                'komponen' => [],
            ];
        }

        $thresholdKm = $kendaraan->threshold_servis_km ?? 1000;
        $result = [];
        $rekomendasiCount = 0;

        foreach ($komponens as $komp) {
            $targetKm = $komp->km_jatuh_tempo;
            $aksi = $komp->jenis_aksi_default instanceof JenisAksi
                ? $komp->jenis_aksi_default->value
                : ($komp->jenis_aksi_default ?? 'G');

            $statusStr = 'AMAN';
            $keterangan = 'Kondisi baik';

            if ($targetKm !== null) {
                $sisaKm = $targetKm - $currentKm;
                if ($sisaKm <= 0) {
                    $statusStr = 'JATUH_TEMPO';
                    $keterangan = 'Terlewat '.number_format(abs($sisaKm), 0, ',', '.').' km dari target ('.number_format($targetKm, 0, ',', '.').' km)';
                } elseif ($sisaKm <= $thresholdKm) {
                    $statusStr = 'SEGERA';
                    $keterangan = 'Mendekati target dalam '.number_format($sisaKm, 0, ',', '.').' km ('.number_format($targetKm, 0, ',', '.').' km)';
                } else {
                    $statusStr = 'AMAN';
                    $keterangan = 'Target servis di '.number_format($targetKm, 0, ',', '.').' km (sisa '.number_format($sisaKm, 0, ',', '.').' km)';
                }
            } else {
                $statusStr = 'BELUM_DATA';
                $keterangan = 'Interval belum ditentukan';
            }

            $isRekomendasi = in_array($statusStr, ['JATUH_TEMPO', 'SEGERA'], true);
            if ($isRekomendasi) {
                $rekomendasiCount++;
            }

            $kategoriRaw = $komp->kategori instanceof \BackedEnum
                ? $komp->kategori->value
                : (string) $komp->kategori;

            $statusLabels = [
                'JATUH_TEMPO' => 'Jatuh Tempo',
                'SEGERA' => 'Perlu Segera',
                'AMAN' => 'Kondisi Baik',
                'BELUM_DATA' => 'Belum Ada Data',
            ];

            $result[] = [
                'id_komponen_kendaraan' => $komp->id,
                'nomor_urut' => $komp->nomor_urut,
                'nama_komponen' => $komp->nama_komponen,
                'kategori' => $kategoriRaw,
                'kategori_label' => str_replace('_', ' ', ucwords(strtolower($kategoriRaw), '_')),
                'interval_km' => $komp->interval_km,
                'interval_bulan' => $komp->interval_bulan,
                'status' => $statusStr,
                'status_label' => $statusLabels[$statusStr] ?? $statusStr,
                'aksi_rekomendasi' => $aksi,
                'aksi_label' => $aksi === 'G' ? 'Ganti' : 'Periksa',
                'km_jatuh_tempo' => $targetKm,
                'sisa_km' => $targetKm !== null ? ($targetKm - $currentKm) : null,
                'keterangan' => $keterangan,
                'is_rekomendasi' => $isRekomendasi,
            ];
        }

        return [
            'has_komponen' => true,
            'template_nama' => $kendaraan->template?->nama,
            'rekomendasi_template' => $rekomendasiTemplate ? [
                'id' => $rekomendasiTemplate->id,
                'nama' => $rekomendasiTemplate->nama,
                'deskripsi' => $rekomendasiTemplate->deskripsi,
            ] : null,
            'current_km' => $currentKm,
            'rekomendasi_count' => $rekomendasiCount,
            'komponen' => $result,
        ];
    }
}
