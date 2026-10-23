<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Tambah Komponen Template') }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Daftarkan suku cadang dan tentukan jadwal servis intervalnya.</p>
            </div>
            <a href="{{ route('template-komponen.index', ['template_id' => $selectedTemplateId]) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition">
                &larr; Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('template-komponen.store') }}" class="space-y-6">
                @csrf

                {{-- Card 1: Informasi Dasar Komponen --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-4 border-b pb-2">Informasi Komponen</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        {{-- Template Target --}}
                        <div class="sm:col-span-2">
                            <x-input-label for="id_template" :value="__('Pilih Template Servis')" class="font-semibold text-gray-700" />
                            <select id="id_template" name="id_template"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm @error('id_template') border-red-500 @enderror"
                                    required>
                                @foreach($templates as $tmpl)
                                    <option value="{{ $tmpl->id }}" {{ (string) old('id_template', $selectedTemplateId) === (string) $tmpl->id ? 'selected' : '' }}>
                                        {{ $tmpl->nama }} ({{ $tmpl->jenis_bbm?->value ?? $tmpl->jenis_bbm }} - {{ $tmpl->transmisi?->value ?? $tmpl->transmisi }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('id_template')" class="mt-1" />
                        </div>

                        {{-- Nama Komponen --}}
                        <div class="sm:col-span-2">
                            <x-input-label for="nama_komponen" :value="__('Nama Komponen / Suku Cadang')" class="font-semibold text-gray-700" />
                            <x-text-input id="nama_komponen" name="nama_komponen" type="text"
                                          class="mt-1 block w-full @error('nama_komponen') border-red-500 @enderror"
                                          :value="old('nama_komponen')" placeholder="Contoh: Oli Gardan, Timing Belt, Kampas Rem Depan" required />
                            <x-input-error :messages="$errors->get('nama_komponen')" class="mt-1" />
                        </div>

                        {{-- Kategori --}}
                        <div>
                            <x-input-label for="kategori" :value="__('Kategori')" class="font-semibold text-gray-700" />
                            <select id="kategori" name="kategori"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm @error('kategori') border-red-500 @enderror"
                                    required>
                                @foreach($kategoriList as $kat)
                                    @php $val = $kat->value; @endphp
                                    <option value="{{ $val }}" {{ old('kategori') === $val ? 'selected' : '' }}>
                                        {{ str_replace('_', ' ', ucwords(strtolower($val), '_')) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('kategori')" class="mt-1" />
                        </div>

                        {{-- Nomor Urut --}}
                        <div>
                            <x-input-label for="nomor_urut" :value="__('Nomor Urut Tampilan (opsional)')" class="font-semibold text-gray-700" />
                            <x-text-input id="nomor_urut" name="nomor_urut" type="number" min="1"
                                          class="mt-1 block w-full @error('nomor_urut') border-red-500 @enderror"
                                          :value="old('nomor_urut')" placeholder="Otomatis di urutan terakhir jika kosong" />
                            <x-input-error :messages="$errors->get('nomor_urut')" class="mt-1" />
                        </div>

                        {{-- Status Aktif --}}
                        <div class="sm:col-span-2 pt-2">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_aktif" value="1"
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-4 w-4"
                                       {{ old('is_aktif', '1') === '1' ? 'checked' : '' }}>
                                <span class="ms-2 text-sm font-medium text-gray-700">Komponen Aktif (ditampilkan saat pengajuan servis kendaraan)</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Matriks Interval Jadwal Servis --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 border-b pb-3">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Matriks Jadwal Servis (Interval)</h3>
                            <p class="text-xs text-gray-500">Tentukan kapan komponen harus diperiksa (P) atau diganti (G) berdasarkan kilometer atau bulan.</p>
                        </div>
                        <button type="button" id="btn-add-schedule"
                                class="inline-flex items-center px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-md text-xs font-semibold transition border border-slate-300">
                            <svg class="h-4 w-4 me-1 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            + Tambah Baris Jadwal
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-slate-50 text-gray-600 text-xs uppercase font-semibold">
                                <tr>
                                    <th class="px-3 py-2 text-left">Interval KM</th>
                                    <th class="px-3 py-2 text-left">Interval Bulan</th>
                                    <th class="px-3 py-2 text-center w-48">Jenis Aksi</th>
                                    <th class="px-3 py-2 text-center w-16">Hapus</th>
                                </tr>
                            </thead>
                            <tbody id="schedule-tbody" class="divide-y divide-gray-100">
                                <tr class="schedule-row hover:bg-slate-50/50">
                                    <td class="px-3 py-2">
                                        <input type="number" min="1" step="500"
                                               name="jadwal[0][interval_km]"
                                               value="10000"
                                               class="w-full text-xs font-mono font-semibold py-1.5 px-2 border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500"
                                               placeholder="cth: 10000" required>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" min="0" step="1"
                                               name="jadwal[0][interval_bulan]"
                                               value="6"
                                               class="w-full text-xs font-mono py-1.5 px-2 border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500"
                                               placeholder="cth: 6">
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <div class="inline-flex rounded-md shadow-sm p-0.5 bg-slate-100 border border-slate-200 text-xs">
                                            <label class="cursor-pointer">
                                                <input type="radio" name="jadwal[0][jenis_aksi]" value="P" class="sr-only peer">
                                                <span class="inline-block px-2.5 py-1 font-semibold rounded peer-checked:bg-blue-600 peer-checked:text-white text-gray-600">
                                                    Periksa (P)
                                                </span>
                                            </label>
                                            <label class="cursor-pointer">
                                                <input type="radio" name="jadwal[0][jenis_aksi]" value="G" class="sr-only peer" checked>
                                                <span class="inline-block px-2.5 py-1 font-semibold rounded peer-checked:bg-amber-600 peer-checked:text-white text-gray-600">
                                                    Ganti (G)
                                                </span>
                                            </label>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" class="btn-remove-row text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('template-komponen.index', ['template_id' => $selectedTemplateId]) }}"
                       class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-semibold hover:bg-gray-300 transition">
                        Batal
                    </a>
                    <x-primary-button class="py-2.5 px-6">
                        {{ __('Simpan Komponen Baru') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    {{-- Script Dynamic Rows --}}
    <script>
        (function () {
            const tbody = document.getElementById('schedule-tbody');
            const btnAdd = document.getElementById('btn-add-schedule');
            let rowIndex = 1;

            function attachRemoveHandler(btn) {
                btn.addEventListener('click', function () {
                    const row = this.closest('tr');
                    row.remove();
                    if (tbody.querySelectorAll('.schedule-row').length === 0) {
                        tbody.innerHTML = `
                            <tr id="empty-schedule-row">
                                <td colspan="4" class="px-3 py-6 text-center text-xs text-gray-400 italic">
                                    Belum ada interval yang ditentukan. Klik "+ Tambah Baris Jadwal" di atas.
                                </td>
                            </tr>
                        `;
                    }
                });
            }

            document.querySelectorAll('.btn-remove-row').forEach(attachRemoveHandler);

            if (btnAdd) {
                btnAdd.addEventListener('click', function () {
                    const emptyRow = document.getElementById('empty-schedule-row');
                    if (emptyRow) {
                        emptyRow.remove();
                    }

                    const tr = document.createElement('tr');
                    tr.className = 'schedule-row hover:bg-slate-50/50';
                    tr.innerHTML = `
                        <td class="px-3 py-2">
                            <input type="number" min="1" step="500"
                                   name="jadwal[${rowIndex}][interval_km]"
                                   class="w-full text-xs font-mono font-semibold py-1.5 px-2 border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500"
                                   placeholder="cth: 20000" required>
                        </td>
                        <td class="px-3 py-2">
                            <input type="number" min="0" step="1"
                                   name="jadwal[${rowIndex}][interval_bulan]"
                                   class="w-full text-xs font-mono py-1.5 px-2 border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500"
                                   placeholder="cth: 12">
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="inline-flex rounded-md shadow-sm p-0.5 bg-slate-100 border border-slate-200 text-xs">
                                <label class="cursor-pointer">
                                    <input type="radio" name="jadwal[${rowIndex}][jenis_aksi]" value="P" class="sr-only peer" checked>
                                    <span class="inline-block px-2.5 py-1 font-semibold rounded peer-checked:bg-blue-600 peer-checked:text-white text-gray-600">
                                        Periksa (P)
                                    </span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="jadwal[${rowIndex}][jenis_aksi]" value="G" class="sr-only peer">
                                    <span class="inline-block px-2.5 py-1 font-semibold rounded peer-checked:bg-amber-600 peer-checked:text-white text-gray-600">
                                        Ganti (G)
                                    </span>
                                </label>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <button type="button" class="btn-remove-row text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                    attachRemoveHandler(tr.querySelector('.btn-remove-row'));
                    rowIndex++;
                });
            }
        })();
    </script>
</x-app-layout>
