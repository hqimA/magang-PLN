<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Data Kendaraan') }}
            </h2>
            <a href="{{ route('kendaraan.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                + Tambah Kendaraan
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">


            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- Filter & pencarian --}}
                    <form method="GET" action="{{ route('kendaraan.index') }}" class="mb-6 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <div class="flex-1">
                            <x-text-input name="search" type="search" value="{{ $search ?? '' }}" placeholder="Cari plat nomor, merk/tipe, atau pengelola..." class="block w-full" />
                        </div>
                        <div class="sm:w-56">
                            <select name="status" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Semua Status</option>
                                <option value="BAIK" @selected(($status ?? '') === 'BAIK')>Aktif</option>
                                <option value="PERLU_SERVIS" @selected(($status ?? '') === 'PERLU_SERVIS')>Perlu Servis</option>
                                <option value="SEDANG_SERVIS" @selected(($status ?? '') === 'SEDANG_SERVIS')>Sedang Servis</option>
                                <option value="RUSAK" @selected(($status ?? '') === 'RUSAK')>Tidak Aktif</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-primary-button>Cari</x-primary-button>
                            @if(($search ?? '') !== '' || ($status ?? '') !== '')
                                <a href="{{ route('kendaraan.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>

                    @if($kendaraans->isEmpty())
                        <p class="text-gray-500 text-center py-8">
                            @if(($search ?? '') !== '' || ($status ?? '') !== '')
                                Data tidak ditemukan.
                            @else
                                Belum ada data kendaraan.
                            @endif <a href="{{ route('kendaraan.create') }}" class="text-indigo-600 hover:underline">Tambah sekarang</a>.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plat Nomor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Merk / Tipe</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahun</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Km</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengelola</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($kendaraans as $index => $kendaraan)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $index + 1 }}</td>
                                            <td class="px-4 py-3">
                                                @if($kendaraan->foto_kendaraan)
                                                    <img src="{{ asset('storage/' . $kendaraan->foto_kendaraan) }}"
                                                         alt="Foto {{ $kendaraan->merk_tipe }}"
                                                         class="h-14 w-20 object-cover rounded-md border border-gray-200" />
                                                @else
                                                    <div class="h-14 w-20 flex items-center justify-center rounded-md border border-dashed border-gray-300 bg-gray-50">
                                                        <svg class="h-7 w-7 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $kendaraan->plat_nomor }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $kendaraan->merk_tipe }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $kendaraan->tahun_pembuatan }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ number_format($kendaraan->kilometer_terakhir) }} km</td>
                                            <td class="px-4 py-3 text-sm">
                                                @php
                                                    $statusMap = [
                                                        'BAIK'         => ['label' => 'Aktif', 'class' => 'bg-green-100 text-green-800'],
                                                        'PERLU_SERVIS' => ['label' => 'Perlu Servis', 'class' => 'bg-yellow-100 text-yellow-800'],
                                                        'SEDANG_SERVIS'=> ['label' => 'Sedang Servis', 'class' => 'bg-orange-100 text-orange-800'],
                                                        'RUSAK'        => ['label' => 'Tidak Aktif', 'class' => 'bg-red-100 text-red-800'],
                                                    ];
                                                    $s = $statusMap[$kendaraan->status_perawatan] ?? ['label' => $kendaraan->status_perawatan, 'class' => 'bg-gray-100 text-gray-800'];
                                                @endphp
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $s['class'] }}">
                                                    {{ $s['label'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $kendaraan->pengelola?->name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <div class="flex items-center gap-2">
                                                    <a href="{{ route('kendaraan.edit', $kendaraan->id) }}"
                                                       class="inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                        Edit
                                                    </a>
                                                    @if(auth()->user()->isAdmin())
                                                        <form method="POST" action="{{ route('kendaraan.destroy', $kendaraan->id) }}"
                                                              onsubmit="return confirm('Yakin ingin menghapus kendaraan ini?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="inline-flex items-center px-3 py-1 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
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
