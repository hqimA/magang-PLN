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
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-gray-700 mb-6">Form Pengajuan Maintenance</h3>

                    @if($kendaraanList->isEmpty())
                        <div class="text-center py-8 text-gray-500">
                            Anda belum memiliki kendaraan yang ditugaskan. Hubungi Admin untuk penugasan kendaraan.
                        </div>
                    @else
                        {{-- Data mileage map di-embed sebagai JSON untuk JS --}}
                        <script>
                            const mileageMap = @json($mileageMap);
                            const mileageRoute = '{{ route('maintenance.mileage-kendaraan', ['kendaraan' => '__ID__']) }}';
                        </script>

                        <form method="POST" action="{{ route('maintenance.store') }}" class="space-y-5">
                            @csrf

                            {{-- Kendaraan --}}
                            <div>
                                <x-input-label for="id_kendaraan" :value="__('Kendaraan')" />
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

                            {{-- Kilometer Saat Pengajuan (read-only, dari Mileage terbaru) --}}
                            <div>
                                <x-input-label for="kilometer_display" :value="__('Kilometer Saat Pengajuan')" />
                                <div id="kilometer_display"
                                     class="mt-1 flex items-center px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-700 min-h-[38px]">
                                    <span id="km-value" class="text-gray-400 italic">— Pilih kendaraan terlebih dahulu —</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-400">Otomatis diambil dari catatan Mileage terbaru kendaraan.</p>
                            </div>

                            {{-- Jenis Pengajuan --}}
                            <div>
                                <x-input-label for="jenis_pengajuan" :value="__('Jenis Pengajuan')" />
                                <select id="jenis_pengajuan" name="jenis_pengajuan"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm @error('jenis_pengajuan') border-red-500 @enderror"
                                        required>
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="RUTIN" {{ old('jenis_pengajuan') === 'RUTIN' ? 'selected' : '' }}>RUTIN</option>
                                    <option value="DARURAT" {{ old('jenis_pengajuan') === 'DARURAT' ? 'selected' : '' }}>DARURAT</option>
                                </select>
                                <x-input-error :messages="$errors->get('jenis_pengajuan')" class="mt-1" />
                            </div>

                            {{-- Deskripsi Keluhan --}}
                            <div>
                                <x-input-label for="deskripsi_keluhan" :value="__('Deskripsi Keluhan')" />
                                <textarea id="deskripsi_keluhan" name="deskripsi_keluhan" rows="4"
                                          class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm @error('deskripsi_keluhan') border-red-500 @enderror"
                                          placeholder="Jelaskan keluhan atau kebutuhan servis secara detail..." required>{{ old('deskripsi_keluhan') }}</textarea>
                                <x-input-error :messages="$errors->get('deskripsi_keluhan')" class="mt-1" />
                            </div>

                            {{-- Estimasi Biaya --}}
                            <div>
                                <x-input-label for="estimasi_biaya" :value="__('Estimasi Biaya (opsional)')" />
                                <div class="mt-1 relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm pointer-events-none">Rp</span>
                                    <x-text-input id="estimasi_biaya" name="estimasi_biaya" type="number" min="0" step="1000"
                                                  class="block w-full pl-9 @error('estimasi_biaya') border-red-500 @enderror"
                                                  :value="old('estimasi_biaya')"
                                                  placeholder="0" />
                                </div>
                                <x-input-error :messages="$errors->get('estimasi_biaya')" class="mt-1" />
                            </div>

                            <div class="pt-2">
                                <x-primary-button class="w-full justify-center">
                                    {{ __('Kirim Pengajuan') }}
                                </x-primary-button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const select = document.getElementById('id_kendaraan');
            const kmSpan = document.getElementById('km-value');

            // Normalise mileageMap keys ke string agar selalu cocok dgn select.value
            const km = {};
            Object.keys(mileageMap).forEach(function (k) {
                km[String(k)] = mileageMap[k];
            });

            function formatKm(val) {
                if (val === null || val === undefined) {
                    return '<span class="text-amber-600 italic">Belum ada data kilometer untuk kendaraan ini.</span>';
                }
                return '<span class="font-semibold text-gray-800">' + Number(val).toLocaleString('id-ID') + ' km</span>';
            }

            function updateKm(kendaraanId) {
                if (!kendaraanId) {
                    kmSpan.innerHTML = '<span class="text-gray-400 italic">— Pilih kendaraan terlebih dahulu —</span>';
                    return;
                }
                const key = String(kendaraanId);
                if (Object.prototype.hasOwnProperty.call(km, key)) {
                    // Sudah ada di map (termasuk null = belum ada mileage)
                    kmSpan.innerHTML = formatKm(km[key]);
                    return;
                }
                // Fallback AJAX untuk kendaraan yang tidak ada di map
                const url = mileageRoute.replace('__ID__', kendaraanId);
                kmSpan.innerHTML = '<span class="text-gray-400 italic">Memuat...</span>';
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        km[key] = data.kilometer_akhir !== undefined ? data.kilometer_akhir : null;
                        kmSpan.innerHTML = formatKm(km[key]);
                    })
                    .catch(function () {
                        kmSpan.innerHTML = '<span class="text-red-500 italic">Gagal memuat data kilometer.</span>';
                    });
            }

            if (select) {
                updateKm(select.value);
                select.addEventListener('change', function () {
                    updateKm(this.value);
                });
            }
        })();
    </script>
</x-app-layout>
