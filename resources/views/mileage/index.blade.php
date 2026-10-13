@php
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
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Armada</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">Daftar Kendaraan</h3>
                            <p class="mt-1 text-sm text-slate-500">Pilih kendaraan untuk mencatat perjalanan.</p>
                        </div>
                        <ul class="divide-y divide-gray-100 max-h-[32rem] overflow-y-auto">
                            @foreach ($kendaraans as $k)
                                @php $isActive = $selected && $k->id === $selected->id; @endphp
                                <li>
                                    <a href="{{ route('mileage.index', ['kendaraan' => $k->id]) }}"
                                       class="block border-l-4 px-5 py-4 transition {{ $isActive ? 'border-indigo-500 bg-indigo-50/70' : 'border-transparent hover:bg-slate-50' }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-sm font-semibold text-slate-900">{{ $k->plat_nomor }}</span>
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ number_format($k->kilometer_terakhir) }} km</span>
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $k->merk_tipe }} &middot; {{ $k->tahun_pembuatan }}</div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Detail + form perjalanan --}}
                    <div class="lg:col-span-2 space-y-6">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-5">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Kendaraan Terpilih</p>
                                    <h3 class="mt-1 text-lg font-semibold text-slate-900">Informasi Kendaraan</h3>
                                </div>
                            </div>
                            <div class="p-6 text-gray-900">
                                <div class="flex flex-col sm:flex-row gap-6">
                                    <div class="sm:w-48">
                                        @if ($selected->foto_kendaraan)
                                            <img src="{{ asset('storage/' . $selected->foto_kendaraan) }}"
                                                 alt="Foto {{ $selected->merk_tipe }}"
                                                 class="h-36 w-full rounded-xl border border-slate-200 object-cover shadow-sm" />
                                        @else
                                            <div class="flex h-36 w-full items-center justify-center rounded-xl border border-dashed border-slate-300 bg-gradient-to-br from-slate-50 to-indigo-50/60">
                                                <svg class="h-10 w-10 text-indigo-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Plat Nomor</p>
                                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $selected->plat_nomor }}</p>
                                        </div>
                                        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Merk / Tipe</p>
                                            <p class="mt-1 text-sm text-slate-700">{{ $selected->merk_tipe }}</p>
                                        </div>
                                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/70 p-4">
                                            <p class="text-xs font-medium uppercase tracking-wider text-indigo-600">Kilometer Terakhir</p>
                                            <p class="mt-1 text-lg font-bold text-indigo-950">{{ number_format($selected->kilometer_terakhir) }} <span class="text-sm font-semibold">km</span></p>
                                        </div>
                                        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Status Kendaraan</p>
                                            @php $sk = $statusKendaraan[$selected->status_perawatan] ?? ['label' => $selected->status_perawatan, 'class' => 'bg-gray-100 text-gray-800']; @endphp
                                            <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $sk['class'] }}">{{ $sk['label'] }}</span>
                                        </div>
                                        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Transmisi</p>
                                            <p class="mt-1 text-sm text-slate-700">{{ $selected->transmisi }}</p>
                                        </div>
                                        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Pengelola</p>
                                            <p class="mt-1 text-sm text-slate-700">{{ $selected->pengelola?->name ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Form catat perjalanan --}}
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-center gap-4 border-b border-indigo-100 bg-gradient-to-r from-indigo-600 via-indigo-600 to-sky-600 px-6 py-5">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white ring-1 ring-white/20">
                                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-100">Pencatatan Baru</p>
                                    <h3 class="mt-1 text-lg font-semibold text-white">Catat Perjalanan</h3>
                                    <p class="mt-1 text-sm text-indigo-100">Isi informasi odometer dan unggah foto sebagai bukti.</p>
                                </div>
                            </div>
                            <div class="p-6 text-gray-900">
                                <form method="POST" action="{{ route('mileage.trip.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="id_kendaraan" value="{{ $selected->id }}">

                                    @if ($errors->any())
                                        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                            Periksa kembali data yang Anda masukkan.
                                        </div>
                                    @endif

                                    <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
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
                                        <div class="md:col-span-2">
                                            <x-input-label for="foto_bukti" :value="__('Foto Bukti Odometer')" />
                                            <input id="foto_bukti" name="foto_bukti" type="file" accept="image/jpeg,image/png,image/webp" required
                                                   class="mt-1 block w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 text-sm text-slate-600 shadow-sm transition file:mr-4 file:border-0 file:bg-indigo-50 file:px-4 file:py-3 file:text-sm file:font-semibold file:text-indigo-700 hover:border-indigo-300 hover:file:bg-indigo-100" />
                                            <p class="mt-2 text-xs text-slate-500">Format JPG, PNG, atau WEBP. Ukuran maksimal 5 MB.</p>
                                            <x-input-error :messages="$errors->get('foto_bukti')" class="mt-2" />
                                        </div>
                                        <div class="md:col-span-2">
                                            <x-input-label for="keterangan" :value="__('Keterangan')" />
                                            <textarea id="keterangan" name="keterangan" rows="3"
                                                      class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                      placeholder="Tujuan, petugas, atau keterangan perjalanan (opsional)">{{ old('keterangan') }}</textarea>
                                            <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
                                        </div>
                                    </div>

                                    <div class="mt-6 flex flex-col-reverse justify-between gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center">
                                        <p class="text-xs text-slate-500">Pastikan angka odometer sesuai dengan foto bukti.</p>
                                        <x-primary-button>{{ __('Simpan Perjalanan') }}</x-primary-button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
