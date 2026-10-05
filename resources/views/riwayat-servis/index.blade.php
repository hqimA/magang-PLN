<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Riwayat Servis') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Header with action --}}
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Daftar Riwayat Servis</h3>
                        <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'tambah-riwayat-servis')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Tambah Data
                        </button>
                    </div>

                    {{-- Filter Form --}}
                    <form method="GET" action="{{ route('service-history.index') }}"
                          class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <x-text-input name="search" type="search"
                                          value="{{ request('search') }}"
                                          placeholder="Cari bengkel, plat, merk..."
                                          class="block w-full" />
                        </div>
                        <div>
                            <select name="id_kendaraan"
                                    class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Semua Kendaraan</option>
                                @foreach($kendaraanList as $k)
                                    <option value="{{ $k->id }}" @selected(request('id_kendaraan') == $k->id)>
                                        {{ $k->plat_nomor }} – {{ $k->merk_tipe }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-text-input name="dari" type="date" value="{{ request('dari') }}"
                                          class="block w-full" />
                        </div>
                        <div>
                            <x-text-input name="sampai" type="date" value="{{ request('sampai') }}"
                                          class="block w-full" />
                        </div>
                        <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-4">
                            <x-primary-button>Cari</x-primary-button>
                            @if(request()->hasAny(['search','id_kendaraan','dari','sampai']))
                                <a href="{{ route('service-history.index') }}"
                                   class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition ease-in-out duration-150">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>

                    {{-- Table --}}
                    @if($riwayatServis->isEmpty())
                        <p class="text-gray-500 text-center py-10">Belum ada riwayat servis.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto/Nota</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kendaraan</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Servis</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bengkel</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Odometer</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Biaya</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($riwayatServis as $servis)
                                        <tr class="hover:bg-gray-50">
                                            {{-- Foto nota thumbnail --}}
                                            <td class="px-4 py-3">
                                                @if($servis->foto_nota)
                                                    <a href="{{ asset('storage/' . $servis->foto_nota) }}" target="_blank">
                                                        @if(str_ends_with(strtolower($servis->foto_nota), '.pdf'))
                                                            <span class="inline-flex items-center px-2 py-1 text-xs rounded bg-red-100 text-red-700 font-medium">PDF</span>
                                                        @else
                                                            <img src="{{ asset('storage/' . $servis->foto_nota) }}"
                                                                 alt="Foto Nota"
                                                                 class="h-12 w-16 object-cover rounded border border-gray-200" />
                                                        @endif
                                                    </a>
                                                @else
                                                    <div class="h-12 w-16 flex items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50">
                                                        <svg class="h-5 w-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 3.75h18A2.25 2.25 0 0123.25 6v12A2.25 2.25 0 0121 20.25H3A2.25 2.25 0 01.75 18V6A2.25 2.25 0 013 3.75z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-sm text-gray-900">{{ $servis->kendaraan->plat_nomor }}</div>
                                                <div class="text-xs text-gray-500">{{ $servis->kendaraan->merk_tipe }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                                {{ $servis->tanggal_servis->translatedFormat('d M Y') }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ $servis->pengajuan ? $servis->pengajuan->jenis_pengajuan : 'UMUM' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $servis->nama_bengkel }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                                {{ number_format($servis->kilometer_servis) }} km
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                                Rp {{ number_format($servis->total_biaya, 0, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                @php
                                                    $status = $servis->pengajuan ? $servis->pengajuan->status_persetujuan : 'SELESAI';
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
                                            </td>
                                            <td class="px-4 py-3">
                                                <a href="{{ route('service-history.show', $servis->id) }}"
                                                   class="inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                    Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        <div class="mt-4">
                            {{ $riwayatServis->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Riwayat --}}
    <x-modal name="tambah-riwayat-servis" focusable>
        <form method="post" action="{{ route('riwayat-servis.store') }}" enctype="multipart/form-data" class="p-6">
            @csrf
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Tambah Riwayat Servis / Upload Dokumentasi') }}
            </h2>

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="id_kendaraan" value="{{ __('Kendaraan') }}" />
                    <select id="id_kendaraan" name="id_kendaraan" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                        <option value="">Pilih Kendaraan...</option>
                        @foreach($kendaraanList as $k)
                            <option value="{{ $k->id }}">{{ $k->plat_nomor }} - {{ $k->merk_tipe }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('id_kendaraan')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="tanggal_servis" value="{{ __('Tanggal Servis') }}" />
                        <x-text-input id="tanggal_servis" name="tanggal_servis" type="date" class="mt-1 block w-full" :value="old('tanggal_servis', date('Y-m-d'))" required />
                        <x-input-error :messages="$errors->get('tanggal_servis')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="kilometer_servis" value="{{ __('Odometer (KM)') }}" />
                        <x-text-input id="kilometer_servis" name="kilometer_servis" type="number" class="mt-1 block w-full" :value="old('kilometer_servis')" required min="0" />
                        <x-input-error :messages="$errors->get('kilometer_servis')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="nama_bengkel" value="{{ __('Nama Bengkel / Tindakan') }}" />
                    <x-text-input id="nama_bengkel" name="nama_bengkel" type="text" class="mt-1 block w-full" :value="old('nama_bengkel')" required />
                    <x-input-error :messages="$errors->get('nama_bengkel')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="total_biaya" value="{{ __('Total Biaya (Rp)') }}" />
                    <x-text-input id="total_biaya" name="total_biaya" type="number" class="mt-1 block w-full" :value="old('total_biaya')" required min="0" />
                    <x-input-error :messages="$errors->get('total_biaya')" class="mt-2" />
                </div>

                <div x-data="{ photoName: null, photoPreview: null }">
                    <x-input-label for="foto_nota" value="{{ __('Upload Foto Dokumentasi / Nota') }}" />
                    <input type="file" id="foto_nota" name="foto_nota" accept="image/*,.pdf" class="hidden"
                           x-ref="photo"
                           x-on:change="
                               photoName = $refs.photo.files[0].name;
                               const reader = new FileReader();
                               reader.onload = (e) => {
                                   photoPreview = e.target.result;
                               };
                               reader.readAsDataURL($refs.photo.files[0]);
                           " />

                    <div class="mt-2">
                        <x-secondary-button x-on:click.prevent="$refs.photo.click()">
                            {{ __('Pilih Foto') }}
                        </x-secondary-button>
                    </div>

                    <x-input-error :messages="$errors->get('foto_nota')" class="mt-2" />

                    <div class="mt-4" x-show="photoPreview" style="display: none;">
                        <span class="block w-24 h-24 rounded shadow-sm bg-cover bg-no-repeat bg-center border border-gray-200"
                              x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
                        </span>
                        <p class="mt-1 text-xs text-gray-500" x-text="photoName"></p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Batal') }}
                </x-secondary-button>

                <x-primary-button class="ms-3">
                    {{ __('Simpan Data') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
