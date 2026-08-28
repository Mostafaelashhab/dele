@php $company = $this->company; $s = $this->stats; @endphp

<div>
    <x-ui.page-header :title="$company->name" :subtitle="$company->contact_person">
        <x-slot:actions>
            <x-ui.badge :tone="$company->status->tone()" dot>{{ $company->status->label() }}</x-ui.badge>
            <x-ui.button variant="secondary" size="sm" wire:click="refreshMetrics">
                {{ __('app.common.refresh') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.stat :label="__('app.nav.deliveries')" :value="$s['total']" icon="package" />
        <x-ui.stat :label="__('app.dashboard.completed_today')" :value="$s['delivered']" icon="check" tone="green" />
        <x-ui.stat :label="__('finance.category.company_payout')" :value="$s['payout']->format(false)" icon="money" />
        <x-ui.stat :label="__('finance.settlement.open')" :value="$s['balance']->format(false)"
                   icon="receipt" tone="amber" />
    </div>

    <div class="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.stat :label="__('app.dashboard.acceptance_rate')"
                   :value="number_format($company->acceptanceRate() * 100, 1).'%'"
                   :tone="$company->acceptanceRate() >= 0.7 ? 'green' : 'amber'" />
        <x-ui.stat :label="__('app.dashboard.completion_rate')"
                   :value="number_format($company->completionRate() * 100, 1).'%'"
                   :tone="$company->completionRate() >= 0.9 ? 'green' : 'amber'" />
        <x-ui.stat :label="__('delivery.labels.pickup')"
                   :value="$s['average_pickup'] !== null ? $s['average_pickup'].' '.__('app.common.minutes') : '—'" />
        <x-ui.stat :label="__('app.dashboard.failed_deliveries')" :value="$s['failed']"
                   :tone="$s['failed'] > 0 ? 'red' : 'neutral'" />
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-3">
        <x-ui.card :title="__('app.nav.riders')" flush>
            <ul class="max-h-72 divide-y divide-white/5 overflow-y-auto">
                @forelse ($company->riders as $rider)
                    <li class="flex items-center gap-2.5 px-4 py-2.5">
                        <span @class([
                            'size-2 shrink-0 rounded-full',
                            'bg-emerald-500' => $rider->status->value === 'online',
                            'bg-warn-500' => $rider->status->value === 'busy',
                            'bg-ink-300' => $rider->status->value === 'offline',
                            'bg-red-500' => $rider->status->value === 'suspended',
                        ])></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-white">{{ $rider->name }}</p>
                            <p class="text-2xs text-ink-400">{{ $rider->vehicle_type->label() }}</p>
                        </div>
                        <span class="tnum text-2xs text-ink-400">
                            {{ $rider->completed_deliveries_count }}
                        </span>
                    </li>
                @empty
                    <li><x-ui.empty icon="users" :title="__('app.common.empty')" /></li>
                @endforelse
            </ul>
        </x-ui.card>

        <x-ui.card :title="__('app.nav.service_areas')">
            <div class="flex flex-wrap gap-1.5">
                @forelse ($company->serviceAreas as $zone)
                    <x-ui.badge tone="blue">{{ $zone->displayName() }}</x-ui.badge>
                @empty
                    <p class="text-sm text-ink-400">{{ __('app.common.all') }}</p>
                @endforelse
            </div>

            <dl class="mt-4 space-y-2 border-t border-white/5 pt-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-ink-400">{{ __('finance.category.commission') }}</dt>
                    <dd class="tnum text-ink-100">
                        {{ number_format($company->commissionBasisPoints() / 100, 1) }}%
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-ink-400">{{ __('finance.period.weekly') }}</dt>
                    <dd class="text-ink-100">{{ $company->settlement_period->label() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-ink-400">{{ __('rider.app.time_left') }}</dt>
                    <dd class="tnum text-ink-100">{{ $company->offerTimeoutSeconds() }}s</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card :title="__('app.nav.pricing')" flush>
            @if ($company->pricingRules->isEmpty())
                <x-ui.empty icon="money" :title="__('app.common.none')"
                            :description="__('pricing.rule.base_fare')" />
            @else
                <ul class="divide-y divide-white/5">
                    @foreach ($company->pricingRules as $rule)
                        <li class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <span class="truncate text-sm text-ink-100">{{ $rule->name }}</span>
                            <span class="tnum shrink-0 text-xs text-ink-400">
                                {{ $rule->amount_minor?->format(false) ?? '—' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card class="mt-5" :title="__('app.nav.deliveries')" flush>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('app.common.order') }}</th>
                        <th>{{ __('app.nav.businesses') }}</th>
                        <th>{{ __('delivery.labels.rider') }}</th>
                        <th>{{ __('app.common.status') }}</th>
                        <th class="text-end">{{ __('delivery.labels.payout') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->recentDeliveries as $delivery)
                        <tr wire:key="{{ $delivery->id }}">
                            <td>
                                <a href="{{ route('admin.orders.show', $delivery->order->number) }}" wire:navigate
                                   class="font-medium text-signal-700 hover:underline">
                                    {{ $delivery->order->number }}
                                </a>
                            </td>
                            <td class="text-ink-200">{{ $delivery->business->displayName() }}</td>
                            <td class="text-ink-200">{{ $delivery->rider?->name ?? '—' }}</td>
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
    </x-ui.card>
</div>
