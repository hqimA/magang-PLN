<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Kendaraan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('kendaraan.update', $kendaraan->id) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Plat Nomor -->
                            <div>
                                <x-input-label for="plat_nomor" :value="__('Nomor Polisi')" />
                                <x-text-input id="plat_nomor" class="block mt-1 w-full" type="text" name="plat_nomor" :value="old('plat_nomor', $kendaraan->plat_nomor)" required autofocus />
                                <x-input-error :messages="$errors->get('plat_nomor')" class="mt-2" />
                            </div>

                            <!-- Merk Tipe -->
                            <div>
                                <x-input-label for="merk_tipe" :value="__('Nama Kendaraan (Merk/Tipe)')" />
                                <x-text-input id="merk_tipe" class="block mt-1 w-full" type="text" name="merk_tipe" :value="old('merk_tipe', $kendaraan->merk_tipe)" required />
                                <x-input-error :messages="$errors->get('merk_tipe')" class="mt-2" />
                            </div>

                            <!-- Tahun Pembuatan -->
                            <div>
                                <x-input-label for="tahun_pembuatan" :value="__('Tahun Pembuatan')" />
                                <x-text-input id="tahun_pembuatan" class="block mt-1 w-full" type="number" name="tahun_pembuatan" :value="old('tahun_pembuatan', $kendaraan->tahun_pembuatan)" required />
                                <x-input-error :messages="$errors->get('tahun_pembuatan')" class="mt-2" />
                            </div>

                            <!-- Kilometer Terakhir -->
                            <div>
                                <x-input-label for="kilometer_terakhir" :value="__('Kilometer')" />
                                <x-text-input id="kilometer_terakhir" class="block mt-1 w-full bg-gray-50" type="text" :value="number_format($kendaraan->kilometer_terakhir).' km'" readonly />
                                <p class="mt-1 text-xs text-gray-500">Kilometer terakhir tidak dapat diubah dari form kendaraan.</p>
                            </div>

                            <!-- Transmisi -->
                            <div>
                                <x-input-label for="transmisi" :value="__('Transmisi')" />
                                <select id="transmisi" name="transmisi" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                    <option value="">-- Pilih Transmisi --</option>
                                    <option value="MANUAL" {{ old('transmisi', $kendaraan->transmisi) == 'MANUAL' ? 'selected' : '' }}>Manual</option>
                                    <option value="OTOMATIS" {{ old('transmisi', $kendaraan->transmisi) == 'OTOMATIS' ? 'selected' : '' }}>Otomatis</option>
                                </select>
                                <x-input-error :messages="$errors->get('transmisi')" class="mt-2" />
                            </div>

                            <!-- Jenis BBM -->
                            <div>
                                <x-input-label for="jenis_bbm" :value="__('Jenis BBM')" />
                                <select id="jenis_bbm" name="jenis_bbm" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                    <option value="">-- Pilih BBM --</option>
                                    <option value="BENSIN" {{ old('jenis_bbm', $kendaraan->jenis_bbm) == 'BENSIN' ? 'selected' : '' }}>Bensin</option>
                                    <option value="SOLAR" {{ old('jenis_bbm', $kendaraan->jenis_bbm) == 'SOLAR' ? 'selected' : '' }}>Solar</option>
                                    <option value="DIESEL" {{ old('jenis_bbm', $kendaraan->jenis_bbm) == 'DIESEL' ? 'selected' : '' }}>Diesel</option>
                                    <option value="LISTRIK" {{ old('jenis_bbm', $kendaraan->jenis_bbm) == 'LISTRIK' ? 'selected' : '' }}>Listrik</option>
                                </select>
                                <x-input-error :messages="$errors->get('jenis_bbm')" class="mt-2" />
                            </div>

                            <!-- Kategori Penggunaan -->
                            <div>
                                <x-input-label for="kategori_penggunaan" :value="__('Kategori Penggunaan')" />
                                <select id="kategori_penggunaan" name="kategori_penggunaan" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="PEJABAT" {{ old('kategori_penggunaan', $kendaraan->kategori_penggunaan) == 'PEJABAT' ? 'selected' : '' }}>Pejabat</option>
                                    <option value="TEKNISI" {{ old('kategori_penggunaan', $kendaraan->kategori_penggunaan) == 'TEKNISI' ? 'selected' : '' }}>Teknisi</option>
                                    <option value="ANGKUT_BARANG" {{ old('kategori_penggunaan', $kendaraan->kategori_penggunaan) == 'ANGKUT_BARANG' ? 'selected' : '' }}>Angkut Barang</option>
                                    <option value="MOTOR_OPERASIONAL" {{ old('kategori_penggunaan', $kendaraan->kategori_penggunaan) == 'MOTOR_OPERASIONAL' ? 'selected' : '' }}>Motor Operasional</option>
                                </select>
                                <x-input-error :messages="$errors->get('kategori_penggunaan')" class="mt-2" />
                            </div>
                            
                            <!-- Tanggal Pembelian -->
                            <div>
                                <x-input-label for="tanggal_pembelian" :value="__('Tanggal Pembelian')" />
                                <x-text-input id="tanggal_pembelian" class="block mt-1 w-full" type="date" name="tanggal_pembelian" :value="old('tanggal_pembelian', \Carbon\Carbon::parse($kendaraan->tanggal_pembelian)->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('tanggal_pembelian')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="tanggal_stnk_berlaku_sampai" :value="__('STNK Berlaku Sampai')" />
                                <x-text-input id="tanggal_stnk_berlaku_sampai" class="block mt-1 w-full" type="date" name="tanggal_stnk_berlaku_sampai" :value="old('tanggal_stnk_berlaku_sampai', $kendaraan->tanggal_stnk_berlaku_sampai?->format('Y-m-d'))" />
                                <x-input-error :messages="$errors->get('tanggal_stnk_berlaku_sampai')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="tanggal_kir_berlaku_sampai" :value="__('KIR Berlaku Sampai')" />
                                <x-text-input id="tanggal_kir_berlaku_sampai" class="block mt-1 w-full" type="date" name="tanggal_kir_berlaku_sampai" :value="old('tanggal_kir_berlaku_sampai', $kendaraan->tanggal_kir_berlaku_sampai?->format('Y-m-d'))" />
                                <x-input-error :messages="$errors->get('tanggal_kir_berlaku_sampai')" class="mt-2" />
                            </div>

                            <!-- Status Perawatan -->
                            <div>
                                <x-input-label for="status_perawatan" :value="__('Status Kendaraan')" />
                                <select id="status_perawatan" name="status_perawatan" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="BAIK" {{ old('status_perawatan', $kendaraan->status_perawatan) == 'BAIK' ? 'selected' : '' }}>Aktif (Baik)</option>
                                    <option value="PERLU_SERVIS" {{ old('status_perawatan', $kendaraan->status_perawatan) == 'PERLU_SERVIS' ? 'selected' : '' }}>Maintenance (Perlu Servis)</option>
                                    <option value="SEDANG_SERVIS" {{ old('status_perawatan', $kendaraan->status_perawatan) == 'SEDANG_SERVIS' ? 'selected' : '' }}>Maintenance (Sedang Servis)</option>
                                    <option value="RUSAK" {{ old('status_perawatan', $kendaraan->status_perawatan) == 'RUSAK' ? 'selected' : '' }}>Tidak Aktif (Rusak)</option>
                                </select>
                                <x-input-error :messages="$errors->get('status_perawatan')" class="mt-2" />
                            </div>

                            <!-- Pengelola -->
                            <div>
                                <x-input-label for="id_pengelola" :value="__('Pengelola')" />
                                <select id="id_pengelola" name="id_pengelola" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                    <option value="">-- Pilih Pengelola --</option>
                                    @foreach($pengelolas as $p)
                                        <option value="{{ $p->id }}" {{ old('id_pengelola', $kendaraan->id_pengelola) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('id_pengelola')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('kendaraan.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                Batal
                            </a>
                            <x-primary-button class="ml-4">
                                {{ __('Simpan Perubahan') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Galeri Foto Kendaraan (Card Terpisah) --}}
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">{{ __('Foto Kendaraan') }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Maksimal 5 foto. Foto pertama otomatis menjadi foto utama.</p>
                        </div>
                        <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full">
                            {{ $kendaraan->fotos->count() }} / 5 foto
                        </span>
                    </div>

                    {{-- Flash error dari foto controller --}}
                    @if(session('error'))
                        <div class="mb-3 p-3 rounded-md bg-red-50 border border-red-200 text-red-700 text-sm">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Grid foto existing --}}
                    @if($kendaraan->fotos->isNotEmpty())
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mb-4">
                            @foreach($kendaraan->fotos as $foto)
                                <div class="relative group rounded-lg overflow-hidden border-2 {{ $foto->is_utama ? 'border-indigo-500 shadow-sm' : 'border-gray-200' }} bg-gray-50">
                                    <img src="{{ \App\Services\ImageStorageService::url($foto->path) }}"
                                         alt="Foto kendaraan"
                                         class="w-full h-28 object-cover" />

                                    {{-- Badge utama --}}
                                    @if($foto->is_utama)
                                        <span class="absolute top-1 left-1 bg-indigo-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded shadow">
                                            UTAMA
                                        </span>
                                    @endif

                                    {{-- Overlay aksi --}}
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-1.5 p-2">
                                        {{-- Perbesar foto --}}
                                        <button type="button"
                                                onclick="openImagePreview('{{ \App\Services\ImageStorageService::url($foto->path) }}', 'Foto Kendaraan')"
                                                class="w-full text-[10px] font-semibold px-2 py-1 bg-white/20 hover:bg-white/30 text-white rounded transition whitespace-nowrap cursor-pointer">
                                            🔍 Perbesar
                                        </button>

                                        {{-- Set sebagai utama (hanya jika bukan utama) --}}
                                        @unless($foto->is_utama)
                                            <form method="POST" action="{{ route('kendaraan.foto.set-utama', [$kendaraan->id, $foto->id]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        class="w-full text-[10px] font-semibold px-2 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition whitespace-nowrap">
                                                    ★ Set Utama
                                                </button>
                                            </form>
                                        @endunless

                                        {{-- Hapus foto --}}
                                        <form method="POST" action="{{ route('kendaraan.foto.destroy', [$kendaraan->id, $foto->id]) }}"
                                              onsubmit="return confirm('Hapus foto ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="w-full text-[10px] font-semibold px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition">
                                                🗑 Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 italic mb-4">Belum ada foto yang diunggah.</p>
                    @endif

                    {{-- Form upload foto baru --}}
                    @if($kendaraan->fotos->count() < 5)
                        <form method="POST" action="{{ route('kendaraan.foto.store', $kendaraan->id) }}" enctype="multipart/form-data" class="mt-2">
                            @csrf
                            <div class="flex flex-col sm:flex-row gap-3 items-start">
                                <div class="flex-1">
                                    <input
                                        id="foto_kendaraan_upload"
                                        type="file"
                                        name="foto_kendaraan[]"
                                        multiple
                                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                        class="block w-full text-sm text-gray-700 border border-gray-300 rounded-md shadow-sm cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 file:mr-4 file:py-2 file:px-4 file:rounded-l-md file:border-0 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:bg-gray-800 file:text-white hover:file:bg-gray-700 transition"
                                    />
                                    <x-input-error :messages="$errors->get('foto_kendaraan')" class="mt-1" />
                                    <x-input-error :messages="$errors->get('foto_kendaraan.*')" class="mt-1" />
                                    <p class="mt-1 text-xs text-gray-500">
                                        Upload hingga {{ 5 - $kendaraan->fotos->count() }} foto sekaligus. Format: JPG, PNG, WebP. Maks 10 MB per file.
                                    </p>
                                </div>
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 whitespace-nowrap">
                                    + Unggah Foto
                                </button>
                            </div>
                        </form>
                    @else
                        <p class="text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                            Batas maksimal 5 foto sudah tercapai. Hapus foto yang ada untuk mengunggah yang baru.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
