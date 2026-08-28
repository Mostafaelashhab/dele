@props([
    /** @var array<int, int|float> */
    'values' => [],
    'width' => 96,
    'height' => 28,
    'tone' => 'series',
])

@php
    $points = collect($values)->values();
    $count = $points->count();
@endphp

@if ($count >= 2)
    @php
        $min = (float) $points->min();
        $max = (float) $points->max();
        $span = max($max - $min, 0.0001);

        // A 2px stroke and an 8px end dot both need room, so the plot is inset
        // by half the largest mark rather than drawn to the edge.
        $inset = 5;
        $plotWidth = $width - ($inset * 2);
        $plotHeight = $height - ($inset * 2);

        $coords = $points->map(function ($value, $index) use ($count, $min, $span, $inset, $plotWidth, $plotHeight) {
            $x = $inset + ($count > 1 ? ($index / ($count - 1)) * $plotWidth : $plotWidth / 2);
            $y = $inset + $plotHeight - ((((float) $value - $min) / $span) * $plotHeight);

            return round($x, 2).','.round($y, 2);
        });

        $last = $points->last();
        [$endX, $endY] = explode(',', $coords->last());

        $stroke = $tone === 'critical' ? 'var(--color-viz-critical)' : 'var(--color-viz-series-1)';
    @endphp

    {{-- Decorative by design: the stat tile's own value carries the number, so
         the sparkline is hidden from assistive technology rather than read out
         as a meaningless list of coordinates. --}}
    <svg viewBox="0 0 {{ $width }} {{ $height }}"
         width="{{ $width }}" height="{{ $height }}"
         fill="none" aria-hidden="true" focusable="false"
         {{ $attributes->merge(['class' => 'overflow-visible']) }}>
        <polyline points="{{ $coords->implode(' ') }}"
                  stroke="{{ $stroke }}" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round"
                  opacity="0.85" />

        {{-- The end dot carries a surface ring so it stays legible where it
             crosses the line or sits against a tile edge. --}}
        <circle cx="{{ $endX }}" cy="{{ $endY }}" r="4"
                fill="{{ $stroke }}" stroke="#ffffff" stroke-width="2" />
    </svg>
@endif
