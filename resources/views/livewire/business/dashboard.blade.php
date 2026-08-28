<div wire:poll.20s>
    <x-ui.page-header :title="__('app.nav.dashboard')" :subtitle="$this->tenantLabel()">
        <x-slot:actions>
            <x-ui.button :href="route('business.orders.create')" icon="plus">
                {{ __('app.dashboard.quick_create') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.stat :label="__('app.dashboard.todays_orders')" :value="$this->metrics['today']" icon="package" />
        <x-ui.stat :label="__('app.dashboard.active_deliveries')" :value="$this->metrics['active']"
                   icon="truck" tone="blue" />
        <x-ui.stat :label="__('app.dashboard.completed_today')" :value="$this->metrics['completed']"
                   icon="check" tone="green" />
        <x-ui.stat :label="__('app.dashboard.late_deliveries')" :value="$this->metrics['late']"
                   icon="clock" :tone="$this->metrics['late'] > 0 ? 'red' : 'neutral'" />
    </div>

    {{-- Reference figures, not decisions: one strip rather than a second row
         of cards competing with the four above. --}}
    <x-ui.stat-row class="mt-3" :items="[
        [
            'label' => __('app.dashboard.failed_deliveries'),
            'value' => (string) $this->metrics['failed'],
            'icon' => 'alert',
            'tone' => $this->metrics['failed'] > 0 ? 'red' : null,
        ],
        [
            'label' => __('app.dashboard.total_cost'),
            'value' => $this->metrics['cost']->format(false),
            'icon' => 'money',
        ],
        [
            'label' => __('app.dashboard.average_time'),
            'value' => $this->metrics['average_minutes'] !== null
                ? $this->metrics['average_minutes'].' '.__('app.common.minutes')
                : '—',
            'icon' => 'clock',
        ],
        [
            'label' => __('finance.settlement.open'),
            'value' => $this->metrics['outstanding']->absolute()->format(false),
            'icon' => 'receipt',
        ],
    ]" />

    <x-ui.card class="mt-4">
        <x-chart.columns
            id="business-daily-outcomes"
            :title="__('app.nav.deliveries')"
            :subtitle="__('app.common.this_month')"
            :rows="$this->dailyRows"
            :series="[
                'delivered' => [
                    'label' => __('delivery.status.delivered'),
                    'token' => 'var(--color-viz-series-1)',
                ],
                'failed' => [
                    'label' => __('delivery.status.failed'),
                    'token' => 'var(--color-viz-critical)',
                ],
            ]"
            :height="160" />
    </x-ui.card>

    @if ($this->mapMarkers !== [])
        <x-ui.card class="mt-4" :title="__('app.dashboard.where_now')" flush>
            <x-ui.map
                id="business-live"
                style="dark"
                :markers="$this->mapMarkers"
                :height="320"
                :mobile-height="240"
                :zoom="13" />
        </x-ui.card>
    @endif

    <div class="mt-5 grid gap-5 xl:grid-cols-3">
        <x-ui.card class="xl:col-span-2" :title="__('app.dashboard.live_operations')" flush>
            <x-slot:actions>
                <x-ui.button variant="ghost" size="sm" :href="route('business.orders.index')">
                    {{ __('app.common.view') }}
                </x-ui.button>
            </x-slot:actions>

            @if ($this->activeDeliveries->isEmpty())
                <x-ui.empty icon="truck" :title="__('app.dashboard.no_active')">
                    <x-ui.button :href="route('business.orders.create')" size="sm" icon="plus">
                        {{ __('app.dashboard.quick_create') }}
                    </x-ui.button>
                </x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('app.common.order') }}</th>
                                <th>{{ __('delivery.labels.customer') }}</th>
                                <th>{{ __('delivery.labels.company') }}</th>
                                <th>{{ __('app.common.status') }}</th>
                                <th class="text-end">{{ __('delivery.labels.eta') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->activeDeliveries as $delivery)
                                <tr wire:key="a-{{ $delivery->id }}">
                                    <td>
                                        <a href="{{ route('business.orders.show', $delivery->order->number) }}"
                                           wire:navigate class="font-medium text-signal-700 hover:underline">
                                            {{ $delivery->order->number }}
                                        </a>
                                    </td>
                                    <td class="text-ink-200">{{ $delivery->order->dropoffSnapshot()->contactName }}</td>
                                    <td>
                                        @if ($delivery->deliveryCompany)
                                            <span class="flex items-center gap-2">
                                                <x-ui.avatar
                                                    :src="$delivery->deliveryCompany->mediaUrl('logo_path')"
                                                    :name="$delivery->deliveryCompany->displayName()"
                                                    icon="truck" size="xs" square />
                                                <span class="truncate text-ink-200">
                                                    {{ $delivery->deliveryCompany->displayName() }}
                                                </span>
                                            </span>
                                        @else
                                            <span class="text-ink-400">{{ __('app.common.unassigned') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <x-ui.badge :tone="$delivery->status->tone()" dot>
                                            {{ $delivery->status->label() }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="tnum text-end {{ $delivery->isLate() ? 'text-red-600' : 'text-ink-400' }}">
                                        {{ $delivery->estimatedArrival()?->shortTime() ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        <x-ui.card :title="__('app.dashboard.recent_orders')" flush>
            <ul class="divide-y divide-white/5">
                @forelse ($this->recent as $delivery)
                    <li class="px-4 py-2.5">
                        <div class="flex items-center justify-between gap-3">
                            <a href="{{ route('business.orders.show', $delivery->order->number) }}" wire:navigate
                               class="truncate text-sm font-medium text-white hover:text-signal-700">
                                {{ $delivery->order->number }}
                            </a>
                            <span class="tnum shrink-0 text-sm text-ink-200">{{ $delivery->price()->format(false) }}</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between gap-3">
                            <span class="flex min-w-0 items-center gap-1.5">
                                <x-ui.badge :tone="$delivery->status->tone()">{{ $delivery->status->label() }}</x-ui.badge>
                                {{-- Whether the handover was actually proven,
                                     visible without opening the order. --}}
                                @if ($delivery->hasProofOfDelivery())
                                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full
                                                 bg-emerald-100 px-1.5 py-0.5 text-2xs font-bold text-emerald-800"
                                          title="{{ __('delivery.proof.title') }}">
                                        <x-ui.icon name="shield" class="size-3" />
                                        {{ __('app.dashboard.proof_ok') }}
                                    </span>
                                @endif
                            </span>
                            <span class="shrink-0 text-2xs text-ink-400">{{ $delivery->created_at->diffForHumans() }}</span>
                        </div>
                    </li>
                @empty
                    <li><x-ui.empty icon="history" :title="__('app.common.empty')" /></li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
</div>
