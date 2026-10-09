<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Edit Template Servis') }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Mengubah data master template jadwal servis.</p>
            </div>
            <a href="{{ route('template-komponen.index', ['template_id' => $template->id]) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition">
                &larr; Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('template-jadwal-servis.update', $template->id) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    {{-- Nama Template --}}
                    <div>
                        <x-input-label for="nama" :value="__('Nama Template')" class="font-semibold text-gray-700" />
                        <x-text-input id="nama" name="nama" type="text"
                                      class="mt-1 block w-full @error('nama') border-red-500 @enderror"
                                      :value="old('nama', $template->nama)" required />
                        <x-input-error :messages="$errors->get('nama')" class="mt-1" />
                    </div>

                    {{-- Deskripsi --}}
                    <div>
                        <x-input-label for="deskripsi" :value="__('Deskripsi Singkat')" class="font-semibold text-gray-700" />
                        <textarea id="deskripsi" name="deskripsi" rows="3"
                                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm @error('deskripsi') border-red-500 @enderror"
                                  placeholder="Jelaskan spesifikasi atau peruntukan kendaraan untuk template ini...">{{ old('deskripsi', $template->deskripsi) }}</textarea>
                        <x-input-error :messages="$errors->get('deskripsi')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        {{-- Jenis BBM --}}
                        <div>
                            <x-input-label for="jenis_bbm" :value="__('Jenis Bahan Bakar')" class="font-semibold text-gray-700" />
                            <select id="jenis_bbm" name="jenis_bbm"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm @error('jenis_bbm') border-red-500 @enderror"
                                    required>
                                @foreach($jenisBbmList as $bbm)
                                    @php $val = $bbm->value; @endphp
                                    <option value="{{ $val }}" {{ old('jenis_bbm', $template->jenis_bbm?->value ?? $template->jenis_bbm) === $val ? 'selected' : '' }}>
                                        {{ $val }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('jenis_bbm')" class="mt-1" />
                        </div>

                        {{-- Transmisi --}}
                        <div>
                            <x-input-label for="transmisi" :value="__('Transmisi')" class="font-semibold text-gray-700" />
                            <select id="transmisi" name="transmisi"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm @error('transmisi') border-red-500 @enderror"
                                    required>
                                @foreach($transmisiList as $t)
                                    @php $val = $t->value; @endphp
                                    <option value="{{ $val }}" {{ old('transmisi', $template->transmisi?->value ?? $template->transmisi) === $val ? 'selected' : '' }}>
                                        {{ $val }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('transmisi')" class="mt-1" />
                        </div>
                    </div>

                    {{-- Status Aktif --}}
                    <div class="pt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_aktif" value="1"
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-4 w-4"
                                   {{ old('is_aktif', $template->is_aktif) ? 'checked' : '' }}>
                            <span class="ms-2 text-sm font-medium text-gray-700">Template Aktif (dapat digunakan pada master data kendaraan)</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('template-komponen.index', ['template_id' => $template->id]) }}"
                           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-semibold hover:bg-gray-300 transition">
                            Batal
                        </a>
                        <x-primary-button class="py-2.5 px-6">
                            {{ __('Simpan Perubahan') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
