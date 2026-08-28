<div>
    <x-ui.page-header :title="__('app.nav.settlements')" :subtitle="__('app.nav.finance')" />

    <x-ui.card class="mb-4" :title="__('app.common.create')">
        <div class="grid gap-3 sm:grid-cols-4">
            <x-ui.field :label="__('form.period_start')" name="periodStart">
                <input type="date" wire:model="periodStart" class="field-input tnum">
            </x-ui.field>
            <x-ui.field :label="__('form.period_end')" name="periodEnd">
                <input type="date" wire:model="periodEnd" class="field-input tnum">
            </x-ui.field>
            <div class="flex items-end">
                <x-ui.button wire:click="generate" wire:loading.attr="disabled" icon="receipt">
                    {{ __('app.common.create') }}
                </x-ui.button>
            </div>
            <x-ui.field :label="__('app.common.status')">
                <select wire:model.live="status" class="field-input">
                    <option value="">{{ __('app.common.all') }}</option>
                    @foreach ($statuses as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </x-ui.field>
        </div>
    </x-ui.card>

    <x-ui.card flush>
        @if ($settlements->isEmpty())
            <x-ui.empty icon="receipt" :title="__('app.common.empty')" />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.order') }}</th>
                            <th>{{ __('finance.account.delivery_company') }}</th>
                            <th>{{ __('app.common.date') }}</th>
                            <th class="text-end">{{ __('app.nav.deliveries') }}</th>
                            <th class="text-end">{{ __('app.common.total') }}</th>
                            <th>{{ __('app.common.status') }}</th>
                            <th class="text-end">{{ __('app.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($settlements as $settlement)
                            @php $party = $settlement->party(); @endphp
                            <tr wire:key="{{ $settlement->id }}">
                                <td>
                                    <a href="{{ route('admin.settlements.show', $settlement->reference) }}"
                                       wire:navigate class="font-mono text-xs font-medium text-signal-700 hover:underline">
                                        {{ $settlement->reference }}
                                    </a>
                                </td>
                                <td class="text-ink-100">
                                    {{ $party?->name ?? '—' }}
                                    <p class="text-2xs text-ink-400">{{ $settlement->party_type->label() }}</p>
                                </td>
                                <td class="tnum text-ink-300">
                                    {{ $settlement->period_start->translatedFormat('d M') }}
                                    – {{ $settlement->period_end->translatedFormat('d M') }}
                                </td>
                                <td class="tnum text-end">{{ $settlement->deliveries_count }}</td>
                                <td class="tnum text-end font-semibold">
                                    {{ $settlement->netPayable()->format(false) }}
                                </td>
                                <td>
                                    <x-ui.badge :tone="match ($settlement->status->value) {
                                        'paid' => 'green',
                                        'open' => 'amber',
                                        'locked' => 'blue',
                                        default => 'slate',
                                    }" dot>{{ $settlement->status->label() }}</x-ui.badge>
                                </td>
                                <td class="text-end">
                                    @if ($settlement->status->value !== 'paid')
                                        <x-ui.button variant="ghost" size="sm"
                                                     wire:click="markPaid('{{ $settlement->id }}')"
                                                     wire:confirm="{{ __('app.common.confirm') }}">
                                            {{ __('finance.settlement.paid') }}
                                        </x-ui.button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($settlements->hasPages())
                <div class="border-t border-white/10 px-4 py-3">{{ $settlements->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</div>
