@php
    $order = $this->order;
    $current = $this->attempts->last();
@endphp

<div @if ($current && ! $current->status->isTerminal()) wire:poll.10s @endif
     x-data="{ toast: null, tone: 'neutral' }"
     @toast.window="toast = $event.detail.message; tone = $event.detail.tone; setTimeout(() => toast = null, 4000)">

    <x-ui.page-header :title="$order->number" :subtitle="$order->business->displayName()">
        <x-slot:actions>
            @if ($current)
                <x-ui.badge :tone="$current->status->tone()" dot>{{ $current->status->label() }}</x-ui.badge>
                @if ($current->delivery_company_id === null && ! $current->status->isTerminal())
                    <x-ui.button variant="secondary" size="sm" wire:click="redispatch">
                        {{ __('delivery.event.DeliveryRequested') }}
                    </x-ui.button>
                @endif
                @if ($current->isCancellable())
                    <x-ui.button variant="secondary" size="sm" wire:click="$set('cancelling', true)">
                        {{ __('app.common.cancel') }}
                    </x-ui.button>
                @endif
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">

            @php $liveDelivery = $this->attempts->last(); @endphp

            @if ($liveDelivery)
                <livewire:shared.delivery-issues :delivery-id="$liveDelivery->id"
                                                 :key="'issues-'.$liveDelivery->id" />
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.card :title="__('delivery.labels.pickup')">
                    <p class="text-sm font-semibold text-white">{{ $order->pickupSnapshot()->contactName }}</p>
                    <p class="mt-1 text-sm text-ink-300">{{ $order->pickupSnapshot()->fullAddress() }}</p>
                    <p class="tnum mt-1 text-sm text-ink-400" dir="ltr">{{ $order->pickupSnapshot()->contactPhone }}</p>
                </x-ui.card>
                <x-ui.card :title="__('delivery.labels.customer')">
                    <p class="text-sm font-semibold text-white">{{ $order->dropoffSnapshot()->contactName }}</p>
                    <p class="mt-1 text-sm text-ink-300">{{ $order->dropoffSnapshot()->fullAddress() }}</p>
                    <p class="tnum mt-1 text-sm text-ink-400" dir="ltr">{{ $order->dropoffSnapshot()->contactPhone }}</p>
                </x-ui.card>
            </div>

            @if ($this->hasMap())
                <x-ui.map
                style="dark"
                    :id="\App\Livewire\Admin\Orders\OrderDetail::MAP_ID"
                    :markers="$this->mapConfig['markers']"
                    :route="$this->mapConfig['route']"
                    :height="260" />
            @endif

            {{-- Every company asked, in the order they were asked, with the
                 score that put them there. --}}
            @foreach ($this->attempts as $attempt)
                <x-ui.card wire:key="attempt-{{ $attempt->id }}"
                           :title="__('app.nav.offers').' — '.__('app.common.order').' '.$attempt->attempt"
                           :subtitle="$attempt->deliveryCompany?->displayName()" flush>
                    @if ($attempt->offers->isEmpty())
                        <x-ui.empty icon="bell" :title="__('delivery.event.NoCompanyAvailable')" />
                    @else
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('app.nav.companies') }}</th>
                                    <th class="text-end">{{ __('app.dashboard.acceptance_rate') }}</th>
                                    <th class="text-end">{{ __('delivery.labels.price') }}</th>
                                    <th class="text-end">{{ __('delivery.labels.eta') }}</th>
                                    <th>{{ __('app.common.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($attempt->offers->sortBy(['round', 'rank']) as $offer)
                                    <tr>
                                        <td class="tnum text-ink-400">{{ $offer->round }}.{{ $offer->rank }}</td>
                                        <td class="font-medium text-white">
                                            {{ $offer->deliveryCompany->displayName() }}
                                        </td>
                                        <td class="tnum text-end">{{ number_format($offer->score() * 100, 1) }}%</td>
                                        <td class="tnum text-end">{{ $offer->quotedPrice()->format(false) }}</td>
                                        <td class="tnum text-end">{{ $offer->quoted_eta_minutes }}</td>
                                        <td>
                                            <x-ui.badge :tone="match ($offer->status->value) {
                                                'accepted' => 'green',
                                                'rejected' => 'red',
                                                'pending' => 'amber',
                                                default => 'slate',
                                            }">{{ $offer->status->label() }}</x-ui.badge>
                                            @if ($offer->rejection_reason)
                                                <p class="mt-0.5 text-2xs text-ink-400">{{ $offer->rejection_reason }}</p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </x-ui.card>
            @endforeach

            <x-ui.card :title="__('delivery.labels.timeline')" flush>
                <ol class="divide-y divide-white/5">
                    @foreach ($this->timeline as $event)
                        <li class="flex items-start gap-3 px-4 py-2.5">
                            <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-ink-300"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-ink-100">{{ $event->type->label() }}</p>
                                <p class="text-2xs text-ink-400">
                                    {{ $event->actor_label ?? $event->actor_type }}
                                    @if ($event->from_status && $event->to_status && $event->from_status !== $event->to_status)
                                        · {{ $event->from_status->label() }} → {{ $event->to_status->label() }}
                                    @endif
                                </p>
                            </div>
                            <span class="tnum shrink-0 text-2xs text-ink-400">
                                {{ $event->occurred_at->translatedFormat('d M g:i:s A') }}
                            </span>
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>
        </div>

        <div class="space-y-5">
            @if ($current)
                <x-ui.card :title="__('delivery.labels.price')">
                    <p class="tnum text-2xl font-bold text-white">{{ $current->price()->format() }}</p>
                    <dl class="mt-3 space-y-1.5 border-t border-white/5 pt-3 text-xs">
                        <div class="flex justify-between">
                            <dt class="text-ink-400">{{ __('finance.category.platform_fee') }}</dt>
                            <dd class="tnum font-medium text-emerald-700">
                                {{ $current->platformFee()->format(false) }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-400">{{ __('finance.category.company_payout') }}</dt>
                            <dd class="tnum text-ink-100">{{ $current->companyPayout()->format(false) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-400">{{ __('finance.category.rider_payout') }}</dt>
                            <dd class="tnum text-ink-100">{{ $current->riderPayout()->format(false) }}</dd>
                        </div>
                    </dl>

                    <dl class="mt-3 space-y-1.5 border-t border-white/5 pt-3 text-xs">
                        @foreach (($current->price_breakdown['lines'] ?? []) as $line)
                            @continue(($line['amount_minor'] ?? 0) === 0)
                            <div class="flex justify-between gap-3">
                                <dt class="text-ink-400">{{ $line['label'] }}</dt>
                                <dd class="tnum shrink-0 text-ink-200">
                                    {{ number_format($line['amount_minor'] / 100, 2) }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </x-ui.card>

                <x-ui.card :title="__('app.nav.finance')" flush>
                    @if ($this->transactions->isEmpty())
                        <x-ui.empty icon="receipt" :title="__('app.common.empty')" />
                    @else
                        <table class="data-table">
                            <tbody>
                                @foreach ($this->transactions as $entry)
                                    <tr>
                                        <td class="text-xs text-ink-300">
                                            {{ $entry->account_type->label() }}
                                            <p class="text-2xs text-ink-400">{{ $entry->category->label() }}</p>
                                        </td>
                                        <td @class([
                                            'tnum text-end text-xs font-medium',
                                            'text-emerald-700' => $entry->entry_type->value === 'credit',
                                            'text-red-700' => $entry->entry_type->value === 'debit',
                                        ])>
                                            {{ $entry->entry_type->value === 'credit' ? '+' : '−' }}{{ $entry->amount()->format(false) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </x-ui.card>

                <x-ui.card :title="__('delivery.labels.tracking')">
                    <a href="{{ $current->trackingUrl() }}" target="_blank" rel="noopener"
                       class="block truncate font-mono text-xs text-signal-700 hover:underline" dir="ltr">
                        {{ $current->trackingUrl() }}
                    </a>
                </x-ui.card>

                <x-delivery.proof :delivery="$current" />
            @endif
        </div>
    </div>

    @if ($cancelling)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4"
             wire:click.self="$set('cancelling', false)">
            <div class="w-full max-w-sm rounded-card bg-white p-5 shadow-xl">
                <h2 class="text-sm font-semibold text-white">{{ __('app.common.cancel') }}</h2>
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
