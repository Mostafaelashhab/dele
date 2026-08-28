@props([
    'label',
    'value',
    'hint' => null,
    'icon' => null,
    'tone' => 'neutral',
    'href' => null,
])

@php
    $tones = [
        'neutral' => 'text-ink-900',
        'green'   => 'text-emerald-700',
        'amber'   => 'text-amber-700',
        'red'     => 'text-red-700',
        'blue'    => 'text-signal-700',
    ];

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->merge([
        'class' => 'block rounded-card border border-ink-200 bg-white p-4 shadow-xs'
            .($href ? ' transition hover:border-ink-300 hover:shadow-sm' : ''),
    ]) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-xs font-medium text-ink-500">{{ $label }}</p>
        @if ($icon)
            <x-ui.icon :name="$icon" class="size-4 shrink-0 text-ink-300" />
        @endif
    </div>
    <p class="tnum mt-2 text-2xl font-semibold tracking-tight {{ $tones[$tone] ?? $tones['neutral'] }}">
        {{ $value }}
    </p>
    @if ($hint)
        <p class="mt-1 text-xs text-ink-500">{{ $hint }}</p>
    @endif
</{{ $tag }}>
