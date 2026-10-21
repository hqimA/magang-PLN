<?php

namespace App\Models;

use App\Enums\UnitPln;
use Database\Factories\KendaraanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[Fillable([
    'plat_nomor',
    'merk_tipe',
    'tahun_pembuatan',
    'transmisi',
    'jenis_bbm',
    'kategori_penggunaan',
    'unit_pln',
    'id_template',          // ditambah: FK ke template_jadwal_servis
    'kilometer_terakhir',
    'interval_servis_km',
    'interval_servis_bulan',
    'threshold_servis_km',
    'threshold_servis_hari',
    'tanggal_stnk_berlaku_sampai',
    'tanggal_kir_berlaku_sampai',
    'tanggal_pembelian',
    'status_perawatan',
    'id_pengelola',
])]
class Kendaraan extends Model
{
    /** @use HasFactory<KendaraanFactory> */
    use HasFactory;

    protected $table = 'kendaraan';

    public $timestamps = false;

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function pengelola(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengelola');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->unit_pln === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('unit_pln', $user->unit_pln->value);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(KendaraanFoto::class, 'id_kendaraan')
            ->orderByDesc('is_utama')
            ->orderBy('urutan')
            ->orderBy('id');
    }

    public function fotoUtama(): HasOne
    {
        return $this->hasOne(KendaraanFoto::class, 'id_kendaraan')->where('is_utama', true)->oldestOfMany('id');
    }

    /**
     * Dapatkan foto cover (foto utama, atau foto urutan pertama sebagai fallback).
     */
    public function fotoCover(): ?KendaraanFoto
    {
        if ($this->relationLoaded('fotoUtama') && $this->fotoUtama) {
            return $this->fotoUtama;
        }

        if ($this->relationLoaded('fotos')) {
            return $this->fotos->firstWhere('is_utama', true) ?? $this->fotos->first();
        }

        return $this->fotoUtama ?? $this->fotos()->first();

        return $this->fotoUtama ?? $this->fotos()->first();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateJadwalServis::class, 'id_template');
    }

    public function laporanKerusakan(): HasMany
    {
        return $this->hasMany(LaporanKerusakan::class, 'id_kendaraan');
    }

    public function pengajuanServis(): HasMany
    {
        return $this->hasMany(PengajuanServis::class, 'id_kendaraan');
    }

    public function riwayatServis(): HasMany
    {
        return $this->hasMany(RiwayatServis::class, 'id_kendaraan');
    }

    public function riwayatServisTerbaru(): HasOne
    {
        return $this->hasOne(RiwayatServis::class, 'id_kendaraan')->latestOfMany('tanggal_servis');
    }

    public function odometerLogs(): HasMany
    {
        return $this->hasMany(OdometerLog::class, 'id_kendaraan');
    }

    public function mileages(): HasMany
    {
        return $this->hasMany(Mileage::class, 'id_kendaraan');
    }

    public function mileageTerbaru(): HasOne
    {
        return $this->hasOne(Mileage::class, 'id_kendaraan')->latestOfMany('id');
    }

    public function komponen(): HasMany
    {
        return $this->hasMany(KomponenKendaraan::class, 'id_kendaraan');
    }

    /**
     * Cari rekomendasi master template berdasarkan jenis BBM dan transmisi kendaraan ini.
     */
    public function templateRekomendasi(): ?TemplateJadwalServis
    {
        $bbm = $this->jenis_bbm;
        $trans = $this->transmisi;
        $bbmValues = in_array($bbm, ['SOLAR', 'DIESEL'], true)
            ? ['SOLAR', 'DIESEL']
            : [$bbm];

        return TemplateJadwalServis::where('is_aktif', true)
            ->where(function ($q) use ($bbmValues) {
                $q->whereIn('jenis_bbm', $bbmValues)
                    ->orWhere('jenis_bbm', 'SEMUA');
            })
            ->where(function ($q) use ($trans) {
                $q->where('transmisi', $trans)
                    ->orWhere('transmisi', 'SEMUA');
            })
            ->orderByRaw('CASE WHEN jenis_bbm = ? THEN 0 WHEN jenis_bbm = ? THEN 2 ELSE 1 END', [$bbm, 'SEMUA'])
            ->orderByRaw('CASE WHEN transmisi = ? THEN 0 ELSE 1 END', [$trans])
            ->orderBy('id')
            ->first();
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    /**
     * Catatan odometer terbaru, dipakai sebagai kilometer terakhir.
     */
    public function mileageTerakhir(): ?Mileage
    {
        return $this->mileages()->orderByDesc('kilometer_akhir')->orderByDesc('tanggal_perjalanan')->first();
    }

    /**
     * @return array{status: string, alasan: string, kilometer_saat_ini: int, target_kilometer: int, sisa_kilometer: int, tanggal_acuan: string, tanggal_target: string, sisa_hari: int}
     */
    public function serviceReminder(?Carbon $today = null): array
    {
        $today ??= today();
        $latestService = $this->relationLoaded('riwayatServisTerbaru')
            ? $this->riwayatServisTerbaru
            : $this->riwayatServisTerbaru()->first();
        $referenceDate = $latestService?->tanggal_servis ?? $this->tanggal_pembelian;
        $referenceKilometer = $latestService?->kilometer_servis ?? 0;
        $targetKilometer = $latestService?->target_kilometer_berikutnya
            ?? $referenceKilometer + $this->interval_servis_km;
        $targetDate = $referenceDate->copy()->addMonths($this->interval_servis_bulan);
        $remainingKilometers = $targetKilometer - $this->kilometer_terakhir;
        $remainingDays = $today->copy()->startOfDay()->diffInDays($targetDate->startOfDay(), false);

        if ($remainingKilometers <= 0) {
            $status = 'TERLAMBAT_SERVIS';
            $reason = 'Jadwal servis terlewati sebanyak '.number_format(abs($remainingKilometers)).' km.';
        } elseif ($remainingKilometers <= $this->threshold_servis_km) {
            $status = 'MENDEKATI_SERVIS';
            $reason = 'Sisa jarak servis tinggal '.number_format($remainingKilometers).' km.';
        } else {
            $status = 'TERJADWAL';
            $reason = 'Kendaraan belum mendekati batas kilometer servis.';
        }

        return [
            'status' => $status,
            'alasan' => $reason,
            'kilometer_saat_ini' => $this->kilometer_terakhir,
            'target_kilometer' => $targetKilometer,
            'sisa_kilometer' => $remainingKilometers,
            'tanggal_acuan' => $referenceDate->toDateString(),
            'tanggal_target' => $targetDate->toDateString(),
            'sisa_hari' => $remainingDays,
        ];
    }

    // ── Casts ────────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'tahun_pembuatan' => 'integer',
            'unit_pln' => UnitPln::class,
            'kilometer_terakhir' => 'integer',
            'interval_servis_km' => 'integer',
            'interval_servis_bulan' => 'integer',
            'threshold_servis_km' => 'integer',
            'threshold_servis_hari' => 'integer',
            'tanggal_pembelian' => 'date',
            'tanggal_stnk_berlaku_sampai' => 'date',
            'tanggal_kir_berlaku_sampai' => 'date',
            'dibuat_pada' => 'datetime',
        ];
    }
}
