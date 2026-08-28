<div>
    <x-ui.page-header :title="__('app.nav.companies')" :subtitle="__('app.intro.company_onboard')" />

    <form wire:submit="save" class="grid gap-5 xl:grid-cols-2">
        <x-ui.card :title="__('app.common.details')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field :label="__('app.common.name')" name="name" required>
                    <input type="text" wire:model="name" class="field-input">
                </x-ui.field>
                <x-ui.field :label="__('form.zone_name_ar')" name="nameAr">
                    <input type="text" wire:model="nameAr" class="field-input">
                </x-ui.field>
                <x-ui.field :label="__('app.auth.contact_name')" name="contactPerson" required>
                    <input type="text" wire:model="contactPerson" class="field-input">
                </x-ui.field>
                <x-ui.field :label="__('app.common.phone')" name="phone" required>
                    <input type="tel" wire:model="phone" class="field-input tnum" dir="ltr"
                           placeholder="01xxxxxxxxx">
                </x-ui.field>
                <x-ui.field :label="__('app.common.email')" name="email">
                    <input type="email" wire:model="email" class="field-input" dir="ltr">
                </x-ui.field>
                <x-ui.field :label="__('app.common.address')" name="addressLine">
                    <input type="text" wire:model="addressLine" class="field-input">
                </x-ui.field>
            </div>
        </x-ui.card>

        <x-ui.card :title="__('app.nav.settlements')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field :label="__('form.commission')" name="commissionBps"
                            :hint="__('form.commission_hint')">
                    <input type="number" min="0" max="5000" step="50" wire:model="commissionBps"
                           class="field-input tnum">
                </x-ui.field>
                <x-ui.field :label="__('form.settlement_period')" name="settlementPeriod">
                    <select wire:model="settlementPeriod" class="field-input">
                        @foreach ($periods as $period)
                            <option value="{{ $period->value }}">{{ $period->label() }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
                <x-ui.field :label="__('form.max_concurrent_company')" name="maxConcurrent">
                    <input type="number" min="1" max="500" wire:model="maxConcurrent" class="field-input tnum">
                </x-ui.field>
                <label class="flex items-start gap-2 self-end pb-2 text-sm text-ink-200">
                    <input type="checkbox" wire:model="autoAccept"
                           class="mt-0.5 size-4 rounded border-white/15 text-signal-600">
                    <span>
                        <span class="block font-medium">{{ __('form.auto_assign') }}</span>
                        <span class="block text-xs text-ink-400">{{ __('form.auto_assign_hint') }}</span>
                    </span>
                </label>
            </div>
        </x-ui.card>

        <x-ui.card :title="__('app.nav.service_areas')">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                @foreach ($this->zones as $zone)
                    <label class="flex items-center gap-2 rounded-md border border-white/10 px-2.5 py-2 text-sm">
                        <input type="checkbox" value="{{ $zone->id }}" wire:model="zoneIds"
                               class="size-4 rounded border-white/15 text-signal-600">
                        <span class="truncate text-ink-200">{{ $zone->displayName() }}</span>
                    </label>
                @endforeach
            </div>
            @error('zoneIds') <p class="field-error">{{ $message }}</p> @enderror
        </x-ui.card>

        <x-ui.card :title="__('app.auth.login')">
            <label class="mb-4 flex items-center gap-2 text-sm text-ink-200">
                <input type="checkbox" wire:model.live="createLogin"
                       class="size-4 rounded border-white/15 text-signal-600">
                {{ __('account.role.company_owner') }}
            </label>

            @if ($createLogin)
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field :label="__('app.common.name')" name="ownerName" required>
                        <input type="text" wire:model="ownerName" class="field-input">
                    </x-ui.field>
                    <x-ui.field :label="__('app.auth.email')" name="ownerEmail" required>
                        <input type="email" wire:model="ownerEmail" class="field-input" dir="ltr">
                    </x-ui.field>
                    <x-ui.field :label="__('app.auth.password')" name="ownerPassword" class="sm:col-span-2" required>
                        <input type="text" wire:model="ownerPassword" class="field-input" dir="ltr">
                    </x-ui.field>
                </div>
            @endif
        </x-ui.card>

        <div class="xl:col-span-2">
            <x-ui.button type="submit" size="lg">{{ __('app.common.create') }}</x-ui.button>
        </div>
    </form>
</div>
