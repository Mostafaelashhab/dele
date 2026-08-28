@php $business = $this->business; $s = $this->stats; @endphp

<div>
    <x-ui.page-header :title="$business->name" :subtitle="$business->category">
        <x-slot:actions>
            <x-ui.badge :tone="$business->status->tone()" dot>{{ $business->status->label() }}</x-ui.badge>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.stat :label="__('app.nav.orders')" :value="$s['total']" icon="package" />
        <x-ui.stat :label="__('app.dashboard.completed_today')" :value="$s['delivered']" icon="check" tone="green" />
        <x-ui.stat :label="__('app.dashboard.revenue')" :value="$s['volume']->format(false)" icon="money" />
        <x-ui.stat :label="__('app.dashboard.platform_fees')" :value="$s['fees']->format(false)"
                   icon="chart" tone="green" />
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-3">
        <x-ui.card :title="__('app.common.details')">
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-500">{{ __('app.common.phone') }}</dt>
                    <dd class="tnum text-ink-800" dir="ltr">{{ $business->phone }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-500">{{ __('app.common.email') }}</dt>
                    <dd class="truncate text-ink-800" dir="ltr">{{ $business->email ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-500">{{ __('address.zone') }}</dt>
                    <dd class="text-ink-800">{{ $business->defaultZone?->displayName() ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-500">{{ __('app.dashboard.platform_fees') }}</dt>
                    <dd class="tnum text-ink-800">
                        {{ number_format($business->platformFeeBasisPoints() / 100, 1) }}%
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-500">{{ __('app.dashboard.average_time') }}</dt>
                    <dd class="tnum text-ink-800">
                        {{ $s['average_minutes'] !== null ? $s['average_minutes'].' '.__('app.common.minutes') : '—' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-3 border-t border-ink-100 pt-2.5">
                    <dt class="text-ink-500">{{ __('finance.settlement.open') }}</dt>
                    <dd class="tnum font-semibold text-ink-900">{{ $s['balance']->format(false) }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card :title="__('app.nav.team')" flush>
            <ul class="divide-y divide-ink-100">
                @foreach ($business->users as $user)
                    <li class="px-4 py-2.5">
                        <p class="text-sm font-medium text-ink-900">{{ $user->name }}</p>
                        <p class="truncate text-2xs text-ink-500" dir="ltr">{{ $user->email }}</p>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        <x-ui.card :title="__('app.nav.companies')" flush>
            @if ($business->companyPreferences->isEmpty())
                <x-ui.empty icon="truck" :title="__('app.common.none')" />
            @else
                <ul class="divide-y divide-ink-100">
                    @foreach ($business->companyPreferences as $preference)
                        <li class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <span class="truncate text-sm text-ink-800">
                                {{ $preference->deliveryCompany->displayName() }}
                            </span>
                            <x-ui.badge :tone="$preference->preference === 'preferred' ? 'green' : 'red'">
                                {{ $preference->preference === 'preferred'
                                    ? __('app.common.active')
                                    : __('audit.action.suspended') }}
                            </x-ui.badge>
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
                        <th>{{ __('delivery.labels.company') }}</th>
                        <th>{{ __('app.common.status') }}</th>
                        <th class="text-end">{{ __('delivery.labels.price') }}</th>
                        <th class="text-end">{{ __('app.common.created') }}</th>
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
                            <td class="text-ink-700">
                                {{ $delivery->deliveryCompany?->displayName() ?? __('app.common.unassigned') }}
                            </td>
                            <td>
                                <x-ui.badge :tone="$delivery->status->tone()" dot>
                                    {{ $delivery->status->label() }}
                                </x-ui.badge>
                            </td>
                            <td class="tnum text-end">{{ $delivery->price()->format(false) }}</td>
                            <td class="tnum text-end text-ink-500">
                                {{ $delivery->created_at->translatedFormat('d M H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
