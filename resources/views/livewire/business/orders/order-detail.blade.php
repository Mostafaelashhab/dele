@php
    $order = $this->order;
    $delivery = $order->currentDelivery;
    $pickup = $order->pickupSnapshot();
    $dropoff = $order->dropoffSnapshot();
@endphp

<div @if ($delivery && ! $delivery->status->isTerminal()) wire:poll.15s @endif
     x-data="{ toast: null, tone: 'neutral' }"
     @toast.window="toast = $event.detail.message; tone = $event.detail.tone; setTimeout(() => toast = null, 4000)">

    <x-ui.page-header :title="$order->number"
                      :subtitle="$order->reference ? __('app.common.order').' '.$order->reference : null">
        <x-slot:actions>
            @if ($delivery)
                <x-ui.badge :tone="$delivery->status->tone()" dot>{{ $delivery->status->label() }}</x-ui.badge>
            @endif
            @if ($delivery?->isCancellable())
                <x-ui.button variant="secondary" size="sm" wire:click="$set('cancelling', true)">
                    {{ __('app.common.cancel') }}
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.card :title="__('delivery.labels.pickup')">
                    <p class="text-sm font-semibold text-ink-900">{{ $pickup->contactName }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $pickup->fullAddress() }}</p>
                    <p class="tnum mt-1 text-sm text-ink-500" dir="ltr">{{ $pickup->contactPhone }}</p>
                </x-ui.card>
                <x-ui.card :title="__('delivery.labels.customer')">
                    <p class="text-sm font-semibold text-ink-900">{{ $dropoff->contactName }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $dropoff->fullAddress() }}</p>
                    <p class="tnum mt-1 text-sm text-ink-500" dir="ltr">{{ $dropoff->contactPhone }}</p>
                </x-ui.card>
            </div>

            @if ($this->hasMap())
                <x-ui.map
                    :id="\App\Livewire\Business\Orders\OrderDetail::MAP_ID"
                    :markers="$this->mapConfig['markers']"
                    :route="$this->mapConfig['route']"
                    :height="240" />
            @endif

            <x-ui.card :title="__('delivery.labels.timeline')" flush>
                <ol class="divide-y divide-ink-100">
                    @foreach ($this->timeline as $event)
                        <li class="flex items-start gap-3 px-4 py-3">
                            <span class="mt-1.5 size-2 shrink-0 rounded-full bg-signal-500"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-ink-800">{{ $event->type->label() }}</p>
                                @if ($company = data_get($event->payload, 'delivery_company_name'))
                                    <p class="text-2xs text-ink-500">{{ $company }}</p>
                                @elseif ($rider = data_get($event->payload, 'rider_name'))
                                    <p class="text-2xs text-ink-500">{{ $rider }}</p>
                                @endif
                            </div>
                            <span class="tnum shrink-0 text-2xs text-ink-400">
                                {{ $event->occurred_at->translatedFormat('d M H:i') }}
                            </span>
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>

            @if ($order->items->isNotEmpty())
                <x-ui.card :title="__('app.nav.orders')" flush>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('app.common.name') }}</th>
                                <th class="text-end">{{ __('app.common.total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>{{ $item->name }} <span class="tnum text-ink-400">×{{ $item->quantity }}</span></td>
                                    <td class="tnum text-end">{{ $item->lineTotal()->format(false) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-ui.card>
            @endif
        </div>

        <div class="space-y-5">
            @if ($delivery)
                <x-ui.card :title="__('delivery.labels.price')">
                    <p class="tnum text-2xl font-bold text-ink-900">{{ $delivery->price()->format() }}</p>

                    <dl class="mt-3 space-y-1.5 border-t border-ink-100 pt-3 text-xs">
                        @foreach (($delivery->price_breakdown['lines'] ?? []) as $line)
                            @continue(($line['amount_minor'] ?? 0) === 0)
                            <div class="flex justify-between gap-3">
                                <dt class="text-ink-500">{{ $line['label'] }}</dt>
                                <dd class="tnum shrink-0 text-ink-800">
                                    {{ number_format($line['amount_minor'] / 100, 2) }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($order->payment_type->requiresCollection())
                        <div class="mt-3 flex justify-between rounded-md bg-amber-50 px-2 py-1.5 text-xs">
                            <span class="font-medium text-amber-900">{{ __('order.payment.cod') }}</span>
                            <span class="tnum font-semibold text-amber-900">
                                {{ $order->cod_amount_minor->format(false) }}
                            </span>
                        </div>
                    @endif
                </x-ui.card>

                <x-ui.card :title="__('delivery.labels.company')">
                    @if ($delivery->deliveryCompany)
                        <p class="text-sm font-semibold text-ink-900">
                            {{ $delivery->deliveryCompany->displayName() }}
                        </p>
                        <p class="tnum mt-1 text-sm text-ink-500" dir="ltr">
                            {{ $delivery->deliveryCompany->phone }}
                        </p>
                        @if ($delivery->rider)
                            <div class="mt-3 border-t border-ink-100 pt-3">
                                <p class="text-xs text-ink-500">{{ __('delivery.labels.rider') }}</p>
                                <p class="text-sm font-medium text-ink-900">{{ $delivery->rider->name }}</p>
                                <p class="tnum text-sm text-ink-500" dir="ltr">{{ $delivery->rider->phone }}</p>
                            </div>
                        @endif
                    @else
                        <p class="py-2 text-sm text-ink-500">{{ __('delivery.status.searching') }}</p>
                    @endif
                </x-ui.card>

                <x-ui.card :title="__('delivery.labels.tracking')">
                    <div x-data="{ copied: false }" class="space-y-2">
                        <input type="text" readonly value="{{ $delivery->trackingUrl() }}"
                               class="field-input text-xs" dir="ltr">
                        <div class="flex gap-2">
                            <x-ui.button variant="secondary" size="sm" class="flex-1"
                                         x-on:click="copied = await window.copyToClipboard('{{ $delivery->trackingUrl() }}')">
                                <span x-text="copied ? @js(__('app.common.copied')) : @js(__('app.common.copy'))"></span>
                            </x-ui.button>
                            <x-ui.button variant="ghost" size="sm" :href="$delivery->trackingUrl()" icon="link">
                                {{ __('app.common.view') }}
                            </x-ui.button>
                        </div>
                    </div>
                </x-ui.card>


                <x-delivery.proof :delivery="$delivery" />
            @endif
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

    <div x-show="toast" x-cloak
         class="fixed bottom-5 z-50 rounded-md px-4 py-3 text-sm text-white shadow-lg ltr:right-5 rtl:left-5"
         :class="tone === 'error' ? 'bg-red-700' : 'bg-ink-900'">
        <span x-text="toast"></span>
    </div>
</div>
