@props([
    'icon' => 'package',
    'illustration' => null,
    'title' => null,
    'description' => null,
    'compact' => false,
])

@php
    // Most empty states name an icon; map the common ones onto a drawing so the
    // screen has something to look at rather than a grey dot.
    $drawing = $illustration ?? match ($icon) {
        'truck', 'navigation', 'map' => 'route',
        'users', 'user' => 'riders',
        'bell' => 'offers',
        'money', 'receipt', 'chart' => 'money',
        'search' => 'search',
        'history' => 'parcel',
        default => 'parcel',
    };
@endphp

<div {{ $attributes->merge([
    'class' => 'flex flex-col items-center justify-center px-6 text-center '.($compact ? 'py-8' : 'py-12'),
]) }}>
    <x-ui.illustration :name="$drawing" :class="$compact ? 'h-20' : 'h-28'" />

    <p class="mt-4 text-sm font-semibold text-ink-800">{{ $title ?? __('app.common.empty') }}</p>

    @if ($description)
        <p class="mt-1.5 max-w-sm text-xs leading-relaxed text-ink-500">{{ $description }}</p>
    @endif

    @if (trim($slot) !== '')
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
