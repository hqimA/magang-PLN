<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Dashboard Admin') }}
        </h2>
    </x-slot>

    <main class="py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section aria-labelledby="fleet-kpi-heading">
                <h3 id="fleet-kpi-heading" class="mb-4 text-lg font-semibold text-gray-900">
                    {{ __('Ringkasan Armada') }}
                </h3>
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <dt class="text-sm font-medium text-gray-600">{{ __('Total Kendaraan') }}</dt>
                        <dd class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format($kpiData['totalKendaraan']) }}</dd>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 shadow-sm">
                        <dt class="text-sm font-medium text-amber-900">{{ __('Kendaraan Bermasalah') }}</dt>
                        <dd class="mt-2 text-3xl font-semibold text-amber-950">{{ number_format($kpiData['kendaraanBermasalah']) }}</dd>
                    </div>
                    <div class="rounded-lg border border-sky-200 bg-sky-50 p-5 shadow-sm">
                        <dt class="text-sm font-medium text-sky-900">{{ __('Pengajuan Menunggu') }}</dt>
                        <dd class="mt-2 text-3xl font-semibold text-sky-950">{{ number_format($kpiData['pendingApproval']) }}</dd>
                    </div>
                    <div class="rounded-lg border border-rose-200 bg-rose-50 p-5 shadow-sm">
                        <dt class="text-sm font-medium text-rose-900">{{ __('Laporan Kerusakan Aktif') }}</dt>
                        <dd class="mt-2 text-3xl font-semibold text-rose-950">{{ number_format($kpiData['laporanKerusakanAktif']) }}</dd>
                    </div>
                </dl>
            </section>

            <section class="grid gap-8 lg:grid-cols-2">
                <div aria-labelledby="monthly-expenses-heading">
                    <h3 id="monthly-expenses-heading" class="mb-4 text-lg font-semibold text-gray-900">
                        {{ __('Pengeluaran Bulan Ini') }}
                    </h3>
                    <p class="rounded-lg border border-emerald-200 bg-emerald-50 p-5 text-2xl font-semibold text-emerald-950">
                        Rp {{ number_format($kpiData['totalPengeluaranBulanIni'], 2, ',', '.') }}
                    </p>
                </div>

                <div aria-labelledby="vehicle-categories-heading">
                    <h3 id="vehicle-categories-heading" class="mb-4 text-lg font-semibold text-gray-900">
                        {{ __('Kendaraan per Kategori') }}
                    </h3>
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-gray-600">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Kategori Penggunaan') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Jumlah') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-900">
                                @forelse ($kpiData['kategoriBreakdown'] as $kategori)
                                    <tr>
                                        <th scope="row" class="px-4 py-3 text-left font-medium">{{ $kategori->kategori_penggunaan }}</th>
                                        <td class="px-4 py-3 text-right">{{ number_format($kategori->jumlah) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-4 py-6 text-center text-gray-500">
                                            {{ __('Belum ada data kendaraan.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </main>
</x-app-layout>