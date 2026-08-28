<div>
    <x-ui.page-header :title="__('app.nav.finance')" :subtitle="$this->tenantLabel()">
        <x-slot:actions>
            <div class="flex gap-1 rounded-md bg-ink-200/60 p-1">
                @foreach ([
                    'today' => __('app.common.today'),
                    'week' => __('app.common.this_week'),
                    'month' => __('app.common.this_month'),
                ] as $key => $label)
                    <button type="button" wire:click="$set('range', '{{ $key }}')"
                            @class([
                                'rounded px-3 py-1.5 text-xs font-semibold transition',
                                'bg-white text-ink-900 shadow-xs' => $range === $key,
                                'text-ink-600' => $range !== $key,
                            ])>{{ $label }}</button>
                @endforeach
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.stat :label="__('app.dashboard.total_cost')" :value="$this->summary['spend']->format(false)"
                   icon="money" />
        <x-ui.stat :label="__('app.nav.deliveries')" :value="$this->summary['count']" icon="package" />
        <x-ui.stat :label="__('app.dashboard.average_time')"
                   :value="$this->summary['average_minutes'] !== null
                       ? $this->summary['average_minutes'].' '.__('app.common.minutes')
                       : '—'" icon="clock" />
        <x-ui.stat :label="__('finance.settlement.open')"
                   :value="$this->summary['outstanding']->format(false)" icon="receipt" tone="amber" />
    </div>

    <x-ui.card class="mt-5" :title="__('app.nav.finance')" flush>
        @if ($this->entries->isEmpty())
            <x-ui.empty icon="receipt" :title="__('app.common.empty')" />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.date') }}</th>
                            <th>{{ __('app.common.order') }}</th>
                            <th>{{ __('app.common.details') }}</th>
                            <th class="text-end">{{ __('app.common.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->entries as $entry)
                            <tr wire:key="{{ $entry->id }}">
                                <td class="tnum text-ink-500">
                                    {{ $entry->occurred_at->translatedFormat('d M H:i') }}
                                </td>
                                <td class="text-ink-700">{{ $entry->delivery?->order?->number ?? '—' }}</td>
                                <td class="text-ink-600">{{ $entry->category->label() }}</td>
                                <td @class([
                                    'tnum text-end font-medium',
                                    'text-emerald-700' => $entry->entry_type->value === 'credit',
                                    'text-ink-900' => $entry->entry_type->value === 'debit',
                                ])>
                                    {{ $entry->entry_type->value === 'credit' ? '+' : '−' }}{{ $entry->amount()->format(false) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</div>
