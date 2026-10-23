<?php

namespace App\Models;

use App\Enums\JenisAksi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanKomponen extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_komponen';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_pengajuan',
        'id_komponen_kendaraan',
        'jenis_aksi',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'dibuat_pada' => 'datetime',
            'jenis_aksi' => JenisAksi::class,
        ];
    }

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function pengajuanServis(): BelongsTo
    {
        return $this->belongsTo(PengajuanServis::class, 'id_pengajuan');
    }

    public function komponenKendaraan(): BelongsTo
    {
        return $this->belongsTo(KomponenKendaraan::class, 'id_komponen_kendaraan');
    }
}
