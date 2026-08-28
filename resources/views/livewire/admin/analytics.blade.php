@php
    $o = $this->overview;

    // Fixed series order — colour follows the series, never its size, so a
    // quiet day never repaints the chart.
    $outcomeSeries = [
        'delivered' => [
            'label' => __('delivery.status.delivered'),
            'token' => 'var(--color-viz-series-1)',
        ],
        'failed' => [
            'label' => __('delivery.status.failed'),
            'token' => 'var(--color-viz-critical)',
        ],
    ];
@endphp

<div>
    <x-ui.page-header :title="__('app.nav.analytics')" :subtitle="config('platform.name')">
        <x-slot:actions>
            {{-- One filter row above everything it scopes. --}}
            <div class="flex gap-1 rounded-md bg-ink-200/60 p-1">
                @foreach (['7' => '7', '30' => '30', '90' => '90'] as $value => $label)
                    <button type="button" wire:click="$set('days', '{{ $value }}')"
                            @class([
                                'tnum rounded px-3 py-1.5 text-xs font-semibold transition',
                                'bg-white text-ink-900 shadow-xs' => $days === $value,
                                'text-ink-600' => $days !== $value,
                            ])>{{ $label }}</button>
                @endforeach
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- The hero figure: the one number the page leads with. Exactly one. --}}
    {{-- Self-registration means companies can now arrive overnight. Without
         this they would sit in Pending, invisible, receiving no offers and
         wondering why. --}}
    @if (($this->overview['pending_companies'] ?? 0) > 0)
        <a href="{{ route('admin.companies.index') }}" wire:navigate
           class="mb-4 flex items-center gap-4 rounded-xl border border-amber-300 bg-amber-50 p-4
                  transition hover:border-amber-400 hover:bg-amber-100">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-lg
                         bg-amber-500 text-white">
                <x-ui.icon name="truck" class="size-5" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="flex items-baseline gap-2">
                    <span class="tnum text-xl font-bold text-amber-900">
                        {{ $this->overview['pending_companies'] }}
                    </span>
                    <span class="text-sm font-bold text-amber-900">
                        {{ __('app.dashboard.pending_companies') }}
                    </span>
                </span>
                <span class="mt-0.5 block text-xs leading-relaxed text-amber-800">
                    {{ __('app.auth.company_pending_body') }}
                </span>
            </span>
            <span class="hidden shrink-0 items-center gap-1.5 rounded-lg bg-amber-500 px-4 py-2.5
                         text-sm font-bold text-white sm:flex">
                {{ __('app.dashboard.review_now') }}
                <x-ui.icon name="chevron-end" class="size-4 rtl:rotate-180" />
            </span>
        </a>
    @endif

    <div class="mb-4 grid gap-4 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-1">
            <p class="text-xs font-medium text-ink-500">{{ __('app.dashboard.revenue') }}</p>
            <p class="mt-1 text-5xl font-semibold tracking-tight text-ink-900">
                {{ $o['volume']->format(false) }}
                <span class="text-lg font-medium text-ink-400">{{ config('platform.currency.code') }}</span>
            </p>
            <div class="mt-3 flex items-end justify-between gap-3">
                <p class="text-xs text-ink-500">
                    {{ __('app.dashboard.platform_fees') }}
                    <span class="tnum font-semibold text-emerald-700">
                        {{ $o['platform_fees']->format(false) }}
                    </span>
                </p>
                <x-chart.sparkline :values="$this->volumeTrend" :width="110" :height="32" />
            </div>
        </x-ui.card>

        <x-ui.card class="lg:col-span-2">
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <x-chart.meter
                    :label="__('app.dashboard.acceptance_rate')"
                    :value="$o['acceptance_rate'] ?? 0"
                    :display="$o['acceptance_rate'] !== null ? number_format($o['acceptance_rate'] * 100, 1).'%' : '—'"
                    :good-above="0.75"
                    :warn-above="0.55"
                    :hint="__('app.nav.offers')" />

                <x-chart.meter
                    :label="__('app.dashboard.proof_rate')"
                    :value="$o['proof_rate'] ?? 0"
                    :display="$o['proof_rate'] !== null ? number_format($o['proof_rate'] * 100, 1).'%' : '—'"
                    :good-above="0.95"
                    :warn-above="0.8"
                    :hint="__('app.dashboard.proof_rate_hint')" />
                <x-chart.meter
                    :label="__('app.dashboard.completion_rate')"
                    :value="$o['completion_rate'] ?? 0"
                    :display="$o['completion_rate'] !== null ? number_format($o['completion_rate'] * 100, 1).'%' : '—'"
                    :good-above="0.9"
                    :warn-above="0.75"
                    :hint="__('app.nav.deliveries')" />

                <div>
                    <p class="truncate text-xs font-medium text-ink-500">{{ __('app.dashboard.average_time') }}</p>
                    <p class="mt-0.5 text-lg font-semibold text-ink-900">
                        {{ $o['average_minutes'] !== null ? $o['average_minutes'].' '.__('app.common.minutes') : '—' }}
                    </p>
                    <p class="mt-1.5 flex items-center gap-1 text-2xs text-ink-400">
                        <x-ui.icon name="clock" class="size-3 shrink-0" />
                        {{ __('delivery.labels.price') }} {{ $o['average_price']->format(false) }}
                    </p>
                </div>
            </div>
        </x-ui.card>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.stat :label="__('app.nav.orders')" :value="number_format($o['orders'])" icon="package" />
        <x-ui.stat :label="__('delivery.status.delivered')" :value="number_format($o['delivered'])"
                   icon="check" tone="green" />
        <x-ui.stat :label="__('app.dashboard.failed_deliveries')" :value="number_format($o['failed'])"
                   icon="alert" :tone="$o['failed'] > 0 ? 'red' : 'neutral'" />
        <x-ui.stat :label="__('app.dashboard.supply_gap')" :value="number_format($o['supply_gaps'])"
                   icon="map" :tone="$o['supply_gaps'] > 0 ? 'red' : 'neutral'" />
    </div>

    <x-ui.card class="mt-4">
        <x-chart.columns
            id="admin-daily-outcomes"
            :title="__('app.nav.deliveries')"
            :subtitle="__('app.common.showing', ['count' => $days, 'total' => $days])"
            :rows="$this->dailyRows"
            :series="$outcomeSeries"
            :height="200" />
    </x-ui.card>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-ui.card>
            <x-chart.bars
                :title="__('app.nav.companies')"
                :subtitle="__('delivery.status.delivered')"
                :rows="$this->companies->map(fn (array $row) => [
                    'label' => $row['company']->displayName(),
                    'value' => $row['delivered'],
                    'display' => number_format($row['delivered']),
                    'href' => route('admin.companies.show', $row['company']->id),
                    'meta' => $row['average_minutes'] !== null
                        ? $row['average_minutes'].' '.__('app.common.minutes')
                        : null,
                ])->all()" />
        </x-ui.card>

        <x-ui.card>
            <x-chart.bars
                :title="__('app.nav.businesses')"
                :subtitle="__('app.dashboard.revenue')"
                :rows="$this->businesses->map(fn (array $row) => [
                    'label' => $row['business']->displayName(),
                    'value' => $row['volume']->minor,
                    'display' => $row['volume']->format(false),
                    'href' => route('admin.businesses.show', $row['business']->id),
                    'meta' => $row['deliveries'].' '.__('app.nav.deliveries'),
                ])->all()" />
        </x-ui.card>
    </div>

    {{-- The company league table: past a handful of bars, a table reads better
         and carries the columns the chart deliberately leaves out. --}}
    <x-ui.card class="mt-4" :title="__('app.nav.companies')" flush>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('app.nav.companies') }}</th>
                        <th class="text-end">{{ __('app.nav.orders') }}</th>
                        <th class="text-end">{{ __('delivery.status.delivered') }}</th>
                        <th class="text-end">{{ __('delivery.status.failed') }}</th>
                        <th class="text-end">{{ __('app.dashboard.average_time') }}</th>
                        <th class="text-end">{{ __('app.dashboard.acceptance_rate') }}</th>
                        <th class="text-end">{{ __('finance.category.company_payout') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->companies as $row)
                        <tr wire:key="c-{{ $row['company']->id }}">
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <x-ui.avatar
                                        :src="$row['company']->mediaUrl('logo_path')"
                                        :name="$row['company']->displayName()"
                                        icon="truck" size="xs" square />
                                    <a href="{{ route('admin.companies.show', $row['company']->id) }}" wire:navigate
                                       class="font-medium text-signal-700 hover:underline">
                                        {{ $row['company']->displayName() }}
                                    </a>
                                </div>
                            </td>
                            <td class="tnum text-end">{{ $row['orders'] }}</td>
                            <td class="tnum text-end">{{ $row['delivered'] }}</td>
                            <td class="tnum text-end {{ $row['failed'] > 0 ? 'text-red-600' : 'text-ink-400' }}">
                                {{ $row['failed'] }}
                            </td>
                            <td class="tnum text-end">{{ $row['average_minutes'] ?? '—' }}</td>
                            <td class="tnum text-end">
                                {{ number_format($row['acceptance_rate'] * 100, 0) }}%
                            </td>
                            <td class="tnum text-end">{{ $row['payout']->format(false) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
