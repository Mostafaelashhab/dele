<div class="flex min-h-dvh flex-col bg-ink-100">
    <header class="safe-top sticky top-0 z-10 flex items-center gap-3 border-b border-ink-200 bg-white px-4 py-3">
        <a href="{{ route('rider.home') }}" wire:navigate class="-ms-1 p-1 text-ink-500">
            <x-ui.icon name="chevron-end" class="size-5 rotate-180 rtl:rotate-0" />
        </a>
        <h1 class="text-sm font-bold text-ink-900">{{ __('app.nav.earnings') }}</h1>
    </header>

    <main class="flex-1 space-y-4 p-4">
        <div class="flex gap-1.5 rounded-md bg-ink-200/60 p-1">
            @foreach ([
                'today' => __('app.common.today'),
                'week' => __('app.common.this_week'),
                'month' => __('app.common.this_month'),
            ] as $key => $label)
                <button type="button" wire:click="$set('range', '{{ $key }}')"
                        @class([
                            'flex-1 rounded py-1.5 text-xs font-semibold transition',
                            'bg-white text-ink-900 shadow-xs' => $range === $key,
                            'text-ink-600' => $range !== $key,
                        ])>{{ $label }}</button>
            @endforeach
        </div>

        <div class="rounded-card bg-ink-900 px-5 py-6 text-white">
            <p class="text-xs text-ink-400">{{ __('app.nav.earnings') }}</p>
            <p class="tnum mt-1 text-3xl font-bold">{{ $this->summary['earned']->format() }}</p>
            <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-white/15 pt-4">
                <div>
                    <dt class="text-2xs text-ink-400">{{ __('app.nav.deliveries') }}</dt>
                    <dd class="tnum mt-0.5 text-lg font-semibold">{{ $this->summary['deliveries'] }}</dd>
                </div>
                <div>
                    <dt class="text-2xs text-ink-400">{{ __('delivery.labels.distance') }}</dt>
                    <dd class="tnum mt-0.5 text-lg font-semibold">
                        {{ $this->summary['distance_km'] }} {{ __('app.common.km') }}
                    </dd>
                </div>
            </dl>
        </div>

        <x-ui.card :title="__('finance.category.rider_payout')"
                   :subtitle="__('finance.settlement.open')">
            <p class="tnum text-2xl font-semibold text-ink-900">
                {{ $this->summary['unsettled']->format() }}
            </p>
        </x-ui.card>

        <x-ui.card flush>
            <ul class="divide-y divide-ink-100">
                @forelse ($this->daily as $day)
                    <li class="flex items-center justify-between px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-ink-900">
                                {{ \Illuminate\Support\Carbon::parse($day['date'])->translatedFormat('D d M') }}
                            </p>
                            <p class="tnum text-xs text-ink-500">
                                {{ $day['count'] }} {{ __('app.nav.deliveries') }}
                            </p>
                        </div>
                        <span class="tnum text-sm font-semibold text-ink-900">
                            {{ $day['total']->format(false) }}
                        </span>
                    </li>
                @empty
                    <li><x-ui.empty icon="money" :title="__('app.common.empty')" /></li>
                @endforelse
            </ul>
        </x-ui.card>
    </main>
</div>
