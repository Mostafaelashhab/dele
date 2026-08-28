@props([
    'title' => null,
    'subtitle' => null,
    'flush' => false,
])

<section {{ $attributes->merge(['class' => 'rounded-card border border-ink-200 bg-white shadow-xs']) }}>
    @if ($title || isset($actions))
        <header class="flex items-center justify-between gap-4 border-b border-ink-200 px-4 py-3">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="truncate text-sm font-semibold text-ink-900">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 truncate text-xs text-ink-500">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div class="{{ $flush ? '' : 'p-4' }}">
        {{ $slot }}
    </div>
</section>
