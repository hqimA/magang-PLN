@php
    $isAdmin = auth()->user()?->isAdmin() ?? false;
    $maintenanceActive = request()->routeIs('maintenance.*')
        || request()->routeIs('service-history.*')
        || request()->routeIs('service-reminder.*')
        || request()->routeIs('service-reminders.*')
        || request()->routeIs('template-komponen.*');
@endphp

{{-- Sidebar Navigation --}}
<aside
    aria-label="Sidebar"
    x-data
    class="fixed inset-y-0 start-0 z-40 flex w-64 shrink-0 flex-col border-e border-gray-100 bg-white transition-transform duration-200 ease-in-out lg:translate-x-0"
    :class="$store.sidebar.open ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="flex h-16 shrink-0 items-center justify-between px-6">
        <a href="{{ route('dashboard') }}" class="flex items-center">
            <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
            <span class="ms-2 font-semibold tracking-tight text-gray-900">Fleet Management</span>
        </a>

        <button type="button" @click="$store.sidebar.close()" class="-me-2 inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-600 focus:outline-none lg:hidden" aria-label="Tutup menu">
            <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav @click="if ($event.target.closest('a')) $store.sidebar.close()" class="flex min-h-0 flex-1 flex-col overflow-y-auto px-4 pt-2">
        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
            class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
            </svg>
            <span>{{ __('Dashboard') }}</span>
        </a>

        {{-- Kendaraan --}}
        <a href="{{ route('kendaraan.index') }}"
            class="mt-1 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('kendaraan.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.06 2.06 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
            </svg>
            <span>{{ __('Kendaraan') }}</span>
        </a>

        {{-- Maintenance (submenu) --}}
        <div x-data="{ open: @js($maintenanceActive) }">
            <button type="button" @click="open = ! open"
                class="mt-1 flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out {{ $maintenanceActive ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.658 5.658a3.75 3.75 0 01-5.303-5.303l5.657-5.657m7.878-1.707a3.75 3.75 0 105.303 5.303l-5.657-5.657m-7.878 1.707L5.742 10.5m4.678 4.678L14.25 10.5m-4.83 4.83a3.75 3.75 0 105.303-5.303" />
                </svg>
                <span class="flex-1 text-start">{{ __('Maintenance') }}</span>
                <svg class="h-4 w-4 shrink-0 transition-transform duration-200 ease-in-out" :class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>

            <div x-show="open" x-transition.duration.200ms class="mt-1 space-y-1 ps-11">
                <a href="{{ route('maintenance.index', ['tab' => 'pengajuan']) }}"
                    class="block rounded-md px-3 py-2 text-sm transition duration-150 ease-in-out {{ request()->routeIs('maintenance.*') && request()->query('tab', 'pengajuan') === 'pengajuan' ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    {{ __('Pengajuan Maintenance') }}
                </a>
                <a href="{{ route('maintenance.index', ['tab' => 'aktif']) }}"
                    class="block rounded-md px-3 py-2 text-sm transition duration-150 ease-in-out {{ request()->routeIs('maintenance.*') && request()->query('tab') === 'aktif' ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    {{ __('Maintenance Aktif') }}
                </a>
                <a href="{{ route('service-history.index') }}"
                    class="block rounded-md px-3 py-2 text-sm transition duration-150 ease-in-out {{ request()->routeIs('service-history.*') ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    {{ __('Riwayat Servis') }}
                </a>
                <a href="{{ route('service-reminder.index') }}"
                    class="flex items-center justify-between gap-2 rounded-md px-3 py-2 text-sm transition duration-150 ease-in-out {{ request()->routeIs('service-reminder.*', 'service-reminders.*') ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    <span>{{ __('Service Reminder') }}</span>
                    <x-service-reminder-badge />
                </a>
                @if($isAdmin)
                    <a href="{{ route('template-komponen.index') }}"
                        class="block rounded-md px-3 py-2 text-sm transition duration-150 ease-in-out {{ request()->routeIs('template-komponen.*') ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        {{ __('Template Komponen') }}
                    </a>
                @endif
            </div>
        </div>

        {{-- Mileage --}}
        <a href="{{ route('mileage.index') }}"
            class="mt-1 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('mileage.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4.5 2.25m4.5-2.25a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ __('Mileage') }}</span>
        </a>

        <div class="flex-1"></div>

        @if($isAdmin)
            {{-- Template Komponen (khusus Admin) --}}
            <a href="{{ route('template-komponen.index') }}"
                class="mt-1 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('template-komponen.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                </svg>
                <span>{{ __('Template Komponen') }}</span>
            </a>

            {{-- Laporan (khusus Admin) --}}
            <a href="{{ route('laporan-pengeluaran.index') }}"
                class="mt-1 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('laporan-pengeluaran.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                <span>{{ __('Laporan') }}</span>
            </a>

            {{-- Manajemen User (khusus Admin) --}}
            <a href="{{ route('user.index') }}"
                class="mt-1 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('user.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                </svg>
                <span>{{ __('Manajemen User') }}</span>
            </a>
        @endif
    </nav>

    <div class="shrink-0 border-t border-gray-100 px-4 py-3">
        {{-- Administrator Fleet (dropdown existing, isi dipertahankan) --}}
        <x-dropdown align="left" width="60" placement="top" contentClasses="py-1 bg-white">
            <x-slot name="trigger">
                <button type="button" class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-gray-600 transition duration-150 ease-in-out hover:bg-gray-50 hover:text-gray-900">
                    <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    <span class="flex-1 text-start">{{ __('Administrator Fleet') }}</span>
                    <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </x-slot>

            <x-slot name="content">
                <x-dropdown-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-dropdown-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-dropdown-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</aside>

{{-- Mobile backdrop --}}
<div x-show="$store.sidebar.open" @click="$store.sidebar.close()" x-transition.opacity.duration.200ms
    class="fixed inset-0 z-20 bg-gray-900/50 lg:hidden" style="display: none;"></div>