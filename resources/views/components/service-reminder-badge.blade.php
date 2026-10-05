@if($isActive && $count > 0)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full ml-2']) }}>
        {{ $count }}
    </span>
@endif