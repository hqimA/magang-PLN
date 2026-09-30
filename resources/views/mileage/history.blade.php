@php
    $statusMap = [
        'SELESAI'  => ['label' => 'Selesai',  'class' => 'bg-green-100 text-green-800'],
        'BERJALAN' => ['label' => 'Berjalan', 'class' => 'bg-blue-100 text-blue-800'],
        'TERBATAS' => ['label' => 'Terbatas', 'class' => 'bg-yellow-100 text-yellow-800'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Trip History') }}
            </h2>
            <a href="{{ route('mileage.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Mileage Tracker
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <form method="GET" action="{{ route('mileage.history') }}" class="mb-6 grid grid-cols-1 sm:grid-cols-4 items-end gap-3">
                        <div>
                            <x-input-label for="kendaraan" :value="__('Kendaraan')" />
                            <select id="kendaraan" name="kendaraan" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Semua Kendaraan</option>
                                @foreach ($kendaraans as $k)
                                    <option value="{{ $k->id }}" @selected($kendaraanId === $k->id)>{{ $k->plat_nomor }} &mdash; {{ $k->merk_tipe }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="dari" :value="__('Dari Tanggal')" />
                            <x-text-input id="dari" type="date" name="dari" :value="$dari" class="block w-full" />
                        </div>
                        <div>
                            <x-input-label for="sampai" :value="__('Sampai Tanggal')" />
                            <x-text-input id="sampai" type="date" name="sampai" :value="$sampai" class="block w-full" />
                        </div>
                        <div class="flex items-center gap-2">
                            <x-primary-button>Tampilkan</x-primary-button>
                            <a href="{{ route('mileage.history') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Reset
                            </a>
                        </div>
                    </form>

                    <p class="mb-4 text-sm text-gray-500">
                        Total jarak: <span class="font-semibold text-gray-900">{{ number_format($riwayat->sum(fn ($m) => $m->total_jarak)) }} km</span>
                        dari <span class="font-semibold text-gray-900">{{ $riwayat->count() }}</span> perjalanan.
                    </p>

                    @if ($riwayat->isEmpty())
                        <p class="text-gray-500 text-center py-8">Data perjalanan tidak ditemukan.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kendaraan</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Km Awal</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Km Akhir</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Jarak</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($riwayat as $i => $m)
                                        @php $st = $statusMap[$m->status_perjalanan] ?? ['label' => $m->status_perjalanan, 'class' => 'bg-gray-100 text-gray-800']; @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $i + 1 }}</td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">{{ $m->kendaraan?->plat_nomor ?? '-' }}</td>
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
</x-app-layout>
