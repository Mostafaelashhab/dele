@props([
    'index' => null,
    'eyebrow' => null,
    'title',
    'body' => null,
    'align' => 'start',
])

{{--
    The opening of a section: a numeral, a label, a heading, and a rule that
    carries a little of the accent so the divider is part of the design rather
    than a leftover border.

    Extracted because eight sections repeating this by hand is how the eyebrow
    colour and heading size drift apart over a few edits.
--}}
<div {{ $attributes->merge(['class' => $align === 'center' ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl']) }}>
    <div @class([
        'flex items-center gap-3',
        'justify-center' => $align === 'center',
    ])>
        @if ($index)
            <span class="section-index">{{ $index }}</span>
            <span class="h-px w-8 bg-white/15" aria-hidden="true"></span>
        @endif

        @if ($eyebrow)
            <p class="text-xs font-bold uppercase tracking-widest text-ember-400">{{ $eyebrow }}</p>
        @endif
    </div>

    <h2 class="mt-4 text-3xl font-bold leading-tight tracking-tight text-white lg:text-4xl">
        {{ $title }}
    </h2>

    <div class="rule-fade mt-5" aria-hidden="true"></div>

    @if ($body)
        <p class="mt-5 text-base leading-relaxed text-ink-400">{{ $body }}</p>
    @endif
</div>
