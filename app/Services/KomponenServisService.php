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

            $currentKm = (int) ($kendaraan->kilometer_terakhir ?? 0);
            $lastServiceKm = (int) ($kendaraan->riwayatServis()
                ->orderByDesc('tanggal_servis')
                ->orderByDesc('id')
                ->value('kilometer_servis') ?? 0);

            $komponens = TemplateKomponen::where('id_template', $template->id)
                ->where('is_aktif', true)
                ->with(['jadwalDetail' => fn ($q) => $q->orderBy('interval_km', 'asc')])
                ->orderBy('nomor_urut', 'asc')
                ->get();

            $insertedCount = 0;

            foreach ($komponens as $tk) {
                $existingComponent = $kendaraan->komponen()
                    ->where('id_template_komponen', $tk->id)
                    ->first();

                // Jika tidak overwrite, lewati jika nama komponen sudah ada pada kendaraan ini
                if (! $overwrite
                    && $existingComponent === null
                    && $kendaraan->komponen()->where('nama_komponen', $tk->nama_komponen)->exists()) {
                    continue;
                }

                $firstJadwal = $tk->jadwalDetail->first();
                $baselineKm = max(
                    $lastServiceKm,
                    (int) ($existingComponent?->km_terakhir_servis ?? 0),
                );
                $dueSchedule = $tk->jadwalDetail
                    ->filter(fn ($schedule): bool => $schedule->interval_km > $baselineKm
                        && $schedule->interval_km <= $currentKm)
                    ->last();
                $nextSchedule = $tk->jadwalDetail
                    ->first(fn ($schedule): bool => $schedule->interval_km > $currentKm);
                $selectedSchedule = $dueSchedule ?? $nextSchedule ?? $tk->jadwalDetail->last();
                $intervalKm = $selectedSchedule?->interval_km ?? $firstJadwal?->interval_km ?? 10000;
                $intervalBulan = $selectedSchedule?->interval_bulan ?? $firstJadwal?->interval_bulan ?? 6;
                $aksiDefault = $selectedSchedule?->jenis_aksi ?? JenisAksi::G;
                $targetKm = $dueSchedule?->interval_km ?? $nextSchedule?->interval_km;
                $targetDate = now()->addMonths($intervalBulan)->toDateString();
                $status = match (true) {
                    $dueSchedule !== null => StatusKomponen::JATUH_TEMPO,
                    $nextSchedule === null => StatusKomponen::BELUM_DATA,
                    $nextSchedule->interval_km - $currentKm <= ($kendaraan->threshold_servis_km ?? 1000) => StatusKomponen::SEGERA,
                    default => StatusKomponen::AMAN,
                };

                $componentAttributes = [
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
                    'status' => $status,
                    'is_aktif' => true,
                ];

                if ($existingComponent !== null) {
                    $existingComponent->fill($componentAttributes);

                    if ($overwrite || $existingComponent->isDirty()) {
                        $existingComponent->save();
                    }
                } else {
                    KomponenKendaraan::create($componentAttributes);
                    $insertedCount++;
                }
            }

            // Simpan id_template rujukan pada kendaraan
            $kendaraan->update(['id_template' => $template->id]);

            return $insertedCount;
        });
    }

    public function applyRecommendedTemplateToKendaraan(Kendaraan $kendaraan, bool $overwrite = false): int
    {
        $template = $kendaraan->templateRekomendasi();

        if ($template === null) {
            return 0;
        }

        return $this->applyTemplateToKendaraan($kendaraan, $template, $overwrite);
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
    public function getKomponenKendaraan(
        Kendaraan $kendaraan,
        ?int $currentKm = null,
        string $jenisPengajuan = 'RUTIN',
    ): array {
        $currentKm = $currentKm
            ?? $kendaraan->mileageTerbaru?->kilometer_akhir
            ?? $kendaraan->kilometer_terakhir
            ?? 0;

        $rekomendasiTemplate = $kendaraan->templateRekomendasi();

        if ($jenisPengajuan === 'RUTIN' && $rekomendasiTemplate !== null) {
            return $this->getKomponenTemplate($kendaraan, $rekomendasiTemplate, $currentKm);
        }

        $komponens = $kendaraan->komponen()
            ->where('is_aktif', true)
            ->orderBy('nomor_urut', 'asc')
            ->get();

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
                'form_key' => 'kendaraan-'.$komp->id,
                'id_komponen_kendaraan' => $komp->id,
                'id_template_komponen' => null,
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
                'lock_action' => false,
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

    /**
     * Berikan rekomendasi dari jadwal template yang jatuh setelah servis terakhir.
     *
     * @return array{
     *     has_komponen: bool,
     *     template_nama: string,
     *     rekomendasi_template: array{id: int, nama: string, deskripsi: ?string},
     *     current_km: int,
     *     rekomendasi_count: int,
     *     komponen: array<int, array>
     * }
     */
    private function getKomponenTemplate(
        Kendaraan $kendaraan,
        TemplateJadwalServis $template,
        int $currentKm,
    ): array {
        $lastServiceKm = (int) ($kendaraan->riwayatServis()
            ->orderByDesc('tanggal_servis')
            ->orderByDesc('id')
            ->value('kilometer_servis') ?? 0);

        $vehicleComponents = $kendaraan->komponen()
            ->where('is_aktif', true)
            ->whereNotNull('id_template_komponen')
            ->get()
            ->keyBy('id_template_komponen');

        $templateComponents = TemplateKomponen::query()
            ->where('id_template', $template->id)
            ->where('is_aktif', true)
            ->with(['jadwalDetail' => fn ($query) => $query->orderBy('interval_km')])
            ->orderBy('nomor_urut')
            ->get();

        $recommendations = [];

        foreach ($templateComponents as $templateComponent) {
            $vehicleComponent = $vehicleComponents->get($templateComponent->id);
            $serviceBaselineKm = max($lastServiceKm, (int) ($vehicleComponent?->km_terakhir_servis ?? 0));
            $dueSchedule = $templateComponent->jadwalDetail
                ->filter(fn ($schedule): bool => $schedule->interval_km > $serviceBaselineKm
                    && $schedule->interval_km <= $currentKm)
                ->last();
            $nextSchedule = $templateComponent->jadwalDetail
                ->first(fn ($schedule): bool => $schedule->interval_km > $currentKm);
            $selectedSchedule = $dueSchedule ?? $nextSchedule ?? $templateComponent->jadwalDetail->last();
            $isRecommended = $dueSchedule !== null;
            $action = $selectedSchedule?->jenis_aksi instanceof JenisAksi
                ? $selectedSchedule->jenis_aksi->value
                : $selectedSchedule?->jenis_aksi;
            $actionLabel = match ($action) {
                'G' => 'Ganti',
                'P' => 'Periksa',
                default => 'Belum diatur',
            };

            if ($isRecommended) {
                $status = 'JATUH_TEMPO';
                $description = "Jadwal {$dueSchedule->interval_km} km telah tercapai.";
            } elseif ($nextSchedule !== null) {
                $status = 'AMAN';
                $description = "Jadwal berikutnya {$nextSchedule->interval_km} km ({$actionLabel}).";
            } else {
                $status = 'BELUM_DATA';
                $description = 'Tidak ada jadwal kilometer berikutnya pada template.';
            }

            $category = $templateComponent->kategori instanceof \BackedEnum
                ? $templateComponent->kategori->value
                : (string) $templateComponent->kategori;

            $recommendations[] = [
                'form_key' => 'template-'.$templateComponent->id,
                'id_komponen_kendaraan' => $vehicleComponent?->id,
                'id_template_komponen' => $templateComponent->id,
                'nomor_urut' => $templateComponent->nomor_urut,
                'nama_komponen' => $templateComponent->nama_komponen,
                'kategori' => $category,
                'kategori_label' => str_replace('_', ' ', ucwords(strtolower($category), '_')),
                'interval_km' => $nextSchedule?->interval_km,
                'interval_bulan' => $nextSchedule?->interval_bulan,
                'status' => $status,
                'status_label' => [
                    'JATUH_TEMPO' => 'Jadwal Tercapai',
                    'AMAN' => 'Belum Jatuh Tempo',
                    'BELUM_DATA' => 'Belum Ada Jadwal',
                ][$status],
                'aksi_rekomendasi' => $action,
                'aksi_label' => $actionLabel,
                'km_jatuh_tempo' => $dueSchedule?->interval_km ?? $nextSchedule?->interval_km,
                'sisa_km' => $nextSchedule !== null ? $nextSchedule->interval_km - $currentKm : null,
                'keterangan' => $description,
                'is_rekomendasi' => $isRecommended,
                'lock_action' => true,
            ];
        }

        return [
            'has_komponen' => $templateComponents->isNotEmpty(),
            'template_nama' => $template->nama,
            'rekomendasi_template' => [
                'id' => $template->id,
                'nama' => $template->nama,
                'deskripsi' => $template->deskripsi,
            ],
            'current_km' => $currentKm,
            'rekomendasi_count' => count(array_filter($recommendations, fn (array $item): bool => $item['is_rekomendasi'])),
            'komponen' => $recommendations,
        ];
    }

    public function createKomponenKendaraanFromTemplate(
        Kendaraan $kendaraan,
        int $idTemplateKomponen,
        int $currentKm,
    ): KomponenKendaraan {
        $template = $kendaraan->templateRekomendasi();

        abort_if($template === null, 422, 'Template servis kendaraan tidak tersedia.');

        $templateComponent = TemplateKomponen::query()
            ->where('id_template', $template->id)
            ->where('is_aktif', true)
            ->with(['jadwalDetail' => fn ($query) => $query->orderBy('interval_km')])
            ->findOrFail($idTemplateKomponen);

        $component = KomponenKendaraan::query()->firstOrNew([
            'id_kendaraan' => $kendaraan->id,
            'id_template_komponen' => $templateComponent->id,
        ]);

        if (! $component->exists) {
            $nextSchedule = $templateComponent->jadwalDetail
                ->first(fn ($schedule): bool => $schedule->interval_km > $currentKm);
            $firstSchedule = $templateComponent->jadwalDetail->first();
            $defaultSchedule = $nextSchedule ?? $templateComponent->jadwalDetail->last();
            abort_if($defaultSchedule === null, 422, 'Komponen template belum memiliki jadwal servis.');
            $action = $defaultSchedule->jenis_aksi;

            $component->fill([
                'nama_komponen' => $templateComponent->nama_komponen,
                'kategori' => $templateComponent->kategori,
                'nomor_urut' => $templateComponent->nomor_urut,
                'interval_km' => $firstSchedule?->interval_km,
                'interval_bulan' => $firstSchedule?->interval_bulan,
                'jenis_aksi_default' => $action,
                'km_jatuh_tempo' => $nextSchedule?->interval_km,
                'tgl_jatuh_tempo' => $nextSchedule !== null
                    ? now()->addMonths($nextSchedule->interval_bulan)->toDateString()
                    : null,
                'status' => StatusKomponen::AMAN,
                'is_aktif' => true,
            ]);
        } else {
            $component->is_aktif = true;
        }

        $component->save();
        $kendaraan->update(['id_template' => $template->id]);

        return $component;
    }
}
