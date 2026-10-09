<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $kendaraan->merk_tipe }} — <span class="font-mono text-indigo-600">{{ $kendaraan->plat_nomor }}</span>
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Detail kendaraan dan manajemen suku cadang & komponen servis mandiri.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('kendaraan.edit', $kendaraan->id) }}"
                   class="inline-flex items-center px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-md transition border border-slate-300">
                    <svg class="h-4 w-4 me-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Kendaraan
                </a>
                <a href="{{ route('kendaraan.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition">
                    &larr; Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ session('success') }}
                    </span>
                </div>
            @endif
            @if(session('info'))
                <div class="p-4 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 text-sm flex items-center justify-between">
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            {{-- 1. Kartu Info Kendaraan --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-sm">
                    <div>
                        <span class="text-xs text-gray-400 block">Plat Nomor</span>
                        <span class="font-bold text-gray-900 font-mono">{{ $kendaraan->plat_nomor }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block">Merk / Tipe</span>
                        <span class="font-semibold text-gray-800">{{ $kendaraan->merk_tipe }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block">Bahan Bakar</span>
                        <span class="font-medium text-gray-800">{{ $kendaraan->jenis_bbm }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block">Transmisi</span>
                        <span class="font-medium text-gray-800">{{ $kendaraan->transmisi }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block">Kilometer Saat Ini</span>
                        <span class="font-bold text-indigo-600">
                            {{ number_format($kendaraan->mileageTerbaru?->kilometer_akhir ?? $kendaraan->kilometer_terakhir) }} km
                        </span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block">Pengelola</span>
                        <span class="font-medium text-gray-800">{{ $kendaraan->pengelola?->name ?? '-' }}</span>
                    </div>
                </div>
            </div>

            {{-- 2. Banner Rekomendasi & Penerapan Template --}}
            <div class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-xl p-6 shadow-sm">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-indigo-600 text-white">
                                Rekomendasi Template
                            </span>
                            @if($kendaraan->template)
                                <span class="text-xs text-gray-500">
                                    Template aktif saat ini: <strong>{{ $kendaraan->template->nama }}</strong>
                                </span>
                            @endif
                        </div>

                        @if($rekomendasiTemplate)
                            <h4 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                <span>Template Cocok:</span>
                                <span class="text-indigo-700 underline decoration-indigo-400">{{ $rekomendasiTemplate->nama }}</span>
                            </h4>
                            <p class="text-xs text-gray-600">
                                Berdasarkan spesifikasi mesin <strong>{{ $kendaraan->jenis_bbm }}</strong> dan transmisi <strong>{{ $kendaraan->transmisi }}</strong>, template ini menyediakan rekomendasi jadwal dan komponen standar pabrikan.
                            </p>
                        @else
                            <h4 class="text-base font-bold text-gray-900">Pilih Template Servis</h4>
                            <p class="text-xs text-gray-600">Pilih salah satu template master untuk menyalin seluruh daftar suku cadang standar ke kendaraan ini.</p>
                        @endif
                    </div>

                    {{-- Form Terapkan Template --}}
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <form method="POST" action="{{ route('kendaraan.apply-template', $kendaraan->id) }}"
                              onsubmit="return confirm('Terapkan template ini ke {{ $kendaraan->plat_nomor }}? Seluruh komponen dari template akan disalin menjadi data mandiri mobil ini.')"
                              class="flex flex-wrap items-center gap-2">
                            @csrf

                            <select name="id_template" class="text-xs font-semibold rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 py-2">
                                @foreach($allTemplates as $tmpl)
                                    <option value="{{ $tmpl->id }}" {{ ($rekomendasiTemplate && $rekomendasiTemplate->id === $tmpl->id) ? 'selected' : '' }}>
                                        {{ $tmpl->nama }} {{ ($rekomendasiTemplate && $rekomendasiTemplate->id === $tmpl->id) ? '★ (Rekomendasi)' : '' }}
                                    </option>
                                @endforeach
                            </select>

                            <input type="hidden" name="mode" value="replace">

                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md transition shadow-sm">
                                <svg class="h-4 w-4 me-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                </svg>
                                {{ $kendaraan->komponen->isNotEmpty() ? 'Terapkan Ulang Template' : 'Terapkan Template' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- 3. Daftar Suku Cadang & Komponen Milik Kendaraan Ini --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5 border-b pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900">Suku Cadang & Komponen Kendaraan Ini</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                    {{ $kendaraan->komponen->count() }} Item
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Daftar komponen ini bersifat mandiri dan eksklusif untuk <strong>{{ $kendaraan->plat_nomor }}</strong>.</p>
                        </div>

                        <button type="button" onclick="document.getElementById('modal-tambah-komponen').classList.remove('hidden')"
                                class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-md transition shadow-sm">
                            <svg class="h-4 w-4 me-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            + Tambah Suku Cadang Khusus
                        </button>
                    </div>

                    @if($kendaraan->komponen->isEmpty())
                        <div class="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.27.1.06-.12l-.773.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.15.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.27-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.11v-1.093c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <p class="font-medium text-gray-700">Kendaraan ini belum memiliki daftar suku cadang</p>
                            <p class="text-xs text-gray-500 mt-1">Gunakan tombol <strong>"Terapkan Template"</strong> di atas untuk menyalin komponen standar, atau tambah suku cadang kustom.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto border border-gray-200 rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-slate-50 text-gray-600 font-semibold text-xs uppercase tracking-wider">
                                    <tr>
                                        <th class="px-4 py-3 text-left w-12">No</th>
                                        <th class="px-4 py-3 text-left">Nama Suku Cadang</th>
                                        <th class="px-4 py-3 text-left">Kategori</th>
                                        <th class="px-4 py-3 text-center">Interval Siklus</th>
                                        <th class="px-4 py-3 text-center">Aksi Default</th>
                                        <th class="px-4 py-3 text-center">Target Jatuh Tempo</th>
                                        <th class="px-4 py-3 text-center">Status</th>
                                        <th class="px-4 py-3 text-center w-28">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach($kendaraan->komponen as $index => $komp)
                                        @php
                                            $currentKm = $kendaraan->mileageTerbaru?->kilometer_akhir ?? $kendaraan->kilometer_terakhir ?? 0;
                                            $sisaKm = $komp->km_jatuh_tempo ? ($komp->km_jatuh_tempo - $currentKm) : null;
                                            $status = 'AMAN';
                                            if ($sisaKm !== null) {
                                                if ($sisaKm <= 0) $status = 'JATUH_TEMPO';
                                                elseif ($sisaKm <= 1000) $status = 'SEGERA';
                                                else $status = 'AMAN';
                                            } else {
                                                $status = 'BELUM_DATA';
                                            }
                                            $statusBadge = [
                                                'JATUH_TEMPO' => 'bg-red-100 text-red-800 border-red-200',
                                                'SEGERA'      => 'bg-amber-100 text-amber-800 border-amber-200',
                                                'AMAN'        => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                'BELUM_DATA'  => 'bg-gray-100 text-gray-600 border-gray-200',
                                            ][$status];
                                        @endphp
                                        <tr class="hover:bg-slate-50/70 transition {{ !$komp->is_aktif ? 'opacity-50' : '' }}">
                                            <td class="px-4 py-3 text-center font-semibold text-gray-400 text-xs">{{ $index + 1 }}</td>
                                            <td class="px-4 py-3 font-semibold text-gray-900">
                                                {{ $komp->nama_komponen }}
                                                @if(!$komp->is_aktif)
                                                    <span class="text-[10px] text-gray-400 font-normal block">(Nonaktif)</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-xs text-gray-600">
                                                @php $kat = $komp->kategori instanceof \BackedEnum ? $komp->kategori->value : (string) $komp->kategori; @endphp
                                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-medium">
                                                    {{ str_replace('_', ' ', ucwords(strtolower($kat), '_')) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center text-xs font-mono font-medium text-gray-700">
                                                {{ $komp->interval_km ? number_format($komp->interval_km) . ' km' : '-' }}
                                                @if($komp->interval_bulan)
                                                    <span class="text-gray-400 block text-[10px]">/ {{ $komp->interval_bulan }} bln</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @php $aksi = $komp->jenis_aksi_default instanceof \BackedEnum ? $komp->jenis_aksi_default->value : (string) $komp->jenis_aksi_default; @endphp
                                                <span class="inline-flex px-2 py-0.5 text-xs font-bold rounded {{ $aksi === 'G' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                                    {{ $aksi === 'G' ? 'Ganti (G)' : 'Periksa (P)' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center text-xs">
                                                @if($komp->km_jatuh_tempo)
                                                    <span class="font-bold text-gray-800">{{ number_format($komp->km_jatuh_tempo) }} km</span>
                                                    <span class="block text-[10px] {{ $sisaKm <= 0 ? 'text-red-600 font-semibold' : 'text-gray-400' }}">
                                                        {{ $sisaKm <= 0 ? 'Terlewat ' . number_format(abs($sisaKm)) . ' km' : 'Sisa ' . number_format($sisaKm) . ' km' }}
                                                    </span>
                                                @else
                                                    <span class="text-gray-400 italic">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex px-2 py-0.5 text-[11px] font-semibold rounded border {{ $statusBadge }}">
                                                    {{ str_replace('_', ' ', $status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    {{-- Tombol Edit Komponen Modal --}}
                                                    <button type="button"
                                                            onclick="editKomponenModal({{ json_encode($komp) }})"
                                                            class="p-1 text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 rounded transition" title="Edit Komponen">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </button>

                                                    {{-- Tombol Hapus Komponen --}}
                                                    <form method="POST" action="{{ route('kendaraan.komponen.destroy', ['kendaraan' => $kendaraan->id, 'komponen' => $komp->id]) }}"
                                                          onsubmit="return confirm('Hapus komponen {{ $komp->nama_komponen }} dari kendaraan ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition" title="Hapus">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </form>
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

    {{-- Modal Tambah Komponen Khusus --}}
    <div id="modal-tambah-komponen" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between pb-3 border-b mb-4">
                <h3 class="text-base font-bold text-gray-900">Tambah Suku Cadang Khusus</h3>
                <button type="button" onclick="document.getElementById('modal-tambah-komponen').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    &times;
                </button>
            </div>

            <form method="POST" action="{{ route('kendaraan.komponen.store', $kendaraan->id) }}" class="space-y-4">
                @csrf
                <div>
                    <x-input-label for="add_nama" :value="__('Nama Suku Cadang')" class="text-xs font-semibold" />
                    <x-text-input id="add_nama" name="nama_komponen" type="text" class="mt-1 block w-full text-sm" placeholder="Contoh: Winch Derek Depan" required />
                </div>

                <div>
                    <x-input-label for="add_kategori" :value="__('Kategori')" class="text-xs font-semibold" />
                    <select id="add_kategori" name="kategori" class="mt-1 block w-full border-gray-300 rounded-md text-sm" required>
                        @foreach($kategoriList as $kat)
                            @php $val = $kat->value; @endphp
                            <option value="{{ $val }}">{{ str_replace('_', ' ', ucwords(strtolower($val), '_')) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="add_interval_km" :value="__('Interval KM')" class="text-xs font-semibold" />
                        <x-text-input id="add_interval_km" name="interval_km" type="number" min="500" step="500" value="10000" class="mt-1 block w-full text-sm font-mono" required />
                    </div>
                    <div>
                        <x-input-label for="add_interval_bulan" :value="__('Interval Bulan')" class="text-xs font-semibold" />
                        <x-text-input id="add_interval_bulan" name="interval_bulan" type="number" min="1" value="6" class="mt-1 block w-full text-sm font-mono" />
                    </div>
                </div>

                <div>
                    <x-input-label :value="__('Aksi Servis Default')" class="text-xs font-semibold" />
                    <div class="mt-1 flex items-center gap-4 text-sm">
                        <label class="inline-flex items-center gap-1 cursor-pointer">
                            <input type="radio" name="jenis_aksi_default" value="G" checked class="text-indigo-600">
                            <span>Ganti (G)</span>
                        </label>
                        <label class="inline-flex items-center gap-1 cursor-pointer">
                            <input type="radio" name="jenis_aksi_default" value="P" class="text-indigo-600">
                            <span>Periksa (P)</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t">
                    <button type="button" onclick="document.getElementById('modal-tambah-komponen').classList.add('hidden')"
                            class="px-3 py-1.5 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                        Batal
                    </button>
                    <x-primary-button class="text-xs py-2">
                        {{ __('Simpan Suku Cadang') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Komponen --}}
    <div id="modal-edit-komponen" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between pb-3 border-b mb-4">
                <h3 class="text-base font-bold text-gray-900">Edit Suku Cadang Kendaraan</h3>
                <button type="button" onclick="document.getElementById('modal-edit-komponen').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    &times;
                </button>
            </div>

            <form id="form-edit-komponen" method="POST" action="" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="edit_nama" :value="__('Nama Suku Cadang')" class="text-xs font-semibold" />
                    <x-text-input id="edit_nama" name="nama_komponen" type="text" class="mt-1 block w-full text-sm" required />
                </div>

                <div>
                    <x-input-label for="edit_kategori" :value="__('Kategori')" class="text-xs font-semibold" />
                    <select id="edit_kategori" name="kategori" class="mt-1 block w-full border-gray-300 rounded-md text-sm" required>
                        @foreach($kategoriList as $kat)
                            @php $val = $kat->value; @endphp
                            <option value="{{ $val }}">{{ str_replace('_', ' ', ucwords(strtolower($val), '_')) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="edit_interval_km" :value="__('Interval KM')" class="text-xs font-semibold" />
                        <x-text-input id="edit_interval_km" name="interval_km" type="number" min="500" step="500" class="mt-1 block w-full text-sm font-mono" required />
                    </div>
                    <div>
                        <x-input-label for="edit_interval_bulan" :value="__('Interval Bulan')" class="text-xs font-semibold" />
                        <x-text-input id="edit_interval_bulan" name="interval_bulan" type="number" min="1" class="mt-1 block w-full text-sm font-mono" />
                    </div>
                </div>

                <div>
                    <x-input-label :value="__('Aksi Servis Default')" class="text-xs font-semibold" />
                    <div class="mt-1 flex items-center gap-4 text-sm">
                        <label class="inline-flex items-center gap-1 cursor-pointer">
                            <input type="radio" id="edit_aksi_g" name="jenis_aksi_default" value="G" class="text-indigo-600">
                            <span>Ganti (G)</span>
                        </label>
                        <label class="inline-flex items-center gap-1 cursor-pointer">
                            <input type="radio" id="edit_aksi_p" name="jenis_aksi_default" value="P" class="text-indigo-600">
                            <span>Periksa (P)</span>
                        </label>
                    </div>
                </div>

                <div class="pt-1">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="edit_is_aktif" name="is_aktif" value="1" class="rounded border-gray-300 text-indigo-600 h-4 w-4">
                        <span class="ms-2 text-xs font-medium text-gray-700">Komponen Aktif</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t">
                    <button type="button" onclick="document.getElementById('modal-edit-komponen').classList.add('hidden')"
                            class="px-3 py-1.5 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                        Batal
                    </button>
                    <x-primary-button class="text-xs py-2">
                        {{ __('Simpan Perubahan') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editKomponenModal(item) {
            const form = document.getElementById('form-edit-komponen');
            form.action = '{{ url('/kendaraan/' . $kendaraan->id . '/komponen') }}/' + item.id;

            document.getElementById('edit_nama').value = item.nama_komponen;
            document.getElementById('edit_kategori').value = typeof item.kategori === 'object' ? item.kategori.value : item.kategori;
            document.getElementById('edit_interval_km').value = item.interval_km || '';
            document.getElementById('edit_interval_bulan').value = item.interval_bulan || '';

            const aksi = typeof item.jenis_aksi_default === 'object' ? item.jenis_aksi_default.value : item.jenis_aksi_default;
            if (aksi === 'P') {
                document.getElementById('edit_aksi_p').checked = true;
            } else {
                document.getElementById('edit_aksi_g').checked = true;
            }

            document.getElementById('edit_is_aktif').checked = Boolean(item.is_aktif);

            document.getElementById('modal-edit-komponen').classList.remove('hidden');
        }
    </script>
</x-app-layout>
