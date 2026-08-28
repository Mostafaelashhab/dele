@props(['items' => []])

@php
    /**
     * Secondary figures, as one strip instead of a second row of cards.
     *
     * A dashboard that presents eight numbers in eight identical boxes has no
     * hierarchy: everything is equally loud, so nothing is. The handful an
     * operator acts on stay as cards; the ones they merely refer to live here,
     * where they are still legible but stop competing.
     *
     * @var array<int, array{label: string, value: string, icon?: string, tone?: string}> $items
     */
    $tones = [
        'green' => 'text-emerald-300',
        'amber' => 'text-warn-300',
        'red' => 'text-red-300',
        'blue' => 'text-signal-300',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-card border border-white/10 bg-white/[0.03]']) }}>
    <dl class="grid divide-white/10 sm:grid-cols-2 sm:divide-x lg:grid-cols-4 rtl:sm:divide-x-reverse">
        @foreach ($items as $item)
            <div @class([
                'flex items-center gap-3 px-4 py-3.5',
                'border-t border-white/10 sm:border-t-0' => ! $loop->first,
                'sm:border-t sm:border-white/10 lg:border-t-0' => $loop->index >= 2,
            ])>
                @if ($item['icon'] ?? null)
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg
                                 bg-white/5 {{ $tones[$item['tone'] ?? ''] ?? 'text-ink-400' }}">
                        <x-ui.icon :name="$item['icon']" class="size-4" />
                    </span>
                @endif

                <div class="min-w-0">
                    <dt class="truncate text-2xs font-semibold uppercase tracking-wider text-ink-400">
                        {{ $item['label'] }}
                    </dt>
                    <dd class="tnum mt-0.5 truncate text-lg font-bold leading-none text-white">
                        {{ $item['value'] }}
                    </dd>
                </div>
            </div>
        @endforeach
    </dl>
</div>
