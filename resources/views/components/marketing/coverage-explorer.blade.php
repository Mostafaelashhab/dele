@props(['zones'])

@php
    /**
     * Coverage, as a map and a price list that point at each other.
     *
     * The map is back, but the thing that made it unreadable before is not:
     * catchment circles are no longer all drawn at once. Only pins are
     * permanent, and the circle for a single zone appears while that zone is
     * the one being pointed at — from either side. Hovering a price chip lifts
     * its pin and frames its area; hovering a pin lifts its chip.
     *
     * That pairing is what makes the two halves worth showing together: the
     * list answers "what does it cost", the map answers "where is that", and
     * neither has to repeat the other.
     *
     * On a touchscreen there is no pointing, so the same pairing is driven by
     * tapping instead — and the hint says so. The hover handlers are not just
     * useless there, they are actively harmful: a tap fires a synthetic
     * mouseenter immediately before the click, which would toggle the zone
     * straight back off. So they are wired up only where hovering exists.
     */
    $tiers = collect($zones)
        ->groupBy(fn ($zone) => $zone->basePrice()->minor)
        ->sortKeys()
        ->values()
        ->map(fn ($group, int $index) => [
            'index' => $index,
            'price' => $group->first()->basePrice(),
            'zones' => $group->sortBy('estimated_minutes')->values(),
            'fastest' => $group->min('estimated_minutes'),
            'slowest' => $group->max('estimated_minutes'),
        ]);

    $tierCount = max($tiers->count(), 1);
@endphp

{{-- One Alpine scope wraps both halves, so the active zone is shared state
     rather than two components trying to stay in step. --}}
<div x-data="{
        active: null,
        hoverable: window.matchMedia('(hover: hover)').matches,
     }"
     @zone-hover="active = $event.detail.id"
     {{ $attributes->merge(['class' => 'grid gap-6 lg:grid-cols-[1fr_1.1fr] lg:items-start']) }}>

    {{-- Sticky at every width, for different reasons: beside the rows on a
         wide screen, above them on a phone — where the zones being tapped are
         below the fold and the highlight would otherwise happen off-screen.
         It sits under the h-16 header, and stays short enough to leave a
         couple of rows visible underneath it. --}}
    <div class="sticky top-16 z-10 -mx-3 bg-ink-50 px-3 pb-3 pt-2 sm:mx-0 sm:bg-transparent
                sm:px-0 sm:pb-0 sm:pt-0 lg:top-24">
        <x-ui.map
            id="landing-coverage"
            style="muted"
            :zone-pins="\App\Support\MapPayload::zonePins($zones)"
            :height="440"
            :mobile-height="260"
            :zoom="12"
            :fit="true" />

        <p class="mt-2.5 flex items-center gap-1.5 px-1 text-xs text-ink-400">
            <x-ui.icon name="pin" class="size-3.5 shrink-0" />
            <span class="no-hover:hidden">{{ __('marketing.zones.explore_hint') }}</span>
            <span class="can-hover:hidden">{{ __('marketing.zones.explore_hint_touch') }}</span>
        </p>
    </div>

    <div class="space-y-3">
        @foreach ($tiers as $tier)
            @php
                $isFurthest = $tier['index'] === $tierCount - 1;
                $accent = match (true) {
                    $tier['index'] === 0 => ['bar' => 'bg-signal-600', 'dot' => 'bg-signal-600'],
                    $isFurthest => ['bar' => 'bg-ember-500', 'dot' => 'bg-ember-500'],
                    default => ['bar' => 'bg-signal-400', 'dot' => 'bg-signal-400'],
                };
            @endphp

            <section class="relative overflow-hidden rounded-2xl border border-ink-200 bg-white">
                <span class="absolute inset-y-0 w-1 {{ $accent['bar'] }} ltr:left-0 rtl:right-0"
                      aria-hidden="true"></span>

                <div class="grid gap-4 p-5 ps-6 sm:grid-cols-[auto_1fr] sm:items-center sm:gap-6">
                    <div class="sm:w-36">
                        <div class="flex items-center gap-1" aria-hidden="true">
                            @for ($pip = 0; $pip < $tierCount; $pip++)
                                <span @class([
                                    'size-1.5 rounded-full',
                                    $accent['dot'] => $pip <= $tier['index'],
                                    'bg-ink-200' => $pip > $tier['index'],
                                ])></span>
                            @endfor
                        </div>

                        <p class="tnum mt-2 text-2xl font-bold tracking-tight text-ink-950">
                            {{ $tier['price']->format(false) }}
                            <span class="text-sm font-medium text-ink-400">
                                {{ config('platform.currency.symbol') }}
                            </span>
                        </p>

                        <p class="mt-1 text-xs text-ink-500">
                            {{ trans_choice('marketing.zones.tier_count', $tier['zones']->count(), [
                                'count' => $tier['zones']->count(),
                            ]) }}
                        </p>
                    </div>

                    <ul class="flex flex-wrap gap-2">
                        @foreach ($tier['zones'] as $zone)
                            <li>
                                {{-- A button, not a div: the pairing has to work
                                     from the keyboard as well as the pointer. --}}
                                <button type="button"
                                        @mouseenter="hoverable && (active = @js($zone->code))"
                                        @mouseleave="hoverable && (active = null)"
                                        @focus="hoverable && (active = @js($zone->code))"
                                        @blur="hoverable && (active = null)"
                                        @click="active = (! hoverable && active === @js($zone->code))
                                            ? null
                                            : @js($zone->code)"
                                        :class="active === @js($zone->code)
                                            ? '{{ $isFurthest ? 'bg-ember-500 ring-ember-500' : 'bg-signal-600 ring-signal-600' }} text-white ring-2'
                                            : '{{ $isFurthest ? 'bg-ember-50 text-ember-900 ring-ember-200' : 'bg-ink-50 text-ink-800 ring-ink-200' }} ring-1'"
                                        class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm
                                               font-medium ring-inset transition">
                                    <span class="truncate">{{ $zone->displayName() }}</span>
                                    <span class="tnum text-xs opacity-70">
                                        {{ $zone->estimated_minutes }}{{ __('marketing.zones.minutes_short') }}
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endforeach
    </div>

    {{-- The one place the highlight is pushed to Leaflet, which owns its own
         DOM behind wire:ignore and cannot be driven by class bindings. --}}
    <div x-effect="$dispatch('map-highlight', { id: 'landing-coverage', zone: active })" class="hidden"></div>
</div>
