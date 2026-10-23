<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">{{ __('Fleet Management') }}</p>
                <h2 class="mt-1 text-xl font-semibold leading-tight text-slate-900">
                    {{ __('Laporan Pengeluaran') }}
                </h2>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-800">
                <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                {{ auth()->user()?->isAdmin() ? __('Admin') : __('Pengelola Kendaraan') }}
            </span>
        </div>
    </x-slot>

    @php
        $totalBiayaServis = collect($rekap)->sum('total_biaya_servis');
        $totalBiayaPerbaikan = collect($rekap)->sum('total_biaya_perbaikan');
        $totalPengeluaran = collect($rekap)->sum('grand_total_pengeluaran');
        $jumlahKendaraan = count($rekap);
    @endphp

    <main class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#2196f3] via-[#1e88e5] to-[#1565c0] p-6 text-white shadow-[0_24px_60px_rgba(33,150,243,0.22)] sm:p-8">
                <div class="pointer-events-none absolute -right-12 -top-24 h-72 w-72 rounded-full bg-white/15 blur-3xl"></div>
                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-300">
                            <svg class="h-4 w-4 text-sky-100" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7.5 15l4-4 3 3L21 7.5" />
                            </svg>
                            {{ __('Ringkasan pemeliharaan armada') }}
                        </span>
                        <h1 class="mt-4 text-2xl font-semibold tracking-tight sm:text-3xl">{{ __('Rekap Pengeluaran') }}</h1>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-slate-300 sm:text-base">
                            {{ __('Pantau biaya servis dan perbaikan kendaraan dalam satu laporan yang mudah dibaca dan diunduh.') }}
                        </p>
                    </div>

                    <form method="GET" action="{{ route('laporan-pengeluaran.index') }}" class="expense-report-filters grid w-full min-w-0 gap-3 rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur sm:grid-cols-[repeat(2,minmax(0,1fr))] lg:grid-cols-[9rem_9rem_13rem_max-content] lg:items-end lg:justify-start">
                        <div class="grid min-w-0 gap-1.5">
                            <label for="bulan" class="text-xs font-medium text-slate-300">{{ __('Periode Bulan') }}</label>
                            <select id="bulan" name="bulan" class="w-full min-w-0 rounded-lg border-white/30 bg-white/10 text-sm text-white shadow-sm focus:border-white focus:ring-white">
                                @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $nomorBulan => $namaBulan)
                                    <option value="{{ $nomorBulan }}" @selected($bulan === $nomorBulan)>{{ __($namaBulan) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid min-w-0 gap-1.5">
                            <label for="tahun" class="text-xs font-medium text-slate-300">{{ __('Tahun') }}</label>
                            <input id="tahun" name="tahun" type="number" min="1000" max="9999" value="{{ $tahun }}" class="w-full min-w-0 rounded-lg border-white/30 bg-white/10 text-sm text-white shadow-sm focus:border-white focus:ring-white">
                        </div>
                        <div class="grid min-w-0 gap-1.5">
                            <label for="unit_pln" class="text-xs font-medium text-slate-300">{{ __('Unit PLN') }}</label>
                            <select id="unit_pln" name="unit_pln" class="w-full min-w-0 rounded-lg border-white/30 bg-white/10 text-sm text-white shadow-sm focus:border-white focus:ring-white">
                                <option value="SEMUA" @selected($unitPlnSelected === 'SEMUA')>{{ __('Semua Unit') }}</option>
                                @foreach ($unitPln as $unit)
                                    <option value="{{ $unit->value }}" @selected($unitPlnSelected === $unit->value)>{{ $unit->value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-blue-800 shadow-sm transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h15m-12 5.25h9m-6 5.25h3" />
                            </svg>
                            {{ __('Tampilkan') }}
                        </button>
                    </form>
                </div>
            </section>

            @if (session('success'))
                <div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm">
                    <p class="font-semibold">{{ __('Permintaan gagal diproses') }}</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section aria-label="{{ __('Ringkasan periode laporan') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_12px_28px_rgba(15,23,42,0.06)]">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ __('Total Pengeluaran') }}</p>
                            <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sky-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m3-9.75c-.75-.75-1.75-1.125-3-1.125-1.657 0-3 .923-3 2.062S10.343 11.25 12 11.25s3 .923 3 2.063-1.343 2.062-3 2.062c-1.25 0-2.25-.375-3-1.125M19.5 12a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">{{ __('Akumulasi biaya pada periode terpilih') }}</p>
                </article>

                <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_12px_28px_rgba(15,23,42,0.06)]">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ __('Biaya Servis') }}</p>
                            <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900">Rp {{ number_format($totalBiayaServis, 0, ',', '.') }}</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sky-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 6.5l.58-2 2 2-1.5 1.5 3.5 3.5 1.5-1.5 2 2-2 2-2 1-7-7 1-2 1.92-1.5zM5.25 15.75l3 3" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">{{ __('Total biaya perawatan berkala') }}</p>
                </article>

                <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_12px_28px_rgba(15,23,42,0.06)]">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ __('Biaya Perbaikan') }}</p>
                            <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900">Rp {{ number_format($totalBiayaPerbaikan, 0, ',', '.') }}</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-rose-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 6.5l.58-2 2 2-1.5 1.5 3.5 3.5 1.5-1.5 2 2-2 2-2 1-7-7 1-2 1.92-1.5zM5.25 15.75l3 3" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">{{ __('Total biaya perbaikan kerusakan') }}</p>
                </article>

                <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_12px_28px_rgba(15,23,42,0.06)]">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ __('Kendaraan Terdaftar') }}</p>
                            <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($jumlahKendaraan, 0, ',', '.') }}</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 11.25l1.5-4.5h10.5l1.5 4.5m-15 0h16.5v6h-16.5v-6zm3 6v1.5m10.5-1.5v1.5M8.25 14.25h.008v.008H8.25v-.008zm7.5 0h.008v.008h-.008v-.008z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">{{ __('Kendaraan pada cakupan laporan ini') }}</p>
                </article>
            </section>

            <section aria-label="{{ __('Ekspor laporan') }}">
                <article class="w-full overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_12px_28px_rgba(15,23,42,0.06)]">
                    <div class="flex items-start gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3.75h5.379c.398 0 .779.158 1.06.44l4.121 4.12c.281.282.44.663.44 1.061v10.879a1.5 1.5 0 01-1.5 1.5h-9.5a1.5 1.5 0 01-1.5-1.5v-15a1.5 1.5 0 011.5-1.5zM13.5 4.5v4.25h4.25M9.75 13.5h6.5m-6.5 3h6.5" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="font-semibold text-slate-900">{{ __('Ekspor Laporan') }}</h3>
                            <p class="mt-1 text-sm leading-5 text-slate-500">{{ __('Unduh rekap periode ini untuk dokumentasi atau pengolahan lebih lanjut.') }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3 px-5 py-5 sm:px-6">
                        <a href="{{ route('laporan-pengeluaran.excel', request()->query()) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75v11.5m0 0l-4-4m4 4l4-4M4.5 16.5v2.25A1.5 1.5 0 006 20.25h12a1.5 1.5 0 001.5-1.5V16.5" />
                            </svg>
                            {{ __('Ekspor Excel') }}
                        </a>
                        <a href="{{ route('laporan-pengeluaran.pdf', request()->query()) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-sky-600 focus:ring-offset-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h6.379c.398 0 .779.158 1.06.44l3.871 3.87c.281.282.44.663.44 1.061v11.129a1.5 1.5 0 01-1.5 1.5h-10.25a1.5 1.5 0 01-1.5-1.5V5.25a1.5 1.5 0 011.5-1.5zM13.5 4.5v4.25h4.25M9 14.25h6m-6 3h6" />
                            </svg>
                            {{ __('Ekspor PDF') }}
                        </a>
                    </div>
                    <div class="mx-5 mb-5 rounded-xl bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500 sm:mx-6">
                        {{ __('File ekspor menggunakan periode dan unit yang sedang dipilih:') }}
                        <span class="font-semibold text-slate-700">{{ str_pad((string) $bulan, 2, '0', STR_PAD_LEFT) }}/{{ $tahun }}</span>
                        <span class="font-semibold text-slate-700">· {{ $unitPlnSelected === 'SEMUA' ? __('Seluruh Unit') : $unitPlnSelected }}</span>
                    </div>

                    <div class="mx-5 mb-5 flex flex-col gap-5 rounded-2xl border border-rose-200 bg-gradient-to-br from-white via-rose-50/50 to-amber-50/70 p-5 shadow-sm sm:mx-6 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div class="flex items-start gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 7.5h12m-10.5 0 .75 12h7.5l.75-12M9.75 7.5V5.25A1.5 1.5 0 0 1 11.25 3.75h1.5a1.5 1.5 0 0 1 1.5 1.5V7.5m-4.5 3.75v4.5m3-4.5v4.5" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="font-semibold text-slate-900">{{ __('Hapus Semua Data Laporan') }}</h3>
                                <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">
                                    {{ __('Sebelum menghapus, pastikan arsip laporan sudah diekspor.') }}
                                </p>
                            </div>
                        </div>
                        <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'verify-report-export')" class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-rose-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-600 focus:ring-offset-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 7.5h12m-10.5 0 .75 12h7.5l.75-12M9.75 7.5V5.25A1.5 1.5 0 0 1 11.25 3.75h1.5a1.5 1.5 0 0 1 1.5 1.5V7.5" />
                            </svg>
                            {{ __('Hapus Semua Data Laporan') }}
                        </button>
                    </div>
                </article>

            </section>

            <x-modal name="verify-report-export" focusable maxWidth="xl">
                <div class="overflow-hidden rounded-2xl">
                    <div class="bg-gradient-to-r from-amber-50 via-white to-rose-50 px-6 py-6 sm:px-8">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3h.008v.008H12v-.008ZM10.29 3.86 1.82 18.5A1.5 1.5 0 0 0 3.12 20.75h17.76a1.5 1.5 0 0 0 1.3-2.25L13.71 3.86a1.98 1.98 0 0 0-3.42 0Z" />
                            </svg>
                        </span>
                        <h2 class="mt-4 text-lg font-semibold text-slate-900">{{ __('Sudah ekspor arsip lengkap laporan?') }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            {{ __('Kami akan memverifikasi arsip terlebih dahulu. Data tidak akan dihapus jika arsip belum diekspor atau data berubah setelah ekspor.') }}
                        </p>
                    </div>
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 px-6 py-5 sm:flex-row sm:justify-end sm:px-8">
                        <button type="button" x-on:click="$dispatch('close-modal', 'verify-report-export')" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
                            {{ __('Batal') }}
                        </button>
                        <a href="{{ route('laporan-pengeluaran.archive') }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-sky-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-900 focus:outline-none focus:ring-2 focus:ring-sky-700 focus:ring-offset-2">
                            {{ __('Belum, ekspor dulu') }}
                        </a>
                        <form method="POST" action="{{ route('laporan-pengeluaran.destroy-all') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center rounded-xl bg-rose-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-600 focus:ring-offset-2 sm:w-auto">
                                {{ __('Sudah ekspor, lanjut hapus') }}
                            </button>
                        </form>
                    </div>
                </div>
            </x-modal>

            <section aria-labelledby="expense-table-heading" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_12px_28px_rgba(15,23,42,0.06)]">
                <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-end sm:justify-between sm:px-6">
                    <div>
                        <h3 id="expense-table-heading" class="text-lg font-semibold text-slate-900">{{ __('Rincian Pengeluaran Kendaraan') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Rekap biaya per kendaraan untuk periode yang dipilih.') }}</p>
                    </div>
                    <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        {{ number_format($jumlahKendaraan, 0, ',', '.') }} {{ __('kendaraan') }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="whitespace-nowrap px-5 py-3.5 font-semibold sm:px-6">{{ __('Kendaraan') }}</th>
                                <th scope="col" class="whitespace-nowrap px-5 py-3.5 font-semibold">{{ __('Unit PLN') }}</th>
                                <th scope="col" class="whitespace-nowrap px-5 py-3.5 font-semibold">{{ __('Pengelola') }}</th>
                                <th scope="col" class="whitespace-nowrap px-5 py-3.5 text-right font-semibold">{{ __('Biaya Servis') }}</th>
                                <th scope="col" class="whitespace-nowrap px-5 py-3.5 text-right font-semibold">{{ __('Biaya Perbaikan') }}</th>
                                <th scope="col" class="whitespace-nowrap px-5 py-3.5 text-right font-semibold sm:px-6">{{ __('Total Pengeluaran') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($rekap as $baris)
                                <tr class="transition hover:bg-slate-50/80">
                                    <th scope="row" class="px-5 py-4 text-left sm:px-6">
                                        <span class="block font-semibold text-slate-900">{{ $baris['plat_nomor'] }}</span>
                                        <span class="mt-1 block text-xs font-normal text-slate-500">{{ $baris['merk_tipe'] }}</span>
                                    </th>
                                    <td class="whitespace-nowrap px-5 py-4">{{ $baris['unit_pln'] }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">{{ $baris['pengelola'] }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right tabular-nums">Rp {{ number_format($baris['total_biaya_servis'], 2, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right tabular-nums">Rp {{ number_format($baris['total_biaya_perbaikan'], 2, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right font-semibold tabular-nums text-slate-900 sm:px-6">Rp {{ number_format($baris['grand_total_pengeluaran'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-16 text-center">
                                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3.75h5.379c.398 0 .779.158 1.06.44l4.121 4.12c.281.282.44.663.44 1.061v10.879a1.5 1.5 0 01-1.5 1.5h-9.5a1.5 1.5 0 01-1.5-1.5v-15a1.5 1.5 0 011.5-1.5zM13.5 4.5v4.25h4.25" />
                                            </svg>
                                        </span>
                                        <p class="mt-4 font-semibold text-slate-800">{{ __('Belum ada data kendaraan') }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ __('Data kendaraan untuk periode ini akan tampil di sini.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($jumlahKendaraan > 0)
                            <tfoot class="border-t border-slate-200 bg-slate-50">
                                <tr>
                                    <th scope="row" colspan="3" class="px-5 py-4 text-left text-sm font-semibold text-slate-800 sm:px-6">{{ __('Total') }}</th>
                                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold tabular-nums text-slate-800">Rp {{ number_format($totalBiayaServis, 2, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold tabular-nums text-slate-800">Rp {{ number_format($totalBiayaPerbaikan, 2, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold tabular-nums text-emerald-800 sm:px-6">Rp {{ number_format($totalPengeluaran, 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </section>
        </div>
    </main>
</x-app-layout>
