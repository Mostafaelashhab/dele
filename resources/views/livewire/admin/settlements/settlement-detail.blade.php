@php $settlement = $this->settlement; $party = $settlement->party(); @endphp

<div>
    <x-ui.page-header :title="$settlement->reference" :subtitle="$party?->name">
        <x-slot:actions>
            <x-ui.badge :tone="$settlement->status->value === 'paid' ? 'green' : 'amber'" dot>
                {{ $settlement->status->label() }}
            </x-ui.badge>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="space-y-5">
            <x-ui.card :title="__('app.common.total')">
                <p class="tnum text-3xl font-bold text-white">{{ $settlement->netPayable()->format() }}</p>
                <dl class="mt-4 space-y-2 border-t border-white/5 pt-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-400">{{ __('app.dashboard.revenue') }}</dt>
                        <dd class="tnum text-ink-100">{{ $settlement->gross()->format(false) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-400">{{ __('finance.category.adjustment') }}</dt>
                        <dd class="tnum text-ink-100">
                            {{ $settlement->adjustments_minor?->format(false) ?? '0.00' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-400">{{ __('finance.category.cod_collected') }}</dt>
                        <dd class="tnum text-ink-100">
                            {{ $settlement->cod_collected_minor?->format(false) ?? '0.00' }}
                        </dd>
                    </div>
                    <div class="flex justify-between border-t border-white/5 pt-2">
                        <dt class="text-ink-400">{{ __('app.nav.deliveries') }}</dt>
                        <dd class="tnum text-ink-100">{{ $settlement->deliveries_count }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-400">{{ __('app.common.date') }}</dt>
                        <dd class="tnum text-ink-100">
                            {{ $settlement->period_start->translatedFormat('d M') }}
                            – {{ $settlement->period_end->translatedFormat('d M Y') }}
                        </dd>
                    </div>
                </dl>

                @if ($settlement->status->value !== 'paid')
                    <div class="mt-4 space-y-2 border-t border-white/5 pt-4">
                        <x-ui.field :label="__('form.payment_reference')" name="paymentReference"
                                    :hint="__('form.payment_reference_hint')">
                            <input type="text" wire:model="paymentReference" class="field-input">
                        </x-ui.field>
                        <x-ui.button variant="success" class="w-full" wire:click="markPaid"
                                     wire:confirm="{{ __('app.common.confirm') }}">
                            {{ __('finance.settlement.paid') }}
                        </x-ui.button>
                    </div>
                @else
                    <p class="mt-4 border-t border-white/5 pt-3 text-xs text-ink-400">
                        {{ __('finance.settlement.paid') }} —
                        {{ $settlement->paid_at?->translatedFormat('d M Y g:i A') }}
                        @if ($settlement->payment_reference)
                            · {{ $settlement->payment_reference }}
                        @endif
                    </p>
                @endif
            </x-ui.card>
        </div>

        <x-ui.card class="xl:col-span-2" :title="__('app.nav.finance')" flush>
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
                                <td class="tnum text-ink-400">
                                    {{ $entry->occurred_at->translatedFormat('d M g:i A') }}
                                </td>
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
            </div>
        </x-ui.card>
    </div>
</div>
