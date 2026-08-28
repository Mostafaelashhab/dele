@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'type' => 'button',
])

@php
    $variants = [
        'primary'   => 'bg-signal-600 text-white hover:bg-signal-700 focus-visible:outline-signal-600 shadow-xs',
        'secondary' => 'bg-white text-ink-800 ring-1 ring-inset ring-ink-300 hover:bg-ink-50 shadow-xs',
        'ghost'     => 'text-ink-600 hover:bg-ink-100 hover:text-ink-900',
        'danger'    => 'bg-red-600 text-white hover:bg-red-700 shadow-xs',
        'success'   => 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs',
        'dark'      => 'bg-ink-900 text-white hover:bg-ink-800 shadow-xs',
    ];

    $sizes = [
        'sm' => 'px-2.5 py-1.5 text-xs gap-1.5',
        'md' => 'px-3.5 py-2 text-sm gap-2',
        'lg' => 'px-5 py-3 text-base gap-2.5',
        'xl' => 'w-full px-6 py-4 text-lg gap-3 touch-target',
    ];

    $classes = 'inline-flex items-center justify-center rounded-md font-semibold transition
                disabled:opacity-50 disabled:pointer-events-none '
        .($variants[$variant] ?? $variants['primary']).' '
        .($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" wire:navigate {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon) <x-ui.icon :name="$icon" class="size-4 shrink-0" /> @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon) <x-ui.icon :name="$icon" class="size-4 shrink-0" /> @endif
        {{ $slot }}
    </button>
@endif
