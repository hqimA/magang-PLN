<?php

namespace App\Models;

use Database\Factories\KendaraanFotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id_kendaraan',
    'path',
    'is_utama',
    'urutan',
])]
class KendaraanFoto extends Model
{
    /** @use HasFactory<KendaraanFotoFactory> */
    use HasFactory;

    protected $table = 'kendaraan_foto';

    public $timestamps = false;

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(Kendaraan::class, 'id_kendaraan');
    }

    // ── Casts ────────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'is_utama' => 'boolean',
            'diunggah_pada' => 'datetime',
        ];
    }
}
