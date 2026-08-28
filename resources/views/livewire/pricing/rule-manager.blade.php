<div>
    <x-ui.page-header :title="__('app.nav.pricing')" :subtitle="$scopeLabel">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="newRule">{{ __('app.common.create') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card flush>
        @if ($this->rules->isEmpty())
            <x-ui.empty icon="money" :title="__('app.common.empty')"
                        :description="__('pricing.rule.base_fare')">
                <x-ui.button size="sm" icon="plus" wire:click="newRule">
                    {{ __('app.common.create') }}
                </x-ui.button>
            </x-ui.empty>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.name') }}</th>
                            <th>{{ __('form.rule_type') }}</th>
                            <th>{{ __('address.zone') }}</th>
                            <th class="text-end">{{ __('app.common.total') }}</th>
                            <th class="text-center">{{ __('app.common.status') }}</th>
                            <th class="text-end">{{ __('app.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->rules as $rule)
                            <tr wire:key="{{ $rule->id }}">
                                <td class="font-medium text-ink-900">{{ $rule->name }}</td>
                                <td class="text-ink-700">{{ $rule->type->label() }}</td>
                                <td class="text-ink-600">
                                    @if ($rule->pickupZone || $rule->dropoffZone)
                                        {{ $rule->pickupZone?->displayName() ?? '*' }}
                                        <span class="text-ink-300">→</span>
                                        {{ $rule->dropoffZone?->displayName() ?? '*' }}
                                    @else
                                        <span class="text-ink-400">{{ __('app.common.all') }}</span>
                                    @endif
                                </td>
                                <td class="tnum text-end">
                                    @if ($rule->rate_minor_per_km > 0)
                                        {{ number_format($rule->rate_minor_per_km / 100, 2) }}/{{ __('app.common.km') }}
                                    @elseif ($rule->percentage_bps != 0)
                                        {{ number_format($rule->percentage_bps / 100, 1) }}%
                                    @else
                                        {{ $rule->amount_minor?->format(false) ?? '0.00' }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" wire:click="toggleRule('{{ $rule->id }}')">
                                        <x-ui.badge :tone="$rule->is_active ? 'green' : 'slate'" dot>
                                            {{ $rule->is_active ? __('app.common.active') : __('app.common.inactive') }}
                                        </x-ui.badge>
                                    </button>
                                </td>
                                <td class="text-end">
                                    <div class="flex justify-end gap-1">
                                        <x-ui.button variant="ghost" size="sm" wire:click="editRule('{{ $rule->id }}')">
                                            {{ __('app.common.edit') }}
                                        </x-ui.button>
                                        <x-ui.button variant="ghost" size="sm"
                                                     wire:click="deleteRule('{{ $rule->id }}')"
                                                     wire:confirm="{{ __('app.common.confirm') }}"
                                                     class="text-red-600 hover:bg-red-50">
                                            {{ __('app.common.delete') }}
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    @if ($editing)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-ink-950/50 p-4"
             wire:click.self="$set('editing', false)">
            <div class="w-full max-w-2xl rounded-card bg-white p-5 shadow-xl">
                <h2 class="text-sm font-semibold text-ink-900">
                    {{ $ruleId ? __('app.common.edit') : __('app.common.create') }} — {{ __('app.nav.pricing') }}
                </h2>

                <form wire:submit="saveRule" class="mt-4 grid gap-4 sm:grid-cols-2">
                    <x-ui.field :label="__('form.rule_name')" name="ruleName" required>
                        <input type="text" wire:model="ruleName" class="field-input">
                    </x-ui.field>

                    <x-ui.field :label="__('form.rule_type')" name="ruleType" required>
                        <select wire:model.live="ruleType" class="field-input">
                            @foreach ($types as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field :label="__('form.rule_pickup_zone')" name="pickupZoneId">
                        <select wire:model="pickupZoneId" class="field-input">
                            <option value="">{{ __('app.common.all') }}</option>
                            @foreach ($this->pricingZones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->displayName() }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field :label="__('form.rule_dropoff_zone')" name="dropoffZoneId">
                        <select wire:model="dropoffZoneId" class="field-input">
                            <option value="">{{ __('app.common.all') }}</option>
                            @foreach ($this->pricingZones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->displayName() }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field :label="__('form.priority')" name="rulePriority">
                        <select wire:model="rulePriority" class="field-input">
                            <option value="">{{ __('app.common.all') }}</option>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field :label="__('form.package_size')" name="rulePackageSize">
                        <select wire:model="rulePackageSize" class="field-input">
                            <option value="">{{ __('app.common.all') }}</option>
                            @foreach ($sizes as $size)
                                <option value="{{ $size->value }}">{{ $size->label() }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field :label="__('form.rule_amount')" name="amount"
                                :hint="config('platform.currency.code')">
                        <input type="number" step="0.5" min="0" wire:model="amount" class="field-input tnum">
                    </x-ui.field>

                    <x-ui.field :label="__('form.rule_rate')" name="ratePerKm">
                        <input type="number" step="0.5" min="0" wire:model="ratePerKm" class="field-input tnum">
                    </x-ui.field>

                    <x-ui.field :label="__('form.rule_percentage')" name="percentageBps"
                                :hint="__('form.rule_percentage_hint')">
                        <input type="number" step="0.5" wire:model="percentageBps" class="field-input tnum">
                    </x-ui.field>

                    <x-ui.field :label="__('form.rule_free_distance')" name="freeUnits"
                                :hint="__('form.rule_free_distance_hint')">
                        <input type="number" min="0" step="100" wire:model="freeUnits" class="field-input tnum">
                    </x-ui.field>

                    <label class="flex items-center gap-2 self-end pb-2 text-sm text-ink-700 sm:col-span-2">
                        <input type="checkbox" wire:model="ruleActive"
                               class="size-4 rounded border-ink-300 text-signal-600">
                        {{ __('form.rule_active') }}
                    </label>

                    <div class="flex gap-2 sm:col-span-2">
                        <x-ui.button type="submit" class="flex-1">{{ __('app.common.save') }}</x-ui.button>
                        <x-ui.button variant="secondary" wire:click="resetRuleForm">
                            {{ __('app.common.cancel') }}
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
