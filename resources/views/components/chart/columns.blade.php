@props([
    'id',
    'title' => null,
    'subtitle' => null,
    /**
     * @var array<int, array{label: string, full: string, values: array<string, int|float>}>
     */
    'rows' => [],
    /**
     * Ordered series definitions: key => ['label' => string, 'token' => css var].
     * Order is fixed — colour follows the series, never its rank.
     *
     * @var array<string, array{label: string, token: string}>
     */
    'series' => [],
    'height' => 190,
    'valueSuffix' => '',
])

@php
    // Geometry is computed here rather than in the browser so the chart is
    // complete in the first paint and needs no layout script.
    $totals = collect($rows)->map(fn (array $row) => array_sum($row['values']));
    $peak = (int) max(1, $totals->max());

    // Round the ceiling up to a clean number so the gridlines land on values a
    // reader can actually name.
    $magnitude = 10 ** max(0, strlen((string) $peak) - 2);
    $ceiling = (int) (ceil($peak / max(1, $magnitude)) * max(1, $magnitude));
    $ceiling = max($ceiling, 1);

    $ticks = collect([1, 0.5, 0])->map(fn (float $fraction) => [
        'fraction' => $fraction,
        'value' => (int) round($ceiling * $fraction),
    ]);

    $peakIndex = $totals->search($totals->max());
    $hasData = $totals->sum() > 0;
    $isMulti = count($series) > 1;
@endphp

<figure {{ $attributes->merge(['class' => 'min-w-0']) }}
        x-data="{ hovered: null, showTable: false }">

    <figcaption class="mb-3 flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            @if ($title)
                <h3 class="text-sm font-semibold text-ink-900">{{ $title }}</h3>
            @endif
            @if ($subtitle)
                <p class="mt-0.5 text-xs text-ink-500">{{ $subtitle }}</p>
            @endif
        </div>

        <div class="flex shrink-0 items-center gap-3">
            {{-- A legend is always present for two or more series: identity is
                 never left to colour matching alone. One series needs none —
                 the title already names it. --}}
            @if ($isMulti)
                <ul class="flex items-center gap-3">
                    @foreach ($series as $key => $definition)
                        <li class="flex items-center gap-1.5">
                            <span class="size-2.5 shrink-0 rounded-sm"
                                  style="background: {{ $definition['token'] }}"></span>
                            <span class="text-2xs font-medium text-ink-600">{{ $definition['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <button type="button" @click="showTable = !showTable"
                    class="rounded p-1 text-ink-400 transition hover:bg-ink-100 hover:text-ink-700"
                    :aria-pressed="showTable"
                    :title="@js(__('form.chart_table_view'))"
                    aria-label="{{ __('form.chart_table_view') }}">
                <x-ui.icon name="receipt" class="size-3.5" />
            </button>
        </div>
    </figcaption>

    @if (! $hasData)
        <div class="flex items-center justify-center rounded-md border border-dashed border-ink-200
                    text-xs text-ink-400"
             style="height: {{ $height }}px">
            {{ __('app.common.empty') }}
        </div>
    @else
        <div class="relative" x-show="! showTable">
            {{-- Plot band. The container is sized to include the axis strip so
                 the labels are never cropped into a nested scrollbar. --}}
            <div class="relative" style="height: {{ $height }}px">

                {{-- Gridlines: solid hairlines, one step off the surface. --}}
                @foreach ($ticks as $tick)
                    <div class="pointer-events-none absolute inset-x-0 flex items-center gap-2"
                         style="bottom: calc({{ $tick['fraction'] * 100 }}% - 1px)">
                        <span class="tnum w-8 shrink-0 text-end text-[10px] leading-none text-ink-400">
                            {{ number_format($tick['value']) }}
                        </span>
                        <span class="h-px flex-1" style="background: var(--color-viz-grid)"></span>
                    </div>
                @endforeach

                {{-- Columns --}}
                <div class="absolute inset-y-0 flex items-end gap-[3px]
                            ltr:left-10 ltr:right-0 rtl:right-10 rtl:left-0">
                    @foreach ($rows as $index => $row)
                        @php
                            $total = array_sum($row['values']);
                            $columnHeight = $ceiling > 0 ? ($total / $ceiling) * 100 : 0;
                            $stack = array_reverse(array_keys($series));
                        @endphp

                        <div class="group relative flex h-full min-w-0 flex-1 flex-col justify-end"
                             @mouseenter="hovered = {{ $index }}"
                             @mouseleave="hovered = null"
                             @focusin="hovered = {{ $index }}"
                             @focusout="hovered = null"
                             tabindex="0"
                             role="img"
                             aria-label="{{ $row['full'] }}: {{ number_format($total) }}{{ $valueSuffix }}">

                            {{-- The peak carries a direct label; every other
                                 value is reachable from the axis, the hover
                                 layer and the table. Labelling all of them
                                 would be noise. --}}
                            @if ($index === $peakIndex && $total > 0)
                                <span class="tnum absolute inset-x-0 -top-0.5 text-center text-[10px]
                                             font-semibold text-ink-600"
                                      style="bottom: calc({{ min(100, $columnHeight) }}% + 4px)">
                                    {{ number_format($total) }}
                                </span>
                            @endif

                            <div class="mx-auto flex w-full max-w-[24px] flex-col justify-end
                                        transition-opacity"
                                 :class="hovered !== null && hovered !== {{ $index }} ? 'opacity-45' : ''"
                                 style="height: {{ max($columnHeight, $total > 0 ? 1.5 : 0) }}%">
                                @foreach ($stack as $position => $key)
                                    @php
                                        $value = $row['values'][$key] ?? 0;
                                        $share = $total > 0 ? ($value / $total) * 100 : 0;
                                        $isTop = $position === 0;
                                    @endphp
                                    @continue($value <= 0)
                                    <div style="height: {{ $share }}%;
                                                background: {{ $series[$key]['token'] }};
                                                {{ $isTop ? 'border-radius: 4px 4px 0 0;' : '' }}
                                                {{ ! $isTop ? 'margin-top: 2px;' : '' }}"></div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Baseline --}}
                <div class="pointer-events-none absolute bottom-0 h-px ltr:left-10 ltr:right-0 rtl:right-10 rtl:left-0"
                     style="background: var(--color-viz-axis)"></div>
            </div>

            {{-- Axis strip, inside the flow so it always has room. --}}
            <div class="mt-1.5 flex gap-[3px] ltr:ms-10 rtl:me-10">
                @foreach ($rows as $index => $row)
                    <span class="tnum min-w-0 flex-1 truncate text-center text-[10px] leading-none
                                 {{ $index === $peakIndex ? 'font-semibold text-ink-600' : 'text-ink-400' }}">
                        {{ $index % max(1, (int) ceil(count($rows) / 8)) === 0 ? $row['label'] : '' }}
                    </span>
                @endforeach
            </div>

            {{-- Hover readout. Enhances; it never gates a value, because the
                 axis, the peak label and the table all carry them too. --}}
            <template x-if="hovered !== null">
                <div class="pointer-events-none absolute -top-1 z-10 rounded-md border border-ink-200
                            bg-white px-2.5 py-1.5 text-2xs shadow-lg ltr:right-0 rtl:left-0">
                    @foreach ($rows as $index => $row)
                        <template x-if="hovered === {{ $index }}">
                            <div>
                                <p class="font-semibold text-ink-900">{{ $row['full'] }}</p>
                                <ul class="mt-1 space-y-0.5">
                                    @foreach ($series as $key => $definition)
                                        <li class="flex items-center gap-2">
                                            <span class="size-2 shrink-0 rounded-sm"
                                                  style="background: {{ $definition['token'] }}"></span>
                                            <span class="text-ink-500">{{ $definition['label'] }}</span>
                                            <span class="tnum ms-auto font-medium text-ink-900">
                                                {{ number_format($row['values'][$key] ?? 0) }}{{ $valueSuffix }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </template>
                    @endforeach
                </div>
            </template>
        </div>

        {{-- The table twin: the same numbers, WCAG-clean, no colour needed. --}}
        <div x-show="showTable" x-cloak class="overflow-x-auto" style="max-height: {{ $height + 40 }}px">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('app.common.date') }}</th>
                        @foreach ($series as $definition)
                            <th class="text-end">{{ $definition['label'] }}</th>
                        @endforeach
                        <th class="text-end">{{ __('app.common.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="whitespace-nowrap">{{ $row['full'] }}</td>
                            @foreach ($series as $key => $definition)
                                <td class="tnum text-end">{{ number_format($row['values'][$key] ?? 0) }}</td>
                            @endforeach
                            <td class="tnum text-end font-semibold">{{ number_format(array_sum($row['values'])) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</figure>
