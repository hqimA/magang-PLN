<?php

namespace App\Models;

use App\Enums\JenisAksi;
use App\Enums\StatusKomponen;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusKomponenKendaraan extends Model
{
    use HasFactory;

    protected $table = 'status_komponen_kendaraan';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_kendaraan',
        'id_template_komponen',
        'km_terakhir_servis',
        'tgl_terakhir_servis',
        'aksi_terakhir',
        'km_jatuh_tempo',
        'tgl_jatuh_tempo',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'km_terakhir_servis' => 'integer',
            'tgl_terakhir_servis' => 'date',
            'km_jatuh_tempo' => 'integer',
            'tgl_jatuh_tempo' => 'date',
            'dibuat_pada' => 'datetime',
            'aksi_terakhir' => JenisAksi::class,
            'status' => StatusKomponen::class,
        ];
    }

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(Kendaraan::class, 'id_kendaraan');
    }

    public function templateKomponen(): BelongsTo
    {
        return $this->belongsTo(TemplateKomponen::class, 'id_template_komponen');
    }
}
