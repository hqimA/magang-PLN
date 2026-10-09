<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'peran'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function kendaraan(): HasMany
    {
        return $this->hasMany(Kendaraan::class, 'id_pengelola');
    }

    public function laporanKerusakan(): HasMany
    {
        return $this->hasMany(LaporanKerusakan::class, 'id_pelapor');
    }

    public function pengajuanServis(): HasMany
    {
        return $this->hasMany(PengajuanServis::class, 'id_pengaju');
    }

    public function pengajuanDisetujui(): HasMany
    {
        return $this->hasMany(PengajuanServis::class, 'id_disetujui_oleh');
    }

    public function riwayatServisDibuat(): HasMany
    {
        return $this->hasMany(RiwayatServis::class, 'id_pembuat');
    }

    public function riwayatPerbaikanDibuat(): HasMany
    {
        return $this->hasMany(RiwayatPerbaikan::class, 'id_pembuat');
    }

    public function odometerLogs(): HasMany
    {
        return $this->hasMany(OdometerLog::class, 'id_penginput');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'id_pengguna');
    }

    public function mileage(): HasMany
    {
        return $this->hasMany(Mileage::class, 'id_pencatat');
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->peran === 'ADMIN';
    }

    public function isPengelola(): bool
    {
        return $this->peran === 'PENGELOLA';
    }

    public function isTeknisi(): bool
    {
        return $this->peran === 'TEKNISI' || $this->isPengelola();
    }

    // ── Casts ────────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
