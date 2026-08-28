@props([
    'title',
    'subtitle' => null,
])

{{-- The top of a portal screen, sized so the page announces itself. --}}
<div {{ $attributes->merge(['class' => 'mb-5 flex flex-wrap items-end justify-between gap-4 border-b border-white/10 pb-4']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-bold tracking-tight text-white">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1.5 flex items-center gap-2 text-sm text-ink-400">
                <span class="size-1.5 shrink-0 rounded-full bg-ember-500" aria-hidden="true"></span>
                {{ $subtitle }}
            </p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
