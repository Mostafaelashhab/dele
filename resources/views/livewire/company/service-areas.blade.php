<div>
    <x-ui.page-header :title="__('app.nav.service_areas')" :subtitle="$this->tenantLabel()">
        <x-slot:actions>
            <x-ui.button wire:click="save">{{ __('app.common.save') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card flush>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('address.zone') }}</th>
                        <th class="text-center">{{ __('delivery.labels.pickup') }}</th>
                        <th class="text-center">{{ __('delivery.labels.dropoff') }}</th>
                        <th class="text-end">{{ __('pricing.component.zone_surcharge', ['zone' => '']) }}</th>
                        <th class="text-end">{{ __('delivery.labels.eta') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->zones as $zone)
                        <tr wire:key="{{ $zone->id }}">
                            <td>
                                <p class="font-medium text-white">{{ $zone->displayName() }}</p>
                                <p class="text-2xs text-ink-400">{{ $zone->code }}</p>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" wire:model="areas.{{ $zone->id }}.pickup"
                                       class="size-4 rounded border-white/15 text-signal-600">
                            </td>
                            <td class="text-center">
                                <input type="checkbox" wire:model="areas.{{ $zone->id }}.dropoff"
                                       class="size-4 rounded border-white/15 text-signal-600">
                            </td>
                            <td class="text-end">
                                <input type="number" step="0.5" min="0"
                                       wire:model="areas.{{ $zone->id }}.surcharge"
                                       class="field-input tnum ms-auto w-24 py-1 text-end">
                            </td>
                            <td class="tnum text-end text-ink-400">
                                {{ $zone->estimated_minutes }} {{ __('app.common.minutes') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
