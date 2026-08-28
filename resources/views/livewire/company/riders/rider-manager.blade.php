<div>
    <x-ui.page-header :title="__('app.nav.riders')" :subtitle="$this->tenantLabel()">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="$set('creating', true)">
                {{ __('app.common.create') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card flush>
        @if ($this->riders->isEmpty())
            <x-ui.empty icon="users" :title="__('app.common.empty')" />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.name') }}</th>
                            <th>{{ __('form.vehicle_type') }}</th>
                            <th>{{ __('app.common.status') }}</th>
                            <th class="text-end">{{ __('app.dashboard.active_deliveries') }}</th>
                            <th class="text-end">{{ __('app.dashboard.completion_rate') }}</th>
                            <th class="text-end">{{ __('app.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->riders as $rider)
                            <tr wire:key="{{ $rider->id }}">
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <x-ui.avatar
                                            :src="$rider->mediaUrl('photo_path')"
                                            :name="$rider->name"
                                            size="sm"
                                            :tone="$rider->status->value === 'online' ? 'green' : 'neutral'" />
                                        <div class="min-w-0">
                                            <p class="font-medium text-ink-900">{{ $rider->name }}</p>
                                            <p class="tnum text-2xs text-ink-500" dir="ltr">{{ $rider->phone }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-ink-700">
                                    <x-ui.icon :name="match ($rider->vehicle_type->value) {
                                        'car' => 'car', 'van' => 'van',
                                        'bicycle' => 'bicycle', 'on_foot' => 'walk',
                                        default => 'motorcycle',
                                    }" class="me-1 inline size-3.5 text-ink-400" />
                                    {{ $rider->vehicle_type->label() }}
                                    @if ($rider->vehicle_identifier)
                                        <span class="tnum text-ink-400">· {{ $rider->vehicle_identifier }}</span>
                                    @endif
                                </td>
                                <td>
                                    <x-ui.badge :tone="$rider->status->tone()" dot>
                                        {{ $rider->status->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="tnum text-end">
                                    {{ $rider->active_deliveries_count }}/{{ $rider->max_concurrent_deliveries }}
                                </td>
                                <td class="tnum text-end">
                                    {{ $rider->completed_deliveries_count > 0
                                        ? number_format($rider->completionRate() * 100, 0).'%'
                                        : '—' }}
                                </td>
                                <td class="text-end">
                                    <x-ui.button variant="ghost" size="sm"
                                                 wire:click="toggleSuspension('{{ $rider->id }}')"
                                                 wire:confirm="{{ __('app.common.confirm') }}">
                                        {{ $rider->status->value === 'suspended'
                                            ? __('audit.action.reinstated')
                                            : __('audit.action.suspended') }}
                                    </x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    @if ($creating)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-ink-950/50 p-4"
             wire:click.self="$set('creating', false)">
            <div class="w-full max-w-lg rounded-card bg-white p-5 shadow-xl">
                <h2 class="text-sm font-semibold text-ink-900">{{ __('app.nav.riders') }}</h2>

                <form wire:submit="save" class="mt-4 grid gap-4 sm:grid-cols-2">
                    <x-ui.image-upload
                        class="sm:col-span-2"
                        property="photo"
                        shape="round"
                        icon="user"
                        :label="__('business.media.rider_photo')"
                        :hint="__('business.media.rider_photo_hint')" />

                    <x-ui.field :label="__('app.common.name')" name="name" required>
                        <input type="text" wire:model="name" class="field-input">
                    </x-ui.field>
                    <x-ui.field :label="__('app.common.phone')" name="phone" required>
                        <input type="tel" wire:model="phone" class="field-input tnum" dir="ltr"
                               placeholder="01xxxxxxxxx">
                    </x-ui.field>
                    <x-ui.field :label="__('form.vehicle_type')" name="vehicleType">
                        <select wire:model="vehicleType" class="field-input">
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->value }}">{{ $vehicle->label() }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                    <x-ui.field :label="__('form.vehicle_plate')" name="vehicleIdentifier"
                                :hint="__('app.common.optional')">
                        <input type="text" wire:model="vehicleIdentifier" class="field-input">
                    </x-ui.field>
                    <x-ui.field :label="__('form.max_concurrent_rider')" name="maxConcurrent">
                        <input type="number" min="1" max="10" wire:model="maxConcurrent" class="field-input tnum">
                    </x-ui.field>

                    <label class="flex items-center gap-2 self-end pb-2 text-sm text-ink-700">
                        <input type="checkbox" wire:model.live="createLogin"
                               class="size-4 rounded border-ink-300 text-signal-600">
                        {{ __('form.create_login') }}
                    </label>

                    @if ($createLogin)
                        <x-ui.field :label="__('app.auth.email')" name="email" required>
                            <input type="email" wire:model="email" class="field-input" dir="ltr">
                        </x-ui.field>
                        <x-ui.field :label="__('app.auth.password')" name="password" required>
                            <input type="text" wire:model="password" class="field-input" dir="ltr">
                        </x-ui.field>
                    @endif

                    <div class="flex gap-2 sm:col-span-2">
                        <x-ui.button type="submit" class="flex-1">{{ __('app.common.save') }}</x-ui.button>
                        <x-ui.button variant="secondary" wire:click="$set('creating', false)">
                            {{ __('app.common.cancel') }}
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
