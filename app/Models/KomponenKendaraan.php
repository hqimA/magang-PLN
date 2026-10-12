<?php

namespace App\Models;

use App\Enums\JenisAksi;
use App\Enums\KategoriKomponen;
use App\Enums\StatusKomponen;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KomponenKendaraan extends Model
{
    use HasFactory;

    protected $table = 'komponen_kendaraan';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_kendaraan',
        'id_template_komponen',
        'nama_komponen',
        'kategori',
        'nomor_urut',
        'interval_km',
        'interval_bulan',
        'jenis_aksi_default',
        'km_terakhir_servis',
        'tgl_terakhir_servis',
        'aksi_terakhir',
        'km_jatuh_tempo',
        'tgl_jatuh_tempo',
        'status',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'nomor_urut' => 'integer',
            'interval_km' => 'integer',
            'interval_bulan' => 'integer',
            'km_terakhir_servis' => 'integer',
            'tgl_terakhir_servis' => 'date',
            'km_jatuh_tempo' => 'integer',
            'tgl_jatuh_tempo' => 'date',
            'is_aktif' => 'boolean',
            'dibuat_pada' => 'datetime',
            'kategori' => KategoriKomponen::class,
            'jenis_aksi_default' => JenisAksi::class,
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

    public function pengajuanKomponen(): HasMany
    {
        return $this->hasMany(PengajuanKomponen::class, 'id_komponen_kendaraan');
    }

    public function detailKomponenServis(): HasMany
    {
        return $this->hasMany(DetailKomponenServis::class, 'id_komponen_kendaraan');
    }
}
