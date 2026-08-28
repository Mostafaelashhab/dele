@props([
    'src' => null,
    'name' => '',
    'icon' => null,
    'size' => 'md',
    'tone' => 'neutral',
    'square' => false,
])

@php
    $sizes = [
        'xs' => ['box' => 'size-6', 'text' => 'text-[10px]', 'icon' => 'size-3'],
        'sm' => ['box' => 'size-8', 'text' => 'text-xs', 'icon' => 'size-4'],
        'md' => ['box' => 'size-10', 'text' => 'text-sm', 'icon' => 'size-5'],
        'lg' => ['box' => 'size-14', 'text' => 'text-lg', 'icon' => 'size-6'],
        'xl' => ['box' => 'size-20', 'text' => 'text-2xl', 'icon' => 'size-8'],
    ];

    $tones = [
        'neutral' => 'bg-ink-100 text-ink-600 ring-ink-200',
        'signal' => 'bg-signal-50 text-signal-700 ring-signal-200',
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'dark' => 'bg-ink-900 text-white ring-ink-800',
    ];

    $spec = $sizes[$size] ?? $sizes['md'];

    // Initials are derived here so an avatar always renders something, even
    // before anyone has uploaded a picture. An empty grey circle reads as a
    // broken image; two letters read as a person.
    $initials = \Illuminate\Support\Str::of($name)
        ->trim()
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $part) => \Illuminate\Support\Str::substr($part, 0, 1))
        ->implode('');
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex shrink-0 items-center justify-center overflow-hidden ring-1 ring-inset '
        .$spec['box'].' '
        .($square ? 'rounded-md' : 'rounded-full').' '
        .($tones[$tone] ?? $tones['neutral']),
]) }}>
    @if (filled($src))
        <img src="{{ $src }}"
             alt="{{ $name }}"
             loading="lazy"
             decoding="async"
             class="size-full object-cover">
    @elseif ($icon)
        <x-ui.icon :name="$icon" :class="$spec['icon']" />
    @else
        <span class="{{ $spec['text'] }} font-semibold leading-none">{{ $initials }}</span>
    @endif
</span>
