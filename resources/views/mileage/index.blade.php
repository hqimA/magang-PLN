@php
    $statusMap = [
        'SELESAI'  => ['label' => 'Selesai',   'class' => 'bg-green-100 text-green-800'],
        'BERJALAN' => ['label' => 'Berjalan',  'class' => 'bg-blue-100 text-blue-800'],
        'TERBATAS' => ['label' => 'Terbatas',  'class' => 'bg-yellow-100 text-yellow-800'],
    ];

    $statusKendaraan = [
        'BAIK'          => ['label' => 'Aktif',         'class' => 'bg-green-100 text-green-800'],
        'PERLU_SERVIS'  => ['label' => 'Perlu Servis',  'class' => 'bg-yellow-100 text-yellow-800'],
        'SEDANG_SERVIS' => ['label' => 'Sedang Servis', 'class' => 'bg-orange-100 text-orange-800'],
        'RUSAK'         => ['label' => 'Tidak Aktif',   'class' => 'bg-red-100 text-red-800'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Mileage Tracker') }}
            </h2>
            <a href="{{ route('mileage.odometer', $selected ? ['kendaraan' => $selected->id] : []) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Input Odometer
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if ($kendaraans->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="text-gray-500 text-center py-8">
                            Belum ada data kendaraan. <a href="{{ route('kendaraan.create') }}" class="text-indigo-600 hover:underline">Tambah sekarang</a>.
                        </p>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Daftar kendaraan --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">Daftar Kendaraan</h3>
                        </div>
                        <ul class="divide-y divide-gray-100 max-h-[32rem] overflow-y-auto">
                            @foreach ($kendaraans as $k)
                                @php $isActive = $selected && $k->id === $selected->id; @endphp
                                <li>
                                    <a href="{{ route('mileage.index', ['kendaraan' => $k->id]) }}"
                                       class="block px-6 py-4 hover:bg-gray-50 {{ $isActive ? 'bg-indigo-50 border-l-4 border-indigo-500' : '' }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-sm font-medium text-gray-900">{{ $k->plat_nomor }}</span>
                                            <span class="text-xs text-gray-600">{{ number_format($k->kilometer_terakhir) }} km</span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">{{ $k->merk_tipe }} &middot; {{ $k->tahun_pembuatan }}</div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Detail + form perjalanan --}}
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">Informasi Kendaraan</h3>
                                <a href="{{ route('mileage.history', ['kendaraan' => $selected->id]) }}"
                                   class="inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Semua Riwayat
                                </a>
                            </div>
                            <div class="p-6 text-gray-900">
                                <div class="flex flex-col sm:flex-row gap-6">
                                    <div class="sm:w-48">
                                        @if ($selected->foto_kendaraan)
                                            <img src="{{ asset('storage/' . $selected->foto_kendaraan) }}"
                                                 alt="Foto {{ $selected->merk_tipe }}"
                                                 class="h-32 w-full object-cover rounded-md border border-gray-200" />
                                        @else
                                            <div class="h-32 w-full flex items-center justify-center rounded-md border border-dashed border-gray-300 bg-gray-50">
                                                <svg class="h-10 w-10 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">Plat Nomor</p>
                                            <p class="text-sm font-medium text-gray-900">{{ $selected->plat_nomor }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">Merk / Tipe</p>
                                            <p class="text-sm text-gray-700">{{ $selected->merk_tipe }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">Kilometer Terakhir</p>
                                            <p class="text-sm font-semibold text-gray-900">{{ number_format($selected->kilometer_terakhir) }} km</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">Status</p>
                                            @php $sk = $statusKendaraan[$selected->status_perawatan] ?? ['label' => $selected->status_perawatan, 'class' => 'bg-gray-100 text-gray-800']; @endphp
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $sk['class'] }}">{{ $sk['label'] }}</span>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">Transmisi</p>
                                            <p class="text-sm text-gray-700">{{ $selected->transmisi }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">Pengelola</p>
                                            <p class="text-sm text-gray-700">{{ $selected->pengelola?->name ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Form catat perjalanan --}}
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">Catat Perjalanan</h3>
                            </div>
                            <div class="p-6 text-gray-900">
                                <form method="POST" action="{{ route('mileage.trip.store') }}">
                                    @csrf
                                    <input type="hidden" name="id_kendaraan" value="{{ $selected->id }}">

                                    @if ($errors->any())
                                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
                                            Periksa kembali data yang Anda masukkan.
                                        </div>
                                    @endif

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <x-input-label for="tanggal_perjalanan" :value="__('Tanggal Perjalanan')" />
                                            <x-text-input id="tanggal_perjalanan" class="block mt-1 w-full" type="date" name="tanggal_perjalanan" :value="old('tanggal_perjalanan', now()->toDateString())" required />
                                            <x-input-error :messages="$errors->get('tanggal_perjalanan')" class="mt-2" />
                                        </div>
                                        <div>
                                            <x-input-label for="kilometer_awal" :value="__('Kilometer Awal')" />
                                            <x-text-input id="kilometer_awal" class="block mt-1 w-full" type="number" min="0" name="kilometer_awal" :value="old('kilometer_awal', $selected->kilometer_terakhir)" required />
                                            <x-input-error :messages="$errors->get('kilometer_awal')" class="mt-2" />
                                        </div>
                                        <div>
                                            <x-input-label for="kilometer_akhir" :value="__('Kilometer Akhir')" />
                                            <x-text-input id="kilometer_akhir" class="block mt-1 w-full" type="number" min="0" name="kilometer_akhir" :value="old('kilometer_akhir')" required />
                                            <x-input-error :messages="$errors->get('kilometer_akhir')" class="mt-2" />
                                        </div>
                                        <div>
                                            <x-input-label for="status_perjalanan" :value="__('Status Perjalanan')" />
                                            <select id="status_perjalanan" name="status_perjalanan" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                                <option value="SELESAI" @selected(old('status_perjalanan', 'SELESAI') === 'SELESAI')>Selesai</option>
                                                <option value="BERJALAN" @selected(old('status_perjalanan') === 'BERJALAN')>Berjalan</option>
                                                <option value="TERBATAS" @selected(old('status_perjalanan') === 'TERBATAS')>Terbatas</option>
                                            </select>
                                            <x-input-error :messages="$errors->get('status_perjalanan')" class="mt-2" />
                                        </div>
                                        <div class="md:col-span-2">
                                            <x-input-label for="keterangan" :value="__('Keterangan')" />
                                            <textarea id="keterangan" name="keterangan" rows="3"
                                                      class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                                      placeholder="Tujuan, petugas, atau keterangan perjalanan (opsional)">{{ old('keterangan') }}</textarea>
                                            <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end mt-6">
                                        <x-primary-button>{{ __('Simpan Perjalanan') }}</x-primary-button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- Trip history kendaraan terpilih --}}
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">Histori Perjalanan</h3>
                            </div>
                            <div class="p-6 text-gray-900">
                                @if ($riwayat->isEmpty())
                                    <p class="text-gray-500 text-center py-8">Belum ada histori perjalanan untuk kendaraan ini.</p>
                                @else
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Km Awal</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Km Akhir</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Jarak</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach ($riwayat as $m)
                                                    @php $st = $statusMap[$m->status_perjalanan] ?? ['label' => $m->status_perjalanan, 'class' => 'bg-gray-100 text-gray-800']; @endphp
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $m->tanggal_perjalanan->format('d M Y') }}</td>
                                                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">{{ number_format($m->kilometer_awal) }} km</td>
                                                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">{{ number_format($m->kilometer_akhir) }} km</td>
                                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">{{ number_format($m->total_jarak) }} km</td>
                                                        <td class="px-4 py-3 text-sm">
                                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $st['class'] }}">{{ $st['label'] }}</span>
                                                        </td>
                                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $m->keterangan ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
