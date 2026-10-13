<x-guest-layout>
    <div class="rounded-[28px] border border-slate-200 bg-white/90 p-6 shadow-[0_30px_60px_rgba(15,23,42,0.08)] backdrop-blur-sm sm:p-7">
        <div class="mb-6">
            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-sky-700">Akses Sistem</p>
            <h2 class="mt-3 text-2xl font-black text-slate-900">Masuk ke akun Anda</h2>
        </div>
        <p class="mt-1 text-sm text-slate-500">Gunakan akun dinas untuk mengakses dashboard armada.</p>

        <x-auth-session-status class="mt-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.75 text-sm text-slate-900 placeholder-slate-400 shadow-inner shadow-slate-200/30 transition focus:border-sky-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-sky-100"
                    placeholder="nama@pln.co.id">
                @error('email')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-sm font-medium text-slate-700">Kata sandi</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm font-medium text-sky-700 hover:text-sky-800 hover:underline">Lupa kata sandi?</a>
                    @endif
                </div>
                <div class="mt-1.5 flex items-center rounded-xl border border-slate-200 bg-slate-50 shadow-inner shadow-slate-200/30 transition focus-within:border-sky-500 focus-within:bg-white focus-within:ring-4 focus-within:ring-sky-100" x-data="{ show: false }">
                    <input :type="show ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password"
                        class="min-w-0 flex-1 border-0 bg-transparent px-3.5 py-2.75 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-0 shadow-none"
                        placeholder="••••••••">
                    <button type="button" @click="show = !show" tabindex="-1"
                        class="flex shrink-0 items-center pr-3 pl-1 text-slate-400 hover:text-slate-600">
                        <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.243L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-sky-700 focus:ring-sky-600">
                Ingat saya di perangkat ini
            </label>

            <button type="submit"
                class="w-full rounded-xl bg-gradient-to-r from-sky-700 to-blue-700 py-3 text-sm font-semibold text-white shadow-[0_14px_30px_rgba(3,105,161,0.35)] transition hover:brightness-110">
                Masuk
            </button>

        </form>
    </div>
</x-guest-layout>
