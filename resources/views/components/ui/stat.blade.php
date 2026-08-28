@props([
    'label',
    'value',
    'hint' => null,
    'icon' => null,
    'tone' => 'neutral',
    'href' => null,
])

@php
    /**
     * A single number, on the same dark ground as the rest of the product.
     *
     * The portals used to be a light theme sitting behind a dark marketing
     * site, so signing in felt like arriving at a different product. They now
     * share one identity: near-black panels, ember for the brand, and colour
     * reserved for saying something.
     *
     * Tone colours the icon and a hairline along the top edge rather than the
     * number: an operator scanning a row needs the figures to read as one set
     * with the exceptional one flagged, not five differently coloured numerals.
     */
    $tones = [
        'neutral' => ['chip' => 'bg-white/5 text-ink-400', 'rail' => 'bg-white/10'],
        'green' => ['chip' => 'bg-emerald-500/10 text-emerald-300', 'rail' => 'bg-emerald-500'],
        'amber' => ['chip' => 'bg-warn-500/10 text-warn-300', 'rail' => 'bg-warn-500'],
        'red' => ['chip' => 'bg-red-500/10 text-red-300', 'rail' => 'bg-red-500'],
        'blue' => ['chip' => 'bg-signal-500/10 text-signal-300', 'rail' => 'bg-signal-500'],
        'brand' => ['chip' => 'bg-ember-500/10 text-ember-400', 'rail' => 'bg-ember-500'],
    ];

    $t = $tones[$tone] ?? $tones['neutral'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->merge([
        'class' => 'group relative block overflow-hidden rounded-card border border-white/10 bg-white/[0.03] p-4'
            .($href ? ' transition hover:border-white/20 hover:bg-white/[0.055]' : ''),
    ]) }}>

    {{-- Neutral cards get a rail too, so a row of stats sits on a continuous
         line instead of looking gap-toothed. --}}
    <span class="absolute inset-x-0 top-0 h-0.5 {{ $t['rail'] }}" aria-hidden="true"></span>

    <div class="flex items-start justify-between gap-3">
        <p class="min-w-0 text-2xs font-semibold uppercase tracking-wider text-ink-400">
            {{ $label }}
        </p>

        @if ($icon)
            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg {{ $t['chip'] }}">
                <x-ui.icon :name="$icon" class="size-4" />
            </span>
        @endif
    </div>

    <p class="tnum mt-3 text-3xl font-bold leading-none tracking-tight text-white">
        {{ $value }}
    </p>

    @if ($hint)
        <p class="mt-2 truncate text-xs text-ink-400">{{ $hint }}</p>
    @endif

    @if ($href)
        <span class="mt-2 flex items-center gap-1 text-xs font-semibold text-ember-400
                     opacity-0 transition group-hover:opacity-100">
            {{ __('app.common.view') }}
            <x-ui.icon name="chevron-end" class="size-3 rtl:rotate-180" />
        </span>
    @endif
</{{ $tag }}>
