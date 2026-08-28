<div>
    <x-ui.page-header :title="__('app.nav.addresses')" :subtitle="$this->tenantLabel()">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="edit">{{ __('app.common.create') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->addresses->isEmpty())
        <x-ui.card>
            <x-ui.empty icon="pin" :title="__('app.common.empty')">
                <x-ui.button size="sm" icon="plus" wire:click="edit">{{ __('app.common.create') }}</x-ui.button>
            </x-ui.empty>
        </x-ui.card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->addresses as $address)
                <x-ui.card wire:key="{{ $address->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-white">{{ $address->label }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-ink-300">{{ $address->composedLine() }}</p>
                            <p class="mt-1 text-xs text-ink-400">{{ $address->zone?->displayName() }}</p>
                            <p class="tnum mt-1 text-xs text-ink-400" dir="ltr">{{ $address->contact_phone }}</p>
                        </div>
                        @if ($address->is_default)
                            <x-ui.badge tone="blue">{{ __('app.common.active') }}</x-ui.badge>
                        @endif
                    </div>
                    <div class="mt-3 flex gap-1 border-t border-white/5 pt-3">
                        <x-ui.button variant="ghost" size="sm" wire:click="edit('{{ $address->id }}')">
                            {{ __('app.common.edit') }}
                        </x-ui.button>
                        <x-ui.button variant="ghost" size="sm" wire:click="delete('{{ $address->id }}')"
                                     wire:confirm="{{ __('app.common.confirm') }}"
                                     class="text-red-600 hover:bg-red-50">
                            {{ __('app.common.delete') }}
                        </x-ui.button>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif

    @if ($editing)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-ink-950/50 p-4"
             wire:click.self="$set('editing', false)">
            <div class="w-full max-w-lg rounded-card bg-white p-5 shadow-xl">
                <h2 class="text-sm font-semibold text-white">{{ __('app.nav.addresses') }}</h2>
                <form wire:submit="save" class="mt-4 grid gap-4 sm:grid-cols-2">
                    <x-ui.field :label="__('address.label')" name="label" required>
                        <input type="text" wire:model="label" class="field-input">
                    </x-ui.field>
                    <x-ui.field :label="__('address.zone')" name="zoneId" required>
                        <select wire:model="zoneId" class="field-input">
                            <option value="">{{ __('app.common.none') }}</option>
                            @foreach ($this->zones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->displayName() }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                    <x-ui.field :label="__('address.contact_name')" name="contactName" required>
                        <input type="text" wire:model="contactName" class="field-input">
                    </x-ui.field>
                    <x-ui.field :label="__('address.contact_phone')" name="contactPhone" required>
                        <input type="tel" wire:model="contactPhone" class="field-input tnum" dir="ltr">
                    </x-ui.field>
                    <x-ui.field :label="__('address.address_line')" name="addressLine" class="sm:col-span-2" required>
                        <input type="text" wire:model="addressLine" class="field-input">
                    </x-ui.field>
                    <x-ui.field :label="__('address.landmark')" name="landmark" class="sm:col-span-2">
                        <input type="text" wire:model="landmark" class="field-input">
                    </x-ui.field>
                    <label class="flex items-center gap-2 text-sm text-ink-200 sm:col-span-2">
                        <input type="checkbox" wire:model="isDefault"
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
