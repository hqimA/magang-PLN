<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Rekap Biaya Pengeluaran Pemeliharaan') }}
        </h2>
    </x-slot>

    <main class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('laporan-pengeluaran.index') }}" class="flex flex-wrap items-end gap-4">
                <div class="grid gap-1">
                    <label for="bulan" class="text-sm font-medium text-gray-700">{{ __('Bulan') }}</label>
                    <select id="bulan" name="bulan" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $nomorBulan => $namaBulan)
                            <option value="{{ $nomorBulan }}" @selected($bulan === $nomorBulan)>{{ __($namaBulan) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-1">
                    <label for="tahun" class="text-sm font-medium text-gray-700">{{ __('Tahun') }}</label>
                    <input id="tahun" name="tahun" type="number" min="1000" max="9999" value="{{ $tahun }}" class="w-32 rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>
                <button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">
                    {{ __('Tampilkan') }}
                </button>
            </form>

            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Plat Nomor') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Kendaraan') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Pengelola') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Biaya Servis') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Biaya Perbaikan') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Total Pengeluaran') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-900">
                        @forelse ($rekap as $baris)
                            <tr>
                                <th scope="row" class="whitespace-nowrap px-4 py-3 text-left font-medium">{{ $baris['plat_nomor'] }}</th>
                                <td class="px-4 py-3">{{ $baris['merk_tipe'] }}</td>
                                <td class="px-4 py-3">{{ $baris['pengelola'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">Rp {{ number_format($baris['total_biaya_servis'], 2, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">Rp {{ number_format($baris['total_biaya_perbaikan'], 2, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold">Rp {{ number_format($baris['grand_total_pengeluaran'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    {{ __('Belum ada data kendaraan.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</x-app-layout>