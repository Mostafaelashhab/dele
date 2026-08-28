@props([
    /** Renders the tile behind the mark. Off when it already sits on one. */
    'tile' => true,
    'wordmark' => false,
    'size' => 'md',
])

@php
    /**
     * The mark, in one place.
     *
     * The browser tab carried a solid drawn truck while every page header
     * carried a thin line-art one borrowed from the generic icon set — two
     * different logos for the same product, on the same screen. This is the
     * one the raster icons are generated from, so the tab, the home screen,
     * the share card and the header now agree.
     *
     * Solid geometry rather than strokes on purpose: the mark has to survive
     * being drawn at 16px, and line art turns to mud at that size.
     */
    $sizes = [
        'sm' => ['tile' => 'size-7 rounded-md', 'text' => 'text-sm'],
        'md' => ['tile' => 'size-8 rounded-lg', 'text' => 'text-base'],
        'lg' => ['tile' => 'size-11 rounded-xl', 'text' => 'text-lg'],
    ];

    $s = $sizes[$size] ?? $sizes['md'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <span @class([
        'flex shrink-0 items-center justify-center text-white',
        $s['tile'],
        'bg-ember-500' => $tile,
    ])>
        <svg viewBox="0 0 32 32" fill="none" class="size-[64%]" role="img"
             aria-label="{{ __('app.name') }}">
            <g fill="currentColor">
                <path d="M3 9h14v12H3z"/>
                <path d="M17 13h6v8h-6z"/>
                <path d="M23 16h5v5h-5z"/>
            </g>
            {{-- The wheels are punched through rather than outlined, so they
                 still read as wheels at 16px.

                 Punching needs to know what is behind the mark, which is only
                 knowable when this component drew it. Off a tile the hubs are
                 cut with a mask instead, so the mark works on any background
                 rather than silently stamping ember dots onto it. --}}
            @if ($tile)
                <g>
                    <circle cx="8.5" cy="22.5" r="3" fill="currentColor"/>
                    <circle cx="8.5" cy="22.5" r="1.3" fill="var(--color-ember-500)"/>
                    <circle cx="22.5" cy="22.5" r="3" fill="currentColor"/>
                    <circle cx="22.5" cy="22.5" r="1.3" fill="var(--color-ember-500)"/>
                </g>
            @else
                @php $mask = 'logo-wheels-'.uniqid(); @endphp

                <mask id="{{ $mask }}">
                    <rect width="32" height="32" fill="black"/>
                    <circle cx="8.5" cy="22.5" r="3" fill="white"/>
                    <circle cx="22.5" cy="22.5" r="3" fill="white"/>
                    <circle cx="8.5" cy="22.5" r="1.3" fill="black"/>
                    <circle cx="22.5" cy="22.5" r="1.3" fill="black"/>
                </mask>

                <rect width="32" height="32" fill="currentColor" mask="url(#{{ $mask }})"/>
            @endif
        </svg>
    </span>

    @if ($wordmark)
        <span class="{{ $s['text'] }} font-bold tracking-tight">{{ __('app.name') }}</span>
    @endif
</span>
