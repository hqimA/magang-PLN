<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-sky-700 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest shadow-sm shadow-sky-700/20 hover:bg-sky-800 focus:bg-sky-800 active:bg-sky-900 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
