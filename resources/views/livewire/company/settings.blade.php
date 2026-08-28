<div>
    <x-ui.page-header :title="__('app.nav.settings')" :subtitle="$this->tenantLabel()" />

    <form wire:submit="save" class="grid gap-5 xl:grid-cols-2">
        <x-ui.card :title="__('app.nav.settings')">
            <x-ui.image-upload
                class="mb-5 border-b border-white/5 pb-5"
                property="logo"
                :label="__('business.media.company_logo')"
                :hint="__('business.media.logo_hint')"
                :current="app(\App\Domain\Tenancy\CurrentTenant::class)->companyOrFail()->mediaUrl('logo_path')"
                icon="truck" />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field :label="__('app.auth.contact_name')" name="contactPerson" required>
                    <input type="text" wire:model="contactPerson" class="field-input">
                </x-ui.field>
                <x-ui.field :label="__('app.common.phone')" name="phone" required>
                    <input type="tel" wire:model="phone" class="field-input tnum" dir="ltr">
                </x-ui.field>
                <x-ui.field :label="__('app.common.email')" name="email">
                    <input type="email" wire:model="email" class="field-input" dir="ltr">
                </x-ui.field>
                <x-ui.field :label="__('app.common.address')" name="addressLine">
                    <input type="text" wire:model="addressLine" class="field-input">
                </x-ui.field>
            </div>
        </x-ui.card>

        <x-ui.card :title="__('app.nav.deliveries')">
            <div class="space-y-4">
                <label class="flex items-start gap-3">
                    <input type="checkbox" wire:model="autoAccept"
                           class="mt-0.5 size-4 rounded border-white/15 text-signal-600">
                    <span>
                        <span class="block text-sm font-medium text-white">
                            {{ __('form.auto_assign') }}
                        </span>
                        <span class="block text-xs text-ink-400">
                            {{ __('form.auto_assign_hint') }}
                        </span>
                    </span>
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field :label="__('form.max_concurrent_company')" name="maxConcurrent">
                        <input type="number" min="1" max="500" wire:model="maxConcurrent" class="field-input tnum">
                    </x-ui.field>
                    <x-ui.field :label="__('form.offer_timeout')" name="offerTimeout"
                                :hint="__('form.offer_timeout_hint')">
                        <input type="number" min="30" max="600" step="10" wire:model="offerTimeout"
                               class="field-input tnum">
                    </x-ui.field>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="xl:col-span-2" :title="__('form.working_hours')" flush>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('form.day') }}</th>
                        <th class="text-center">{{ __('form.closed') }}</th>
                        <th>{{ __('form.opens') }}</th>
                        <th>{{ __('form.closes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($days as $day)
                        <tr wire:key="{{ $day }}">
                            <td class="font-medium capitalize text-white">
                                {{ \Illuminate\Support\Carbon::parse('next '.$day)->translatedFormat('l') }}
                            </td>
                            <td class="text-center">
                                <input type="checkbox" wire:model="hours.{{ $day }}.closed"
                                       class="size-4 rounded border-white/15 text-red-600"
                                       title="{{ __('app.common.inactive') }}">
                            </td>
                            <td>
                                <input type="time" wire:model="hours.{{ $day }}.opens"
                                       class="field-input tnum w-32 py-1">
                            </td>
                            <td>
                                <input type="time" wire:model="hours.{{ $day }}.closes"
                                       class="field-input tnum w-32 py-1">
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
