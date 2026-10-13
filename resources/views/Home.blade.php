<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">
            {{ __('Home') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-[30px] border border-slate-200 bg-gradient-to-br from-slate-950 via-sky-950 to-slate-900 shadow-[0_25px_60px_rgba(15,23,42,0.28)]">
                <div class="p-8 text-slate-50 sm:p-10">
                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-amber-300">
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
