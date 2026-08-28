@props([
    'tone' => 'neutral',
    'dot' => false,
])

@php
    /*
     * Tinted glass rather than pale fills: a badge sits on a near-black panel
     * now, where a 50-weight background is a bright blob.
     *
     * Indigo is gone. It was never in the theme — a stray Tailwind default
     * that had become a status tone in two places — and a palette with an
     * undocumented seventh hue in it is not a palette.
     */
    $tones = [
        'neutral' => 'bg-white/[0.06] text-ink-200 ring-white/15',
        'slate'   => 'bg-white/[0.06] text-ink-300 ring-white/15',
        'green'   => 'bg-emerald-500/12 text-emerald-300 ring-emerald-500/30',
        'amber'   => 'bg-warn-500/12 text-warn-300 ring-warn-500/30',
        'blue'    => 'bg-signal-500/12 text-signal-300 ring-signal-500/30',
        'brand'   => 'bg-ember-500/12 text-ember-400 ring-ember-500/30',
        'red'     => 'bg-red-500/12 text-red-300 ring-red-500/30',
    ];

    $dots = [
        'neutral' => 'bg-ink-400',
        'slate'   => 'bg-ink-400',
        'green'   => 'bg-emerald-400',
        'amber'   => 'bg-warn-400',
        'blue'    => 'bg-signal-400',
        'brand'   => 'bg-ember-400',
        'red'     => 'bg-red-400',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-semibold
                whitespace-nowrap ring-1 ring-inset '.($tones[$tone] ?? $tones['neutral']),
]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full {{ $dots[$tone] ?? $dots['neutral'] }}"></span>
    @endif
    {{ $slot }}
</span>
