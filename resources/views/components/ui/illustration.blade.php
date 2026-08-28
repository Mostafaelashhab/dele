@props([
    'name' => 'parcel',
    'class' => 'h-28',
])

{{--
    Empty-state illustrations.

    Line art in the interface's own palette rather than stock imagery: an empty
    board is a common, ordinary state, and the drawing should say "nothing here
    yet" without competing with the data that appears once there is some.
--}}

@php
    $ink = 'var(--color-ink-300)';
    $accent = 'var(--color-signal-400)';
    $wash = 'var(--color-ink-100)';
@endphp

<svg {{ $attributes->merge(['class' => $class]) }}
     viewBox="0 0 160 120" fill="none" aria-hidden="true" focusable="false">
    @switch($name)
        @case('parcel')
            <ellipse cx="80" cy="104" rx="46" ry="7" fill="{{ $wash }}"/>
            <path d="M52 46 80 32l28 14v32L80 92 52 78z" stroke="{{ $ink }}" stroke-width="2.5"
                  stroke-linejoin="round"/>
            <path d="M52 46 80 60l28-14M80 60v32" stroke="{{ $ink }}" stroke-width="2.5"
                  stroke-linejoin="round"/>
            <path d="M66 39l28 14" stroke="{{ $accent }}" stroke-width="2.5" stroke-linecap="round"/>
            @break

        @case('route')
            <ellipse cx="80" cy="104" rx="52" ry="7" fill="{{ $wash }}"/>
            <path d="M36 84c14-6 6-22 20-28s30 4 38-8" stroke="{{ $ink }}" stroke-width="2.5"
                  stroke-linecap="round" stroke-dasharray="6 8"/>
            <circle cx="36" cy="84" r="6" stroke="{{ $ink }}" stroke-width="2.5"/>
            <path d="M104 26a12 12 0 0 1 12 12c0 8-12 20-12 20S92 46 92 38a12 12 0 0 1 12-12Z"
                  stroke="{{ $accent }}" stroke-width="2.5" stroke-linejoin="round"/>
            <circle cx="104" cy="38" r="4" fill="{{ $accent }}"/>
            @break

        @case('riders')
            <ellipse cx="80" cy="104" rx="48" ry="7" fill="{{ $wash }}"/>
            <circle cx="46" cy="80" r="14" stroke="{{ $ink }}" stroke-width="2.5"/>
            <circle cx="114" cy="80" r="14" stroke="{{ $ink }}" stroke-width="2.5"/>
            <path d="M46 80l18-34h22l14 34" stroke="{{ $ink }}" stroke-width="2.5"
                  stroke-linejoin="round"/>
            <path d="M86 46h16l4 12" stroke="{{ $accent }}" stroke-width="2.5" stroke-linecap="round"
                  stroke-linejoin="round"/>
            @break

        @case('offers')
            <ellipse cx="80" cy="104" rx="46" ry="7" fill="{{ $wash }}"/>
            <rect x="40" y="34" width="80" height="54" rx="6" stroke="{{ $ink }}" stroke-width="2.5"/>
            <path d="m40 42 40 26 40-26" stroke="{{ $ink }}" stroke-width="2.5" stroke-linejoin="round"/>
            <circle cx="118" cy="34" r="9" fill="{{ $accent }}"/>
            @break

        @case('money')
            <ellipse cx="80" cy="104" rx="44" ry="7" fill="{{ $wash }}"/>
            <rect x="34" y="42" width="92" height="52" rx="6" stroke="{{ $ink }}" stroke-width="2.5"/>
            <circle cx="80" cy="68" r="13" stroke="{{ $accent }}" stroke-width="2.5"/>
            <path d="M48 68h.01M112 68h.01" stroke="{{ $ink }}" stroke-width="3" stroke-linecap="round"/>
            <path d="M48 32h68" stroke="{{ $ink }}" stroke-width="2.5" stroke-linecap="round" opacity=".5"/>
            @break

        @case('search')
            <ellipse cx="80" cy="104" rx="42" ry="7" fill="{{ $wash }}"/>
            <circle cx="72" cy="56" r="26" stroke="{{ $ink }}" stroke-width="2.5"/>
            <path d="m92 76 20 20" stroke="{{ $accent }}" stroke-width="3" stroke-linecap="round"/>
            @break

        @default
            <ellipse cx="80" cy="104" rx="46" ry="7" fill="{{ $wash }}"/>
            <rect x="44" y="36" width="72" height="56" rx="6" stroke="{{ $ink }}" stroke-width="2.5"/>
            <path d="M58 56h44M58 70h28" stroke="{{ $ink }}" stroke-width="2.5" stroke-linecap="round"/>
    @endswitch
</svg>
