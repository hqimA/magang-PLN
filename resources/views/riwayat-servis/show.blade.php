<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Detail Riwayat Servis') }}
            </h2>
            <a href="{{ route('service-history.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition ease-in-out duration-150">
                &larr; Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Kendaraan Info --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-gray-700 mb-4">Informasi Kendaraan</h3>
                    <div class="flex items-start gap-5">
                        @if($riwayatServis->kendaraan->foto_kendaraan)
                            <img src="{{ asset('storage/' . $riwayatServis->kendaraan->foto_kendaraan) }}"
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
                                <dd class="font-semibold text-gray-900">{{ $riwayatServis->kendaraan->plat_nomor }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Merk / Tipe</dt>
                                <dd class="font-medium text-gray-900">{{ $riwayatServis->kendaraan->merk_tipe }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Tahun</dt>
                                <dd class="text-gray-900">{{ $riwayatServis->kendaraan->tahun_pembuatan }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Pengelola</dt>
                                <dd class="text-gray-900">{{ $riwayatServis->kendaraan->pengelola?->name ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Servis Detail --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-gray-700 mb-4">Detail Servis</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Tanggal Servis</dt>
                            <dd class="mt-1 font-medium text-gray-900">
                                {{ $riwayatServis->tanggal_servis->translatedFormat('d F Y') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Nama Bengkel</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $riwayatServis->nama_bengkel }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Odometer saat Servis</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ number_format($riwayatServis->kilometer_servis) }} km</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Target KM Servis Berikutnya</dt>
                            <dd class="mt-1 font-medium text-gray-900">
                                {{ $riwayatServis->target_kilometer_berikutnya
                                    ? number_format($riwayatServis->target_kilometer_berikutnya) . ' km'
                                    : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Total Biaya</dt>
                            <dd class="mt-1 font-semibold text-gray-900">
                                Rp {{ number_format($riwayatServis->total_biaya, 0, ',', '.') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Dicatat Oleh</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $riwayatServis->pembuat?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Jenis Servis</dt>
                            <dd class="mt-1 font-medium text-gray-900">
                                @if($riwayatServis->pengajuan)
                                    @php
                                        $jenis = $riwayatServis->pengajuan->jenis_pengajuan;
                                        $jenisClass = $jenis === 'DARURAT' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800';
                                    @endphp
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $jenisClass }}">
                                        {{ $jenis }}
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                        UMUM
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Status</dt>
                            <dd class="mt-1 font-medium text-gray-900">
                                @php
                                    $status = $riwayatServis->pengajuan ? $riwayatServis->pengajuan->status_persetujuan : 'SELESAI';
                                    $statusClass = match($status) {
                                        'MENUNGGU' => 'bg-amber-100 text-amber-800',
                                        'DISETUJUI', 'SELESAI' => 'bg-emerald-100 text-emerald-800',
                                        'DITOLAK' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                @endphp
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusClass }}">
                                    {{ $status }}
                                </span>
                            </dd>
                        </div>
                        @if($riwayatServis->pengajuan)
                            <div class="sm:col-span-2">
                                <dt class="text-gray-500">Deskripsi Keluhan</dt>
                                <dd class="mt-1 text-gray-900 whitespace-pre-wrap">{{ $riwayatServis->pengajuan->deskripsi_keluhan }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Foto Nota --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-gray-700 mb-4">Foto / Nota Servis</h3>
                    @if($riwayatServis->foto_nota)
                        @if(str_ends_with(strtolower($riwayatServis->foto_nota), '.pdf'))
                            <a href="{{ asset('storage/' . $riwayatServis->foto_nota) }}" target="_blank"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 border border-red-200 rounded-md text-sm font-medium text-red-700 hover:bg-red-100 transition">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                Lihat Nota PDF
                            </a>
                        @else
                            <a href="{{ asset('storage/' . $riwayatServis->foto_nota) }}" target="_blank">
                                <img src="{{ asset('storage/' . $riwayatServis->foto_nota) }}"
                                     alt="Foto Nota Servis"
                                     class="max-h-80 rounded-lg border border-gray-200 object-contain" />
                            </a>
                        @endif
                    @else
                        <p class="text-sm text-gray-400 italic">Tidak ada foto nota yang dilampirkan.</p>
                    @endif
                </div>
            </div>

            {{-- Rincian Sparepart --}}
            @if($riwayatServis->rincianSparepart->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-gray-700 mb-4">Rincian Sparepart</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Sparepart</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Harga Satuan</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    @foreach($riwayatServis->rincianSparepart as $i => $sp)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-700">{{ $i + 1 }}</td>
                                            <td class="px-4 py-3 font-medium text-gray-900">{{ $sp->nama_sparepart }}</td>
                                            <td class="px-4 py-3 text-right text-gray-700">{{ $sp->jumlah }}</td>
                                            <td class="px-4 py-3 text-right text-gray-700">Rp {{ number_format($sp->harga_satuan, 0, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-right font-medium text-gray-900">Rp {{ number_format($sp->subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-gray-50">
                                        <td colspan="4" class="px-4 py-3 text-right font-semibold text-gray-700">Total Biaya Servis</td>
                                        <td class="px-4 py-3 text-right font-bold text-gray-900">
                                            Rp {{ number_format($riwayatServis->total_biaya, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
