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

            {{-- Rincian Komponen yang Diajukan --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-700">Komponen & Suku Cadang yang Diajukan</h3>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200">
                            {{ $pengajuan->komponen->count() }} Komponen
                        </span>
                    </div>

                    @if($pengajuan->komponen->isEmpty())
                        <div class="text-center py-6 bg-gray-50 rounded-lg border border-dashed border-gray-200 text-gray-500 text-sm">
                            Tidak ada rincian komponen spesifik (Pengajuan Servis Umum).
                        </div>
                    @else
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
                                <thead class="bg-gray-50 text-gray-600 font-semibold text-xs uppercase tracking-wider">
                                    <tr>
                                        <th class="px-4 py-3">No</th>
                                        <th class="px-4 py-3">Komponen</th>
                                        <th class="px-4 py-3">Kategori</th>
                                        <th class="px-4 py-3 text-center">Aksi yang Diajukan</th>
                                        <th class="px-4 py-3">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach($pengajuan->komponen as $index => $item)
                                        <tr class="hover:bg-gray-50/70 transition">
                                            <td class="px-4 py-3 font-medium text-gray-400 w-12">{{ $index + 1 }}</td>
                                            <td class="px-4 py-3 font-semibold text-gray-900">
                                                {{ $item->komponenKendaraan?->nama_komponen ?? 'Komponen #' . $item->id_komponen_kendaraan }}
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 text-xs">
                                                @php
                                                    $kat = $item->komponenKendaraan?->kategori instanceof \BackedEnum
                                                        ? $item->komponenKendaraan->kategori->value
                                                        : (string) ($item->komponenKendaraan?->kategori ?? '-');
                                                @endphp
                                                <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 font-medium">
                                                    {{ str_replace('_', ' ', ucwords(strtolower($kat), '_')) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @php
                                                    $aksiVal = $item->jenis_aksi instanceof \BackedEnum ? $item->jenis_aksi->value : (string) $item->jenis_aksi;
                                                @endphp
                                                @if($aksiVal === 'G')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                                        Ganti (G)
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                                        Periksa (P)
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 text-xs italic">
                                                {{ $item->catatan ?? '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
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

            {{-- Action Buttons: Selesaikan Maintenance (status DISETUJUI & belum selesai) --}}
            @if($pengajuan->status_persetujuan === 'DISETUJUI' && !$pengajuan->riwayatServis)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h4 class="font-semibold text-gray-900">Maintenance Sedang Berjalan</h4>
                            <p class="text-sm text-gray-500 mt-1">Servis telah disetujui. Setelah pengerjaan selesai, klik tombol untuk mencatat data ke Riwayat Servis.</p>
                        </div>
                        <x-primary-button
                            type="button"
                            x-data=""
                            x-on:click="$dispatch('open-modal', 'modal-selesaikan-maintenance-show')"
                            class="bg-emerald-600 hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-900 shrink-0 justify-center">
                            Selesaikan Maintenance
                        </x-primary-button>
                    </div>
                </div>

                {{-- Modal Selesaikan Maintenance --}}
                <x-modal name="modal-selesaikan-maintenance-show" focusable>
                    <form method="POST" action="{{ route('riwayat-servis.store') }}" enctype="multipart/form-data" class="p-6">
                        @csrf
                        <input type="hidden" name="id_pengajuan" value="{{ $pengajuan->id }}" />
                        <input type="hidden" name="id_kendaraan" value="{{ $pengajuan->id_kendaraan }}" />

                        <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">
                                Selesaikan Maintenance
                            </h2>
                            <button type="button" x-on:click="$dispatch('close')" class="text-gray-400 hover:text-gray-500">
                                <span class="sr-only">Tutup</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <p class="mt-2 text-sm text-gray-500">
                            Lengkapi rincian servis selesai untuk dicatat ke Riwayat Servis kendaraan.
                        </p>

                        <div class="mt-4 p-3 bg-gray-50 rounded-lg text-sm space-y-1.5 border border-gray-200">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500 text-xs uppercase tracking-wider font-medium">Kendaraan</span>
                                <span class="font-semibold text-gray-900">{{ $pengajuan->kendaraan->merk_tipe }} ({{ $pengajuan->kendaraan->plat_nomor }})</span>
                            </div>
                            <div class="flex justify-between items-start gap-4">
                                <span class="text-gray-500 text-xs uppercase tracking-wider font-medium">Keluhan</span>
                                <span class="text-gray-700 text-right truncate max-w-xs">{{ $pengajuan->deskripsi_keluhan }}</span>
                            </div>
                        </div>

                        <div class="mt-5 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="tanggal_servis_show" value="Tanggal Servis Selesai *" />
                                    <x-text-input id="tanggal_servis_show" name="tanggal_servis" type="date"
                                                  class="mt-1 block w-full text-sm"
                                                  value="{{ date('Y-m-d') }}" required />
                                    <x-input-error :messages="$errors->get('tanggal_servis')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="kilometer_servis_show" value="Odometer Selesai (KM) *" />
                                    <x-text-input id="kilometer_servis_show" name="kilometer_servis" type="number" min="0"
                                                  class="mt-1 block w-full text-sm"
                                                  value="{{ $kmAkhir ?? $pengajuan->kendaraan->kilometer_terakhir }}" required />
                                    <x-input-error :messages="$errors->get('kilometer_servis')" class="mt-1" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="nama_bengkel_show" value="Nama Bengkel / Pelaksana Servis *" />
                                <x-text-input id="nama_bengkel_show" name="nama_bengkel" type="text"
                                              class="mt-1 block w-full text-sm"
                                              placeholder="Contoh: Bengkel Resmi Toyota / Bengkel Rekanan PLN" required />
                                <x-input-error :messages="$errors->get('nama_bengkel')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label for="total_biaya_show" value="Total Biaya Aktual (Rp) *" />
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm pointer-events-none">Rp</span>
                                    <x-text-input id="total_biaya_show" name="total_biaya" type="number" min="0" step="1000"
                                                  class="block w-full pl-9 text-sm"
                                                  value="{{ (int) $pengajuan->estimasi_biaya }}" required />
                                </div>
                                <x-input-error :messages="$errors->get('total_biaya')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label for="foto_nota_show" value="Upload Nota / Dokumentasi Servis (Opsional)" />
                                <input id="foto_nota_show" name="foto_nota" type="file" accept="image/*,.pdf"
                                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                <x-input-error :messages="$errors->get('foto_nota')" class="mt-1" />
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-gray-200">
                            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                                Batal
                            </x-secondary-button>
                            <x-primary-button class="bg-emerald-600 hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-900">
                                Simpan ke Riwayat Servis
                            </x-primary-button>
                        </div>
                    </form>
                </x-modal>
            @endif

        </div>
    </div>
</x-app-layout>
