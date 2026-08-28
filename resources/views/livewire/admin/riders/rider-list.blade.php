<div wire:poll.30s>
    <x-ui.page-header :title="__('app.nav.riders')"
                      :subtitle="__('app.common.showing', ['count' => $riders->count(), 'total' => $riders->total()])" />

    <x-ui.card class="mb-4">
        <div class="grid gap-3 sm:grid-cols-3">
            <x-ui.field :label="__('app.common.search')">
                <input type="search" wire:model.live.debounce.400ms="search" class="field-input">
            </x-ui.field>
            <x-ui.field :label="__('app.common.status')">
                <select wire:model.live="status" class="field-input">
                    <option value="">{{ __('app.common.all') }}</option>
                    @foreach ($statuses as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
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
        </div>
    </x-ui.card>

    <x-ui.card flush>
        @if ($riders->isEmpty())
            <x-ui.empty icon="users" :title="__('app.common.empty')" />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.name') }}</th>
                            <th>{{ __('app.nav.companies') }}</th>
                            <th>{{ __('rider.vehicle.motorcycle') }}</th>
                            <th>{{ __('app.common.status') }}</th>
                            <th class="text-end">{{ __('app.dashboard.active_deliveries') }}</th>
                            <th class="text-end">{{ __('app.dashboard.completed_today') }}</th>
                            <th class="text-end">{{ __('app.tracking.last_updated') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($riders as $rider)
                            <tr wire:key="{{ $rider->id }}">
                                <td>
                                    <p class="font-medium text-white">{{ $rider->name }}</p>
                                    <p class="tnum text-2xs text-ink-400" dir="ltr">{{ $rider->phone }}</p>
                                </td>
                                <td class="text-ink-200">{{ $rider->deliveryCompany->displayName() }}</td>
                                <td class="text-ink-300">{{ $rider->vehicle_type->label() }}</td>
                                <td>
                                    <x-ui.badge :tone="$rider->status->tone()" dot>
                                        {{ $rider->status->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="tnum text-end">
                                    {{ $rider->active_deliveries_count }}/{{ $rider->max_concurrent_deliveries }}
                                </td>
                                <td class="tnum text-end">{{ $rider->completed_deliveries_count }}</td>
                                <td class="tnum text-end text-ink-400">
                                    {{ $rider->last_seen_at?->diffForHumans(short: true) ?? __('app.common.never') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($riders->hasPages())
                <div class="border-t border-white/10 px-4 py-3">{{ $riders->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</div>
