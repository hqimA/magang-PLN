```blade
@php
    $isAdmin = auth()->user()?->isAdmin() ?? false;

    $maintenanceActive = request()->routeIs('maintenance.*')
        || request()->routeIs('service-history.*')
        || request()->routeIs('service-reminder.*')
        || request()->routeIs('service-reminders.*');
@endphp

{{-- Sidebar Navigation --}}
<aside
    aria-label="Sidebar"
    x-data
    class="fixed inset-y-0 start-0 z-40 flex w-64 shrink-0 flex-col border-e border-gray-100 bg-white transition-transform duration-200 ease-in-out lg:translate-x-0"
    :class="$store.sidebar.open ? 'translate-x-0' : '-translate-x-full'"
>
    {{-- Sidebar Header --}}
    <div class="flex h-16 shrink-0 items-center justify-between px-6">
        <a href="{{ route('dashboard') }}" class="flex items-center">
            <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
            <span class="ms-2 font-semibold tracking-tight text-gray-900">
                Fleet Management
            </span>
        </a>

        {{-- Close sidebar mobile --}}
        <button
            type="button"
            @click="$store.sidebar.close()"
            class="-me-2 inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-600 focus:outline-none lg:hidden"
            aria-label="Tutup menu"
        >
            <i class="hgi hgi-stroke hgi-cancel-01 text-xl"></i>
        </button>
    </div>

    {{-- Navigation --}}
    <nav
        @click="if ($event.target.closest('a')) $store.sidebar.close()"
        class="flex min-h-0 flex-1 flex-col overflow-y-auto px-4 pt-2"
    >

        {{-- Dashboard --}}
        <a
            href="{{ route('dashboard') }}"
            class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <i class="hgi hgi-stroke hgi-dashboard-square-01 h-5 w-5 shrink-0"></i>
            <span>{{ __('Dashboard') }}</span>
        </a>

        {{-- Kendaraan --}}
        <a
            href="{{ route('kendaraan.index') }}"
            class="mt-1 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('kendaraan.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <i class="hgi hgi-stroke hgi-car-01 h-5 w-5 shrink-0"></i>
            <span>{{ __('Kendaraan') }}</span>
        </a>

        {{-- Maintenance (submenu) --}}
        <div x-data="{ open: @js($maintenanceActive) }">

            <button
                type="button"
                @click="open = ! open"
                class="mt-1 flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out {{ $maintenanceActive ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
            >
                <i class="hgi hgi-stroke hgi-wrench-01 h-5 w-5 shrink-0"></i>

                <span class="flex-1 text-start">
                    {{ __('Maintenance') }}
                </span>

                <i
                    class="hgi hgi-stroke hgi-arrow-down-01 h-4 w-4 shrink-0 transition-transform duration-200 ease-in-out"
                    :class="open ? 'rotate-180' : ''"
                ></i>
            </button>

            <div
                x-show="open"
                x-transition.duration.200ms
                class="mt-1 space-y-1 ps-11"
            >
                {{-- Pengajuan Maintenance --}}
                <a
                    href="{{ route('maintenance.index', ['tab' => 'pengajuan']) }}"
                    class="block rounded-md px-3 py-2 text-sm transition duration-150 ease-in-out {{ request()->routeIs('maintenance.*') && request()->query('tab', 'pengajuan') === 'pengajuan' ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                >
                    {{ __('Pengajuan Maintenance') }}
                </a>

                {{-- Maintenance Aktif --}}
                <a
                    href="{{ route('maintenance.index', ['tab' => 'aktif']) }}"
                    class="block rounded-md px-3 py-2 text-sm transition duration-150 ease-in-out {{ request()->routeIs('maintenance.*') && request()->query('tab') === 'aktif' ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                >
                    {{ __('Maintenance Aktif') }}
                </a>

                {{-- Riwayat Servis --}}
                <a
                    href="{{ route('service-history.index') }}"
                    class="block rounded-md px-3 py-2 text-sm transition duration-150 ease-in-out {{ request()->routeIs('service-history.*') ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                >
                    {{ __('Riwayat Servis') }}
                </a>

                {{-- Service Reminder --}}
                <a
                    href="{{ route('service-reminder.index') }}"
                    class="flex items-center justify-between gap-2 rounded-md px-3 py-2 text-sm transition duration-150 ease-in-out {{ request()->routeIs('service-reminder.*', 'service-reminders.*') ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                >
                    <span>{{ __('Service Reminder') }}</span>
                    <x-service-reminder-badge />
                </a>
            </div>
        </div>

        {{-- Mileage --}}
        <a
            href="{{ route('mileage.index') }}"
            class="mt-1 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('mileage.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            <i class="hgi hgi-stroke hgi-dashboard-speed-01 text-xl shrink-0"></i>

            <span>{{ __('Mileage') }}</span>
        </a>

        {{-- Spacer --}}
        <div class="flex-1"></div>

        {{-- Admin Only --}}
        @if($isAdmin)

            {{-- Laporan --}}
            <a
                href="{{ route('laporan-pengeluaran.index') }}"
                class="mt-1 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('laporan-pengeluaran.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
            >
                <i class="hgi hgi-stroke hgi-file-01 h-5 w-5 shrink-0"></i>
                <span>{{ __('Laporan') }}</span>
            </a>

            {{-- Manajemen User --}}
            <a
                href="{{ route('user.index') }}"
                class="mt-1 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('user.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
            >
                <i class="hgi hgi-stroke hgi-user-group h-5 w-5 shrink-0"></i>
                <span>{{ __('Manajemen User') }}</span>
            </a>

        @endif
    </nav>

    {{-- Bottom User Dropdown --}}
    <div class="shrink-0 border-t border-gray-100 px-4 py-3">

        {{-- Administrator Fleet --}}
        <x-dropdown
            align="left"
            width="60"
            placement="top"
            contentClasses="py-1 bg-white"
        >
            <x-slot name="trigger">
                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-gray-600 transition duration-150 ease-in-out hover:bg-gray-50 hover:text-gray-900"
                >
                    <i class="hgi hgi-stroke hgi-user-circle-02 h-5 w-5 shrink-0"></i>

                    <span class="flex-1 text-start">
                        {{ __('Administrator Fleet') }}
                    </span>

                    <i class="hgi hgi-stroke hgi-arrow-down-01 h-4 w-4 shrink-0"></i>
                </button>
            </x-slot>

            <x-slot name="content">

                {{-- Profile --}}
                <x-dropdown-link :href="route('profile.edit')">
                    <span class="flex items-center gap-2">
                        <i class="hgi hgi-stroke hgi-user-circle-02 text-lg"></i>
                        <span>{{ __('Profile') }}</span>
                    </span>
                </x-dropdown-link>

                {{-- Authentication --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-dropdown-link
                        :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                    >
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

{{-- Mobile backdrop --}}
<div
    x-show="$store.sidebar.open"
    @click="$store.sidebar.close()"
    x-transition.opacity.duration.200ms
    class="fixed inset-0 z-20 bg-gray-900/50 lg:hidden"
    style="display: none;"
></div>
```