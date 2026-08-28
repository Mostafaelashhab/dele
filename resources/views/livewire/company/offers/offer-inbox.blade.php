<div wire:poll.8s
     x-data="{ toast: null, tone: 'neutral' }"
     @toast.window="toast = $event.detail.message; tone = $event.detail.tone; setTimeout(() => toast = null, 4000)">

    <x-ui.page-header :title="__('app.nav.offers')"
                      :subtitle="__('app.dashboard.pending_offers')" />

    @if ($this->offers->isEmpty())
        <x-ui.card>
            <x-ui.empty icon="bell"
                        :title="__('app.common.empty')"
                        :description="__('app.dashboard.no_active')" />
        </x-ui.card>
    @else
        <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->offers as $offer)
                @php
                    $delivery = $offer->delivery;
                    $order = $delivery->order;
                @endphp

                <article class="flex flex-col overflow-hidden rounded-card border-2 border-signal-300 bg-white shadow-sm">
                    {{-- The timer runs in the browser from the server's expiry
                         instant, so every dispatcher sees the same deadline
                         without a request per tick. --}}
                    <div class="flex items-center justify-between bg-signal-50 px-4 py-2"
                         x-data="{ left: {{ $offer->secondsRemaining() }} }"
                         x-init="setInterval(() => left > 0 && left--, 1000)">
                        <span class="text-xs font-semibold text-signal-800">
                            {{ $delivery->business->displayName() }}
                        </span>
                        <span class="tnum inline-flex items-center gap-1 text-xs font-bold"
                              :class="left <= 15 ? 'text-red-700' : 'text-signal-800'">
                            <x-ui.icon name="clock" class="size-3.5" />
                            <span x-text="left"></span>s
                        </span>
                    </div>

                    <div class="flex-1 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-ink-900">{{ $order->number }}</p>
                                <p class="mt-0.5 text-xs text-ink-500">
                                    {{ $order->priority->label() }} · {{ $order->package_size->label() }}
                                </p>
                            </div>
                            <div class="shrink-0 text-end">
                                <p class="tnum text-xl font-bold text-emerald-700">
                                    {{ $offer->payout()->format(false) }}
                                </p>
                                <p class="text-2xs text-ink-500">{{ __('delivery.labels.payout') }}</p>
                            </div>
                        </div>

                        <dl class="mt-4 space-y-2.5 border-t border-ink-100 pt-3 text-xs">
                            <div class="flex gap-2">
                                <dt class="w-16 shrink-0 font-semibold text-ink-500">
                                    {{ __('delivery.labels.pickup') }}
                                </dt>
                                <dd class="min-w-0 flex-1 text-ink-800">
                                    {{ $order->pickupSnapshot()->area ?? $order->pickupSnapshot()->addressLine }}
                                </dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="w-16 shrink-0 font-semibold text-ink-500">
                                    {{ __('delivery.labels.dropoff') }}
                                </dt>
                                <dd class="min-w-0 flex-1 text-ink-800">
                                    {{ $order->dropoffSnapshot()->area ?? $order->dropoffSnapshot()->addressLine }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-3 flex items-center gap-4 border-t border-ink-100 pt-3 text-xs text-ink-600">
                            <span class="tnum inline-flex items-center gap-1">
                                <x-ui.icon name="navigation" class="size-3.5 text-ink-400" />
                                {{ number_format($delivery->distance_meters / 1000, 1) }} {{ __('app.common.km') }}
                            </span>
                            <span class="tnum inline-flex items-center gap-1">
                                <x-ui.icon name="clock" class="size-3.5 text-ink-400" />
                                {{ $offer->quoted_eta_minutes }} {{ __('app.common.minutes') }}
                            </span>
                            @if ($order->payment_type->requiresCollection())
                                <x-ui.badge tone="amber">{{ __('order.payment.cod') }}</x-ui.badge>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 border-t border-ink-200 p-3">
                        <x-ui.button variant="success" class="col-span-2"
                                     wire:click="accept('{{ $offer->id }}')"
                                     wire:loading.attr="disabled"
                                     wire:target="accept('{{ $offer->id }}')">
                            {{ __('rider.app.accept') }}
                        </x-ui.button>
                        <x-ui.button variant="secondary" wire:click="startReject('{{ $offer->id }}')">
                            {{ __('rider.app.reject') }}
                        </x-ui.button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    @if ($this->recentlyClosed->isNotEmpty())
        <x-ui.card class="mt-6" :title="__('app.nav.history')" flush>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.order') }}</th>
                            <th>{{ __('app.common.status') }}</th>
                            <th>{{ __('delivery.labels.payout') }}</th>
                            <th>{{ __('app.common.date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->recentlyClosed as $offer)
                            <tr>
                                <td class="font-medium text-ink-900">{{ $offer->delivery->order->number }}</td>
                                <td>
                                    <x-ui.badge :tone="$offer->status->value === 'accepted' ? 'green' : 'slate'">
                                        {{ $offer->status->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="tnum">{{ $offer->payout()->format(false) }}</td>
                                <td class="tnum text-ink-500">{{ $offer->responded_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    {{-- Rejection reason capture. Asking why is what turns a decline into a
         signal the matching engine can eventually learn from. --}}
    @if ($rejecting)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4"
             wire:click.self="$set('rejecting', null)">
            <div class="w-full max-w-sm rounded-card bg-white p-5 shadow-xl">
                <h2 class="text-sm font-semibold text-ink-900">{{ __('rider.app.confirm_reject') }}</h2>
                <form wire:submit="reject" class="mt-4 space-y-3">
                    <x-ui.field :label="__('app.common.reason')" name="rejectionReason">
                        <input type="text" wire:model="rejectionReason" class="field-input" autofocus>
                    </x-ui.field>
                    <div class="flex gap-2">
                        <x-ui.button type="submit" variant="danger" class="flex-1">
                            {{ __('app.common.confirm') }}
                        </x-ui.button>
                        <x-ui.button variant="secondary" wire:click="$set('rejecting', null)">
                            {{ __('app.common.cancel') }}
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
