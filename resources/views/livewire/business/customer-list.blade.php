<div>
    <x-ui.page-header :title="__('app.nav.customers')" :subtitle="$this->tenantLabel()" />

    <x-ui.card class="mb-4">
        <x-ui.field :label="__('app.common.search')">
            <input type="search" wire:model.live.debounce.400ms="search" class="field-input"
                   placeholder="{{ __('app.common.name') }} / {{ __('app.common.phone') }}">
        </x-ui.field>
    </x-ui.card>

    <x-ui.card flush>
        @if ($customers->isEmpty())
            <x-ui.empty icon="users" :title="__('app.common.empty')" />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.name') }}</th>
                            <th>{{ __('app.common.phone') }}</th>
                            <th class="text-end">{{ __('app.nav.orders') }}</th>
                            <th class="text-end">{{ __('app.common.updated') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr wire:key="{{ $customer->id }}">
                                <td class="font-medium text-ink-900">{{ $customer->name }}</td>
                                <td class="tnum text-ink-600" dir="ltr">{{ $customer->phone }}</td>
                                <td class="tnum text-end">{{ $customer->orders_count }}</td>
                                <td class="tnum text-end text-ink-500">
                                    {{ $customer->last_ordered_at?->diffForHumans() ?? __('app.common.never') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($customers->hasPages())
                <div class="border-t border-ink-200 px-4 py-3">{{ $customers->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</div>
