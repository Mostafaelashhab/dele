@props([
    'id',
    'markers' => [],
    'zones' => [],
    // Zones rendered as pins that pair with a companion list, rather than
    // as permanently drawn catchment circles.
    'zonePins' => [],
    'route' => [],
    'height' => 360,
    // Narrow-viewport height; falls back to `height` when not given.
    'mobileHeight' => null,
    'zoom' => 13,
    'maxZoom' => 16,
    'fit' => true,
    'pickable' => false,
    'scrollZoom' => false,
    // muted (default) · dark · schematic — see the map stylesheet.
    'style' => 'muted',
])

@php
    $config = [
        'markers' => $markers,
        'zones' => $zones,
        'zonePins' => $zonePins,
        'route' => $route,
        'zoom' => $zoom,
        'maxZoom' => $maxZoom,
        'fit' => $fit,
        'pickable' => $pickable,
        'scrollWheelZoom' => $scrollZoom,
        'style' => $style,
    ];
@endphp

{{--
    Leaflet owns the DOM inside this element, so it is fenced off from
    Livewire's morphing with wire:ignore — otherwise a poll would tear out the
    tile layer on every refresh. Fresh data arrives instead as a window event
    carrying this map's id, which lets a live board move its markers without
    the map itself being rebuilt.
--}}
<div
    wire:ignore
    x-data="mapComponent()"
    x-init="initMap(@js($config))"
    @map-refresh.window="$event.detail?.id === @js($id) && render($event.detail.config)"
    @map-highlight.window="$event.detail?.id === @js($id) && highlight($event.detail.zone)"
    {{ $attributes->merge(['class' => 'map-surface']) }}
    style="--map-height: {{ $height }}px; --map-height-narrow: {{ $mobileHeight ?? $height }}px"
    role="application"
    aria-label="{{ __('app.dashboard.live_map') }}"
>
    <div x-ref="canvas" class="h-full w-full"></div>
</div>
