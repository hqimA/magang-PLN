<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Input Odometer Manual') }}
            </h2>
            <a href="{{ route('mileage.index', $selected ? ['kendaraan' => $selected->id] : []) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Mileage Tracker
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
                    {{-- Form --}}
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">Form Odometer</h3>
                            </div>
                            <div class="p-6 text-gray-900">
                                <form method="POST" action="{{ route('mileage.odometer.store') }}">
                                    @csrf

                                    @if ($errors->any())
                                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
                                            Periksa kembali data yang Anda masukkan.
                                        </div>
                                    @endif

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="md:col-span-2">
                                            <x-input-label for="id_kendaraan" :value="__('Kendaraan')" />
                                            <select id="id_kendaraan" name="id_kendaraan"
                                                    onchange="window.location.href='{{ route('mileage.odometer') }}?kendaraan='+this.value"
                                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                                <option value="">-- Pilih Kendaraan --</option>
                                                @foreach ($kendaraans as $k)
                                                    <option value="{{ $k->id }}" @selected(old('id_kendaraan', $selected?->id) == $k->id)>
                                                        {{ $k->plat_nomor }} &mdash; {{ $k->merk_tipe }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <x-input-error :messages="$errors->get('id_kendaraan')" class="mt-2" />
                                        </div>

                                        <div>
                                            <x-input-label for="odometer_terakhir_display" :value="__('Odometer Terakhir')" />
                                            <x-text-input id="odometer_terakhir_display" class="block mt-1 w-full bg-gray-50" type="text" value="{{ $selected ? number_format($selected->kilometer_terakhir).' km' : '-' }}" readonly />
                                            <p class="mt-1 text-xs text-gray-500">Nilai odometer baru tidak boleh lebih kecil dari nilai ini.</p>
                                        </div>

                                        <div>
                                            <x-input-label for="odometer_baru" :value="__('Odometer Terbaru (km)')" />
                                            <x-text-input id="odometer_baru" class="block mt-1 w-full" type="number" min="{{ $selected?->kilometer_terakhir ?? 0 }}" step="1" name="odometer_baru"
                                                          :value="old('odometer_baru', $selected?->kilometer_terakhir)" required />
                                            <x-input-error :messages="$errors->get('odometer_baru')" class="mt-2" />
                                        </div>

                                        <div>
                                            <x-input-label for="tanggal_perjalanan" :value="__('Tanggal Pencatatan')" />
                                            <x-text-input id="tanggal_perjalanan" class="block mt-1 w-full" type="date" name="tanggal_perjalanan" :value="old('tanggal_perjalanan', now()->toDateString())" required />
                                            <x-input-error :messages="$errors->get('tanggal_perjalanan')" class="mt-2" />
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
                                                      placeholder="Keterangan pencatatan odometer (opsional)">{{ old('keterangan') }}</textarea>
                                            <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end mt-6">
                                        <x-primary-button>{{ __('Simpan Odometer') }}</x-primary-button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">10 Pencatatan Terakhir</h3>
                            </div>
                            <div class="p-6 text-gray-900">
                                @if ($riwayatTerbaru->isEmpty())
                                    <p class="text-gray-500 text-center py-8">Belum ada pencatatan odometer.</p>
                                @else
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Km Awal</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Km Akhir</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Jarak</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach ($riwayatTerbaru as $m)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $m->tanggal_perjalanan->format('d M Y') }}</td>
                                                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">{{ number_format($m->kilometer_awal) }} km</td>
                                                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">{{ number_format($m->kilometer_akhir) }} km</td>
                                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">{{ number_format($m->total_jarak) }} km</td>
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

                    {{-- Ringkasan kendaraan terpilih --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg h-fit">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">Ringkasan Kendaraan</h3>
                        </div>
                        <div class="p-6 text-gray-900 space-y-3 text-sm">
                            @if ($selected)
                                <div class="flex justify-between gap-2">
                                    <span class="text-gray-500">Plat Nomor</span>
                                    <span class="font-medium text-gray-900">{{ $selected->plat_nomor }}</span>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <span class="text-gray-500">Merk / Tipe</span>
                                    <span class="text-gray-700 text-right">{{ $selected->merk_tipe }}</span>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <span class="text-gray-500">Odometer Terakhir</span>
                                    <span class="font-semibold text-gray-900">{{ number_format($selected->kilometer_terakhir) }} km</span>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <span class="text-gray-500">Jumlah Perjalanan</span>
                                    <span class="text-gray-700">{{ $riwayatTerbaru->count() > 10 ? '>10' : $riwayatTerbaru->count() }} data terbaru</span>
                                </div>
                                <a href="{{ route('mileage.index', ['kendaraan' => $selected->id]) }}"
                                   class="block text-center mt-4 inline-flex w-full items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Lihat Mileage Tracker
                                </a>
                            @else
                                <p class="text-gray-500 text-center py-4">Pilih kendaraan terlebih dahulu.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
