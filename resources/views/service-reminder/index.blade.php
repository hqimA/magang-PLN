<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Service Reminder') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Success Message --}}
            @if (session('success'))
                <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Settings Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Pengaturan Notifikasi</h3>
                    @if(auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('service-reminder.settings') }}" class="flex flex-col sm:flex-row items-center gap-6">
                        @csrf
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ $setting->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-5 w-5">
                            <span class="ml-2 text-sm font-medium text-gray-700">Aktifkan Notifikasi Reminder</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-700">Munculkan reminder</span>
                            <x-text-input name="reminder_days_before" type="number" min="1" max="365" value="{{ $setting->reminder_days_before }}" class="w-20 text-center" />
                            <span class="text-sm text-gray-700">hari sebelum jatuh tempo.</span>
                        </div>
                        <x-primary-button>Simpan Pengaturan</x-primary-button>
                    </form>
                    @else
                    <p class="text-sm text-gray-500">
                        Pengaturan notifikasi hanya dapat diubah oleh Admin.
                    </p>
                    @endif
                </div>
            </div>

            {{-- Reminder List Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900" x-data="{ filter: 'all' }">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <h3 class="text-lg font-medium text-gray-900">Daftar Kendaraan Perlu Servis</h3>
                        
                        <div class="flex flex-wrap gap-2">
                            <button @click="filter = 'all'" :class="{'bg-gray-800 text-white': filter === 'all', 'bg-gray-200 text-gray-700': filter !== 'all'}" class="px-4 py-2 text-sm font-medium rounded-md transition-colors">Semua</button>
                            <button @click="filter = 'akan_servis'" :class="{'bg-yellow-500 text-white': filter === 'akan_servis', 'bg-yellow-100 text-yellow-800': filter !== 'akan_servis'}" class="px-4 py-2 text-sm font-medium rounded-md transition-colors">Akan Servis</button>
                            <button @click="filter = 'jatuh_tempo'" :class="{'bg-orange-500 text-white': filter === 'jatuh_tempo', 'bg-orange-100 text-orange-800': filter !== 'jatuh_tempo'}" class="px-4 py-2 text-sm font-medium rounded-md transition-colors">Jatuh Tempo</button>
                            <button @click="filter = 'terlambat'" :class="{'bg-red-600 text-white': filter === 'terlambat', 'bg-red-100 text-red-800': filter !== 'terlambat'}" class="px-4 py-2 text-sm font-medium rounded-md transition-colors">Terlambat</button>
                        </div>
                    </div>

                    @if(!$setting->is_active)
                        <div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50" role="alert">
                            Service reminder saat ini dinonaktifkan.
                        </div>
                    @elseif($vehicles->isEmpty())
                        <p class="text-gray-500 text-center py-8">
                            Tidak ada kendaraan yang membutuhkan reminder servis saat ini.
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kendaraan</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plat Nomor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jadwal Servis Berikutnya</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Odometer</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sisa Hari</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($vehicles as $index => $vehicle)
                                        @php
                                            $statusLabelForFilter = 'akan_servis';
                                            if ($vehicle['reminder']['status'] === 'MENDEKATI_SERVIS' && $vehicle['reminder']['sisa_hari'] == 0) {
                                                $statusLabelForFilter = 'jatuh_tempo';
                                            } elseif ($vehicle['reminder']['status'] === 'TERLAMBAT_SERVIS') {
                                                $statusLabelForFilter = 'terlambat';
                                            }
                                        @endphp
                                        <tr class="hover:bg-gray-50" x-show="filter === 'all' || filter === '{{ $statusLabelForFilter }}'" style="display: none;" x-transition>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $index + 1 }}</td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center">
                                                    @if($vehicle['foto'])
                                                        <img src="{{ asset('storage/' . $vehicle['foto']) }}" class="h-10 w-14 object-cover rounded-md border border-gray-200 mr-3" />
                                                    @else
                                                        <div class="h-10 w-14 flex items-center justify-center rounded-md border border-dashed border-gray-300 bg-gray-50 mr-3">
                                                            <svg class="h-5 w-5 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                    <span class="text-sm font-medium text-gray-900">{{ $vehicle['merk_tipe'] }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $vehicle['plat_nomor'] }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ \Carbon\Carbon::parse($vehicle['reminder']['tanggal_target'])->translatedFormat('d M Y') }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ number_format($vehicle['kilometer_terakhir']) }} km</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                @if($vehicle['reminder']['sisa_hari'] < 0)
                                                    <span class="text-red-600 font-semibold">{{ abs($vehicle['reminder']['sisa_hari']) }} hari lewat</span>
                                                @elseif($vehicle['reminder']['sisa_hari'] == 0)
                                                    <span class="text-orange-600 font-semibold">Hari ini</span>
                                                @else
                                                    {{ $vehicle['reminder']['sisa_hari'] }} hari
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                @php
                                                    $statusData = [
                                                        'MENDEKATI_SERVIS' => ['label' => 'Akan Servis', 'class' => 'bg-yellow-100 text-yellow-800'],
                                                        'TERLAMBAT_SERVIS' => ['label' => 'Terlambat', 'class' => 'bg-red-100 text-red-800'],
                                                    ];
                                                    
                                                    // Handle "Jatuh Tempo" manually if exactly 0 days. Wait, MENDEKATI_SERVIS applies even if 0 days. 
                                                    // I will override the label for Jatuh Tempo.
                                                    $statusLabel = $statusData[$vehicle['reminder']['status']]['label'] ?? $vehicle['reminder']['status'];
                                                    $statusClass = $statusData[$vehicle['reminder']['status']]['class'] ?? 'bg-gray-100 text-gray-800';
                                                    
                                                    if ($vehicle['reminder']['status'] === 'MENDEKATI_SERVIS' && $vehicle['reminder']['sisa_hari'] == 0) {
                                                        $statusLabel = 'Jatuh Tempo';
                                                        $statusClass = 'bg-orange-100 text-orange-800';
                                                    }
                                                @endphp
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusClass }}">
                                                    {{ $statusLabel }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700 max-w-xs truncate" title="{{ $vehicle['reminder']['alasan'] }}">
                                                {{ $vehicle['reminder']['alasan'] }}
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
</x-app-layout>
