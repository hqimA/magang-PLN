@php
    $approvalStatusLabels = [
        'MENUNGGU' => ['label' => 'Menunggu', 'class' => 'bg-amber-100 text-amber-800'],
        'DISETUJUI' => ['label' => 'Disetujui', 'class' => 'bg-emerald-100 text-emerald-800'],
        'DITOLAK' => ['label' => 'Ditolak', 'class' => 'bg-rose-100 text-rose-800'],
        'DIBATALKAN' => ['label' => 'Dibatalkan', 'class' => 'bg-slate-100 text-slate-700'],
    ];
@endphp

<x-app-layout>
    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header class="pengelola-dashboard__hero overflow-hidden rounded-3xl p-6 text-white shadow-xl sm:p-8">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="pengelola-dashboard__eyebrow text-sm font-semibold uppercase tracking-[0.2em]">Fleet Management PLN</p>
                        <h1 class="pengelola-dashboard__title mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Dashboard Pengelola</h1>
                        <p class="pengelola-dashboard__intro mt-2 text-sm sm:text-base">
                            Selamat datang, {{ $pengelola->name }}. Ringkasan armada dan maintenance untuk unit Anda.
                        </p>
                        <p class="pengelola-dashboard__unit mt-4 inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-medium">
                            <i class="hgi hgi-stroke hgi-location-01"></i>
                            Unit PLN: {{ $pengelola->unit_pln?->value ?? 'Belum ditetapkan' }}
                        </p>
                    </div>

                    <nav aria-label="Aksi cepat" class="flex flex-wrap gap-2">
                        <a href="{{ route('kendaraan.index') }}" class="pengelola-dashboard__action pengelola-dashboard__action--primary inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm transition">
                            <i class="hgi hgi-stroke hgi-car-01"></i>
                            Lihat kendaraan
                        </a>
                        <a href="{{ route('maintenance.create') }}" class="pengelola-dashboard__action pengelola-dashboard__action--secondary inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition">
                            <i class="hgi hgi-stroke hgi-wrench-01"></i>
                            Ajukan maintenance
                        </a>
                    </nav>
                </div>
            </header>

            <section aria-label="Ringkasan armada dan maintenance" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="gacor-card">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-slate-500">Kendaraan unit</p>
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
                            <i class="hgi hgi-stroke hgi-car-01 text-xl"></i>
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['totalVehicles'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-slate-500">Armada pada Unit PLN Anda</p>
                </article>

                <article class="gacor-card">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-slate-500">Pengajuan menunggu</p>
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                            <i class="hgi hgi-stroke hgi-clock-01 text-xl"></i>
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['pendingRequests'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-slate-500">Pengajuan dari armada unit Anda</p>
                </article>

                <article class="gacor-card">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-slate-500">Maintenance berjalan</p>
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                            <i class="hgi hgi-stroke hgi-wrench-01 text-xl"></i>
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['activeMaintenance'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-slate-500">Disetujui dan belum tercatat selesai</p>
                </article>

                <article class="gacor-card">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-slate-500">Service Reminder</p>
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                            <i class="hgi hgi-stroke hgi-notification-01 text-xl"></i>
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['serviceReminders'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-slate-500">Kendaraan mendekati atau melewati jadwal servis</p>
                </article>
            </section>

            <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <article class="gacor-card">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Service Reminder</h2>
                            <p class="mt-1 text-sm text-slate-500">Kendaraan unit yang perlu diperhatikan jadwal servisnya.</p>
                        </div>
                        <a href="{{ route('service-reminder.index') }}" class="shrink-0 text-sm font-semibold text-blue-700 hover:text-blue-900">
                            Lihat semua
                        </a>
                    </div>

                    @if (! $serviceRemindersEnabled)
                        <p class="mt-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">Service Reminder sedang dinonaktifkan.</p>
                    @elseif ($serviceReminders->isEmpty())
                        <p class="mt-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">Belum ada kendaraan unit yang mendekati jadwal servis.</p>
                    @else
                        <ul class="mt-5 divide-y divide-slate-100">
                            @foreach ($serviceReminders as $item)
                                @php
                                    $vehicle = $item['vehicle'];
                                    $reminder = $item['reminder'];
                                    $isOverdue = $reminder['status'] === 'TERLAMBAT_SERVIS';
                                @endphp
                                <li class="flex flex-col gap-2 py-4 first:pt-0 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $vehicle->plat_nomor }}</p>
                                        <p class="mt-0.5 text-sm text-slate-500">{{ $vehicle->merk_tipe }}</p>
                                    </div>
                                    <span class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-semibold {{ $isOverdue ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                        @if ($isOverdue)
                                            Terlambat {{ number_format(abs($reminder['sisa_kilometer']), 0, ',', '.') }} km
                                        @else
                                            Sisa {{ number_format($reminder['sisa_kilometer'], 0, ',', '.') }} km
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </article>

                <article class="gacor-card overflow-hidden p-0">
                    <div class="flex items-start justify-between gap-4 p-5 sm:p-6">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Pengajuan maintenance terbaru</h2>
                            <p class="mt-1 text-sm text-slate-500">Aktivitas pengajuan dari armada Unit PLN Anda.</p>
                        </div>
                        <a href="{{ route('maintenance.index') }}" class="shrink-0 text-sm font-semibold text-blue-700 hover:text-blue-900">
                            Buka maintenance
                        </a>
                    </div>

                    @if ($latestRequests->isEmpty())
                        <p class="mx-5 mb-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-600 sm:mx-6 sm:mb-6">Belum ada pengajuan maintenance untuk unit ini.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-5 py-3 font-semibold sm:px-6" scope="col">Kendaraan</th>
                                        <th class="px-5 py-3 font-semibold" scope="col">Jenis</th>
                                        <th class="px-5 py-3 font-semibold" scope="col">Status</th>
                                        <th class="px-5 py-3 font-semibold sm:px-6" scope="col">Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($latestRequests as $maintenanceRequest)
                                        @php
                                            $status = $approvalStatusLabels[$maintenanceRequest->status_persetujuan] ?? ['label' => $maintenanceRequest->status_persetujuan, 'class' => 'bg-slate-100 text-slate-700'];
                                        @endphp
                                        <tr>
                                            <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-900 sm:px-6">
                                                {{ $maintenanceRequest->kendaraan?->plat_nomor ?? 'Kendaraan' }}
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                                {{ $maintenanceRequest->jenis_pengajuan === 'DARURAT' ? 'Darurat' : 'Rutin' }}
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-4">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $status['class'] }}">{{ $status['label'] }}</span>
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-4 text-slate-500 sm:px-6">
                                                {{ $maintenanceRequest->dibuat_pada?->format('d M Y') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </article>
            </section>

            <section aria-label="Data kendaraan dan mileage" class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <article class="gacor-card overflow-hidden p-0">
                    <div class="flex items-start justify-between gap-4 p-5 sm:p-6">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Data kendaraan</h2>
                            <p class="mt-1 text-sm text-slate-500">Armada Unit PLN Anda.</p>
                        </div>
                        <a href="{{ route('kendaraan.index') }}" class="shrink-0 text-sm font-semibold text-blue-700 hover:text-blue-900">
                            Lihat semua
                        </a>
                    </div>

                    @if ($unitVehicles->isEmpty())
                        <p class="mx-5 mb-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-600 sm:mx-6 sm:mb-6">Belum ada kendaraan pada unit ini.</p>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($unitVehicles as $vehicle)
                                <li class="px-5 py-4 sm:px-6">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <a href="{{ route('kendaraan.show', $vehicle) }}" class="group block">
                                                <span class="font-semibold text-blue-800 group-hover:text-blue-950 group-hover:underline">{{ $vehicle->plat_nomor }}</span>
                                                <span class="mt-0.5 block text-sm text-slate-500 group-hover:text-slate-700">{{ $vehicle->merk_tipe }} &middot; Tahun {{ $vehicle->tahun_pembuatan }}</span>
                                            </a>
                                        </div>
                                        <span class="w-fit rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-800">
                                            {{ number_format($vehicle->kilometer_terakhir, 0, ',', '.') }} km
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </article>

                <article class="gacor-card overflow-hidden p-0">
                    <div class="flex items-start justify-between gap-4 p-5 sm:p-6">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Data Mileage</h2>
                            <p class="mt-1 text-sm text-slate-500">Catatan perjalanan terbaru dari unit Anda.</p>
                        </div>
                        <a href="{{ route('mileage.history') }}" class="shrink-0 text-sm font-semibold text-blue-700 hover:text-blue-900">
                            Lihat semua
                        </a>
                    </div>

                    @if ($recentMileage->isEmpty() && $unitVehicles->isEmpty())
                        <p class="mx-5 mb-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-600 sm:mx-6 sm:mb-6">Belum ada data perjalanan pada unit ini.</p>
                    @elseif ($recentMileage->isEmpty())
                        <div class="mx-5 mb-3 rounded-xl bg-sky-50 p-4 text-sm text-sky-900 sm:mx-6">
                            Belum ada riwayat perjalanan. Berikut odometer terakhir yang tersimpan pada data kendaraan.
                        </div>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($unitVehicles as $vehicle)
                                <li class="px-5 py-4 sm:px-6">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <a href="{{ route('kendaraan.show', $vehicle->id) }}" class="group block">
                                                <span class="font-semibold text-blue-800 group-hover:text-blue-950 group-hover:underline">{{ $vehicle->plat_nomor }}</span>
                                                <span class="mt-0.5 block text-sm text-slate-500 group-hover:text-slate-700">{{ $vehicle->merk_tipe }}</span>
                                            </a>
                                        </div>
                                        <span class="w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-800">
                                            Odometer {{ number_format($vehicle->kilometer_terakhir, 0, ',', '.') }} km
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($recentMileage as $mileage)
                                <li class="px-5 py-4 sm:px-6">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <a href="{{ route('mileage.history', ['kendaraan' => $mileage->id_kendaraan]) }}" class="group block">
                                                <span class="font-semibold text-blue-800 group-hover:text-blue-950 group-hover:underline">{{ $mileage->kendaraan?->plat_nomor ?? 'Kendaraan' }}</span>
                                                <span class="mt-0.5 block text-sm text-slate-500 group-hover:text-slate-700">
                                                    {{ $mileage->kendaraan?->merk_tipe ?? 'Data kendaraan' }}
                                                    &middot; {{ $mileage->tanggal_perjalanan?->format('d M Y') }}
                                                    &middot; {{ number_format($mileage->kilometer_awal, 0, ',', '.') }}–{{ number_format($mileage->kilometer_akhir, 0, ',', '.') }} km
                                                </span>
                                            </a>
                                        </div>
                                        <span class="w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-800">
                                            +{{ number_format($mileage->total_jarak, 0, ',', '.') }} km
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
