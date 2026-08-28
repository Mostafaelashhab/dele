<div>
    <x-ui.page-header :title="__('app.nav.orders')"
                      :subtitle="__('app.common.showing', ['count' => $deliveries->count(), 'total' => $deliveries->total()])" />

    <x-ui.card class="mb-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <x-ui.field :label="__('app.common.search')" class="lg:col-span-2">
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
            <x-ui.field :label="__('app.nav.businesses')">
                <select wire:model.live="business" class="field-input">
                    <option value="">{{ __('app.common.all') }}</option>
                    @foreach ($businesses as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
            </x-ui.field>
            <x-ui.field :label="__('app.nav.companies')">
                <select wire:model.live="company" class="field-input">
                    <option value="">{{ __('app.common.all') }}</option>
                    @foreach ($companies as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
            </x-ui.field>
            <div class="grid grid-cols-2 gap-2">
                <x-ui.field :label="__('app.common.from')">
                    <input type="date" wire:model.live="from" class="field-input tnum">
                </x-ui.field>
                <x-ui.field :label="__('app.common.to')">
                    <input type="date" wire:model.live="to" class="field-input tnum">
                </x-ui.field>
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <x-ui.button variant="ghost" size="sm" wire:click="resetFilters">
                {{ __('app.common.reset') }}
            </x-ui.button>
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
                            <th>{{ __('delivery.labels.customer') }}</th>
                            <th>{{ __('delivery.labels.company') }}</th>
                            <th>{{ __('delivery.labels.rider') }}</th>
                            <th>{{ __('app.common.status') }}</th>
                            <th class="text-end">{{ __('delivery.labels.price') }}</th>
                            <th class="text-end">{{ __('app.common.created') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deliveries as $delivery)
                            <tr wire:key="{{ $delivery->id }}">
                                <td>
                                    <a href="{{ route('admin.orders.show', $delivery->order->number) }}"
                                       wire:navigate class="font-medium text-signal-700 hover:underline">
                                        {{ $delivery->order->number }}
                                    </a>
                                </td>
                                <td class="text-ink-200">{{ $delivery->business->displayName() }}</td>
                                <td>
                                    <p class="text-ink-100">{{ $delivery->order->dropoffSnapshot()->contactName }}</p>
                                    <p class="text-2xs text-ink-400">{{ $delivery->order->dropoffSnapshot()->area }}</p>
                                </td>
                                <td class="text-ink-200">
                                    {{ $delivery->deliveryCompany?->displayName() ?? __('app.common.unassigned') }}
                                </td>
                                <td class="text-ink-200">{{ $delivery->rider?->name ?? '—' }}</td>
                                <td>
                                    <x-ui.badge :tone="$delivery->status->tone()" dot>
                                        {{ $delivery->status->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="tnum text-end">{{ $delivery->price()->format(false) }}</td>
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
