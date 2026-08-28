<div wire:poll.15s="refreshBoard">
    <x-ui.page-header :title="__('app.nav.dashboard')" :subtitle="$this->tenantLabel()">
        <x-slot:actions>
            <x-ui.button :href="route('company.offers.index')" icon="bell">
                {{ __('app.nav.offers') }}
                @if ($this->metrics['pending_offers'] > 0)
                    <span class="tnum ms-1 rounded bg-white/20 px-1.5 py-0.5 text-2xs">
                        {{ $this->metrics['pending_offers'] }}
                    </span>
                @endif
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- A self-registered company can sign in and configure itself, but
         `DeliveryCompany::dispatchable()` excludes anything that is not Active,
         so no offer will reach it yet. Saying so is the difference between a
         dashboard that looks broken and one that is honestly waiting. --}}
    @if ($this->company()->status === \App\Enums\AccountStatus::Pending)
        <div class="mb-3 flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg
                         bg-amber-100 text-amber-700">
                <x-ui.icon name="clock" class="size-5" />
            </span>
            <div class="min-w-0">
                <p class="text-sm font-bold text-amber-900">{{ __('app.auth.company_pending_title') }}</p>
                <p class="mt-1 text-xs leading-relaxed text-amber-800">
                    {{ __('app.auth.company_pending_body') }}
                </p>
            </div>
        </div>
    @endif

    @if ($this->metrics['pending_offers'] > 0)
        <a href="{{ route('company.offers.index') }}" wire:navigate
           class="mb-3 flex items-center gap-4 rounded-xl border border-ember-300 bg-ember-50 p-4
                  transition hover:border-ember-400 hover:bg-ember-100">
            <span class="relative flex size-11 shrink-0 items-center justify-center rounded-lg
                         bg-ember-500 text-white">
                <x-ui.icon name="bell" class="size-5" />
                <span class="absolute -end-1 -top-1 flex size-3">
                    <span class="absolute inline-flex size-full animate-ping rounded-full
                                 bg-ember-400 opacity-75"></span>
                    <span class="relative inline-flex size-3 rounded-full bg-ember-600"></span>
                </span>
            </span>
            <span class="min-w-0 flex-1">
                <span class="flex items-baseline gap-2">
                    <span class="tnum text-xl font-bold text-ember-900">
                        {{ $this->metrics['pending_offers'] }}
                    </span>
                    <span class="text-sm font-bold text-ember-900">
                        {{ __('app.dashboard.offers_waiting') }}
                    </span>
                </span>
                <span class="mt-0.5 block text-xs leading-relaxed text-ember-800">
                    {{ __('app.dashboard.offers_waiting_hint') }}
                </span>
            </span>
            <span class="hidden shrink-0 items-center gap-1.5 rounded-lg bg-ember-500 px-4 py-2.5
                         text-sm font-bold text-white sm:flex">
                {{ __('app.dashboard.answer_now') }}
                <x-ui.icon name="chevron-end" class="size-4 rtl:rotate-180" />
            </span>
        </a>
    @endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.stat :label="__('app.dashboard.active_deliveries')"
                   :value="$this->metrics['active']" icon="package" />
        <x-ui.stat :label="__('app.dashboard.available_riders')"
                   :value="$this->metrics['available_riders']" icon="users" tone="green" />
        <x-ui.stat :label="__('app.dashboard.completed_today')"
                   :value="$this->metrics['completed_today']" icon="check" />
        <x-ui.stat :label="__('app.dashboard.revenue')"
                   :value="$this->metrics['revenue_today']->format(false)" icon="money" />
    </div>

    <div class="mt-3 grid gap-3 lg:grid-cols-2">
        <x-ui.card>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-chart.meter
                    :label="__('app.dashboard.acceptance_rate')"
                    :value="$this->metrics['acceptance_rate']"
                    :good-above="0.75"
                    :warn-above="0.55"
                    :hint="__('app.nav.offers')" />
                <x-chart.meter
                    :label="__('app.dashboard.completion_rate')"
                    :value="$this->metrics['completion_rate']"
                    :good-above="0.9"
                    :warn-above="0.75"
                    :hint="__('app.nav.deliveries')" />
            </div>
        </x-ui.card>

        <div class="grid grid-cols-2 gap-3">
            <x-ui.stat :label="__('app.dashboard.busy_riders')" :value="$this->metrics['busy_riders']"
                       icon="motorcycle" />
            <x-ui.stat :label="__('finance.settlement.open')"
                       :value="$this->metrics['unsettled']->format(false)"
                       :href="route('company.settlements.index')" icon="receipt" tone="green" />
        </div>
    </div>

    {{-- The fleet, plotted. A dispatcher deciding who to send needs to see
         where everyone already is, not read it out of a column. --}}
    <x-ui.map
        class="mt-4"
        :id="\App\Livewire\Company\Dashboard::MAP_ID"
        :markers="$this->mapConfig['markers']"
        :zones="$this->mapConfig['zones']"
        :height="360"
        :mobile-height="260"
        scroll-zoom />

    <div class="mt-5 grid gap-5 xl:grid-cols-3">
        <x-ui.card class="xl:col-span-2" :title="__('app.dashboard.live_operations')" flush>
            <x-slot:actions>
                <x-ui.button variant="ghost" size="sm" :href="route('company.deliveries.index')">
                    {{ __('app.common.view') }}
                </x-ui.button>
            </x-slot:actions>

            @if ($this->activeDeliveries->isEmpty())
                <x-ui.empty icon="truck" :title="__('app.dashboard.no_active')" />
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('app.common.order') }}</th>
                                <th>{{ __('delivery.labels.dropoff') }}</th>
                                <th>{{ __('delivery.labels.rider') }}</th>
                                <th>{{ __('app.common.status') }}</th>
                                <th class="text-end">{{ __('delivery.labels.payout') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->activeDeliveries as $delivery)
                                <tr wire:key="d-{{ $delivery->id }}">
                                    <td>
                                        <a href="{{ route('company.deliveries.show', $delivery->public_id) }}"
                                           wire:navigate class="font-medium text-signal-700 hover:underline">
                                            {{ $delivery->order->number }}
                                        </a>
                                        <p class="text-2xs text-ink-500">{{ $delivery->business->displayName() }}</p>
                                    </td>
                                    <td class="text-ink-700">{{ $delivery->order->dropoffSnapshot()->area ?? '—' }}</td>
                                    <td class="text-ink-700">
                                        {{ $delivery->rider?->name ?? __('app.common.unassigned') }}
                                    </td>
                                    <td>
                                        <x-ui.badge :tone="$delivery->status->tone()" dot>
                                            {{ $delivery->status->label() }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="tnum text-end">{{ $delivery->companyPayout()->format(false) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        <x-ui.card :title="__('app.nav.riders')" flush>
            <x-slot:actions>
                <x-ui.button variant="ghost" size="sm" :href="route('company.riders.index')">
                    {{ __('app.common.view') }}
                </x-ui.button>
            </x-slot:actions>

            <ul class="divide-y divide-ink-100">
                @forelse ($this->riders as $rider)
                    <li class="flex items-center gap-3 px-4 py-2.5">
                        <span class="relative shrink-0">
                            <x-ui.avatar
                                :src="$rider->mediaUrl('photo_path')"
                                :name="$rider->name"
                                size="sm"
                                :tone="$rider->status->value === 'online' ? 'green' : 'neutral'" />
                            <span @class([
                                'absolute -bottom-0.5 size-2.5 rounded-full ring-2 ring-white ltr:-right-0.5 rtl:-left-0.5',
                                'bg-emerald-500' => $rider->status->value === 'online',
                                'bg-amber-500' => $rider->status->value === 'busy',
                                'bg-ink-300' => $rider->status->value === 'offline',
                            ])></span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-ink-900">{{ $rider->name }}</p>
                            <p class="flex items-center gap-1 text-2xs text-ink-500">
                                <x-ui.icon :name="match ($rider->vehicle_type->value) {
                                    'car' => 'car', 'van' => 'van',
                                    'bicycle' => 'bicycle', 'on_foot' => 'walk',
                                    default => 'motorcycle',
                                }" class="size-3 shrink-0" />
                                {{ $rider->vehicle_type->label() }}
                            </p>
                        </div>
                        <span class="tnum text-xs text-ink-500">
                            {{ $rider->active_deliveries_count }}/{{ $rider->max_concurrent_deliveries }}
                        </span>
                    </li>
                @empty
                    <li><x-ui.empty icon="users" :title="__('app.common.empty')" /></li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
</div>
