@php
    $statusMap = [
        'SELESAI'  => ['label' => 'Selesai',   'class' => 'bg-green-100 text-green-800'],
        'BERJALAN' => ['label' => 'Berjalan',  'class' => 'bg-blue-100 text-blue-800'],
        'TERBATAS' => ['label' => 'Terbatas',  'class' => 'bg-yellow-100 text-yellow-800'],
    ];

    $statusKendaraan = [
        'BAIK'          => ['label' => 'Kondisi Baik',  'class' => 'bg-green-100 text-green-800 border-green-200'],
        'PERLU_SERVIS'  => ['label' => 'Perlu Servis',  'class' => 'bg-yellow-100 text-yellow-800 border-yellow-200'],
        'SEDANG_SERVIS' => ['label' => 'Sedang Servis', 'class' => 'bg-orange-100 text-orange-800 border-orange-200'],
        'RUSAK'         => ['label' => 'Rusak',         'class' => 'bg-red-100 text-red-800 border-red-200'],
    ];

    $reminderStatusMap = [
        'TERJADWAL'        => ['label' => 'Jadwal Aman',      'class' => 'bg-emerald-50 text-emerald-800 border-emerald-300'],
        'MENDEKATI_SERVIS' => ['label' => 'Mendekati Servis', 'class' => 'bg-amber-50 text-amber-800 border-amber-300'],
        'TERLAMBAT_SERVIS' => ['label' => 'Terlambat Servis', 'class' => 'bg-rose-50 text-rose-800 border-rose-300'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Kendaraan Pengelola') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Daftar armada di bawah tanggung jawab: <span class="font-semibold text-gray-700">{{ $currentPengelola->name }}</span>
                    @if ($isAdmin)
                        <span class="inline-flex items-center ml-2 px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Mode Admin</span>
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($isAdmin && $pengelolas->isNotEmpty())
                    <form method="GET" action="{{ route('pengelola.kendaraan') }}" class="flex items-center gap-2">
                        <label for="pengelola-select" class="text-xs font-medium text-gray-600">Pilih Pengelola:</label>
                        <select id="pengelola-select" name="pengelola" onchange="this.form.submit()"
                                class="text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5 pl-3 pr-8">
                            @foreach ($pengelolas as $p)
                                <option value="{{ $p->id }}" @selected($p->id === $currentPengelola->id)>
                                    {{ $p->name }} ({{ $p->kendaraan_count ?? $p->kendaraan->count() }} unit)
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                @if ($selected)
                    <a href="{{ route('mileage.odometer', ['kendaraan' => $selected->id]) }}"
                       class="inline-flex items-center px-3 py-1.5 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 transition">
                        Input Odometer
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($kendaraans->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Belum Ada Kendaraan</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Tidak ditemukan kendaraan untuk pengelola <span class="font-semibold">{{ $currentPengelola->name }}</span>.
                        </p>
                        @if ($isAdmin)
                            <div class="mt-6">
                                <a href="{{ route('kendaraan.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                    Tambah Kendaraan Baru
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- Panel Kiri: Daftar Kendaraan --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">Daftar Kendaraan</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $kendaraans->count() }} unit
                            </span>
                        </div>

                        <ul class="divide-y divide-gray-100 max-h-[36rem] overflow-y-auto">
                            @foreach ($kendaraans as $k)
                                @php
                                    $isActive = $selected && $k->id === $selected->id;
                                    $sk = $statusKendaraan[$k->status_perawatan] ?? ['label' => $k->status_perawatan, 'class' => 'bg-gray-100 text-gray-800 border-gray-200'];
                                @endphp
                                <li>
                                    <a href="{{ route('pengelola.kendaraan', array_filter(['pengelola' => $isAdmin ? $currentPengelola->id : null, 'kendaraan' => $k->id])) }}"
                                       class="block px-6 py-4 transition hover:bg-gray-50 {{ $isActive ? 'bg-indigo-50/70 border-l-4 border-indigo-600' : '' }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-sm font-semibold text-gray-900">{{ $k->plat_nomor }}</span>
                                            <span class="inline-flex px-2 py-0.5 text-[11px] font-medium rounded-full border {{ $sk['class'] }}">
                                                {{ $sk['label'] }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-600 mt-1 font-medium">{{ $k->merk_tipe }}</div>
                                        <div class="flex items-center justify-between text-xs text-gray-500 mt-2">
                                            <span>Tahun {{ $k->tahun_pembuatan }}</span>
                                            <span class="font-medium text-gray-700">{{ number_format($k->kilometer_terakhir) }} km</span>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Panel Kanan: Detail Kendaraan Terpilih --}}
                    <div class="lg:col-span-2 space-y-6">

                        {{-- Card 1: Informasi Kendaraan --}}
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">Detail Kendaraan</h3>
                                    <p class="text-xs text-gray-500">{{ $selected->plat_nomor }} &mdash; {{ $selected->merk_tipe }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('mileage.index', ['kendaraan' => $selected->id]) }}"
                                       class="inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                                        Mileage Tracker
                                    </a>
                                    @if ($isAdmin)
                                        <a href="{{ route('kendaraan.edit', $selected->id) }}"
                                           class="inline-flex items-center px-3 py-1 bg-indigo-50 border border-indigo-300 text-indigo-700 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-indigo-100">
                                            Edit Data
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="p-6 text-gray-900">
                                <div class="flex flex-col sm:flex-row gap-6">
                                    <div class="sm:w-48 flex-shrink-0">
                                        @if ($selected->foto_kendaraan)
                                            <img src="{{ asset('storage/' . $selected->foto_kendaraan) }}"
                                                 alt="Foto {{ $selected->merk_tipe }}"
                                                 class="h-36 w-full object-cover rounded-lg border border-gray-200 shadow-sm" />
                                        @else
                                            <div class="h-36 w-full flex flex-col items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 text-gray-400">
                                                <svg class="h-10 w-10 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                                </svg>
                                                <span class="text-[11px] mt-1">Tidak ada foto</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex-1 grid grid-cols-2 sm:grid-cols-3 gap-4">
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">Plat Nomor</p>
                                            <p class="text-base font-bold text-gray-900">{{ $selected->plat_nomor }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">Merk / Tipe</p>
                                            <p class="text-sm font-semibold text-gray-800">{{ $selected->merk_tipe }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">Kilometer Terakhir</p>
                                            <p class="text-sm font-bold text-indigo-600">{{ number_format($selected->kilometer_terakhir) }} km</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">Status Perawatan</p>
                                            @php $sk = $statusKendaraan[$selected->status_perawatan] ?? ['label' => $selected->status_perawatan, 'class' => 'bg-gray-100 text-gray-800 border-gray-200']; @endphp
                                            <span class="inline-flex mt-0.5 px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $sk['class'] }}">
                                                {{ $sk['label'] }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">Transmisi</p>
                                            <p class="text-sm font-medium text-gray-700">{{ $selected->transmisi }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">Jenis BBM</p>
                                            <p class="text-sm font-medium text-gray-700">{{ $selected->jenis_bbm }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">Tahun Pembuatan</p>
                                            <p class="text-sm text-gray-700">{{ $selected->tahun_pembuatan }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">Kategori</p>
                                            <p class="text-sm text-gray-700">{{ str_replace('_', ' ', $selected->kategori_penggunaan) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">Pengelola</p>
                                            <p class="text-sm font-medium text-gray-800">{{ $selected->pengelola?->name ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Card 2: Status Servis & Reminder --}}
                        @if ($serviceReminder)
                            @php
                                $rInfo = $reminderStatusMap[$serviceReminder['status']] ?? ['label' => $serviceReminder['status'], 'class' => 'bg-gray-50 text-gray-800 border-gray-300'];
                            @endphp
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                    <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">Jadwal & Pengingat Servis</h3>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $rInfo['class'] }}">
                                        {{ $rInfo['label'] }}
                                    </span>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                                        <div class="p-3 bg-gray-50 rounded-lg">
                                            <p class="text-xs text-gray-500 uppercase">Target KM Servis</p>
                                            <p class="text-base font-bold text-gray-900 mt-1">{{ number_format($serviceReminder['target_kilometer']) }} km</p>
                                        </div>
                                        <div class="p-3 bg-gray-50 rounded-lg">
                                            <p class="text-xs text-gray-500 uppercase">Sisa Jarak</p>
                                            <p class="text-base font-bold {{ $serviceReminder['sisa_kilometer'] <= 0 ? 'text-red-600' : 'text-gray-900' }} mt-1">
                                                {{ number_format($serviceReminder['sisa_kilometer']) }} km
                                            </p>
                                        </div>
                                        <div class="p-3 bg-gray-50 rounded-lg">
                                            <p class="text-xs text-gray-500 uppercase">Target Tanggal</p>
                                            <p class="text-base font-bold text-gray-900 mt-1">{{ \Illuminate\Support\Carbon::parse($serviceReminder['tanggal_target'])->format('d M Y') }}</p>
                                        </div>
                                        <div class="p-3 bg-gray-50 rounded-lg">
                                            <p class="text-xs text-gray-500 uppercase">Sisa Waktu</p>
                                            <p class="text-base font-bold {{ $serviceReminder['sisa_hari'] <= 0 ? 'text-red-600' : 'text-gray-900' }} mt-1">
                                                {{ $serviceReminder['sisa_hari'] }} hari
                                            </p>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-4 text-center italic">
                                        {{ $serviceReminder['alasan'] }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        {{-- Card 3: Histori Servis & Perjalanan --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            {{-- Riwayat Servis Terakhir --}}
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">Riwayat Servis</h4>
                                </div>
                                <div class="p-6">
                                    @if ($riwayatServis->isEmpty())
                                        <p class="text-xs text-gray-500 text-center py-6">Belum ada catatan servis.</p>
                                    @else
                                        <ul class="divide-y divide-gray-100 text-xs">
                                            @foreach ($riwayatServis as $rs)
                                                <li class="py-2.5 flex items-center justify-between">
                                                    <div>
                                                        <p class="font-medium text-gray-900">{{ $rs->nama_bengkel ?? 'Servis Berkala' }}</p>
                                                        <p class="text-gray-500">{{ $rs->tanggal_servis?->format('d M Y') }} &middot; {{ number_format($rs->kilometer_servis) }} km</p>
                                                    </div>
                                                    <span class="font-semibold text-gray-800">
                                                        Rp {{ number_format($rs->total_biaya, 0, ',', '.') }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>

                            {{-- Riwayat Perjalanan Terakhir --}}
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">Histori Perjalanan</h4>
                                    <a href="{{ route('mileage.history', ['kendaraan' => $selected->id]) }}" class="text-xs text-indigo-600 hover:underline">
                                        Lihat Semua
                                    </a>
                                </div>
                                <div class="p-6">
                                    @if ($riwayatPerjalanan->isEmpty())
                                        <p class="text-xs text-gray-500 text-center py-6">Belum ada histori perjalanan.</p>
                                    @else
                                        <ul class="divide-y divide-gray-100 text-xs">
                                            @foreach ($riwayatPerjalanan as $rp)
                                                @php $st = $statusMap[$rp->status_perjalanan] ?? ['label' => $rp->status_perjalanan, 'class' => 'bg-gray-100 text-gray-800']; @endphp
                                                <li class="py-2.5 flex items-center justify-between">
                                                    <div>
                                                        <p class="font-medium text-gray-900">{{ $rp->tanggal_perjalanan->format('d M Y') }}</p>
                                                        <p class="text-gray-500">{{ number_format($rp->kilometer_awal) }} &rarr; {{ number_format($rp->kilometer_akhir) }} km ({{ number_format($rp->total_jarak) }} km)</p>
                                                    </div>
                                                    <span class="inline-flex px-2 py-0.5 font-medium rounded-full {{ $st['class'] }}">
                                                        {{ $st['label'] }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>

                        </div>

                    </div>

                </div>
            @endif

        </div>
    </div>
</x-app-layout>
