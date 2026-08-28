@php $m = $this->metrics; @endphp

<div wire:poll.15s>
    <x-ui.page-header :title="__('app.nav.dashboard')"
                      :subtitle="config('platform.name').' — '.now()->translatedFormat('l d F')">
        <x-slot:actions>
            <x-ui.button variant="secondary" :href="route('admin.live')" icon="map">
                {{ __('app.nav.live') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.stat :label="__('app.dashboard.todays_orders')" :value="$m['orders']" icon="package" />
        <x-ui.stat :label="__('app.dashboard.active_deliveries')" :value="$m['active']"
                   icon="truck" tone="blue" :href="route('admin.live')" />
        <x-ui.stat :label="__('app.dashboard.online_riders')" :value="$m['online_riders']"
                   icon="users" tone="green" />
        <x-ui.stat :label="__('app.dashboard.active_companies')" :value="$m['active_companies']"
                   icon="store" :href="route('admin.companies.index')" />
    </div>

    <div class="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.stat :label="__('app.dashboard.revenue')" :value="$m['volume']->format(false)" icon="money" />
        <x-ui.stat :label="__('app.dashboard.platform_fees')" :value="$m['platform_fees']->format(false)"
                   icon="chart" tone="green" />
        <x-ui.stat :label="__('app.dashboard.failed_deliveries')" :value="$m['failed']"
                   icon="alert" :tone="$m['failed'] > 0 ? 'red' : 'neutral'" />
        <x-ui.stat :label="__('app.dashboard.average_time')"
                   :value="$m['average_minutes'] !== null ? $m['average_minutes'].' '.__('app.common.minutes') : '—'"
                   icon="clock" />
    </div>

    @if ($m['supply_gaps'] > 0)
        {{-- A supply gap is the one number that should interrupt an operator:
             it means a business asked and the network had nobody to send. --}}
        <div class="mt-3 flex items-start gap-2.5 rounded-card border border-amber-300 bg-amber-50 px-4 py-3">
            <x-ui.icon name="alert" class="mt-0.5 size-4 shrink-0 text-amber-700" />
            <div>
                <p class="text-sm font-semibold text-amber-900">
                    {{ __('app.dashboard.supply_gap') }}: <span class="tnum">{{ $m['supply_gaps'] }}</span>
                </p>
                <p class="text-xs text-amber-800">{{ __('delivery.event.NoCompanyAvailable') }}</p>
            </div>
        </div>
    @endif

    <div class="mt-5 grid gap-5 xl:grid-cols-3">
        <x-ui.card class="xl:col-span-2" :title="__('app.dashboard.live_operations')" flush>
            <x-slot:actions>
                <x-ui.button variant="ghost" size="sm" :href="route('admin.orders.index')">
                    {{ __('app.common.view') }}
                </x-ui.button>
            </x-slot:actions>

            @if ($this->operations->isEmpty())
                <x-ui.empty icon="truck" :title="__('app.dashboard.no_active')" />
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('app.common.order') }}</th>
                                <th>{{ __('app.nav.businesses') }}</th>
                                <th>{{ __('delivery.labels.company') }}</th>
                                <th>{{ __('delivery.labels.rider') }}</th>
                                <th>{{ __('app.common.status') }}</th>
                                <th class="text-end">{{ __('delivery.labels.price') }}</th>
                                <th class="text-end">{{ __('delivery.labels.eta') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->operations as $delivery)
                                <tr wire:key="{{ $delivery->id }}">
                                    <td>
                                        <a href="{{ route('admin.orders.show', $delivery->order->number) }}"
                                           wire:navigate class="font-medium text-signal-700 hover:underline">
                                            {{ $delivery->order->number }}
                                        </a>
                                    </td>
                                    <td class="text-ink-700">{{ $delivery->business->displayName() }}</td>
                                    <td class="text-ink-700">
                                        {{ $delivery->deliveryCompany?->displayName() ?? __('app.common.unassigned') }}
                                    </td>
                                    <td class="text-ink-700">{{ $delivery->rider?->name ?? '—' }}</td>
                                    <td>
                                        <x-ui.badge :tone="$delivery->status->tone()" dot>
                                            {{ $delivery->status->label() }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="tnum text-end">{{ $delivery->price()->format(false) }}</td>
                                    <td class="tnum text-end {{ $delivery->isLate() ? 'font-semibold text-red-600' : 'text-ink-500' }}">
                                        {{ $delivery->estimatedArrival()?->translatedFormat('H:i') ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        <x-ui.card :title="__('app.dashboard.pending_offers')" flush>
            <ul class="divide-y divide-ink-100">
                @forelse ($this->openOffers as $offer)
                    <li class="px-4 py-2.5" wire:key="{{ $offer->id }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-medium text-ink-900">
                                {{ $offer->delivery->order->number }}
                            </span>
                            <span class="tnum shrink-0 text-xs font-semibold text-amber-700">
                                {{ $offer->secondsRemaining() }}s
                            </span>
                        </div>
                        <p class="mt-0.5 truncate text-2xs text-ink-500">
                            {{ $offer->deliveryCompany->displayName() }}
                        </p>
                    </li>
                @empty
                    <li><x-ui.empty icon="bell" :title="__('app.common.empty')" /></li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
</div>
