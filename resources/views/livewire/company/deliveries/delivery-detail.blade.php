@php
    $delivery = $this->delivery;
    $order = $delivery->order;
    $pickup = $order->pickupSnapshot();
    $dropoff = $order->dropoffSnapshot();
    $canAssign = in_array($delivery->status, [
        \App\Enums\DeliveryStatus::Accepted,
        \App\Enums\DeliveryStatus::Assigned,
    ], true);
@endphp

<div wire:poll.10s
     x-data="{ toast: null, tone: 'neutral' }"
     @toast.window="toast = $event.detail.message; tone = $event.detail.tone; setTimeout(() => toast = null, 4000)">

    <x-ui.page-header :title="$order->number" :subtitle="$delivery->business->displayName()">
        <x-slot:actions>
            <x-ui.badge :tone="$delivery->status->tone()" dot>{{ $delivery->status->label() }}</x-ui.badge>
            @if ($delivery->isCancellable())
                <x-ui.button variant="secondary" size="sm" wire:click="$set('cancelling', true)">
                    {{ __('app.common.cancel') }}
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">

            @if ($this->hasMap())
                <x-ui.map
                    :id="\App\Livewire\Company\Deliveries\DeliveryDetail::MAP_ID"
                    :markers="$this->mapConfig['markers']"
                    :route="$this->mapConfig['route']"
                    :height="260" />
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.card :title="__('delivery.labels.pickup')">
                    <p class="text-sm font-semibold text-ink-900">{{ $pickup->contactName }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $pickup->fullAddress() }}</p>
                    <a href="tel:{{ $pickup->contactPhone }}"
                       class="tnum mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-signal-700">
                        <x-ui.icon name="phone" class="size-3.5" />
                        {{ $pickup->contactPhone }}
                    </a>
                </x-ui.card>

                <x-ui.card :title="__('delivery.labels.customer')">
                    <p class="text-sm font-semibold text-ink-900">{{ $dropoff->contactName }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $dropoff->fullAddress() }}</p>
                    <a href="tel:{{ $dropoff->contactPhone }}"
                       class="tnum mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-signal-700">
                        <x-ui.icon name="phone" class="size-3.5" />
                        {{ $dropoff->contactPhone }}
                    </a>
                </x-ui.card>
            </div>

            @if ($canAssign)
                <x-ui.card :title="__('app.nav.riders')"
                           :subtitle="__('delivery.event.RiderAssigned')" flush>
                    @if ($this->hasOpenAssignment())
                        <div class="border-b border-ink-100 bg-amber-50 px-4 py-2.5">
                            <p class="text-xs font-medium text-amber-900">
                                {{ __('delivery.assignment.offered') }}
                            </p>
                        </div>
                    @endif

                    @if ($this->availableRiders->isEmpty())
                        <x-ui.empty icon="users" :title="__('delivery.errors.rider_unavailable')" />
                    @else
                        <ul class="divide-y divide-ink-100">
                            @foreach ($this->availableRiders as $entry)
                                @php $rider = $entry['rider']; @endphp
                                <li class="flex items-center gap-3 px-4 py-3" wire:key="r-{{ $rider->id }}">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full
                                                 bg-ink-100 text-xs font-semibold text-ink-600">
                                        {{ mb_substr($rider->name, 0, 1) }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-ink-900">{{ $rider->name }}</p>
                                        <p class="text-xs text-ink-500">
                                            {{ $rider->vehicle_type->label() }}
                                            · {{ $rider->active_deliveries_count }}/{{ $rider->max_concurrent_deliveries }}
                                            @if ($entry['distance'] !== null)
                                                · <span class="tnum">{{ number_format($entry['distance'] / 1000, 1) }}
                                                    {{ __('app.common.km') }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <x-ui.button size="sm" wire:click="assign('{{ $rider->id }}')"
                                                 wire:loading.attr="disabled">
                                        {{ __('delivery.event.RiderAssigned') }}
                                    </x-ui.button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card>
            @endif

            <x-ui.card :title="__('delivery.labels.timeline')" flush>
                <ol class="divide-y divide-ink-100">
                    @foreach ($this->timeline as $event)
                        <li class="flex items-start gap-3 px-4 py-2.5">
                            <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-ink-300"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-ink-800">{{ $event->type->label() }}</p>
                                @if ($event->actor_label)
                                    <p class="text-2xs text-ink-500">{{ $event->actor_label }}</p>
                                @endif
                            </div>
                            <span class="tnum shrink-0 text-2xs text-ink-400">
                                {{ $event->occurred_at->translatedFormat('d M H:i') }}
                            </span>
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>
        </div>

        <div class="space-y-5">
            <x-ui.card :title="__('delivery.labels.price')">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-500">{{ __('delivery.labels.payout') }}</dt>
                        <dd class="tnum font-semibold text-emerald-700">
                            {{ $delivery->companyPayout()->format() }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">{{ __('finance.category.rider_payout') }}</dt>
                        <dd class="tnum text-ink-800">{{ $delivery->riderPayout()->format() }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-ink-100 pt-2">
                        <dt class="text-ink-500">{{ __('delivery.labels.distance') }}</dt>
                        <dd class="tnum text-ink-800">
                            {{ number_format($delivery->distance_meters / 1000, 1) }} {{ __('app.common.km') }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">{{ __('delivery.labels.eta') }}</dt>
                        <dd class="tnum text-ink-800">
                            {{ $delivery->estimated_minutes }} {{ __('app.common.minutes') }}
                        </dd>
                    </div>
                    @if ($order->payment_type->requiresCollection())
                        <div class="flex justify-between rounded-md bg-amber-50 px-2 py-1.5">
                            <dt class="font-medium text-amber-900">{{ __('order.payment.cod') }}</dt>
                            <dd class="tnum font-semibold text-amber-900">
                                {{ $order->cod_amount_minor->format() }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-ui.card>

            @if ($order->items->isNotEmpty())
                <x-ui.card :title="__('app.nav.orders')" flush>
                    <ul class="divide-y divide-ink-100">
                        @foreach ($order->items as $item)
                            <li class="flex justify-between px-4 py-2 text-sm">
                                <span class="text-ink-800">{{ $item->name }}</span>
                                <span class="tnum text-ink-500">×{{ $item->quantity }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif

            <x-ui.card :title="__('delivery.labels.tracking')">
                <div x-data="{ copied: false }" class="flex items-center gap-2">
                    <input type="text" readonly value="{{ $delivery->trackingUrl() }}"
                           class="field-input flex-1 text-xs" dir="ltr">
                    <x-ui.button variant="secondary" size="sm"
                                 x-on:click="copied = await window.copyToClipboard('{{ $delivery->trackingUrl() }}')">
                        <span x-text="copied ? @js(__('app.common.copied')) : @js(__('app.common.copy'))"></span>
                    </x-ui.button>
                </div>
            </x-ui.card>


            <x-delivery.proof :delivery="$delivery" />
        </div>
    </div>

    @if ($cancelling)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4"
             wire:click.self="$set('cancelling', false)">
            <div class="w-full max-w-sm rounded-card bg-white p-5 shadow-xl">
                <h2 class="text-sm font-semibold text-ink-900">{{ __('app.common.cancel') }}</h2>
                <form wire:submit="cancel" class="mt-4 space-y-3">
                    <x-ui.field :label="__('app.common.reason')" name="cancellationReason" required>
                        <input type="text" wire:model="cancellationReason" class="field-input" autofocus>
                    </x-ui.field>
                    <div class="flex gap-2">
                        <x-ui.button type="submit" variant="danger" class="flex-1">
                            {{ __('app.common.confirm') }}
                        </x-ui.button>
                        <x-ui.button variant="secondary" wire:click="$set('cancelling', false)">
                            {{ __('app.common.close') }}
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div x-show="toast" x-cloak x-transition
         class="fixed bottom-5 z-50 rounded-md px-4 py-3 text-sm text-white shadow-lg ltr:right-5 rtl:left-5"
         :class="tone === 'error' ? 'bg-red-700' : 'bg-ink-900'">
        <span x-text="toast"></span>
    </div>
</div>
