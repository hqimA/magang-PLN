@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-sky-200 focus:border-sky-500 focus:ring-sky-200 rounded-xl shadow-sm']) }}>
