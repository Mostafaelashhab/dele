@props([
    'label',
    /** Ratio in 0..1 */
    'value' => 0.0,
    'display' => null,
    'goodAbove' => 0.9,
    'warnAbove' => 0.7,
    'hint' => null,
])

@php
    $ratio = max(0.0, min(1.0, (float) $value));

    // Severity, not identity — so this uses the reserved status tokens and
    // always ships with a label beside it, never colour alone.
    [$fill, $state, $icon] = match (true) {
        $ratio >= $goodAbove => ['var(--color-viz-good)', __('form.meter_healthy'), 'check'],
        $ratio >= $warnAbove => ['var(--color-viz-warning)', __('form.meter_watch'), 'clock'],
        default => ['var(--color-viz-critical)', __('form.meter_low'), 'alert'],
    };
@endphp

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <div class="flex items-baseline justify-between gap-2">
        <p class="truncate text-xs font-medium text-ink-500">{{ $label }}</p>
        <p class="shrink-0 text-lg font-semibold text-ink-900">
            {{ $display ?? number_format($ratio * 100, 1).'%' }}
        </p>
    </div>

    {{-- The unfilled track is a lighter step of the same family, so the state
         reads across the whole bar rather than only where it is filled. --}}
    <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full"
         style="background: var(--color-viz-track)"
         role="meter"
         aria-valuenow="{{ round($ratio * 100) }}"
         aria-valuemin="0"
         aria-valuemax="100"
         aria-label="{{ $label }}">
        <div class="h-full rounded-full transition-[width] duration-500"
             style="width: {{ $ratio * 100 }}%; background: {{ $fill }}"></div>
    </div>

    {{-- The status colour never carries the meaning alone: the icon and the
         state name ride with it. --}}
    <p class="mt-1.5 flex items-center gap-1 text-2xs text-ink-500">
        <x-ui.icon :name="$icon" class="size-3 shrink-0" />
        <span class="font-medium">{{ $state }}</span>
        @if ($hint)
            <span class="text-ink-400">· {{ $hint }}</span>
        @endif
    </p>
</div>
