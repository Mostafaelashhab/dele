@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'mb-5 flex flex-wrap items-end justify-between gap-3']) }}>
    <div class="min-w-0">
        <h1 class="text-xl font-semibold tracking-tight text-ink-900">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-ink-500">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
