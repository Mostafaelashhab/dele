@php
    $offer = $this->offer;
    $delivery = $offer->delivery;
    $order = $delivery->order;
    $breakdown = $offer->score_breakdown ?? [];
    $scores = $breakdown['scores'] ?? [];
    $weights = $breakdown['weights'] ?? [];
@endphp

<div x-data="{ toast: null, tone: 'neutral' }"
     @toast.window="toast = $event.detail.message; tone = $event.detail.tone; setTimeout(() => toast = null, 4000)">

    <x-ui.page-header :title="$order->number" :subtitle="$delivery->business->displayName()">
        <x-slot:actions>
            <x-ui.badge :tone="$offer->status->value === 'pending' ? 'amber' : 'slate'" dot>
                {{ $offer->status->label() }}
            </x-ui.badge>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.card :title="__('delivery.labels.pickup')">
                    <p class="text-sm font-semibold text-ink-900">{{ $order->pickupSnapshot()->contactName }}</p>
                    <p class="mt-1 text-sm text-ink-600">{{ $order->pickupSnapshot()->fullAddress() }}</p>
                </x-ui.card>
                <x-ui.card :title="__('delivery.labels.dropoff')">
                    <p class="text-sm font-semibold text-ink-900">{{ $order->dropoffSnapshot()->area ?? '—' }}</p>
                    <p class="mt-1 text-sm text-ink-600">{{ $order->dropoffSnapshot()->addressLine }}</p>
                </x-ui.card>
            </div>

            @if ($scores !== [])
                <x-ui.card :title="__('offer.why_you')" :subtitle="__('offer.why_you_hint')">
                    <ul class="space-y-3">
                        @foreach ($scores as $key => $value)
                            @continue($key === 'preferred_bonus')
                            <li>
                                <div class="mb-1 flex items-center justify-between text-xs">
                                    <span class="min-w-0 pe-2 font-medium text-ink-700">
                                        {{ __('offer.factor.'.$key) }}
                                    </span>
                                    <span class="tnum shrink-0 text-ink-500">
                                        {{ number_format($value * 100, 0) }}%
                                        @if (isset($weights[$key]))
                                            <span class="text-ink-300">
                                                × {{ number_format($weights[$key], 2) }}
                                            </span>
                                        @endif
                                    </span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-ink-100">
                                    <div class="h-full rounded-full bg-signal-500"
                                         style="width: {{ min(100, $value * 100) }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-4 border-t border-ink-100 pt-3 text-xs text-ink-500">
                        {{ __('offer.total_score') }}:
                        <span class="tnum font-semibold text-ink-800">
                            {{ number_format(($breakdown['total_score'] ?? 0) * 100, 1) }}%
                        </span>
                    </p>
                </x-ui.card>
            @endif
        </div>

        <div class="space-y-5">
            <x-ui.card>
                <p class="text-xs text-ink-500">{{ __('delivery.labels.payout') }}</p>
                <p class="tnum mt-1 text-3xl font-bold text-emerald-700">{{ $offer->payout()->format() }}</p>

                <dl class="mt-4 space-y-2 border-t border-ink-100 pt-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-500">{{ __('delivery.labels.distance') }}</dt>
                        <dd class="tnum">{{ number_format($delivery->distance_meters / 1000, 1) }} {{ __('app.common.km') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">{{ __('delivery.labels.eta') }}</dt>
                        <dd class="tnum">{{ $offer->quoted_eta_minutes }} {{ __('app.common.minutes') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">{{ __('delivery.priority.standard') }}</dt>
                        <dd>{{ $order->priority->label() }}</dd>
                    </div>
                </dl>

                @if ($offer->isAnswerable())
                    <div class="mt-4 space-y-2 border-t border-ink-100 pt-4">
                        <x-ui.button variant="success" size="lg" class="w-full" wire:click="accept">
                            {{ __('rider.app.accept') }}
                        </x-ui.button>
                        <input type="text" wire:model="rejectionReason" class="field-input"
                               placeholder="{{ __('app.common.reason') }}">
                        <x-ui.button variant="secondary" class="w-full" wire:click="reject">
                            {{ __('rider.app.reject') }}
                        </x-ui.button>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>

    <div x-show="toast" x-cloak
         class="fixed bottom-5 z-50 rounded-md px-4 py-3 text-sm text-white shadow-lg ltr:right-5 rtl:left-5"
         :class="tone === 'error' ? 'bg-red-700' : 'bg-ink-900'">
        <span x-text="toast"></span>
    </div>
</div>
