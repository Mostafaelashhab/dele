@props([
    'title' => null,
    'subtitle' => null,
    'flush' => false,
    'icon' => null,
])

{{--
    A titled panel, on the product's one ground.

    Matches the marketing site's `.panel`: a hairline border over a barely
    lifted surface. Signing in should not feel like arriving somewhere else.
--}}
<section {{ $attributes->merge(['class' => 'rounded-card border border-white/10 bg-white/[0.03]']) }}>
    @if ($title || isset($actions))
        <header class="flex items-center justify-between gap-4 border-b border-white/10 px-4 py-3.5">
            <div class="flex min-w-0 items-center gap-2.5">
                @if ($icon)
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-lg
                                 bg-white/5 text-ink-400">
                        <x-ui.icon :name="$icon" class="size-4" />
                    </span>
                @endif

                <div class="min-w-0">
                    @if ($title)
                        <h2 class="truncate text-base font-bold tracking-tight text-white">
                            {{ $title }}
                        </h2>
                    @endif
                    @if ($subtitle)
                        <p class="mt-0.5 truncate text-xs text-ink-400">{{ $subtitle }}</p>
                    @endif
                </div>
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
