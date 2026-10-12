<?php

namespace App\Models;

use App\Enums\JenisAksi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailKomponenServis extends Model
{
    use HasFactory;

    protected $table = 'detail_komponen_servis';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_riwayat_servis',
        'id_komponen_kendaraan',
        'jenis_aksi_dilakukan',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'dibuat_pada' => 'datetime',
            'jenis_aksi_dilakukan' => JenisAksi::class,
        ];
    }

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function riwayatServis(): BelongsTo
    {
        return $this->belongsTo(RiwayatServis::class, 'id_riwayat_servis');
    }

    public function komponenKendaraan(): BelongsTo
    {
        return $this->belongsTo(KomponenKendaraan::class, 'id_komponen_kendaraan');
    }
}
