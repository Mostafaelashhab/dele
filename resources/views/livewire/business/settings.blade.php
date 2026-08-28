<div>
    <x-ui.page-header :title="__('app.nav.settings')" :subtitle="$this->tenantLabel()" />

    <form wire:submit="save" class="grid gap-5 xl:grid-cols-2">
        <x-ui.card :title="__('app.auth.business_name')">
            <x-ui.image-upload
                class="mb-5 border-b border-ink-100 pb-5"
                property="logo"
                :label="__('business.media.logo')"
                :hint="__('business.media.logo_hint')"
                :current="app(\App\Domain\Tenancy\CurrentTenant::class)->businessOrFail()->mediaUrl('logo_path')"
                icon="store" />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field :label="__('app.common.name')" name="name" class="sm:col-span-2" required>
                    <input type="text" wire:model="name" class="field-input">
                </x-ui.field>
                <x-ui.field :label="__('app.auth.contact_name')" name="contactPerson" required>
                    <input type="text" wire:model="contactPerson" class="field-input">
                </x-ui.field>
                <x-ui.field :label="__('app.common.phone')" name="phone" required>
                    <input type="tel" wire:model="phone" class="field-input tnum" dir="ltr">
                </x-ui.field>
                <x-ui.field :label="__('app.common.email')" name="email">
                    <input type="email" wire:model="email" class="field-input" dir="ltr">
                </x-ui.field>
                <x-ui.field :label="__('address.zone')" name="defaultZoneId">
                    <select wire:model="defaultZoneId" class="field-input">
                        <option value="">{{ __('app.common.none') }}</option>
                        @foreach ($this->zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->displayName() }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
                <x-ui.field :label="__('app.common.address')" name="addressLine" class="sm:col-span-2">
                    <input type="text" wire:model="addressLine" class="field-input">
                </x-ui.field>
            </div>
        </x-ui.card>

        <x-ui.card :title="__('app.nav.deliveries')"
                   :subtitle="__('app.intro.business_matching')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field :label="__('form.default_priority')" name="defaultPriority">
                    <select wire:model="defaultPriority" class="field-input">
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field :label="__('form.matching_strategy')" name="matchingStrategy"
                            :hint="__('form.matching_strategy_hint')">
                    <select wire:model="matchingStrategy" class="field-input">
                        <option value="">{{ __('form.matching_balanced') }}</option>
                        <option value="cheapest">{{ __('form.matching_cheapest') }}</option>
                        <option value="fastest">{{ __('form.matching_fastest') }}</option>
                    </select>
                </x-ui.field>
            </div>
        </x-ui.card>

        <x-ui.card class="xl:col-span-2" :title="__('app.nav.companies')" flush>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('app.nav.companies') }}</th>
                        <th class="text-end">{{ __('app.dashboard.acceptance_rate') }}</th>
                        <th class="text-end">{{ __('app.dashboard.completion_rate') }}</th>
                        <th>{{ __('app.common.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->companies as $company)
                        <tr wire:key="{{ $company->id }}">
                            <td class="font-medium text-ink-900">{{ $company->displayName() }}</td>
                            <td class="tnum text-end">
                                {{ number_format($company->acceptanceRate() * 100, 0) }}%
                            </td>
                            <td class="tnum text-end">
                                {{ number_format($company->completionRate() * 100, 0) }}%
                            </td>
                            <td>
                                <select wire:model="preferences.{{ $company->id }}" class="field-input w-40 py-1">
                                    <option value="">{{ __('app.common.none') }}</option>
                                    <option value="preferred">{{ __('app.common.active') }}</option>
                                    <option value="blocked">{{ __('audit.action.suspended') }}</option>
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-ui.card>

        <div class="xl:col-span-2">
            <x-ui.button type="submit">{{ __('app.common.save') }}</x-ui.button>
        </div>
    </form>
</div>
