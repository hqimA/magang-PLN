<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Data Kendaraan') }}
            </h2>
            @if($isAdmin)
                <div class="flex flex-wrap items-center gap-2">
                    <form id="bulk-delete-vehicles" method="POST" action="{{ route('kendaraan.destroy-selected') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" data-delete-vehicles
                                aria-describedby="selected-vehicle-count"
                                class="inline-flex items-center rounded-md border border-red-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            Hapus Kendaraan Terpilih
                            <span class="ml-2 rounded-full bg-red-100 px-2 py-0.5" data-selected-vehicle-count>0</span>
                        </button>
                        <span id="selected-vehicle-count" class="sr-only" aria-live="polite">0 kendaraan dipilih</span>
                    </form>
                    <a href="{{ route('kendaraan.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        + Tambah Kendaraan
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">

            @if($isAdmin)
                @if($errors->get('file'))
                    <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-semibold">File belum dapat diimpor</p>
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            @foreach($errors->get('file') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section aria-labelledby="kendaraan-import-heading" class="overflow-hidden rounded-2xl border border-indigo-100 bg-white shadow-sm">
                    <div class="flex flex-col gap-4 border-b border-indigo-100 bg-gradient-to-r from-indigo-50 to-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <h3 id="kendaraan-import-heading" class="font-semibold text-slate-900">Impor Kendaraan dari Excel</h3>
                            <p class="mt-1 text-sm text-slate-600">Tambahkan data armada sesuai kolom pada tabel kendaraan.</p>
                        </div>
                        <a href="{{ route('kendaraan.import.template') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50">
                            Unduh Template
                        </a>
                    </div>

                    <div class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
                        <form method="POST" action="{{ route('kendaraan.import') }}" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            @csrf
                            <div class="grid min-w-0 flex-1 gap-1.5">
                                <label for="kendaraan-import-file" class="text-xs font-semibold text-slate-700">Pilih file Excel</label>
                                <input id="kendaraan-import-file" name="file" type="file" accept=".xlsx,.xls" required class="block w-full min-w-0 rounded-lg border border-slate-200 bg-white text-sm text-slate-600 shadow-sm file:me-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2.5 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 focus:border-indigo-600 focus:ring-indigo-600">
                                <span class="text-xs text-slate-400">Format .xlsx atau .xls, maksimal 10 MB</span>
                            </div>
                            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                                Impor Kendaraan
                            </button>
                        </form>

                        <details class="group rounded-xl border border-slate-200 bg-slate-50">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-slate-700 marker:hidden">
                                Petunjuk kolom dan pengisian
                                <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
                            </summary>
                            <div class="space-y-2 border-t border-slate-200 px-4 py-3 text-xs leading-5 text-slate-600">
                                <p>Gunakan satu file Excel sesuai template. Isi kolom Foto Kendaraan dengan URL HTTPS langsung menuju file gambar publik (bukan halaman preview atau tautan berbagi). Gambar yang ditempel atau ditautkan sebagai objek Excel tidak didukung.</p>
                                <p>Jangan mengubah nama atau urutan 11 kolom template. Isi tanggal dengan format tanggal/bulan/tahun (DD/MM/YYYY), contoh 13/10/2026; tanggal STNK dan KIR boleh dikosongkan.</p>
                                <p>Gunakan MANUAL/OTOMATIS, BENSIN/SOLAR/DIESEL/LISTRIK, dan kategori PEJABAT/TEKNISI/ANGKUT_BARANG (atau Angkut Barang)/MOTOR_OPERASIONAL. Foto harus berupa JPG, PNG, atau WebP publik maksimal 10 MB dan 20 megapiksel per foto. Server akan mengunduh, mengompresi, mengubahnya ke WebP, dan mengunggahnya ke Cloudinary.</p>
                                <p>Kendaraan hasil impor belum diberikan kepada Pengelola. Admin dapat menetapkan Pengelola melalui menu edit kendaraan.</p>
                                <p class="font-semibold text-amber-800">Jika template, isi baris, gambar, atau plat nomor tidak valid/duplikat, seluruh file ditolak dan tidak ada kendaraan yang ditambahkan.</p>
                            </div>
                        </details>
                    </div>
                </section>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- Filter & pencarian --}}
                    <form method="GET" action="{{ route('kendaraan.index') }}" class="mb-6 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <div class="flex-1">
                            <x-text-input name="search" type="search" value="{{ $search ?? '' }}" placeholder="Cari plat nomor, merk/tipe, atau pengelola..." class="block w-full" />
                        </div>
                        <div class="sm:w-56">
                            <select name="status" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Semua Status</option>
                                <option value="BAIK" @selected(($status ?? '') === 'BAIK')>Aktif</option>
                                <option value="PERLU_SERVIS" @selected(($status ?? '') === 'PERLU_SERVIS')>Perlu Servis</option>
                                <option value="SEDANG_SERVIS" @selected(($status ?? '') === 'SEDANG_SERVIS')>Sedang Servis</option>
                                <option value="RUSAK" @selected(($status ?? '') === 'RUSAK')>Tidak Aktif</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-primary-button>Cari</x-primary-button>
                            @if(($search ?? '') !== '' || ($status ?? '') !== '')
                                <a href="{{ route('kendaraan.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>

                    @if($kendaraans->isEmpty())
                        <p class="text-gray-500 text-center py-8">
                            @if(($search ?? '') !== '' || ($status ?? '') !== '')
                                Data tidak ditemukan.
                            @else
                                Belum ada kendaraan yang ditetapkan untuk akun ini.
                            @endif
                            @if($isAdmin)
                                <a href="{{ route('kendaraan.create') }}" class="text-indigo-600 hover:underline">Tambah sekarang</a>.
                            @endif
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        @if($isAdmin)
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <label class="inline-flex items-center gap-2">
                                                    <input type="checkbox" data-select-all-vehicles
                                                           aria-label="Pilih semua kendaraan yang ditampilkan"
                                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                    <span class="sr-only">Pilih semua kendaraan yang ditampilkan</span>
                                                </label>
                                            </th>
                                        @endif
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plat Nomor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Merk / Tipe</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahun</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Km</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengelola</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($kendaraans as $index => $kendaraan)
                                        <tr class="hover:bg-gray-50">
                                            @if($isAdmin)
                                                <td class="px-4 py-3">
                                                    <input type="checkbox" name="kendaraan_ids[]"
                                                           value="{{ $kendaraan->id }}"
                                                           form="bulk-delete-vehicles"
                                                           data-vehicle-checkbox
                                                           aria-label="Pilih kendaraan {{ $kendaraan->plat_nomor }}"
                                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                </td>
                                            @endif
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $index + 1 }}</td>
                                            <td class="px-4 py-3">
                                                @if($kendaraan->foto_kendaraan)
                                                    <img src="{{ \App\Services\CloudinaryService::url($kendaraan->foto_kendaraan, 'w_150,h_150,c_fill,f_auto,q_auto') }}"
                                                         alt="Foto {{ $kendaraan->merk_tipe }}"
                                                         class="h-14 w-20 object-cover rounded-md border border-gray-200" />
                                                @else
                                                    <div class="h-14 w-20 flex items-center justify-center rounded-md border border-dashed border-gray-300 bg-gray-50">
                                                        <svg class="h-7 w-7 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $kendaraan->plat_nomor }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $kendaraan->merk_tipe }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $kendaraan->tahun_pembuatan }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ number_format($kendaraan->kilometer_terakhir) }} km</td>
                                            <td class="px-4 py-3 text-sm">
                                                @php
                                                    $statusMap = [
                                                        'BAIK'         => ['label' => 'Aktif', 'class' => 'bg-green-100 text-green-800'],
                                                        'PERLU_SERVIS' => ['label' => 'Perlu Servis', 'class' => 'bg-yellow-100 text-yellow-800'],
                                                        'SEDANG_SERVIS'=> ['label' => 'Sedang Servis', 'class' => 'bg-orange-100 text-orange-800'],
                                                        'RUSAK'        => ['label' => 'Tidak Aktif', 'class' => 'bg-red-100 text-red-800'],
                                                    ];
                                                    $s = $statusMap[$kendaraan->status_perawatan] ?? ['label' => $kendaraan->status_perawatan, 'class' => 'bg-gray-100 text-gray-800'];
                                                @endphp
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $s['class'] }}">
                                                    {{ $s['label'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $kendaraan->pengelola?->name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <div class="flex items-center gap-2">
                                                    <a href="{{ route('kendaraan.show', $kendaraan->id) }}"
                                                       class="inline-flex items-center px-3 py-1 bg-indigo-50 border border-indigo-200 rounded-md font-semibold text-xs text-indigo-700 uppercase tracking-widest shadow-sm hover:bg-indigo-100 transition ease-in-out duration-150">
                                                        {{ $isAdmin ? 'Komponen' : 'Detail' }}
                                                    </a>
                                                    @if($isAdmin)
                                                        <a href="{{ route('kendaraan.edit', $kendaraan->id) }}"
                                                           class="inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                            Edit
                                                        </a>
                                                        <form method="POST" action="{{ route('kendaraan.destroy', $kendaraan->id) }}"
                                                              onsubmit="return confirm('Yakin ingin menghapus kendaraan ini?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="inline-flex items-center px-3 py-1 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    @endif
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

    @if($isAdmin)
        <script>
            const bulkDeleteForm = document.getElementById('bulk-delete-vehicles');
            const vehicleCheckboxes = Array.from(document.querySelectorAll('[data-vehicle-checkbox]'));
            const selectAllVehicles = document.querySelector('[data-select-all-vehicles]');
            const deleteVehiclesButton = document.querySelector('[data-delete-vehicles]');
            const selectedVehicleCount = document.querySelector('[data-selected-vehicle-count]');
            const selectedVehicleAnnouncement = document.getElementById('selected-vehicle-count');

            function updateSelectedVehicles() {
                const checkedCount = vehicleCheckboxes.filter((checkbox) => checkbox.checked).length;

                deleteVehiclesButton.disabled = checkedCount === 0;
                selectedVehicleCount.textContent = checkedCount;
                selectedVehicleAnnouncement.textContent = `${checkedCount} kendaraan dipilih`;

                if (selectAllVehicles) {
                    selectAllVehicles.checked = checkedCount > 0 && checkedCount === vehicleCheckboxes.length;
                    selectAllVehicles.indeterminate = checkedCount > 0 && checkedCount < vehicleCheckboxes.length;
                }
            }

            vehicleCheckboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', updateSelectedVehicles);
            });

            selectAllVehicles?.addEventListener('change', () => {
                vehicleCheckboxes.forEach((checkbox) => {
                    checkbox.checked = selectAllVehicles.checked;
                });
                updateSelectedVehicles();
            });

            bulkDeleteForm.addEventListener('submit', (event) => {
                const checkedCount = vehicleCheckboxes.filter((checkbox) => checkbox.checked).length;

                if (checkedCount === 0 || !window.confirm(`Yakin ingin menghapus ${checkedCount} kendaraan yang dipilih beserta data terkait? Tindakan ini tidak dapat dibatalkan.`)) {
                    event.preventDefault();
                }
            });

            updateSelectedVehicles();
        </script>
    @endif
</x-app-layout>
