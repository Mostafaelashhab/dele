@php $rider = $this->rider(); @endphp

<div class="flex min-h-dvh flex-col bg-ink-100" wire:poll.15s
     x-data="{ toast: null }"
     @rider-error.window="toast = $event.detail.message; setTimeout(() => toast = null, 4000)"
     @rider-blocked.window="toast = $event.detail.message; setTimeout(() => toast = null, 4000)"
     @rider-online.window="window.__startReporter?.()">

    <header class="safe-top bg-ink-950 px-5 pb-5 pt-4 text-white">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate text-base font-semibold">{{ $rider->name }}</p>
                <p class="truncate text-xs text-ink-400">{{ $rider->deliveryCompany->displayName() }}</p>
            </div>
            <a href="{{ route('rider.earnings') }}" wire:navigate
               class="rounded-md bg-white/10 px-2.5 py-1.5 text-xs font-semibold text-white">
                {{ __('app.nav.earnings') }}
            </a>
        </div>

        {{-- The shift toggle is the largest control on the screen because it
             is the one a rider reaches for while holding a helmet. --}}
        <button type="button" wire:click="toggleAvailability"
                @class([
                    'touch-target mt-5 flex w-full items-center justify-center gap-2.5 rounded-lg
                     text-base font-bold transition',
                    'bg-emerald-500 text-white' => $rider->isOnline(),
                    'bg-white/10 text-ink-300 ring-1 ring-inset ring-white/20' => ! $rider->isOnline(),
                ])>
            <x-ui.icon name="power" class="size-5" />
            {{ $rider->isOnline() ? __('rider.app.online') : __('rider.app.offline') }}
        </button>

        <dl class="mt-5 grid grid-cols-2 gap-3">
            <div class="rounded-md bg-white/5 px-3 py-2.5">
                <dt class="text-2xs text-ink-400">{{ __('rider.app.deliveries_today') }}</dt>
                <dd class="tnum mt-0.5 text-xl font-semibold">{{ $this->today['deliveries'] }}</dd>
            </div>
            <div class="rounded-md bg-white/5 px-3 py-2.5">
                <dt class="text-2xs text-ink-400">{{ __('rider.app.earnings_today') }}</dt>
                <dd class="tnum mt-0.5 text-xl font-semibold">{{ $this->today['earnings']->format(false) }}</dd>
            </div>
        </dl>

        {{-- The rider's own standing. It belongs on this screen because it is
             not decoration: acceptance and rating feed the company's ranking,
             which decides how many of these offers arrive at all. --}}
        <dl class="mt-3 grid grid-cols-3 gap-3 border-t border-white/10 pt-4">
            <div>
                <dt class="text-2xs text-ink-400">{{ __('rider.app.your_rating') }}</dt>
                <dd class="tnum mt-0.5 flex items-center gap-1 text-sm font-bold">
                    <x-ui.icon name="star" class="size-3.5 text-amber-400" />
                    {{ $rider->rating_bps > 0 ? number_format($rider->rating(), 1) : '—' }}
                </dd>
            </div>
            <div>
                <dt class="text-2xs text-ink-400">{{ __('rider.app.your_acceptance') }}</dt>
                <dd class="tnum mt-0.5 text-sm font-bold">
                    {{ $rider->acceptance_rate_bps > 0
                        ? number_format($rider->acceptanceRate() * 100, 0).'%'
                        : '—' }}
                </dd>
            </div>
            <div>
                <dt class="text-2xs text-ink-400">{{ __('rider.app.lifetime_deliveries') }}</dt>
                <dd class="tnum mt-0.5 text-sm font-bold">
                    {{ number_format($rider->completed_deliveries_count) }}
                </dd>
            </div>
        </dl>
    </header>

    <main class="flex-1 space-y-4 p-4">

        @if ($this->pendingAssignments->isNotEmpty())
            <section>
                <h2 class="mb-2 px-1 text-xs font-semibold uppercase tracking-wide text-ink-500">
                    {{ __('rider.app.available_deliveries') }}
                    <span class="tnum">({{ $this->pendingAssignments->count() }})</span>
                </h2>

                <div class="space-y-3">
                    @foreach ($this->pendingAssignments as $assignment)
                        @php $order = $assignment->delivery->order; @endphp
                        <a href="{{ route('rider.deliveries.show', $assignment->delivery->public_id) }}" wire:navigate
                           class="block rounded-card border-2 border-signal-500 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-start gap-2.5">
                                    {{-- The shop's mark. A rider who has been
                                         there before recognises it instantly,
                                         faster than reading the name. --}}
                                    <x-ui.avatar
                                        :src="$assignment->delivery->business->mediaUrl('logo_path')"
                                        :name="$assignment->delivery->business->displayName()"
                                        :icon="$assignment->delivery->business->hasMedia('logo_path')
                                            ? null
                                            : $assignment->delivery->business->categoryIcon()"
                                        size="md" square />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-ink-900">
                                            {{ $assignment->delivery->business->displayName() }}
                                        </p>
                                        <p class="mt-0.5 truncate text-xs text-ink-500">
                                            {{ $order->pickupSnapshot()->area }}
                                            <span class="mx-1 text-ink-300">→</span>
                                            {{ $order->dropoffSnapshot()->area }}
                                        </p>
                                    </div>
                                </div>
                                <span class="tnum shrink-0 text-lg font-bold text-emerald-700">
                                    {{ $assignment->payout()->format(false) }}
                                </span>
                            </div>

                            <div class="mt-3 flex items-center justify-between border-t border-ink-100 pt-3">
                                <span class="tnum text-xs text-ink-500">
                                    {{ number_format($assignment->delivery->distance_meters / 1000, 1) }}
                                    {{ __('app.common.km') }}
                                </span>
                                @if ($assignment->expires_at)
                                    <span class="tnum inline-flex items-center gap-1 text-xs font-semibold text-amber-700">
                                        <x-ui.icon name="clock" class="size-3.5" />
                                        {{ $assignment->secondsRemaining() }}s
                                    </span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($this->activeDeliveries->isNotEmpty())
            <section>
                <h2 class="mb-2 px-1 text-xs font-semibold uppercase tracking-wide text-ink-500">
                    {{ __('rider.app.active_delivery') }}
                </h2>

                <div class="space-y-3">
                    @foreach ($this->activeDeliveries as $delivery)
                        <a href="{{ route('rider.deliveries.show', $delivery->public_id) }}" wire:navigate
                           class="block rounded-card border border-ink-200 bg-white p-4 shadow-xs">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-ink-900">
                                        {{ $delivery->order->number }}
                                    </p>
                                    <p class="mt-0.5 truncate text-xs text-ink-500">
                                        {{ $delivery->order->dropoffSnapshot()->area }}
                                    </p>
                                </div>
                                <x-ui.badge :tone="$delivery->status->tone()" dot>
                                    {{ $delivery->status->label() }}
                                </x-ui.badge>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($this->pendingAssignments->isEmpty() && $this->activeDeliveries->isEmpty())
            <x-ui.card>
                <x-ui.empty
                    icon="package"
                    :title="__('rider.app.no_deliveries')"
                    :description="$rider->isOnline() ? __('rider.app.no_deliveries_hint') : __('rider.app.offline_hint')" />
            </x-ui.card>
        @endif
    </main>

    <nav class="safe-bottom sticky bottom-0 flex border-t border-ink-200 bg-white">
        @foreach ([
            ['route' => 'rider.home', 'icon' => 'package', 'label' => __('app.nav.deliveries')],
            ['route' => 'rider.history', 'icon' => 'history', 'label' => __('app.nav.history')],
            ['route' => 'rider.earnings', 'icon' => 'money', 'label' => __('app.nav.earnings')],
        ] as $tab)
            <a href="{{ route($tab['route']) }}" wire:navigate
               @class([
                   'flex flex-1 flex-col items-center gap-1 py-2.5 text-2xs font-medium',
                   'text-signal-600' => request()->routeIs($tab['route']),
                   'text-ink-400' => ! request()->routeIs($tab['route']),
               ])>
                <x-ui.icon :name="$tab['icon']" class="size-5" />
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    <div x-show="toast" x-cloak x-transition
         class="fixed inset-x-4 bottom-24 z-50 rounded-md bg-ink-900 px-4 py-3 text-sm text-white shadow-lg">
        <span x-text="toast"></span>
    </div>
</div>
