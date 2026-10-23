<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi';

    public $timestamps = false;

    protected $fillable = [
        'id_pengguna',
        'judul',
        'pesan',
        'sudah_dibaca',
        'tipe_referensi',
        'dibuat_pada',
    ];

    protected function casts(): array
    {
        return [
            'sudah_dibaca' => 'boolean',
            'dibuat_pada' => 'datetime',
        ];
    }

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }
}
