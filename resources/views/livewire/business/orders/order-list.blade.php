<div>
    <x-ui.page-header :title="__('app.nav.orders')" :subtitle="$this->tenantLabel()">
        <x-slot:actions>
            <x-ui.button :href="route('business.orders.create')" icon="plus">
                {{ __('app.dashboard.quick_create') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card class="mb-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <x-ui.field :label="__('app.common.search')" class="lg:col-span-2">
                <input type="search" wire:model.live.debounce.400ms="search" class="field-input"
                       placeholder="{{ __('app.common.order') }} / {{ __('delivery.labels.customer') }}">
            </x-ui.field>
            <x-ui.field :label="__('app.common.status')">
                <select wire:model.live="status" class="field-input">
                    <option value="">{{ __('app.common.all') }}</option>
                    @foreach ($statuses as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </x-ui.field>
            <x-ui.field :label="__('app.common.from')">
                <input type="date" wire:model.live="from" class="field-input tnum">
            </x-ui.field>
            <x-ui.field :label="__('app.common.to')">
                <input type="date" wire:model.live="to" class="field-input tnum">
            </x-ui.field>
        </div>
        @if ($search || $status || $from || $to)
            <div class="mt-3 flex justify-end">
                <x-ui.button variant="ghost" size="sm" wire:click="resetFilters">
                    {{ __('app.common.reset') }}
                </x-ui.button>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card flush>
        @if ($orders->isEmpty())
            <x-ui.empty icon="package" :description="__('app.common.empty_hint')">
                <x-ui.button :href="route('business.orders.create')" size="sm" icon="plus">
                    {{ __('app.dashboard.quick_create') }}
                </x-ui.button>
            </x-ui.empty>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.order') }}</th>
                            <th>{{ __('delivery.labels.customer') }}</th>
                            <th>{{ __('delivery.labels.company') }}</th>
                            <th>{{ __('delivery.labels.rider') }}</th>
                            <th>{{ __('app.common.status') }}</th>
                            <th class="text-end">{{ __('delivery.labels.price') }}</th>
                            <th class="text-end">{{ __('app.common.created') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @php $delivery = $order->currentDelivery; @endphp
                            <tr wire:key="{{ $order->id }}">
                                <td>
                                    <a href="{{ route('business.orders.show', $order->number) }}" wire:navigate
                                       class="font-medium text-signal-700 hover:underline">
                                        {{ $order->number }}
                                    </a>
                                    @if ($order->reference)
                                        <p class="text-2xs text-ink-400">{{ $order->reference }}</p>
                                    @endif
                                </td>
                                <td>
                                    <p class="text-ink-800">{{ $order->dropoffSnapshot()->contactName }}</p>
                                    <p class="text-2xs text-ink-500">{{ $order->dropoffSnapshot()->area }}</p>
                                </td>
                                <td class="text-ink-700">
                                    {{ $delivery?->deliveryCompany?->displayName() ?? __('app.common.unassigned') }}
                                </td>
                                <td class="text-ink-700">{{ $delivery?->rider?->name ?? '—' }}</td>
                                <td>
                                    @if ($delivery)
                                        <x-ui.badge :tone="$delivery->status->tone()" dot>
                                            {{ $delivery->status->label() }}
                                        </x-ui.badge>
                                    @else
                                        <x-ui.badge>{{ $order->status->label() }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class="tnum text-end">{{ $delivery?->price()->format(false) ?? '—' }}</td>
                                <td class="tnum text-end text-ink-500">
                                    {{ $order->created_at->translatedFormat('d M H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="border-t border-ink-200 px-4 py-3">{{ $orders->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</div>
