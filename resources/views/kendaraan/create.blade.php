<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Kendaraan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('kendaraan.store') }}" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Plat Nomor -->
                            <div>
                                <x-input-label for="plat_nomor" :value="__('Nomor Polisi')" />
                                <x-text-input id="plat_nomor" class="block mt-1 w-full" type="text" name="plat_nomor" :value="old('plat_nomor')" required autofocus />
                                <x-input-error :messages="$errors->get('plat_nomor')" class="mt-2" />
                            </div>

                            <!-- Merk Tipe -->
                            <div>
                                <x-input-label for="merk_tipe" :value="__('Nama Kendaraan (Merk/Tipe)')" />
                                <x-text-input id="merk_tipe" class="block mt-1 w-full" type="text" name="merk_tipe" :value="old('merk_tipe')" required />
                                <x-input-error :messages="$errors->get('merk_tipe')" class="mt-2" />
                            </div>

                            <!-- Tahun Pembuatan -->
                            <div>
                                <x-input-label for="tahun_pembuatan" :value="__('Tahun Pembuatan')" />
                                <x-text-input id="tahun_pembuatan" class="block mt-1 w-full" type="number" name="tahun_pembuatan" :value="old('tahun_pembuatan')" required />
                                <x-input-error :messages="$errors->get('tahun_pembuatan')" class="mt-2" />
                            </div>

                            <!-- Kilometer Terakhir -->
                            <div>
                                <x-input-label for="kilometer_terakhir" :value="__('Kilometer')" />
                                <x-text-input id="kilometer_terakhir" class="block mt-1 w-full" type="number" name="kilometer_terakhir" :value="old('kilometer_terakhir')" required />
                                <x-input-error :messages="$errors->get('kilometer_terakhir')" class="mt-2" />
                            </div>

                            <!-- Transmisi -->
                            <div>
                                <x-input-label for="transmisi" :value="__('Transmisi')" />
                                <select id="transmisi" name="transmisi" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                    <option value="">-- Pilih Transmisi --</option>
                                    <option value="MANUAL" {{ old('transmisi') == 'MANUAL' ? 'selected' : '' }}>Manual</option>
                                    <option value="OTOMATIS" {{ old('transmisi') == 'OTOMATIS' ? 'selected' : '' }}>Otomatis</option>
                                </select>
                                <x-input-error :messages="$errors->get('transmisi')" class="mt-2" />
                            </div>

                            <!-- Jenis BBM -->
                            <div>
                                <x-input-label for="jenis_bbm" :value="__('Jenis BBM')" />
                                <select id="jenis_bbm" name="jenis_bbm" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                    <option value="">-- Pilih BBM --</option>
                                    <option value="BENSIN" {{ old('jenis_bbm') == 'BENSIN' ? 'selected' : '' }}>Bensin</option>
                                    <option value="SOLAR" {{ old('jenis_bbm') == 'SOLAR' ? 'selected' : '' }}>Solar</option>
                                    <option value="DIESEL" {{ old('jenis_bbm') == 'DIESEL' ? 'selected' : '' }}>Diesel</option>
                                    <option value="LISTRIK" {{ old('jenis_bbm') == 'LISTRIK' ? 'selected' : '' }}>Listrik</option>
                                </select>
                                <x-input-error :messages="$errors->get('jenis_bbm')" class="mt-2" />
                            </div>

                            <!-- Kategori Penggunaan -->
                            <div>
                                <x-input-label for="kategori_penggunaan" :value="__('Kategori Penggunaan')" />
                                <select id="kategori_penggunaan" name="kategori_penggunaan" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="PEJABAT" {{ old('kategori_penggunaan') == 'PEJABAT' ? 'selected' : '' }}>Pejabat</option>
                                    <option value="TEKNISI" {{ old('kategori_penggunaan') == 'TEKNISI' ? 'selected' : '' }}>Teknisi</option>
                                    <option value="ANGKUT_BARANG" {{ old('kategori_penggunaan') == 'ANGKUT_BARANG' ? 'selected' : '' }}>Angkut Barang</option>
                                    <option value="MOTOR_OPERASIONAL" {{ old('kategori_penggunaan') == 'MOTOR_OPERASIONAL' ? 'selected' : '' }}>Motor Operasional</option>
                                </select>
                                <x-input-error :messages="$errors->get('kategori_penggunaan')" class="mt-2" />
                            </div>
                            
                            <!-- Tanggal Pembelian -->
                            <div>
                                <x-input-label for="tanggal_pembelian" :value="__('Tanggal Pembelian')" />
                                <x-text-input id="tanggal_pembelian" class="block mt-1 w-full" type="date" name="tanggal_pembelian" :value="old('tanggal_pembelian')" required />
                                <x-input-error :messages="$errors->get('tanggal_pembelian')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="tanggal_stnk_berlaku_sampai" :value="__('STNK Berlaku Sampai')" />
                                <x-text-input id="tanggal_stnk_berlaku_sampai" class="block mt-1 w-full" type="date" name="tanggal_stnk_berlaku_sampai" :value="old('tanggal_stnk_berlaku_sampai')" />
                                <x-input-error :messages="$errors->get('tanggal_stnk_berlaku_sampai')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="tanggal_kir_berlaku_sampai" :value="__('KIR Berlaku Sampai')" />
                                <x-text-input id="tanggal_kir_berlaku_sampai" class="block mt-1 w-full" type="date" name="tanggal_kir_berlaku_sampai" :value="old('tanggal_kir_berlaku_sampai')" />
                                <x-input-error :messages="$errors->get('tanggal_kir_berlaku_sampai')" class="mt-2" />
                            </div>

                            <!-- Status Perawatan -->
                            <div>
                                <x-input-label for="status_perawatan" :value="__('Status Kendaraan')" />
                                <select id="status_perawatan" name="status_perawatan" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="BAIK" {{ old('status_perawatan') == 'BAIK' ? 'selected' : '' }}>Aktif (Baik)</option>
                                    <option value="PERLU_SERVIS" {{ old('status_perawatan') == 'PERLU_SERVIS' ? 'selected' : '' }}>Maintenance (Perlu Servis)</option>
                                    <option value="SEDANG_SERVIS" {{ old('status_perawatan') == 'SEDANG_SERVIS' ? 'selected' : '' }}>Maintenance (Sedang Servis)</option>
                                    <option value="RUSAK" {{ old('status_perawatan') == 'RUSAK' ? 'selected' : '' }}>Tidak Aktif (Rusak)</option>
                                </select>
                                <x-input-error :messages="$errors->get('status_perawatan')" class="mt-2" />
                            </div>

                            <!-- Pengelola -->
                            <div>
                                <x-input-label for="id_pengelola" :value="__('Pengelola')" />
                                <select id="id_pengelola" name="id_pengelola" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                    <option value="">-- Pilih Pengelola --</option>
                                    @foreach($pengelolas as $p)
                                        <option value="{{ $p->id }}" {{ old('id_pengelola') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('id_pengelola')" class="mt-2" />
                            </div>
                        </div>

                        {{-- Foto Kendaraan --}}
                        <div class="mt-6">
                            <x-input-label for="foto_kendaraan" :value="__('Foto Kendaraan')" />
                            <div class="mt-1">
                                <input
                                    id="foto_kendaraan"
                                    type="file"
                                    name="foto_kendaraan"
                                    accept="image/jpg,image/jpeg,image/png,image/webp"
                                    class="block w-full text-sm text-gray-700 border border-gray-300 rounded-md shadow-sm cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 file:mr-4 file:py-2 file:px-4 file:rounded-l-md file:border-0 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:bg-gray-800 file:text-white hover:file:bg-gray-700 transition"
                                    onchange="previewFoto(this)"
                                />
                                <x-input-error :messages="$errors->get('foto_kendaraan')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, WebP. Maksimal 2 MB. (Opsional)</p>
                            </div>
                            <div id="preview-container" class="mt-3 hidden">
                                <p class="text-xs text-gray-500 mb-1">Preview:</p>
                                <img id="foto-preview" alt="Preview Foto" class="h-40 w-auto rounded-md border border-gray-200 object-cover" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('kendaraan.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                Batal
                            </a>
                            <x-primary-button class="ml-4">
                                {{ __('Simpan') }}
                            </x-primary-button>
                        </div>
                    </form>

                    <script>
                        function previewFoto(input) {
                            const container = document.getElementById('preview-container');
                            const img = document.getElementById('foto-preview');
                            if (input.files && input.files[0]) {
                                const reader = new FileReader();
                                reader.onload = (e) => {
                                    img.src = e.target.result;
                                    container.classList.remove('hidden');
                                };
                                reader.readAsDataURL(input.files[0]);
                            } else {
                                container.classList.add('hidden');
                                img.removeAttribute('src');
                            }
                        }
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
