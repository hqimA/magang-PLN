<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Detail Pengajuan Maintenance') }}
            </h2>
            <a href="{{ route('maintenance.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition ease-in-out duration-150">
                &larr; Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Informasi Kendaraan --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-gray-700 mb-4">Informasi Kendaraan</h3>
                    <div class="flex items-start gap-5">
                        @if($pengajuan->kendaraan->foto_kendaraan)
                            <img src="{{ asset('storage/' . $pengajuan->kendaraan->foto_kendaraan) }}"
                                 alt="Foto Kendaraan"
                                 class="h-24 w-36 object-cover rounded-lg border border-gray-200 shrink-0" />
                        @else
                            <div class="h-24 w-36 flex items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 shrink-0">
                                <svg class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                </svg>
                            </div>
                        @endif
                        <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm flex-1">
                            <div>
                                <dt class="text-gray-500">Plat Nomor</dt>
                                <dd class="font-semibold text-gray-900">{{ $pengajuan->kendaraan->plat_nomor }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Merk / Tipe</dt>
                                <dd class="font-medium text-gray-900">{{ $pengajuan->kendaraan->merk_tipe }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Tahun</dt>
                                <dd class="text-gray-900">{{ $pengajuan->kendaraan->tahun_pembuatan }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Pengelola</dt>
                                <dd class="text-gray-900">{{ $pengajuan->kendaraan->pengelola?->name ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Detail Pengajuan --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-gray-700 mb-4">Detail Pengajuan</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Jenis Pengajuan</dt>
                            <dd class="mt-1">
                                @php
                                    $jenisClass = $pengajuan->jenis_pengajuan === 'DARURAT'
                                        ? 'bg-red-100 text-red-800'
                                        : 'bg-blue-100 text-blue-800';
                                @endphp
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $jenisClass }}">
                                    {{ $pengajuan->jenis_pengajuan }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Status Persetujuan</dt>
                            <dd class="mt-1">
                                @php
                                    $statusMap = [
                                        'MENUNGGU'  => ['label' => 'Menunggu',  'class' => 'bg-amber-100 text-amber-800'],
                                        'DISETUJUI' => ['label' => 'Disetujui', 'class' => 'bg-emerald-100 text-emerald-800'],
                                        'DITOLAK'   => ['label' => 'Ditolak',   'class' => 'bg-red-100 text-red-800'],
                                    ];
                                    $st = $statusMap[$pengajuan->status_persetujuan] ?? ['label' => $pengajuan->status_persetujuan, 'class' => 'bg-gray-100 text-gray-800'];
                                @endphp
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $st['class'] }}">
                                    {{ $st['label'] }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Tanggal Pengajuan</dt>
                            <dd class="mt-1 font-medium text-gray-900">
                                {{ $pengajuan->dibuat_pada->translatedFormat('d F Y') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Pengaju</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $pengajuan->pengaju?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Kilometer Akhir</dt>
                            <dd class="mt-1 font-semibold text-gray-900">
                                @php
                                    $kmAkhir = $pengajuan->kendaraan->mileageTerbaru?->kilometer_akhir ?? $pengajuan->kilometer_pengajuan ?? $pengajuan->kendaraan->kilometer_terakhir;
                                @endphp
                                {{ $kmAkhir !== null ? number_format($kmAkhir) . ' km' : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Estimasi Biaya</dt>
                            <dd class="mt-1 font-semibold text-gray-900">
                                {{ $pengajuan->estimasi_biaya
                                    ? 'Rp ' . number_format($pengajuan->estimasi_biaya, 0, ',', '.')
                                    : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Disetujui Oleh</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $pengajuan->disetujuiOleh?->name ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500">Deskripsi Keluhan</dt>
                            <dd class="mt-1 text-gray-900 whitespace-pre-wrap">{{ $pengajuan->deskripsi_keluhan }}</dd>
                        </div>
                        @if($pengajuan->status_persetujuan === 'DITOLAK' && $pengajuan->alasan_penolakan)
                            <div class="sm:col-span-2">
                                <dt class="text-gray-500">Alasan Penolakan</dt>
                                <dd class="mt-1 text-red-700 whitespace-pre-wrap bg-red-50 rounded-md p-3 border border-red-100">
                                    {{ $pengajuan->alasan_penolakan }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Riwayat Servis jika sudah diselesaikan --}}
            @if($pengajuan->riwayatServis)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-gray-700 mb-4">Riwayat Servis Terkait</h3>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                            <div>
                                <dt class="text-gray-500">Tanggal Servis</dt>
                                <dd class="mt-1 font-medium text-gray-900">
                                    {{ \Carbon\Carbon::parse($pengajuan->riwayatServis->tanggal_servis)->translatedFormat('d F Y') }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Nama Bengkel</dt>
                                <dd class="mt-1 font-medium text-gray-900">{{ $pengajuan->riwayatServis->nama_bengkel }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Odometer saat Servis</dt>
                                <dd class="mt-1 font-medium text-gray-900">
                                    {{ number_format($pengajuan->riwayatServis->kilometer_servis) }} km
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Total Biaya Aktual</dt>
                                <dd class="mt-1 font-semibold text-gray-900">
                                    Rp {{ number_format($pengajuan->riwayatServis->total_biaya, 0, ',', '.') }}
                                </dd>
                            </div>
                        </dl>
                        <div class="mt-4">
                            <a href="{{ route('service-history.show', $pengajuan->riwayatServis->id) }}"
                               class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                Lihat Detail Servis Lengkap &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Action Buttons: Admin + status masih MENUNGGU --}}
            @if(auth()->user()->isAdmin() && $pengajuan->status_persetujuan === 'MENUNGGU')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex flex-col sm:flex-row gap-3">
                        <form method="POST" action="{{ route('pengajuan.approve', $pengajuan->id) }}" class="flex-1">
                            @csrf
                            @method('PATCH')
                            <x-primary-button
                                class="w-full justify-center bg-emerald-600 hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-900 focus:ring-emerald-500"
                                onclick="return confirm('Apakah Anda yakin ingin menyetujui pengajuan ini?')">
                                {{ __('Approve Pengajuan') }}
                            </x-primary-button>
                        </form>
                        <form method="POST" action="{{ route('pengajuan.reject', $pengajuan->id) }}"
                              x-data="{ open: false }"
                              x-on:submit.prevent="if(!open) { open = true; } else { $el.submit(); }"
                              class="flex-1 flex flex-col gap-2">
                            @csrf
                            @method('PATCH')
                            <div x-show="open" class="w-full" style="display:none;">
                                <x-text-input name="alasan_penolakan" class="w-full text-sm"
                                              placeholder="Masukkan alasan penolakan..." />
                            </div>
                            <div class="flex gap-2">
                                <x-danger-button class="flex-1 justify-center"
                                                 x-text="open ? 'Konfirmasi Tolak' : 'Tolak Pengajuan'">
                                    {{ __('Tolak Pengajuan') }}
                                </x-danger-button>
                                <x-secondary-button type="button" x-show="open"
                                                    x-on:click="open = false" style="display:none;">
                                    Batal
                                </x-secondary-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
