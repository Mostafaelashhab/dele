<div>
    <x-ui.page-header :title="__('app.nav.settlements')" :subtitle="$this->tenantLabel()" />

    <div class="grid gap-3 sm:grid-cols-2">
        <x-ui.stat :label="__('finance.settlement.open')"
                   :value="$this->balances['unsettled']->format(false)" icon="money" tone="green" />
        <x-ui.stat :label="__('app.common.total')"
                   :value="$this->balances['lifetime']->format(false)" icon="chart" />
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <x-ui.card :title="__('app.nav.settlements')" flush>
            @if ($this->settlements->isEmpty())
                <x-ui.empty icon="receipt" :title="__('app.common.empty')" />
            @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.date') }}</th>
                            <th>{{ __('app.common.status') }}</th>
                            <th class="text-end">{{ __('app.nav.deliveries') }}</th>
                            <th class="text-end">{{ __('app.common.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->settlements as $settlement)
                            <tr>
                                <td class="tnum">
                                    {{ $settlement->period_start->translatedFormat('d M') }}
                                    –
                                    {{ $settlement->period_end->translatedFormat('d M') }}
                                </td>
                                <td>
                                    <x-ui.badge :tone="$settlement->status->value === 'paid' ? 'green' : 'amber'">
                                        {{ $settlement->status->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="tnum text-end">{{ $settlement->deliveries_count }}</td>
                                <td class="tnum text-end font-semibold">
                                    {{ $settlement->netPayable()->format(false) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-ui.card>

        <x-ui.card :title="__('app.nav.finance')" flush>
            @if ($this->entries->isEmpty())
                <x-ui.empty icon="money" :title="__('app.common.empty')" />
            @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.order') }}</th>
                            <th>{{ __('app.common.details') }}</th>
                            <th class="text-end">{{ __('app.common.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->entries as $entry)
                            <tr>
                                <td class="text-ink-200">{{ $entry->delivery?->order?->number ?? '—' }}</td>
                                <td class="text-ink-300">{{ $entry->category->label() }}</td>
                                <td @class([
                                    'tnum text-end font-medium',
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
    </div>
</div>
