@props([
    'title' => null,
    'subtitle' => null,
    /**
     * @var array<int, array{label: string, value: int|float, display: string, href?: string, meta?: string}>
     */
    'rows' => [],
    'limit' => 8,
    'emphasise' => null,
])

@php
    // Horizontal bars, one series, one colour. Colouring each bar by its own
    // size would double-encode length as hue and burn the only free channel on
    // information the chart already shows.
    $visible = collect($rows)->sortByDesc('value')->take($limit)->values();
    $peak = max(1, (float) $visible->max('value'));
    $hidden = max(0, count($rows) - $visible->count());
@endphp

<figure {{ $attributes->merge(['class' => 'min-w-0']) }}>
    @if ($title)
        <figcaption class="mb-3">
            <h3 class="text-sm font-semibold text-ink-900">{{ $title }}</h3>
            @if ($subtitle)
                <p class="mt-0.5 text-xs text-ink-500">{{ $subtitle }}</p>
            @endif
        </figcaption>
    @endif

    @if ($visible->isEmpty())
        <p class="py-8 text-center text-xs text-ink-400">{{ __('app.common.empty') }}</p>
    @else
        <ul class="space-y-2.5">
            @foreach ($visible as $row)
                @php
                    $share = ($row['value'] / $peak) * 100;
                    // Emphasis: one row in the accent, the rest recessive, when
                    // the story is about a single entity rather than the set.
                    $isMuted = $emphasise !== null && ($row['label'] ?? null) !== $emphasise;
                @endphp
                <li>
                    <div class="mb-1 flex items-baseline justify-between gap-3">
                        <span class="min-w-0 truncate text-xs font-medium text-ink-800">
                            @isset($row['href'])
                                <a href="{{ $row['href'] }}" wire:navigate class="hover:text-signal-700 hover:underline">
                                    {{ $row['label'] }}
                                </a>
                            @else
                                {{ $row['label'] }}
                            @endisset
                            @isset($row['meta'])
                                <span class="text-ink-400">· {{ $row['meta'] }}</span>
                            @endisset
                        </span>
                        {{-- The value rides the bar end, in a text token — never
                             in the series colour. --}}
                        <span class="tnum shrink-0 text-xs font-semibold text-ink-900">{{ $row['display'] }}</span>
                    </div>

                    <div class="h-2 w-full overflow-hidden rounded-sm"
                         style="background: var(--color-viz-track)">
                        <div class="h-full rounded-e-sm"
                             style="width: {{ max($share, $row['value'] > 0 ? 2 : 0) }}%;
                                    background: {{ $isMuted ? 'var(--color-ink-300)' : 'var(--color-viz-series-1)' }}"></div>
                    </div>
                </li>
            @endforeach
        </ul>

        @if ($hidden > 0)
            {{-- Never truncate silently: a chart that hides rows must say so. --}}
            <p class="tnum mt-3 text-2xs text-ink-400">
                {{ __('form.chart_more_rows', ['count' => $hidden]) }}
            </p>
        @endif
    @endif
</figure>
