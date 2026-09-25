<x-guest-layout>

    <h2 class="text-xl font-semibold text-slate-900">Masuk ke akun Anda</h2>
    <p class="mt-1 text-sm text-slate-500">Gunakan akun dinas untuk mengakses dashboard armada.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-[#08203E] focus:ring-1 focus:ring-[#08203E] focus:outline-none"
                placeholder="nama@pln.co.id">
            @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-slate-700">Kata sandi</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm text-[#08203E] hover:underline">Lupa kata sandi?</a>
                @endif
            </div>
            <div class="mt-1.5 flex items-center rounded-lg border border-slate-300 bg-white focus-within:border-[#08203E] focus-within:ring-1 focus-within:ring-[#08203E]" x-data="{ show: false }">
                <input :type="show ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password"
                    class="min-w-0 flex-1 border-0 border-none bg-transparent px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-none focus:outline-none focus:ring-0 shadow-none"
                    placeholder="••••••••">
                <button type="button" @click="show = !show" tabindex="-1"
                    class="flex shrink-0 items-center pr-3 pl-1 text-slate-400 hover:text-slate-600">
                    <svg x-show="!show" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg x-show="show" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.243L9.88 9.88" />
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember --}}
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#08203E] focus:ring-[#08203E]">
            Ingat saya di perangkat ini
        </label>

        <button type="submit"
            class="w-full rounded-lg bg-[#08203E] py-2.5 text-sm font-medium text-white hover:bg-[#0B2C55] transition-colors">
            Masuk
        </button>

        @if (Route::has('register'))
            <p class="text-center text-sm text-slate-500">
                Belum punya akun?
                <a href="{{ route('register') }}" class="font-medium text-[#08203E] hover:underline">Daftar</a>
            </p>
        @endif
    </form>
</x-guest-layout>