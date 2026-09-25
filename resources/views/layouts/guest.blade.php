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
    <body class="antialiased bg-[#F5F7FA] text-slate-800">
        <div class="min-h-screen flex">

            {{-- Panel kiri: brand / route visual --}}
            <div class="hidden lg:flex lg:w-[44%] relative bg-[#08203E] overflow-hidden flex-col justify-between p-12">
                <svg class="absolute inset-0 w-full h-full opacity-[0.14]" viewBox="0 0 600 900" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                    <path d="M-20 780 C 120 700, 160 560, 260 520 S 420 420, 460 300 S 560 140, 640 60" stroke="#F5A623" stroke-width="3" fill="none"/>
                    <circle cx="-20" cy="780" r="6" fill="#F5A623"/>
                    <circle cx="260" cy="520" r="6" fill="#F5A623"/>
                    <circle cx="460" cy="300" r="6" fill="#F5A623"/>
                    <circle cx="640" cy="60" r="6" fill="#F5A623"/>
                    <path d="M-40 200 C 100 260, 220 220, 300 140 S 480 20, 620 40" stroke="#4C7EA8" stroke-width="2" fill="none"/>
                </svg>

                <div class="relative z-10 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-md bg-[#F5A623] flex items-center justify-center font-semibold text-[#08203E] text-sm">P</div>
                    <span class="text-white font-medium tracking-tight">PLN Fleet Management</span>
                </div>

                <div class="relative z-10 max-w-sm">
                    <h1 class="text-3xl font-semibold text-white leading-snug">
                        Pantau armada, rute, dan status kendaraan dalam satu layar.
                    </h1>
                    <p class="mt-4 text-[#9FB6CE] text-sm leading-relaxed">
                        Data posisi, konsumsi bahan bakar, dan jadwal perawatan terhubung langsung dari lapangan.
                    </p>
                </div>

                <div class="relative z-10 text-[#6B87A3] text-xs">
                    &copy; {{ date('Y') }} PT PLN (Persero). Internal use only.
                </div>
            </div>

            {{-- Panel kanan: form --}}
            <div class="flex-1 flex items-center justify-center p-6 sm:p-10">
                <div class="w-full max-w-sm">
                    <div class="lg:hidden flex items-center gap-2 mb-8">
                        <div class="w-8 h-8 rounded-md bg-[#08203E] flex items-center justify-center font-semibold text-[#F5A623] text-sm">P</div>
                        <span class="font-medium text-slate-900">PLN Fleet Management</span>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>