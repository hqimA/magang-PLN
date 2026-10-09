<?php

namespace App\Models;

use App\Enums\KategoriKomponen;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateKomponen extends Model
{
    use HasFactory;

    protected $table = 'template_komponen';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_template',
        'nomor_urut',
        'nama_komponen',
        'kategori',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'nomor_urut' => 'integer',
            'is_aktif' => 'boolean',
            'dibuat_pada' => 'datetime',
            'kategori' => KategoriKomponen::class,
        ];
    }

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateJadwalServis::class, 'id_template');
    }

    public function jadwalDetail(): HasMany
    {
        return $this->hasMany(TemplateJadwalDetail::class, 'id_template_komponen');
    }

    public function statusKendaraan(): HasMany
    {
        return $this->hasMany(StatusKomponenKendaraan::class, 'id_template_komponen');
    }

    public function komponenKendaraan(): HasMany
    {
        return $this->hasMany(KomponenKendaraan::class, 'id_template_komponen');
    }
}
