<?php

namespace App\View\Components;

use App\Models\Notifikasi;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class MaintenanceRequestBadge extends Component
{
    public int $count = 0;

    public function __construct()
    {
        $user = Auth::user();

        if (! $user?->isAdmin()) {
            return;
        }

        $this->count = Notifikasi::query()
            ->where('id_pengguna', $user->id)
            ->where('tipe_referensi', 'like', 'pengajuan_servis:%')
            ->where('sudah_dibaca', false)
            ->count();
    }

    public function render(): View|Closure|string
    {
        return view('components.maintenance-request-badge');
    }
}
