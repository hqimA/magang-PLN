<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PengajuanServis extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_servis';

    public $timestamps = false;

    protected $fillable = [
        'id_kendaraan',
        'id_pengaju',
        'jenis_pengajuan',
        'deskripsi_keluhan',
        'estimasi_biaya',
        'kilometer_pengajuan',
        'status_persetujuan',
        'alasan_penolakan',
        'id_disetujui_oleh',
        'dibuat_pada',
    ];

    protected function casts(): array
    {
        return [
            'estimasi_biaya' => 'decimal:2',
            'kilometer_pengajuan' => 'integer',
            'dibuat_pada' => 'datetime',
        ];
    }

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(Kendaraan::class, 'id_kendaraan');
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengaju');
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_disetujui_oleh');
    }

    public function riwayatServis(): HasOne
    {
        return $this->hasOne(RiwayatServis::class, 'id_pengajuan');
    }

    public function komponen(): HasMany
    {
        return $this->hasMany(PengajuanKomponen::class, 'id_pengajuan');
    }
}
