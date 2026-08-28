<div>
    <x-ui.page-header :title="__('app.nav.deliveries')" :subtitle="$this->tenantLabel()" />

    <x-ui.card class="mb-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.field :label="__('app.common.search')">
                <input type="search" wire:model.live.debounce.400ms="search" class="field-input"
                       placeholder="{{ __('app.common.order') }}">
            </x-ui.field>

            <x-ui.field :label="__('app.common.status')">
                <select wire:model.live="status" class="field-input">
                    <option value="">{{ __('app.common.all') }}</option>
                    <option value="active">{{ __('app.dashboard.active_deliveries') }}</option>
                    @foreach ($statuses as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field :label="__('delivery.labels.rider')">
                <select wire:model.live="rider" class="field-input">
                    <option value="">{{ __('app.common.all') }}</option>
                    @foreach ($riders as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <div class="flex items-end">
                <x-ui.button variant="secondary" wire:click="resetFilters">
                    {{ __('app.common.reset') }}
                </x-ui.button>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card flush>
        @if ($deliveries->isEmpty())
            <x-ui.empty icon="package" :description="__('app.common.empty_hint')" />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.order') }}</th>
                            <th>{{ __('app.nav.businesses') }}</th>
                            <th>{{ __('delivery.labels.dropoff') }}</th>
                            <th>{{ __('delivery.labels.rider') }}</th>
                            <th>{{ __('app.common.status') }}</th>
                            <th class="text-end">{{ __('delivery.labels.payout') }}</th>
                            <th class="text-end">{{ __('app.common.created') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deliveries as $delivery)
                            <tr wire:key="{{ $delivery->id }}">
                                <td>
                                    <a href="{{ route('company.deliveries.show', $delivery->public_id) }}"
                                       wire:navigate class="font-medium text-signal-700 hover:underline">
                                        {{ $delivery->order->number }}
                                    </a>
                                </td>
                                <td class="text-ink-200">{{ $delivery->business->displayName() }}</td>
                                <td class="text-ink-200">{{ $delivery->order->dropoffSnapshot()->area ?? '—' }}</td>
                                <td class="text-ink-200">{{ $delivery->rider?->name ?? __('app.common.unassigned') }}</td>
                                <td>
                                    <x-ui.badge :tone="$delivery->status->tone()" dot>
                                        {{ $delivery->status->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="tnum text-end">{{ $delivery->companyPayout()->format(false) }}</td>
                                <td class="tnum text-end text-ink-400">
                                    {{ $delivery->created_at->translatedFormat('d M g:i A') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($deliveries->hasPages())
                <div class="border-t border-white/10 px-4 py-3">{{ $deliveries->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</div>
