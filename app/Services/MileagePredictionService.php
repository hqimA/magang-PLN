<?php

namespace App\Services;

use App\Models\Kendaraan;

class MileagePredictionService
{
    /**
     * @return array{
     *     plat_nomor: string,
     *     kilometer_saat_ini: int,
     *     target_kilometer_servis: int,
     *     sisa_kilometer: int,
     *     rata_rata_km_harian: float,
     *     sisa_hari_prediksi: int,
     *     tanggal_prediksi_servis: string,
     *     status_prediksi: string
     * }
     */
    public function predictNextService(Kendaraan $kendaraan): array
    {
        $servisTerakhir = $kendaraan->riwayatServis()
            ->orderBy('tanggal_servis', 'desc')
            ->first();
        $kmAwal = $servisTerakhir?->kilometer_servis ?? 0;
        $tanggalAwal = $servisTerakhir?->tanggal_servis ?? $kendaraan->tanggal_pembelian;
        $hariIni = today();
        $totalHari = max(
            1,
            (int) $tanggalAwal->copy()->startOfDay()->diffInDays($hariIni->copy()->startOfDay()),
        );
        $totalKmDitempuh = $kendaraan->kilometer_terakhir - $kmAwal;
        $rataRataKmHarian = round($totalKmDitempuh / $totalHari, 2);
        $targetNextKm = $servisTerakhir?->target_kilometer_berikutnya
            ?? ($kmAwal + $kendaraan->interval_servis_km);
        $sisaKm = $targetNextKm - $kendaraan->kilometer_terakhir;

        if ($sisaKm <= 0) {
            $sisaHariPrediksi = 0;
            $tanggalPrediksiServis = $hariIni;
            $statusPrediksi = 'OVERDUE';
        } else {
            $sisaHariPrediksi = (int) ceil($sisaKm / max(1, $rataRataKmHarian));
            $tanggalPrediksiServis = $hariIni->copy()->addDays($sisaHariPrediksi);
            $statusPrediksi = $sisaHariPrediksi <= $kendaraan->threshold_servis_hari
                ? 'WARNING'
                : 'SAFE';
        }

        return [
            'plat_nomor' => $kendaraan->plat_nomor,
            'kilometer_saat_ini' => $kendaraan->kilometer_terakhir,
            'target_kilometer_servis' => $targetNextKm,
            'sisa_kilometer' => $sisaKm,
            'rata_rata_km_harian' => $rataRataKmHarian,
            'sisa_hari_prediksi' => $sisaHariPrediksi,
            'tanggal_prediksi_servis' => $tanggalPrediksiServis->toDateString(),
            'status_prediksi' => $statusPrediksi,
        ];
    }
}
