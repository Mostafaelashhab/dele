@props([
    'tone' => 'neutral',
    'dot' => false,
])

@php
    $tones = [
        'neutral' => 'bg-ink-100 text-ink-700 ring-ink-200',
        'slate'   => 'bg-ink-100 text-ink-600 ring-ink-200',
        'green'   => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'amber'   => 'bg-amber-50 text-amber-900 ring-amber-200',
        'blue'    => 'bg-signal-50 text-signal-800 ring-signal-200',
        'indigo'  => 'bg-indigo-50 text-indigo-800 ring-indigo-200',
        'red'     => 'bg-red-50 text-red-800 ring-red-200',
    ];

    $dots = [
        'neutral' => 'bg-ink-400',
        'slate'   => 'bg-ink-400',
        'green'   => 'bg-emerald-500',
        'amber'   => 'bg-amber-500',
        'blue'    => 'bg-signal-500',
        'indigo'  => 'bg-indigo-500',
        'red'     => 'bg-red-500',
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
