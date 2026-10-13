<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'PLN Fleet') }}</title>

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="antialiased bg-[radial-gradient(circle_at_top,_rgba(146,164,188,0.12),transparent_25%),linear-gradient(180deg,#eef4fb_0%,#f8fafc_100%)] text-slate-800">
        <div class="min-h-screen flex">
            <div class="hidden lg:flex lg:w-[46%] relative overflow-hidden bg-gradient-to-br from-[#071827] via-[#0b1f34] to-[#123b60] p-12 text-white">
                <div class="absolute inset-0 opacity-25">
                    <div class="absolute left-[-8%] top-[-5%] h-64 w-64 rounded-full bg-amber-300/40 blur-3xl"></div>
                    <div class="absolute bottom-[-10%] right-[-5%] h-64 w-64 rounded-full bg-sky-400/30 blur-3xl"></div>
                </div>

                <svg class="absolute inset-0 h-full w-full opacity-20" viewBox="0 0 800 900" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                    <path d="M-20 780C120 700 160 560 270 520C380 480 430 420 470 300C510 180 570 120 650 60" stroke="#FBBF24" stroke-width="3" fill="none"/>
                    <circle cx="270" cy="520" r="6" fill="#FBBF24"/>
                    <circle cx="470" cy="300" r="6" fill="#FBBF24"/>
                    <circle cx="650" cy="60" r="6" fill="#FBBF24"/>
                    <path d="M-40 220C100 280 220 220 300 150C380 80 490 40 620 60" stroke="#7DD3FC" stroke-width="2" fill="none"/>
                </svg>

                <div class="relative z-10 flex w-full flex-col justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-300 to-yellow-500 font-black text-slate-900 shadow-[0_10px_25px_rgba(251,191,36,0.35)]">P</div>
                        <span class="text-sm font-semibold tracking-[0.12em] text-slate-200 uppercase">PLN Fleet Management</span>
                    </div>

                    <div class="max-w-md">
                        <h1 class="text-4xl font-black leading-tight text-white">
                            Pantau armada, rute, dan status kendaraan dalam satu layar.
                        </h1>
                        <p class="mt-4 text-base leading-relaxed text-slate-300">
                            Data posisi, konsumsi bahan bakar, dan jadwal perawatan terhubung langsung dari lapangan untuk mendukung operasional internal PLN.
                        </p>
                    </div>

                    <div class="text-xs text-slate-400">
                        &copy; {{ date('Y') }} PT PLN (Persero). Internal use only.
                    </div>
                </div>
            </div>

            <div class="flex flex-1 items-center justify-center p-6 sm:p-10">
                <div class="w-full max-w-md">
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-sky-700 to-sky-500 font-black text-white shadow-lg shadow-sky-500/20">P</div>
                        <span class="text-sm font-semibold tracking-[0.12em] text-slate-700 uppercase">PLN Fleet</span>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
