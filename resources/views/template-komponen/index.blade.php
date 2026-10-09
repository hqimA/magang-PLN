<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Template Komponen & Jadwal Servis') }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Kelola tipe template master serta standar suku cadang dan matriks servis per tipe kendaraan.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('template-jadwal-servis.create') }}"
                   class="inline-flex items-center px-3.5 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition shadow-sm">
                    <svg class="h-4 w-4 me-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Template Baru
                </a>
                @if($selectedTemplate)
                    <a href="{{ route('template-komponen.create', ['template_id' => $selectedTemplate->id]) }}"
                       class="inline-flex items-center px-3.5 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition shadow-sm">
                        <svg class="h-4 w-4 me-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Komponen
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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

            {{-- Template Selector Tabs --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-3">
                <div class="flex items-center gap-2 overflow-x-auto pb-1 text-sm">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-2">Template:</span>
                    @foreach($templates as $tmpl)
                        <a href="{{ route('template-komponen.index', ['template_id' => $tmpl->id]) }}"
                           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg font-medium text-xs transition shrink-0 {{ ($selectedTemplate && $selectedTemplate->id === $tmpl->id) ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-gray-700 hover:bg-slate-200' }}">
                            <span>{{ $tmpl->nama }}</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ ($selectedTemplate && $selectedTemplate->id === $tmpl->id) ? 'bg-indigo-700 text-indigo-100' : 'bg-slate-200 text-gray-600' }}">
                                {{ $tmpl->komponen_count }}
                            </span>
                        </a>
                    @endforeach

                    <a href="{{ route('template-jadwal-servis.create') }}"
                       class="inline-flex items-center gap-1 px-3 py-2 rounded-lg font-semibold text-xs text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-dashed border-emerald-300 transition shrink-0">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Template
                    </a>
                </div>
            </div>

            @if($selectedTemplate)
                {{-- Banner Info Template Terpilih --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-600">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900">{{ $selectedTemplate->nama }}</h3>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $selectedTemplate->is_aktif ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $selectedTemplate->is_aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ $selectedTemplate->deskripsi ?? 'Tidak ada deskripsi.' }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-2 text-xs">
                                <div class="px-3 py-1.5 rounded-md bg-slate-50 border border-slate-200">
                                    <span class="text-gray-400">BBM:</span>
                                    <span class="font-semibold text-gray-800 ml-1">{{ $selectedTemplate->jenis_bbm?->value ?? $selectedTemplate->jenis_bbm }}</span>
                                </div>
                                <div class="px-3 py-1.5 rounded-md bg-slate-50 border border-slate-200">
                                    <span class="text-gray-400">Transmisi:</span>
                                    <span class="font-semibold text-gray-800 ml-1">{{ $selectedTemplate->transmisi?->value ?? $selectedTemplate->transmisi }}</span>
                                </div>
                                <div class="px-3 py-1.5 rounded-md bg-slate-50 border border-slate-200">
                                    <span class="text-gray-400">Total Komponen:</span>
                                    <span class="font-bold text-indigo-600 ml-1">{{ $komponens->count() }}</span>
                                </div>
                            </div>

                            {{-- Tombol Edit & Hapus Template --}}
                            <div class="flex items-center gap-1.5 ps-2 border-s border-gray-200">
                                <a href="{{ route('template-jadwal-servis.edit', $selectedTemplate->id) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-md transition border border-slate-300"
                                   title="Edit nama, BBM, atau transmisi template ini">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit Template
                                </a>

                                <form method="POST" action="{{ route('template-jadwal-servis.destroy', $selectedTemplate->id) }}"
                                      onsubmit="return confirm('Hapus template {{ $selectedTemplate->nama }} beserta seluruh komponennya?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold rounded-md transition border border-red-200"
                                            title="Hapus template ini">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tabel Daftar Komponen --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-semibold text-gray-800 text-base">Daftar Komponen & Matriks Servis</h4>
                            <span class="text-xs text-gray-400">
                                <span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-500 me-1"></span>P = Periksa
                                <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500 ms-3 me-1"></span>G = Ganti
                            </span>
                        </div>

                        @if($komponens->isEmpty())
                            <div class="text-center py-12 text-gray-400">
                                <p class="text-sm">Belum ada komponen yang didaftarkan pada template ini.</p>
                                <a href="{{ route('template-komponen.create', ['template_id' => $selectedTemplate->id]) }}" class="text-indigo-600 text-xs font-semibold hover:underline mt-2 inline-block">
                                    + Tambah Komponen Pertama
                                </a>
                            </div>
                        @else
                            <div class="overflow-x-auto border border-gray-200 rounded-xl">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-slate-50 text-gray-600 font-semibold text-xs uppercase tracking-wider">
                                        <tr>
                                            <th class="px-4 py-3 text-left w-12">No</th>
                                            <th class="px-4 py-3 text-left">Nama Komponen</th>
                                            <th class="px-4 py-3 text-left">Kategori</th>
                                            <th class="px-4 py-3 text-left">Matriks Jadwal (Interval KM & Aksi)</th>
                                            <th class="px-4 py-3 text-center w-24">Status</th>
                                            <th class="px-4 py-3 text-center w-28">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach($komponens as $komp)
                                            <tr class="hover:bg-slate-50/70 transition">
                                                <td class="px-4 py-3 font-semibold text-gray-400 text-xs text-center">
                                                    {{ $komp->nomor_urut }}
                                                </td>
                                                <td class="px-4 py-3 font-semibold text-gray-900">
                                                    {{ $komp->nama_komponen }}
                                                </td>
                                                <td class="px-4 py-3 text-xs text-gray-600">
                                                    @php
                                                        $kategoriVal = $komp->kategori instanceof \BackedEnum ? $komp->kategori->value : (string) $komp->kategori;
                                                    @endphp
                                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-medium">
                                                        {{ str_replace('_', ' ', ucwords(strtolower($kategoriVal), '_')) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if($komp->jadwalDetail->isEmpty())
                                                        <span class="text-xs text-gray-400 italic">Belum diset</span>
                                                    @else
                                                        <div class="flex flex-wrap gap-1 max-w-lg">
                                                            @foreach($komp->jadwalDetail as $jdw)
                                                                @php
                                                                    $aksi = $jdw->jenis_aksi instanceof \BackedEnum ? $jdw->jenis_aksi->value : (string) $jdw->jenis_aksi;
                                                                    $badgeClass = $aksi === 'G' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-blue-100 text-blue-800 border-blue-200';
                                                                @endphp
                                                                <span class="inline-flex items-center text-[11px] px-1.5 py-0.5 rounded border font-mono font-medium {{ $badgeClass }}"
                                                                      title="Interval: {{ number_format($jdw->interval_km) }} km ({{ $jdw->interval_bulan }} bln) — Aksi: {{ $aksi === 'G' ? 'Ganti' : 'Periksa' }}">
                                                                    {{ number_format($jdw->interval_km / 1000) }}k:{{ $aksi }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $komp->is_aktif ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                                                        {{ $komp->is_aktif ? 'Aktif' : 'Nonaktif' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <a href="{{ route('template-komponen.edit', $komp->id) }}"
                                                           class="p-1.5 text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 rounded transition"
                                                           title="Edit Komponen & Jadwal">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </a>
                                                        <form method="POST" action="{{ route('template-komponen.destroy', $komp->id) }}"
                                                              onsubmit="return confirm('Hapus atau nonaktifkan komponen {{ $komp->nama_komponen }}?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition"
                                                                    title="Hapus / Nonaktifkan">
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
            @endif

        </div>
    </div>
</x-app-layout>
