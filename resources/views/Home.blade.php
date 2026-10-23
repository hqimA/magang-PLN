<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">
            {{ __('Home') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-[30px] border border-sky-400/30 bg-gradient-to-br from-[#2196f3] via-[#1e88e5] to-[#1565c0] shadow-[0_25px_60px_rgba(33,150,243,0.22)]">
                <div class="p-8 text-slate-50 sm:p-10">
                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-sky-100">
                        PLN Internal
                    </p>
                    <div class="mt-5 max-w-2xl">
                        <p class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                            {{ __("You're logged in as pengelola!") }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
