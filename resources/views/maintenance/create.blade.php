<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Ajukan Maintenance') }}
            </h2>
            <a href="{{ route('maintenance.index', ['tab' => 'pengajuan']) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition ease-in-out duration-150">
                &larr; Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 sm:p-8">
                    <div class="border-b border-gray-100 pb-4 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Form Pengajuan Maintenance</h3>
                            <p class="text-sm text-gray-500">Isi rincian pengajuan dan pilih komponen yang perlu diperiksa atau diganti.</p>
                        </div>
                    </div>

                    @if($kendaraanList->isEmpty())
                        <div class="text-center py-10 bg-gray-50 rounded-xl border border-dashed border-gray-200 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                            </svg>
                            <p class="font-medium text-gray-700">Belum ada kendaraan yang ditugaskan</p>
                            <p class="text-xs text-gray-500 mt-1">Hubungi Administrator untuk penugasan kendaraan operasional Anda.</p>
                        </div>
                    @else
                        {{-- Data route & map embed --}}
                        <script>
                            const mileageMap = @json($mileageMap);
                            const mileageRoute = '{{ route('maintenance.mileage-kendaraan', ['kendaraan' => '__ID__']) }}';
                            const komponenRoute = '{{ route('maintenance.komponen-kendaraan', ['kendaraan' => '__ID__']) }}';
                            const kendaraanDetailRoute = '{{ route('kendaraan.show', ['kendaraan' => '__ID__']) }}';
                        </script>

                        <form method="POST" action="{{ route('maintenance.store') }}" id="maintenance-form" class="space-y-6">
                            @csrf

                            {{-- Bagian 1: Data Dasar Kendaraan --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/70 p-5 rounded-xl border border-slate-200">
                                {{-- Kendaraan --}}
                                <div>
                                    <x-input-label for="id_kendaraan" :value="__('Kendaraan')" class="font-semibold text-gray-700" />
                                    <select id="id_kendaraan" name="id_kendaraan"
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm @error('id_kendaraan') border-red-500 @enderror"
                                            required>
                                        <option value="">-- Pilih Kendaraan --</option>
                                        @foreach($kendaraanList as $k)
                                            <option value="{{ $k->id }}" {{ (string) old('id_kendaraan', request('kendaraan')) === (string) $k->id ? 'selected' : '' }}>
                                                {{ $k->merk_tipe }} — {{ $k->plat_nomor }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('id_kendaraan')" class="mt-1" />
                                </div>

                                {{-- Kilometer Saat Pengajuan --}}
                                <div>
                                    <x-input-label for="kilometer_display" :value="__('Kilometer Saat Pengajuan')" class="font-semibold text-gray-700" />
                                    <div id="kilometer_display"
                                         class="mt-1 flex items-center justify-between px-3 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 min-h-[38px] shadow-sm">
                                        <span id="km-value" class="text-gray-400 italic">— Pilih kendaraan terlebih dahulu —</span>
                                        <span class="text-xs text-indigo-600 font-medium">Odometer Terakhir</span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Snapshot odometer otomatis dari data mileage/odometer terkini.</p>
                                </div>

                                {{-- Jenis Pengajuan --}}
                                <div>
                                    <x-input-label for="jenis_pengajuan" :value="__('Jenis Pengajuan')" class="font-semibold text-gray-700" />
                                    <select id="jenis_pengajuan" name="jenis_pengajuan"
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm @error('jenis_pengajuan') border-red-500 @enderror"
                                            required>
                                        <option value="">-- Pilih Jenis --</option>
                                        <option value="RUTIN" {{ old('jenis_pengajuan', 'RUTIN') === 'RUTIN' ? 'selected' : '' }}>RUTIN (Servis Berkala)</option>
                                        <option value="DARURAT" {{ old('jenis_pengajuan') === 'DARURAT' ? 'selected' : '' }}>DARURAT (Kerusakan Mendadak)</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('jenis_pengajuan')" class="mt-1" />
                                </div>

                                {{-- Estimasi Biaya --}}
                                <div>
                                    <x-input-label for="estimasi_biaya" :value="__('Estimasi Biaya (opsional)')" class="font-semibold text-gray-700" />
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm pointer-events-none">Rp</span>
                                        <x-text-input id="estimasi_biaya" name="estimasi_biaya" type="number" min="0" step="1000"
                                                      class="block w-full pl-9 @error('estimasi_biaya') border-red-500 @enderror"
                                                      :value="old('estimasi_biaya')"
                                                      placeholder="0" />
                                    </div>
                                    <x-input-error :messages="$errors->get('estimasi_biaya')" class="mt-1" />
                                </div>
                            </div>

                            {{-- Bagian 2: Seleksi Komponen Servis (Struktur Baru) --}}
                            <div class="border border-slate-200 rounded-xl overflow-hidden bg-white shadow-sm">
                                <div class="bg-gradient-to-r from-slate-50 to-indigo-50/40 p-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <h4 class="font-semibold text-gray-800 text-base flex items-center gap-2">
                                            <span>Rincian Komponen & Suku Cadang Servis</span>
                                            <span id="badge-template" class="hidden text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 border border-indigo-200"></span>
                                        </h4>
                                        <p class="text-xs text-gray-500 mt-0.5">Tentukan suku cadang yang akan diperiksa (P) atau diganti (G) berdasarkan riwayat dan jadwal servis.</p>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span id="selected-counter" class="text-xs font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 border border-slate-200">
                                            0 Komponen Dipilih
                                        </span>
                                        <button type="button" id="btn-select-all-recommended" class="hidden text-xs font-medium px-3 py-1 bg-amber-50 text-amber-700 border border-amber-300 rounded-md hover:bg-amber-100 transition">
                                            Pilih Semua Rekomendasi
                                        </button>
                                    </div>
                                </div>

                                <div class="p-5">
                                    {{-- Placeholder Awal --}}
                                    <div id="komponen-placeholder" class="text-center py-8 text-gray-400">
                                        <svg class="mx-auto h-10 w-10 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <p class="text-sm italic">Pilih kendaraan di atas untuk menampilkan daftar komponen dan rekomendasi servis.</p>
                                    </div>

                                    {{-- Loading State --}}
                                    <div id="komponen-loading" class="hidden text-center py-8 text-gray-500">
                                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mb-2"></div>
                                        <p class="text-sm">Memuat matriks komponen dan status servis...</p>
                                    </div>

                                    {{-- Empty/No Component State --}}
                                    <div id="komponen-no-template" class="hidden p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                            <div>
                                                <p class="font-semibold" id="empty-title">Kendaraan belum memiliki daftar komponen/suku cadang.</p>
                                                <p class="text-xs text-amber-700 mt-1" id="empty-desc">
                                                    Anda dapat menerapkan template atau menambahkan komponen pada halaman detail kendaraan.
                                                </p>
                                            </div>
                                            <a id="btn-goto-kendaraan" href="#" class="inline-flex items-center justify-center shrink-0 px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-md text-xs font-semibold shadow-sm transition">
                                                Kelola Komponen Kendaraan &rarr;
                                            </a>
                                        </div>
                                    </div>

                                    {{-- Kontainer Komponen Aktif --}}
                                    <div id="komponen-content" class="hidden space-y-6">
                                        {{-- Seksi Rekomendasi (JATUH TEMPO / SEGERA) --}}
                                        <div id="section-recommended" class="hidden">
                                            <div class="flex items-center gap-2 mb-3">
                                                <span class="h-2.5 w-2.5 rounded-full bg-red-500 animate-pulse"></span>
                                                <h5 class="text-sm font-bold text-gray-800 uppercase tracking-wider">
                                                    Perlu Perhatian Segera (<span id="recommended-count">0</span>)
                                                </h5>
                                                <span class="text-xs text-gray-400">Komponen yang telah jatuh tempo atau mendekati batas servis</span>
                                            </div>
                                            <div id="recommended-list" class="space-y-3"></div>
                                        </div>

                                        {{-- Seksi Komponen Lainnya (Accordion) --}}
                                        <div id="section-other" class="border-t border-slate-200 pt-4">
                                            <button type="button" id="btn-toggle-other" class="flex items-center justify-between w-full text-left font-semibold text-sm text-gray-700 hover:text-indigo-600 transition py-1">
                                                <span class="flex items-center gap-2">
                                                    <svg id="arrow-other" class="h-4 w-4 transform transition-transform text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                    <span>Komponen Lainnya (<span id="other-count">0</span>)</span>
                                                </span>
                                                <span class="text-xs text-gray-400 font-normal">Klik untuk memilih komponen tambahan</span>
                                            </button>
                                            <div id="other-list" class="hidden mt-3 space-y-3"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Bagian 3: Deskripsi Keluhan --}}
                            <div>
                                <x-input-label for="deskripsi_keluhan" :value="__('Deskripsi Keluhan / Keterangan Tambahan')" class="font-semibold text-gray-700" />
                                <textarea id="deskripsi_keluhan" name="deskripsi_keluhan" rows="3"
                                          class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm @error('deskripsi_keluhan') border-red-500 @enderror"
                                          placeholder="Jelaskan detail keluhan, indikasi kerusakan, atau catatan khusus untuk bengkel..." required>{{ old('deskripsi_keluhan') }}</textarea>
                                <x-input-error :messages="$errors->get('deskripsi_keluhan')" class="mt-1" />
                            </div>

                            <div class="pt-2">
                                <x-primary-button class="w-full justify-center py-3 text-sm font-semibold tracking-wide shadow-md">
                                    {{ __('Kirim Pengajuan Maintenance') }}
                                </x-primary-button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Script Interaksi Komponen & AJAX --}}
    <script>
        (function () {
            const selectKendaraan = document.getElementById('id_kendaraan');
            const kmSpan = document.getElementById('km-value');
            const badgeTemplate = document.getElementById('badge-template');
            const selectedCounter = document.getElementById('selected-counter');
            const btnSelectAllRecommended = document.getElementById('btn-select-all-recommended');

            const placeholder = document.getElementById('komponen-placeholder');
            const loading = document.getElementById('komponen-loading');
            const noTemplate = document.getElementById('komponen-no-template');
            const emptyTitle = document.getElementById('empty-title');
            const emptyDesc = document.getElementById('empty-desc');
            const btnGotoKendaraan = document.getElementById('btn-goto-kendaraan');
            const content = document.getElementById('komponen-content');

            const secRecommended = document.getElementById('section-recommended');
            const listRecommended = document.getElementById('recommended-list');
            const countRecommended = document.getElementById('recommended-count');

            const secOther = document.getElementById('section-other');
            const listOther = document.getElementById('other-list');
            const countOther = document.getElementById('other-count');
            const btnToggleOther = document.getElementById('btn-toggle-other');
            const arrowOther = document.getElementById('arrow-other');

            let currentComponents = [];
            const km = {};
            Object.keys(mileageMap || {}).forEach(k => { km[String(k)] = mileageMap[k]; });

            function formatKm(val) {
                if (val === null || val === undefined) {
                    return '<span class="text-amber-600 italic">Belum ada catatan kilometer.</span>';
                }
                return '<span class="font-bold text-gray-800">' + Number(val).toLocaleString('id-ID') + ' km</span>';
            }

            function updateKm(kendaraanId) {
                if (!kendaraanId) {
                    kmSpan.innerHTML = '<span class="text-gray-400 italic">— Pilih kendaraan terlebih dahulu —</span>';
                    return;
                }
                const key = String(kendaraanId);
                if (Object.prototype.hasOwnProperty.call(km, key)) {
                    kmSpan.innerHTML = formatKm(km[key]);
                    return;
                }
                kmSpan.innerHTML = '<span class="text-gray-400 italic">Memuat...</span>';
                fetch(mileageRoute.replace('__ID__', kendaraanId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        km[key] = data.kilometer_akhir !== undefined ? data.kilometer_akhir : null;
                        kmSpan.innerHTML = formatKm(km[key]);
                    })
                    .catch(() => {
                        kmSpan.innerHTML = '<span class="text-red-500 italic">Gagal memuat odometer.</span>';
                    });
            }

            function updateCounter() {
                const checkedBoxes = document.querySelectorAll('.komponen-checkbox:checked');
                const total = checkedBoxes.length;
                selectedCounter.textContent = total + ' Komponen Dipilih';
                if (total > 0) {
                    selectedCounter.className = 'text-xs font-semibold px-2.5 py-1 rounded-md bg-indigo-100 text-indigo-700 border border-indigo-200';
                } else {
                    selectedCounter.className = 'text-xs font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 border border-slate-200';
                }
            }

            function createComponentRow(item, index) {
                const statusColor = {
                    'JATUH_TEMPO': 'bg-red-100 text-red-800 border-red-200',
                    'SEGERA': 'bg-amber-100 text-amber-800 border-amber-200',
                    'AMAN': 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    'BELUM_DATA': 'bg-gray-100 text-gray-700 border-gray-200',
                }[item.status] || 'bg-gray-100 text-gray-700 border-gray-200';

                const cardBorder = item.is_rekomendasi
                    ? 'border-red-200 bg-red-50/20 hover:border-red-300'
                    : 'border-slate-200 bg-white hover:border-slate-300';

                const div = document.createElement('div');
                div.className = `p-3.5 rounded-lg border transition ${cardBorder}`;
                div.id = `komponen-card-${item.id_komponen_kendaraan}`;

                div.innerHTML = `
                    <div class="flex items-start gap-3">
                        <div class="pt-0.5">
                            <input type="checkbox"
                                   id="chk-${item.id_komponen_kendaraan}"
                                   class="komponen-checkbox h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                   data-id="${item.id_komponen_kendaraan}"
                                   data-rekomendasi="${item.is_rekomendasi ? '1' : '0'}"
                                   ${item.is_rekomendasi ? 'checked' : ''}>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label for="chk-${item.id_komponen_kendaraan}" class="font-semibold text-gray-900 text-sm cursor-pointer select-none">
                                    ${item.nama_komponen}
                                </label>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[11px] px-2 py-0.5 rounded border font-medium ${statusColor}">
                                        ${item.status_label}
                                    </span>
                                    <span class="text-[11px] px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-medium">
                                        ${item.kategori_label}
                                    </span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">${item.keterangan}</p>

                            {{-- Area Kontrol Aksi & Catatan jika dicentang --}}
                            <div id="controls-${item.id_komponen_kendaraan}" class="${item.is_rekomendasi ? '' : 'hidden'} mt-3 pt-3 border-t border-slate-100">
                                <input type="hidden" name="komponen[${index}][id_komponen_kendaraan]" value="${item.id_komponen_kendaraan}" id="input-id-${item.id_komponen_kendaraan}" ${item.is_rekomendasi ? '' : 'disabled'}>

                                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-gray-600">Aksi:</span>
                                        <div class="inline-flex rounded-md shadow-sm p-0.5 bg-slate-100 border border-slate-200">
                                            <label class="cursor-pointer">
                                                <input type="radio" name="komponen[${index}][jenis_aksi]" value="P" class="sr-only peer" ${item.aksi_rekomendasi === 'P' ? 'checked' : ''} ${item.is_rekomendasi ? '' : 'disabled'}>
                                                <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded peer-checked:bg-indigo-600 peer-checked:text-white text-gray-600 hover:text-gray-900 transition">
                                                    Periksa (P)
                                                </span>
                                            </label>
                                            <label class="cursor-pointer">
                                                <input type="radio" name="komponen[${index}][jenis_aksi]" value="G" class="sr-only peer" ${item.aksi_rekomendasi === 'G' ? 'checked' : ''} ${item.is_rekomendasi ? '' : 'disabled'}>
                                                <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded peer-checked:bg-indigo-600 peer-checked:text-white text-gray-600 hover:text-gray-900 transition">
                                                    Ganti (G)
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="flex-1">
                                        <input type="text"
                                               name="komponen[${index}][catatan]"
                                               placeholder="Catatan komponen (opsional, cth: bunyi berdecit)"
                                               class="w-full text-xs px-2.5 py-1.5 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                               ${item.is_rekomendasi ? '' : 'disabled'}>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                const chk = div.querySelector('.komponen-checkbox');
                chk.addEventListener('change', function () {
                    const controls = div.querySelector(`#controls-${item.id_komponen_kendaraan}`);
                    const inputs = controls.querySelectorAll('input');
                    if (this.checked) {
                        controls.classList.remove('hidden');
                        inputs.forEach(inp => inp.disabled = false);
                    } else {
                        controls.classList.add('hidden');
                        inputs.forEach(inp => inp.disabled = true);
                    }
                    updateCounter();
                });

                return div;
            }

            function loadKomponen(kendaraanId) {
                if (!kendaraanId) {
                    placeholder.classList.remove('hidden');
                    loading.classList.add('hidden');
                    noTemplate.classList.add('hidden');
                    content.classList.add('hidden');
                    badgeTemplate.classList.add('hidden');
                    btnSelectAllRecommended.classList.add('hidden');
                    selectedCounter.textContent = '0 Komponen Dipilih';
                    return;
                }

                placeholder.classList.add('hidden');
                noTemplate.classList.add('hidden');
                content.classList.add('hidden');
                loading.classList.remove('hidden');

                const url = komponenRoute.replace('__ID__', kendaraanId);
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        loading.classList.add('hidden');

                        if (!data.has_komponen) {
                            noTemplate.classList.remove('hidden');
                            badgeTemplate.classList.add('hidden');
                            btnSelectAllRecommended.classList.add('hidden');

                            if (btnGotoKendaraan) {
                                btnGotoKendaraan.href = kendaraanDetailRoute.replace('__ID__', kendaraanId);
                            }

                            if (data.rekomendasi_template) {
                                emptyTitle.textContent = `Kendaraan belum memiliki daftar komponen (Rekomendasi: ${data.rekomendasi_template.nama})`;
                                emptyDesc.textContent = `Kunjungi halaman detail kendaraan untuk menerapkan template "${data.rekomendasi_template.nama}" atau menambahkan sparepart secara mandiri.`;
                            } else {
                                emptyTitle.textContent = 'Kendaraan belum memiliki daftar komponen/suku cadang.';
                                emptyDesc.textContent = 'Kunjungi halaman detail kendaraan untuk memilih template servis yang sesuai atau menambahkan komponen.';
                            }
                            return;
                        }

                        if (data.template_nama) {
                            badgeTemplate.textContent = 'Template: ' + data.template_nama;
                            badgeTemplate.classList.remove('hidden');
                        } else {
                            badgeTemplate.classList.add('hidden');
                        }

                        currentComponents = data.komponen || [];
                        listRecommended.innerHTML = '';
                        listOther.innerHTML = '';

                        const recommended = currentComponents.filter(c => c.is_rekomendasi);
                        const others = currentComponents.filter(c => !c.is_rekomendasi);

                        countRecommended.textContent = recommended.length;
                        countOther.textContent = others.length;

                        if (recommended.length > 0) {
                            secRecommended.classList.remove('hidden');
                            btnSelectAllRecommended.classList.remove('hidden');
                            recommended.forEach((item, idx) => {
                                listRecommended.appendChild(createComponentRow(item, idx));
                            });
                        } else {
                            secRecommended.classList.add('hidden');
                            btnSelectAllRecommended.classList.add('hidden');
                        }

                        others.forEach((item, idx) => {
                            listOther.appendChild(createComponentRow(item, recommended.length + idx));
                        });

                        content.classList.remove('hidden');
                        updateCounter();
                    })
                    .catch(() => {
                        loading.classList.add('hidden');
                        placeholder.classList.remove('hidden');
                    });
            }

            // Accordion toggle
            if (btnToggleOther) {
                btnToggleOther.addEventListener('click', function () {
                    const isHidden = listOther.classList.contains('hidden');
                    if (isHidden) {
                        listOther.classList.remove('hidden');
                        arrowOther.classList.add('rotate-180');
                    } else {
                        listOther.classList.add('hidden');
                        arrowOther.classList.remove('rotate-180');
                    }
                });
            }

            // Pilih semua rekomendasi button
            if (btnSelectAllRecommended) {
                btnSelectAllRecommended.addEventListener('click', function () {
                    const checkboxes = listRecommended.querySelectorAll('.komponen-checkbox');
                    checkboxes.forEach(c => {
                        if (!c.checked) {
                            c.checked = true;
                            c.dispatchEvent(new Event('change'));
                        }
                    });
                });
            }

            if (selectKendaraan) {
                updateKm(selectKendaraan.value);
                loadKomponen(selectKendaraan.value);

                selectKendaraan.addEventListener('change', function () {
                    updateKm(this.value);
                    loadKomponen(this.value);
                });
            }
        })();
    </script>
</x-app-layout>
