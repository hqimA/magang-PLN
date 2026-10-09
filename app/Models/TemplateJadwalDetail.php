<?php

namespace App\Models;

use App\Enums\JenisAksi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateJadwalDetail extends Model
{
    use HasFactory;

    protected $table = 'template_jadwal_detail';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_template_komponen',
        'interval_km',
        'interval_bulan',
        'jenis_aksi',
    ];

    protected function casts(): array
    {
        return [
            'interval_km' => 'integer',
            'interval_bulan' => 'integer',
            'dibuat_pada' => 'datetime',
            'jenis_aksi' => JenisAksi::class,
        ];
    }

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function komponen(): BelongsTo
    {
        return $this->belongsTo(TemplateKomponen::class, 'id_template_komponen');
    }
}
