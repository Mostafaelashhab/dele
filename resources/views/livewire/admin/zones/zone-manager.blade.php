<div>
    <x-ui.page-header :title="__('app.nav.zones')" :subtitle="config('platform.city')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="edit">{{ __('app.common.create') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Coverage, drawn. Overlaps and gaps are visible here and nowhere
         else; clicking places the centre of the zone being edited. --}}
    <x-ui.map
                style="dark"
        class="mb-4"
        :id="\App\Livewire\Admin\Zones\ZoneManager::MAP_ID"
        :zones="$this->mapConfig['zones']"
        :markers="$this->mapConfig['markers']"
        :height="380"
        :mobile-height="280"
        pickable
        scroll-zoom
        @map-picked="$wire.placeCentre($event.detail.lat, $event.detail.lng)" />

    <x-ui.card flush>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('app.common.name') }}</th>
                        <th>{{ __('app.common.details') }}</th>
                        <th class="text-end">{{ __('pricing.rule.base_fare') }}</th>
                        <th class="text-end">{{ __('delivery.labels.eta') }}</th>
                        <th class="text-end">{{ __('app.nav.companies') }}</th>
                        <th class="text-center">{{ __('app.common.status') }}</th>
                        <th class="text-end">{{ __('app.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->zones as $zone)
                        <tr wire:key="{{ $zone->id }}">
                            <td>
                                <p class="font-medium text-white">{{ $zone->name_ar }}</p>
                                <p class="text-2xs text-ink-400">{{ $zone->name }} · {{ $zone->code }}</p>
                            </td>
                            <td class="tnum font-mono text-2xs text-ink-400" dir="ltr">
                                {{ number_format($zone->centroid_latitude, 4) }},
                                {{ number_format($zone->centroid_longitude, 4) }}
                                · {{ $zone->radius_meters }}m
                            </td>
                            <td class="tnum text-end">{{ $zone->basePrice()->format(false) }}</td>
                            <td class="tnum text-end">{{ $zone->estimated_minutes }}</td>
                            <td class="tnum text-end">{{ $zone->delivery_companies_count }}</td>
                            <td class="text-center">
                                <button type="button" wire:click="toggle('{{ $zone->id }}')">
                                    <x-ui.badge :tone="$zone->is_active ? 'green' : 'slate'" dot>
                                        {{ $zone->is_active ? __('app.common.active') : __('app.common.inactive') }}
                                    </x-ui.badge>
                                </button>
                            </td>
                            <td class="text-end">
                                <x-ui.button variant="ghost" size="sm" wire:click="edit('{{ $zone->id }}')">
                                    {{ __('app.common.edit') }}
                                </x-ui.button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    @if ($editing)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-ink-950/50 p-4"
             wire:click.self="$set('editing', false)">
            <div class="w-full max-w-xl rounded-card bg-white p-5 shadow-xl">
                <h2 class="text-sm font-semibold text-white">{{ __('app.nav.zones') }}</h2>
                <form wire:submit="save" class="mt-4 grid gap-4 sm:grid-cols-2">
                    <x-ui.field :label="__('form.zone_name_en')" name="name" required>
                        <input type="text" wire:model="name" class="field-input" dir="ltr">
                    </x-ui.field>
                    <x-ui.field :label="__('form.zone_name_ar')" name="nameAr" required>
                        <input type="text" wire:model="nameAr" class="field-input">
                    </x-ui.field>
                    <x-ui.field :label="__('form.zone_code')" name="code" required>
                        <input type="text" wire:model="code" class="field-input font-mono" dir="ltr">
                    </x-ui.field>
                    <x-ui.field :label="__('form.zone_sort')" name="sortOrder">
                        <input type="number" min="0" wire:model="sortOrder" class="field-input tnum">
                    </x-ui.field>
                    <p class="sm:col-span-2 -mb-1 flex items-center gap-1.5 rounded-md bg-signal-50 px-2.5 py-1.5
                              text-2xs text-signal-900">
                        <x-ui.icon name="pin" class="size-3.5 shrink-0" />
                        {{ __('form.zone_centre_hint') }}
                    </p>

                    <x-ui.field label="Latitude" name="latitude" required>
                        <input type="number" step="0.0000001" wire:model="latitude" class="field-input tnum" dir="ltr">
                    </x-ui.field>
                    <x-ui.field label="Longitude" name="longitude" required>
                        <input type="number" step="0.0000001" wire:model="longitude" class="field-input tnum" dir="ltr">
                    </x-ui.field>
                    <x-ui.field :label="__('form.zone_radius')" name="radius" :hint="__('form.zone_radius_hint')">
                        <input type="number" min="100" step="100" wire:model="radius" class="field-input tnum">
                    </x-ui.field>
                    <x-ui.field :label="__('form.zone_base_price')" name="basePrice">
                        <input type="number" step="0.5" min="0" wire:model="basePrice" class="field-input tnum">
                    </x-ui.field>
                    <x-ui.field :label="__('form.zone_eta')" name="estimatedMinutes">
                        <input type="number" min="1" wire:model="estimatedMinutes" class="field-input tnum">
                    </x-ui.field>
                    <label class="flex items-center gap-2 self-end pb-2 text-sm text-ink-200">
                        <input type="checkbox" wire:model="active"
                               class="size-4 rounded border-white/15 text-signal-600">
                        {{ __('app.common.active') }}
                    </label>
                    <div class="flex gap-2 sm:col-span-2">
                        <x-ui.button type="submit" class="flex-1">{{ __('app.common.save') }}</x-ui.button>
                        <x-ui.button variant="secondary" wire:click="$set('editing', false)">
                            {{ __('app.common.cancel') }}
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
