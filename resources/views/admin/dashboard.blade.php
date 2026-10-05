<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Dashboard Admin') }}
        </h2>
    </x-slot>

    <main class="py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            
            {{-- QUICK SHORTCUT BAR --}}
            <section aria-labelledby="shortcuts-heading" class="mb-8">
                <h3 id="shortcuts-heading" class="sr-only">{{ __('Quick Shortcuts') }}</h3>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('kendaraan.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-md text-sm font-medium hover:bg-indigo-100 transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Tambah Kendaraan
                    </a>
                    <a href="{{ route('maintenance.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-md text-sm font-medium hover:bg-indigo-100 transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Maintenance
                    </a>
                    <a href="{{ route('mileage.odometer') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-200 rounded-md text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                        Input Odometer
                    </a>
                    <a href="{{ route('service-history.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-200 rounded-md text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                        Riwayat Servis
                    </a>
                    <a href="{{ route('service-reminder.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-200 rounded-md text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                        Service Reminder
                    </a>
                    <a href="#approval-heading" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-200 rounded-md text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                        Approval Biaya
                    </a>
                </div>
            </section>

            <div class="grid gap-8 lg:grid-cols-2 mb-8">
                {{-- ALERTS & NOTIFIKASI --}}
                <section aria-labelledby="alerts-heading" class="flex flex-col h-full">
                    <h3 id="alerts-heading" class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                        {{ __('Alerts & Notifikasi') }}
                        @php
                            $totalAlerts = array_sum($kpiData['alerts']);
                        @endphp
                        @if($totalAlerts > 0)
                            <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">{{ $totalAlerts }}</span>
                        @endif
                    </h3>
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm flex-1 overflow-hidden flex flex-col">
                        @if($totalAlerts === 0)
                            <div class="p-6 text-center text-gray-500 flex-1 flex items-center justify-center">
                                {{ __('Tidak ada notifikasi baru.') }}
                            </div>
                        @else
                            <ul class="divide-y divide-gray-100">
                                @if($kpiData['alerts']['terlambat'] > 0)
                                    <li class="p-4 bg-red-50 hover:bg-red-100 transition flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <span class="text-red-600">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            </span>
                                            <span class="text-sm font-medium text-red-900">{{ $kpiData['alerts']['terlambat'] }} kendaraan terlambat melakukan servis</span>
                                        </div>
                                        <a href="{{ route('service-reminder.index') }}" class="text-sm font-medium text-red-700 hover:text-red-800 underline">Lihat</a>
                                    </li>
                                @endif
                                @if($kpiData['alerts']['mendekati'] > 0)
                                    <li class="p-4 bg-amber-50 hover:bg-amber-100 transition flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <span class="text-amber-600">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            </span>
                                            <span class="text-sm font-medium text-amber-900">{{ $kpiData['alerts']['mendekati'] }} kendaraan mendekati jadwal servis</span>
                                        </div>
                                        <a href="{{ route('service-reminder.index') }}" class="text-sm font-medium text-amber-700 hover:text-amber-800 underline">Lihat</a>
                                    </li>
                                @endif
                                @if($kpiData['alerts']['pengajuan'] > 0)
                                    <li class="p-4 bg-sky-50 hover:bg-sky-100 transition flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <span class="text-sky-600">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                                </svg>
                                            </span>
                                            <span class="text-sm font-medium text-sky-900">{{ $kpiData['alerts']['pengajuan'] }} pengajuan biaya menunggu persetujuan</span>
                                        </div>
                                        <a href="#approval-heading" class="text-sm font-medium text-sky-700 hover:text-sky-800 underline">Lihat</a>
                                    </li>
                                @endif
                                @if($kpiData['alerts']['maintenance'] > 0)
                                    <li class="p-4 bg-indigo-50 hover:bg-indigo-100 transition flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <span class="text-indigo-600">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </span>
                                            <span class="text-sm font-medium text-indigo-900">{{ $kpiData['alerts']['maintenance'] }} maintenance masih pending</span>
                                        </div>
                                        <a href="{{ route('maintenance.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-800 underline">Lihat</a>
                                    </li>
                                @endif
                            </ul>
                        @endif
                    </div>
                </section>

                {{-- RECENT LOGS --}}
                <section aria-labelledby="logs-heading" class="flex flex-col h-full">
                    <h3 id="logs-heading" class="mb-4 text-lg font-semibold text-gray-900">
                        {{ __('Recent Activity') }}
                    </h3>
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm flex-1 p-5 overflow-hidden flex flex-col">
                        @if($kpiData['recentLogs']->isEmpty())
                            <div class="text-center text-gray-500 flex-1 flex items-center justify-center">
                                {{ __('Belum ada aktivitas tercatat.') }}
                            </div>
                        @else
                            <div class="flow-root overflow-y-auto">
                                <ul role="list" class="-mb-8">
                                    @foreach($kpiData['recentLogs'] as $index => $log)
                                        <li>
                                            <div class="relative pb-8">
                                                @if($index !== count($kpiData['recentLogs']) - 1)
                                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                                @endif
                                                <div class="relative flex space-x-3">
                                                    <div>
                                                        <span class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center ring-8 ring-white">
                                                            @if($log->type === 'kendaraan')
                                                                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
                                                            @elseif($log->type === 'pengajuan')
                                                                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                                            @else
                                                                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" /></svg>
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                        <div>
                                                            <p class="text-sm text-gray-900 font-medium">{{ $log->title }}</p>
                                                            <p class="text-sm text-gray-500 mt-0.5">{{ $log->description }}</p>
                                                        </div>
                                                        <div class="text-right text-xs whitespace-nowrap text-gray-500">
                                                            <time datetime="{{ $log->timestamp }}">{{ \Carbon\Carbon::parse($log->timestamp)->diffForHumans() }}</time>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            <section aria-labelledby="fleet-kpi-heading">
                <h3 id="fleet-kpi-heading" class="mb-4 text-lg font-semibold text-gray-900">
                    {{ __('Ringkasan Armada') }}
                </h3>
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <dt class="text-sm font-medium text-gray-600">{{ __('Total Kendaraan') }}</dt>
                        <dd class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format($kpiData['totalKendaraan']) }}</dd>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 shadow-sm">
                        <dt class="text-sm font-medium text-amber-900">{{ __('Kendaraan Bermasalah') }}</dt>
                        <dd class="mt-2 text-3xl font-semibold text-amber-950">{{ number_format($kpiData['kendaraanBermasalah']) }}</dd>
                    </div>
                    <div class="rounded-lg border border-sky-200 bg-sky-50 p-5 shadow-sm">
                        <dt class="text-sm font-medium text-sky-900">{{ __('Pengajuan Menunggu') }}</dt>
                        <dd class="mt-2 text-3xl font-semibold text-sky-950">{{ number_format($kpiData['pendingApproval']) }}</dd>
                    </div>
                    <div class="rounded-lg border border-rose-200 bg-rose-50 p-5 shadow-sm">
                        <dt class="text-sm font-medium text-rose-900">{{ __('Laporan Kerusakan Aktif') }}</dt>
                        <dd class="mt-2 text-3xl font-semibold text-rose-950">{{ number_format($kpiData['laporanKerusakanAktif']) }}</dd>
                    </div>
                </dl>
            </section>

            <section aria-labelledby="maintenance-kpi-heading">
                <h3 id="maintenance-kpi-heading" class="mb-4 text-lg font-semibold text-gray-900">
                    {{ __('Ringkasan Maintenance & Pengeluaran') }}
                </h3>
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <dt class="text-sm font-medium text-gray-600">{{ __('Total Maintenance') }}</dt>
                        <dd class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format($kpiData['totalMaintenance']) }}</dd>
                    </div>
                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-5 shadow-sm">
                        <dt class="text-sm font-medium text-indigo-900">{{ __('Total Biaya Maintenance') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-indigo-950">Rp {{ number_format($kpiData['totalBiayaMaintenance'], 0, ',', '.') }}</dd>
                    </div>
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                        <dt class="text-sm font-medium text-emerald-900">{{ __('Total Pengeluaran (Keseluruhan)') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-emerald-950">Rp {{ number_format($kpiData['totalPengeluaranLengkap'], 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </section>

            <section class="grid gap-8 lg:grid-cols-3">
                <div aria-labelledby="monthly-expenses-heading">
                    <h3 id="monthly-expenses-heading" class="mb-4 text-lg font-semibold text-gray-900">
                        {{ __('Pengeluaran Bulan Ini') }}
                    </h3>
                    <p class="rounded-lg border border-emerald-200 bg-emerald-50 p-5 text-2xl font-semibold text-emerald-950">
                        Rp {{ number_format($kpiData['totalPengeluaranBulanIni'], 2, ',', '.') }}
                    </p>
                </div>

                <div aria-labelledby="vehicle-categories-heading">
                    <h3 id="vehicle-categories-heading" class="mb-4 text-lg font-semibold text-gray-900">
                        {{ __('Kendaraan per Kategori') }}
                    </h3>
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-gray-600">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Kategori Penggunaan') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Jumlah') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-900">
                                @forelse ($kpiData['kategoriBreakdown'] as $kategori)
                                    <tr>
                                        <th scope="row" class="px-4 py-3 text-left font-medium">{{ $kategori->kategori_penggunaan }}</th>
                                        <td class="px-4 py-3 text-right">{{ number_format($kategori->jumlah) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-4 py-6 text-center text-gray-500">
                                            {{ __('Belum ada data kendaraan.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div aria-labelledby="fuel-types-heading">
                    <h3 id="fuel-types-heading" class="mb-4 text-lg font-semibold text-gray-900">
                        {{ __('Tipe Bahan Bakar') }}
                    </h3>
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-gray-600">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Jenis BBM') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Jumlah') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-900">
                                @forelse ($kpiData['bbmBreakdown'] as $bbm)
                                    <tr>
                                        <th scope="row" class="px-4 py-3 text-left font-medium">{{ $bbm->jenis_bbm }}</th>
                                        <td class="px-4 py-3 text-right">{{ number_format($bbm->jumlah) }} kendaraan</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-4 py-6 text-center text-gray-500">
                                            {{ __('Belum ada data kendaraan.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            <section class="grid gap-8 lg:grid-cols-2 mt-8">
                {{-- Lifecycle Kendaraan Widget --}}
                <div aria-labelledby="lifecycle-heading">
                    <h3 id="lifecycle-heading" class="mb-2 text-lg font-semibold text-gray-900">
                        {{ __('Lifecycle Kendaraan') }}
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">{{ __('Distribusi usia kendaraan berdasarkan tahun penggunaan') }}</p>
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm h-full">
                        @if(array_sum($kpiData['lifecycleData']) === 0)
                            <p class="text-gray-500 text-center py-4">{{ __('Belum ada data lifecycle kendaraan.') }}</p>
                        @else
                            <div class="space-y-6">
                                @php
                                    $total = max(1, array_sum($kpiData['lifecycleData']));
                                @endphp
                                <div>
                                    <div class="flex justify-between text-sm font-medium mb-1">
                                        <span class="text-gray-700">0–1 Tahun (Baru)</span>
                                        <span class="text-gray-900">{{ $kpiData['lifecycleData']['baru'] }} kendaraan</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-blue-500 h-2.5 rounded-full" style="width: {{ ($kpiData['lifecycleData']['baru'] / $total) * 100 }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm font-medium mb-1">
                                        <span class="text-gray-700">2–3 Tahun (Normal)</span>
                                        <span class="text-gray-900">{{ $kpiData['lifecycleData']['normal'] }} kendaraan</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-emerald-500 h-2.5 rounded-full" style="width: {{ ($kpiData['lifecycleData']['normal'] / $total) * 100 }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm font-medium mb-1">
                                        <span class="text-gray-700">4 Tahun (Perlu Perhatian)</span>
                                        <span class="text-gray-900">{{ $kpiData['lifecycleData']['perhatian'] }} kendaraan</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-amber-500 h-2.5 rounded-full" style="width: {{ ($kpiData['lifecycleData']['perhatian'] / $total) * 100 }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm font-medium mb-1">
                                        <span class="text-gray-700">5+ Tahun (Prioritas Maintenance)</span>
                                        <span class="text-gray-900">{{ $kpiData['lifecycleData']['prioritas'] }} kendaraan</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-rose-500 h-2.5 rounded-full" style="width: {{ ($kpiData['lifecycleData']['prioritas'] / $total) * 100 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Persetujuan Biaya Widget --}}
                <div aria-labelledby="approval-heading">
                    <h3 id="approval-heading" class="mb-4 text-lg font-semibold text-gray-900">
                        {{ __('Persetujuan Biaya') }}
                    </h3>
                    
                    @if(session('success'))
                        <div class="mb-4 p-3 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-md text-sm font-medium">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="mb-4 p-3 bg-red-50 text-red-800 border border-red-200 rounded-md text-sm font-medium">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden flex flex-col h-full max-h-[500px]">
                        @if($kpiData['pengajuanMenungguList']->isEmpty())
                            <div class="p-6 text-center text-gray-500 flex-1 flex items-center justify-center">
                                {{ __('Tidak ada pengajuan biaya yang menunggu persetujuan.') }}
                            </div>
                        @else
                            <ul class="divide-y divide-gray-200 overflow-y-auto">
                                @foreach($kpiData['pengajuanMenungguList'] as $pengajuan)
                                    <li class="p-5">
                                        <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-4">
                                            <div>
                                                <div class="flex items-center gap-2 mb-1">
                                                    <h4 class="text-base font-semibold text-gray-900">{{ $pengajuan->kendaraan->merk_tipe }}</h4>
                                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800">{{ $pengajuan->kendaraan->plat_nomor }}</span>
                                                </div>
                                                <div class="text-sm text-gray-600 space-y-1">
                                                    <p><span class="font-medium text-gray-900">Jenis:</span> {{ $pengajuan->jenis_pengajuan }}</p>
                                                    <p><span class="font-medium text-gray-900">Biaya:</span> Rp {{ number_format($pengajuan->estimasi_biaya, 0, ',', '.') }}</p>
                                                    <p><span class="font-medium text-gray-900">Diajukan:</span> {{ $pengajuan->dibuat_pada->translatedFormat('d F Y') }} oleh {{ $pengajuan->pengaju->name ?? '-' }}</p>
                                                </div>
                                            </div>
                                            <div class="inline-flex px-2 py-1 rounded text-xs font-semibold bg-amber-100 text-amber-800 uppercase tracking-widest">
                                                PENDING
                                            </div>
                                        </div>
                                        
                                        @if($pengajuan->deskripsi_keluhan)
                                            <div class="mb-4 p-3 bg-gray-50 rounded-md border border-gray-100">
                                                <p class="text-xs text-gray-500 font-medium mb-1">Keterangan / Keluhan:</p>
                                                <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ $pengajuan->deskripsi_keluhan }}</p>
                                            </div>
                                        @endif

                                        <div class="flex flex-col sm:flex-row gap-2 mt-3 pt-3 border-t border-gray-100">
                                            <form method="POST" action="{{ route('pengajuan.approve', $pengajuan->id) }}" class="flex-1">
                                                @csrf
                                                @method('PATCH')
                                                <x-primary-button class="w-full justify-center bg-emerald-600 hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-900 focus:ring-emerald-500" onclick="return confirm('Apakah Anda yakin ingin menyetujui pengajuan ini?')">
                                                    {{ __('Approve') }}
                                                </x-primary-button>
                                            </form>
                                            
                                            <form method="POST" action="{{ route('pengajuan.reject', $pengajuan->id) }}" x-data="{ open: false }" x-on:submit.prevent="if(!open) { open = true; } else { $el.submit(); }" class="flex-1 flex flex-col items-center">
                                                @csrf
                                                @method('PATCH')
                                                
                                                <div x-show="open" class="mb-2 w-full" style="display: none;">
                                                    <x-text-input name="alasan_penolakan" class="w-full text-sm" placeholder="Masukkan alasan penolakan..." :required="false" />
                                                </div>
                                                
                                                <div class="flex gap-2 w-full">
                                                    <x-danger-button class="flex-1 justify-center" x-text="open ? 'Konfirmasi Reject' : 'Reject'">
                                                        {{ __('Reject') }}
                                                    </x-danger-button>
                                                    <x-secondary-button type="button" x-show="open" x-on:click="open = false" style="display: none;">
                                                        Batal
                                                    </x-secondary-button>
                                                </div>
                                            </form>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </main>
</x-app-layout>