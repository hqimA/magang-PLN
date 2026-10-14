@php
    $isAdmin = auth()->user()?->isAdmin() ?? false;

    $maintenanceActive = request()->routeIs('maintenance.*')
        || request()->routeIs('service-history.*')
        || request()->routeIs('service-reminder.*')
        || request()->routeIs('service-reminders.*')
        || request()->routeIs('template-komponen.*');
@endphp

<aside
    aria-label="Sidebar"
    x-data
    class="fixed inset-y-0 start-0 z-40 flex w-64 shrink-0 flex-col border-e border-slate-700/80 bg-gradient-to-b from-slate-950 via-slate-950 to-[#071827] text-slate-100 shadow-[0_25px_70px_rgba(2,6,23,0.45)] backdrop-blur-xl transition-transform duration-200 ease-in-out lg:translate-x-0"
    :class="$store.sidebar.open ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="flex h-16 shrink-0 items-center justify-between px-6">
        <a href="{{ route('dashboard') }}" class="flex items-center">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-amber-300 via-yellow-400 to-amber-500 text-slate-950 shadow-[0_10px_25px_rgba(251,191,36,0.4)]">
                <x-application-logo class="block h-5 w-auto fill-current text-current" />
            </div>
            <span class="ms-2 font-bold tracking-tight text-white">Fleet Management</span>
        </a>

        <button
            type="button"
            @click="$store.sidebar.close()"
            class="-me-2 inline-flex items-center justify-center rounded-md p-2 text-slate-400 transition duration-150 ease-in-out hover:bg-white/5 hover:text-white focus:outline-none lg:hidden"
            aria-label="Tutup menu"
        >
            <i class="hgi hgi-stroke hgi-cancel-01 text-xl"></i>
        </button>
    </div>

    <nav
        @click="if ($event.target.closest('a')) $store.sidebar.close()"
        class="flex min-h-0 flex-1 flex-col overflow-y-auto px-4 pt-2"
    >
        <a
            href="{{ route('dashboard') }}"
            class="flex items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('dashboard') ? 'border-amber-400/40 bg-gradient-to-r from-amber-300/10 to-sky-500/10 text-amber-200' : 'border-transparent text-slate-300 hover:border-white/10 hover:bg-white/5 hover:text-white' }}"
        >
            <i class="hgi hgi-stroke hgi-dashboard-square-01 h-5 w-5 shrink-0"></i>
            <span>{{ __('Dashboard') }}</span>
        </a>

        <a
            href="{{ route('kendaraan.index') }}"
            class="mt-1 flex items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('kendaraan.*') ? 'border-amber-400/40 bg-gradient-to-r from-amber-300/10 to-sky-500/10 text-amber-200' : 'border-transparent text-slate-300 hover:border-white/10 hover:bg-white/5 hover:text-white' }}"
        >
            <i class="hgi hgi-stroke hgi-car-01 h-5 w-5 shrink-0"></i>
            <span>{{ __('Kendaraan') }}</span>
        </a>

        <div x-data="{ open: @js($maintenanceActive) }">
            <button
                type="button"
                @click="open = ! open"
                class="mt-1 flex w-full items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-medium transition duration-150 ease-in-out {{ $maintenanceActive ? 'border-amber-400/40 bg-gradient-to-r from-amber-300/10 to-sky-500/10 text-amber-200' : 'border-transparent text-slate-300 hover:border-white/10 hover:bg-white/5 hover:text-white' }}"
            >
                <i class="hgi hgi-stroke hgi-wrench-01 h-5 w-5 shrink-0"></i>
                <span class="flex-1 text-start">{{ __('Maintenance') }}</span>
                <i class="hgi hgi-stroke hgi-arrow-down-01 h-4 w-4 shrink-0 transition-transform duration-200 ease-in-out" :class="open ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="open" x-transition.duration.200ms class="mt-1 space-y-1 ps-11">
                <a href="{{ route('maintenance.index', ['tab' => 'pengajuan']) }}" class="block rounded-xl px-3 py-2 text-sm transition duration-150 ease-in-out {{ request()->routeIs('maintenance.*') && request()->query('tab', 'pengajuan') === 'pengajuan' ? 'bg-amber-300/10 font-medium text-amber-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    {{ __('Pengajuan Maintenance') }}
                </a>
                <a href="{{ route('maintenance.index', ['tab' => 'aktif']) }}" class="block rounded-xl px-3 py-2 text-sm transition duration-150 ease-in-out {{ request()->routeIs('maintenance.*') && request()->query('tab') === 'aktif' ? 'bg-amber-300/10 font-medium text-amber-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    {{ __('Maintenance Aktif') }}
                </a>
                <a href="{{ route('service-history.index') }}" class="block rounded-xl px-3 py-2 text-sm transition duration-150 ease-in-out {{ request()->routeIs('service-history.*') ? 'bg-amber-300/10 font-medium text-amber-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    {{ __('Riwayat Servis') }}
                </a>
                <a href="{{ route('service-reminder.index') }}" class="flex items-center justify-between gap-2 rounded-xl px-3 py-2 text-sm transition duration-150 ease-in-out {{ request()->routeIs('service-reminder.*', 'service-reminders.*') ? 'bg-amber-300/10 font-medium text-amber-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <span>{{ __('Service Reminder') }}</span>
                    <x-service-reminder-badge />
                </a>
                @if($isAdmin)
                    <a href="{{ route('template-komponen.index') }}" class="block rounded-xl px-3 py-2 text-sm transition duration-150 ease-in-out {{ request()->routeIs('template-komponen.*') ? 'bg-amber-300/10 font-medium text-amber-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                        {{ __('Template Komponen') }}
                    </a>
                @endif
            </div>
        </div>

        <a href="{{ route('mileage.index') }}" class="mt-1 flex items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('mileage.*') ? 'border-amber-400/40 bg-gradient-to-r from-amber-300/10 to-sky-500/10 text-amber-200' : 'border-transparent text-slate-300 hover:border-white/10 hover:bg-white/5 hover:text-white' }}">
            <i class="hgi hgi-stroke hgi-dashboard-speed-01 text-xl shrink-0"></i>
            <span>{{ __('Mileage') }}</span>
        </a>

        @if($isAdmin)
            <a href="{{ route('laporan-pengeluaran.index') }}" class="mt-1 flex items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('laporan-pengeluaran.*') ? 'border-amber-400/40 bg-gradient-to-r from-amber-300/10 to-sky-500/10 text-amber-200' : 'border-transparent text-slate-300 hover:border-white/10 hover:bg-white/5 hover:text-white' }}">
                <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                <span>{{ __('Laporan') }}</span>
            </a>
        @endif

        <div class="flex-1"></div>

        @if($isAdmin)
            <a href="{{ route('template-komponen.index') }}" class="mt-1 flex items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('template-komponen.*') ? 'border-amber-400/40 bg-gradient-to-r from-amber-300/10 to-sky-500/10 text-amber-200' : 'border-transparent text-slate-300 hover:border-white/10 hover:bg-white/5 hover:text-white' }}">
                <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                </svg>
                <span>{{ __('Template Komponen') }}</span>
            </a>

            <a href="{{ route('user.index') }}" class="mt-1 flex items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('user.*') ? 'border-amber-400/40 bg-gradient-to-r from-amber-300/10 to-sky-500/10 text-amber-200' : 'border-transparent text-slate-300 hover:border-white/10 hover:bg-white/5 hover:text-white' }}">
                <i class="hgi hgi-stroke hgi-user-group h-5 w-5 shrink-0"></i>
                <span>{{ __('Manajemen User') }}</span>
            </a>
        @endif
    </nav>

    <div class="shrink-0 border-t border-slate-800/80 px-4 py-3">
        <x-dropdown align="left" width="60" placement="top" contentClasses="py-1 bg-slate-950 border border-slate-800">
            <x-slot name="trigger">
                <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-200 transition duration-150 ease-in-out hover:bg-white/5 hover:text-white">
                    <i class="hgi hgi-stroke hgi-user-circle-02 h-5 w-5 shrink-0"></i>
                    <span class="flex-1 text-start">{{ auth()->user()?->isAdmin() ? __('Admin') : __('Pengelola Kendaraan') }}</span>
                    <i class="hgi hgi-stroke hgi-arrow-down-01 h-4 w-4 shrink-0"></i>
                </button>
            </x-slot>

            <x-slot name="content">
                <x-dropdown-link :href="route('profile.edit')">
                    <span class="flex items-center gap-2">
                        <i class="hgi hgi-stroke hgi-user-circle-02 text-lg"></i>
                        <span>{{ __('Profile') }}</span>
                    </span>
                </x-dropdown-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        <span class="flex items-center gap-2">
                            <i class="hgi hgi-stroke hgi-logout-01 text-lg"></i>
                            <span>{{ __('Log Out') }}</span>
                        </span>
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</aside>

<div x-show="$store.sidebar.open" @click="$store.sidebar.close()" x-transition.opacity.duration.200ms class="fixed inset-0 z-20 bg-slate-950/60 lg:hidden" style="display: none;"></div>
