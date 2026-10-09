<?php

namespace App\Models;

use App\Enums\JenisBbmTemplate;
use App\Enums\TransmisiTemplate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateJadwalServis extends Model
{
    use HasFactory;

    protected $table = 'template_jadwal_servis';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = null;

    protected $fillable = [
        'nama',
        'deskripsi',
        'jenis_bbm',
        'transmisi',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
            'dibuat_pada' => 'datetime',
            'jenis_bbm' => JenisBbmTemplate::class,
            'transmisi' => TransmisiTemplate::class,
        ];
    }

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function komponen(): HasMany
    {
        return $this->hasMany(TemplateKomponen::class, 'id_template');
    }

    public function kendaraan(): HasMany
    {
        return $this->hasMany(Kendaraan::class, 'id_template');
    }
}
