<?php

namespace App\View\Components;

use App\Models\PengajuanServis;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class ActiveMaintenanceBadge extends Component
{
    public int $count = 0;

    public function __construct()
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $this->count = PengajuanServis::query()
            ->where('status_persetujuan', 'DISETUJUI')
            ->whereDoesntHave('riwayatServis')
            ->whereHas('kendaraan', fn ($query) => $query->visibleTo($user))
            ->count();
    }

    public function render(): View|Closure|string
    {
        return view('components.active-maintenance-badge');
    }
}
