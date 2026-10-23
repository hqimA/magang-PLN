<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Maintenance') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <nav class="flex flex-wrap gap-2" aria-label="Filter status maintenance">
                    <a href="{{ route('maintenance.index', ['tab' => 'pengajuan']) }}" @if($tab === 'pengajuan') aria-current="page" @endif class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium shadow-sm transition {{ $tab === 'pengajuan' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                        Pengajuan Maintenance
                    </a>
                    <a href="{{ route('maintenance.index', ['tab' => 'proses']) }}" @if($tab === 'proses') aria-current="page" @endif class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium shadow-sm transition {{ $tab === 'proses' ? 'bg-amber-500 text-white' : 'bg-amber-100 text-amber-800 hover:bg-amber-200' }}">
                        Proses Maintenance
                    </a>
                    <a href="{{ route('maintenance.index', ['tab' => 'usai']) }}" @if($tab === 'usai') aria-current="page" @endif class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium shadow-sm transition {{ $tab === 'usai' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                        Usai Maintenance
                    </a>
                    <a href="{{ route('maintenance.index', ['tab' => 'ditolak']) }}" @if($tab === 'ditolak') aria-current="page" @endif class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium shadow-sm transition {{ $tab === 'ditolak' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100' }}">
                        Maintenance Ditolak
                    </a>
            </nav>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    @if($tab === 'pengajuan')
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Daftar Pengajuan Maintenance</h3>
                            @if(!auth()->user()->isAdmin())
                                <a href="{{ route('maintenance.create') }}"
                                   class="inline-flex items-center px-3.5 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                                    + Ajukan Maintenance
                                </a>
                            @endif
                        </div>

                        @if($pengajuanMaintenance->isEmpty())
                            <div class="text-center py-8 text-gray-500">
                                <p>{{ auth()->user()->isAdmin() ? 'Tidak ada pengajuan maintenance yang menunggu persetujuan.' : 'Belum ada pengajuan maintenance.' }}</p>
                                @if(!auth()->user()->isAdmin())
                                    <div class="mt-4">
                                        <a href="{{ route('maintenance.create') }}"
                                           class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition shadow-sm">
                                            + Ajukan Maintenance Sekarang
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @else
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kendaraan</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KM</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($pengajuanMaintenance as $p)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="font-medium text-gray-900">{{ $p->kendaraan->merk_tipe }}</div>
                                                <div class="text-sm text-gray-500">{{ $p->kendaraan->plat_nomor }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                                    {{ $p->jenis_pengajuan }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                @php
                                                    $kmAkhir = $p->kendaraan->kilometer_terakhir;
                                                @endphp
                                                {{ $kmAkhir !== null ? number_format($kmAkhir) . ' km' : '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $p->dibuat_pada->translatedFormat('d M Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $statusMap = [
                                                        'MENUNGGU'  => ['label' => 'Menunggu',  'class' => 'bg-amber-50 text-amber-700 ring-amber-600/20'],
                                                        'DISETUJUI' => ['label' => 'Disetujui', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'],
                                                        'DITOLAK'   => ['label' => 'Ditolak',   'class' => 'bg-red-50 text-red-700 ring-red-600/20'],
                                                        'DIBATALKAN' => ['label' => 'Dibatalkan', 'class' => 'bg-gray-50 text-gray-700 ring-gray-600/20'],
                                                    ];
                                                    $st = $statusMap[$p->status_persetujuan] ?? ['label' => $p->status_persetujuan, 'class' => 'bg-gray-50 text-gray-700 ring-gray-600/20'];
                                                @endphp
                                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $st['class'] }}">
                                                    {{ $st['label'] }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('maintenance.show', ['pengajuan' => $p->id, 'tab' => $tab]) }}" class="text-indigo-600 hover:text-indigo-900">Detail</a>
                                                @if(!auth()->user()->isAdmin() && in_array($p->status_persetujuan, ['MENUNGGU', 'DITOLAK'], true))
                                                    <span class="mx-1 text-gray-300">|</span>
                                                    <form method="POST" action="{{ route('maintenance.cancel', $p) }}" class="inline"
                                                          onsubmit="return confirm('Yakin ingin membatalkan pengajuan maintenance ini?')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-red-600 hover:text-red-800">
                                                            Batalkan
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    @elseif($tab === 'proses')
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Daftar Proses Maintenance</h3>
                        </div>

                        @if($maintenanceAktif->isEmpty())
                            <div class="text-center py-8 text-gray-500">
                                Belum ada maintenance yang sedang diproses.
                            </div>
                        @else
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kendaraan</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KM</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Disetujui</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($maintenanceAktif as $m)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="font-medium text-gray-900">{{ $m->kendaraan->merk_tipe }}</div>
                                                <div class="text-sm text-gray-500">{{ $m->kendaraan->plat_nomor }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                                    {{ $m->jenis_pengajuan }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                @php
                                                    $kmAkhir = $m->kendaraan->kilometer_terakhir;
                                                @endphp
                                                {{ $kmAkhir !== null ? number_format($kmAkhir) . ' km' : '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $m->dibuat_pada->translatedFormat('d M Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                                    SEDANG BERJALAN
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('maintenance.show', ['pengajuan' => $m->id, 'tab' => $tab]) }}" class="text-indigo-600 hover:text-indigo-900">Detail</a>
                                                <span class="mx-1 text-gray-300">|</span>
                                                <button type="button"
                                                        x-data=""
                                                        x-on:click="
                                                            $dispatch('set-selesaikan', {
                                                                id: {{ $m->id }},
                                                                kendaraan_id: {{ $m->kendaraan->id }},
                                                                kendaraan_text: '{{ addslashes($m->kendaraan->merk_tipe) }} ({{ $m->kendaraan->plat_nomor }})',
                                                                kilometer: {{ $kmAkhir ?? 0 }},
                                                                estimasi_biaya: {{ (int) $m->estimasi_biaya }},
                                                                deskripsi: '{{ addslashes(preg_replace('/\s+/', ' ', $m->deskripsi_keluhan)) }}'
                                                            });
                                                            $dispatch('open-modal', 'modal-selesaikan-maintenance');
                                                        "
                                                        class="text-emerald-600 hover:text-emerald-900 font-medium">
                                                    Selesaikan
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    @elseif($tab === 'usai')
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-lg font-medium text-gray-900">Daftar Maintenance Usai</h3>
                            @if(auth()->user()->isAdmin() || auth()->user()->unit_pln !== null)
                                <form method="GET" action="{{ route('maintenance.export.completed') }}" class="flex flex-wrap items-center gap-2">
                                    @if(auth()->user()->isAdmin())
                                        <label for="unit_pln_completed" class="sr-only">Unit PLN ekspor maintenance usai</label>
                                        <select id="unit_pln_completed" name="unit_pln" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="SEMUA">Semua Unit</option>
                                            @foreach($unitPln as $unit)
                                                <option value="{{ $unit->value }}">{{ $unit->value }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="hidden" name="unit_pln" value="{{ auth()->user()->unit_pln->value }}">
                                        <span class="text-sm text-gray-600">Unit: {{ auth()->user()->unit_pln->value }}</span>
                                    @endif
                                    <button type="submit" class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white shadow-sm transition hover:bg-emerald-700">Export Excel</button>
                                </form>
                            @endif
                        </div>

                        @if($maintenanceUsai->isEmpty())
                            <div class="text-center py-8 text-gray-500">
                                Belum ada maintenance yang selesai.
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kendaraan</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Servis</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bengkel</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Odometer</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Biaya</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($maintenanceUsai as $servis)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="font-medium text-gray-900">{{ $servis->kendaraan->merk_tipe }}</div>
                                                    <div class="text-sm text-gray-500">{{ $servis->kendaraan->plat_nomor }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $servis->pengajuan?->jenis_pengajuan ?? 'UMUM' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $servis->tanggal_servis->translatedFormat('d M Y') }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $servis->nama_bengkel }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ number_format($servis->kilometer_servis) }} km</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">Rp {{ number_format($servis->total_biaya, 0, ',', '.') }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <a href="{{ route('service-history.show', $servis->id) }}" class="text-indigo-600 hover:text-indigo-900">Detail</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4">
                                {{ $maintenanceUsai->links() }}
                            </div>
                        @endif
                    @elseif($tab === 'ditolak')
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-lg font-medium text-gray-900">Daftar Maintenance Ditolak</h3>
                            @if(auth()->user()->isAdmin() || auth()->user()->unit_pln !== null)
                                <form method="GET" action="{{ route('maintenance.export.rejected') }}" class="flex flex-wrap items-center gap-2">
                                    @if(auth()->user()->isAdmin())
                                        <label for="unit_pln_rejected" class="sr-only">Unit PLN ekspor maintenance ditolak</label>
                                        <select id="unit_pln_rejected" name="unit_pln" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="SEMUA">Semua Unit</option>
                                            @foreach($unitPln as $unit)
                                                <option value="{{ $unit->value }}">{{ $unit->value }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="hidden" name="unit_pln" value="{{ auth()->user()->unit_pln->value }}">
                                        <span class="text-sm text-gray-600">Unit: {{ auth()->user()->unit_pln->value }}</span>
                                    @endif
                                    <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white shadow-sm transition hover:bg-red-700">Export Excel</button>
                                </form>
                            @endif
                        </div>

                        @if($maintenanceDitolak->isEmpty())
                            <div class="text-center py-8 text-gray-500">
                                Belum ada pengajuan maintenance yang ditolak.
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kendaraan</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Pengajuan</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alasan Penolakan</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($maintenanceDitolak as $pengajuan)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="font-medium text-gray-900">{{ $pengajuan->kendaraan->merk_tipe }}</div>
                                                    <div class="text-sm text-gray-500">{{ $pengajuan->kendaraan->plat_nomor }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $pengajuan->jenis_pengajuan }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $pengajuan->dibuat_pada->translatedFormat('d M Y') }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-700">{{ $pengajuan->alasan_penolakan ?: 'Tidak ada alasan yang dicantumkan.' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <a href="{{ route('maintenance.show', ['pengajuan' => $pengajuan->id, 'tab' => $tab]) }}" class="text-indigo-600 hover:text-indigo-900">Detail</a>
                                                    @if(!auth()->user()->isAdmin() && $pengajuan->id_pengaju === auth()->id())
                                                        <span class="mx-1 text-gray-300">|</span>
                                                        <form method="POST" action="{{ route('maintenance.cancel', $pengajuan) }}" class="inline" onsubmit="return confirm('Yakin ingin membatalkan pengajuan maintenance ini?')">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="text-red-600 hover:text-red-800">Batalkan</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Selesaikan Maintenance (Input Riwayat Servis) --}}
    <x-modal name="modal-selesaikan-maintenance" focusable>
        <form method="POST" action="{{ route('riwayat-servis.store') }}" enctype="multipart/form-data" class="p-6"
              x-data="{
                  maintenance: {
                      id: '',
                      kendaraan_id: '',
                      kendaraan_text: '',
                      kilometer: '',
                      estimasi_biaya: '',
                      deskripsi: ''
                  }
              }"
              x-on:set-selesaikan.window="maintenance = $event.detail">
            @csrf
            <input type="hidden" name="id_pengajuan" x-bind:value="maintenance.id" />
            <input type="hidden" name="id_kendaraan" x-bind:value="maintenance.kendaraan_id" />

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
                    <span class="font-semibold text-gray-900" x-text="maintenance.kendaraan_text"></span>
                </div>
                <div class="flex justify-between items-start gap-4">
                    <span class="text-gray-500 text-xs uppercase tracking-wider font-medium">Keluhan</span>
                    <span class="text-gray-700 text-right truncate max-w-xs" x-text="maintenance.deskripsi"></span>
                </div>
            </div>

            <div class="mt-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="tanggal_servis" value="Tanggal Servis Selesai *" />
                        <x-text-input id="tanggal_servis" name="tanggal_servis" type="date"
                                      class="mt-1 block w-full text-sm"
                                      value="{{ date('Y-m-d') }}" required />
                        <x-input-error :messages="$errors->get('tanggal_servis')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="kilometer_servis" value="Odometer Selesai (KM)" />
                        <p id="kilometer_servis" class="mt-1 block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
                           x-text="maintenance.kilometer ? Number(maintenance.kilometer).toLocaleString('id-ID') + ' km' : '-'"></p>
                        <p class="mt-1 text-xs text-gray-500">Nilai diambil otomatis dari kilometer terakhir kendaraan.</p>
                    </div>
                </div>

                <div>
                    <x-input-label for="nama_bengkel" value="Nama Bengkel / Pelaksana Servis *" />
                    <x-text-input id="nama_bengkel" name="nama_bengkel" type="text"
                                  class="mt-1 block w-full text-sm"
                                  placeholder="Contoh: Bengkel Resmi Toyota / Bengkel Rekanan PLN" required />
                    <x-input-error :messages="$errors->get('nama_bengkel')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="total_biaya" value="Total Biaya Aktual (Rp) *" />
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm pointer-events-none">Rp</span>
                        <x-text-input id="total_biaya" name="total_biaya" type="number" min="0" step="1000"
                                      class="block w-full pl-9 text-sm"
                                      x-bind:value="maintenance.estimasi_biaya" required />
                    </div>
                    <x-input-error :messages="$errors->get('total_biaya')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="foto_nota" value="Upload Foto Nota / Dokumentasi Servis *" />
                    <input id="foto_nota" name="foto_nota" type="file" accept="image/*,.pdf" required
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                    <p class="mt-1 text-xs text-gray-500">Foto nota wajib diunggah untuk menyelesaikan maintenance. File gambar atau PDF, maksimal 10 MB.</p>
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
</x-app-layout>
