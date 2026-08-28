<div>
    <x-ui.page-header :title="__('app.nav.settings')" :subtitle="config('platform.name')" />

    <form wire:submit="save" class="grid gap-5 xl:grid-cols-2">
        <x-ui.card :title="__('form.weights')" :subtitle="__('form.weights_hint')">
            <div class="space-y-4">
                @foreach ($weights as $key => $value)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label class="min-w-0 pe-2 text-xs font-semibold text-ink-700" for="w-{{ $key }}">
                                {{ __('offer.factor.'.$key) }}
                            </label>
                            <span class="tnum text-xs text-ink-500">
                                {{ number_format((float) $value, 2) }}
                            </span>
                        </div>
                        <input type="range" id="w-{{ $key }}" min="0" max="1" step="0.05"
                               wire:model.live="weights.{{ $key }}"
                               class="w-full accent-signal-600">
                    </div>
                @endforeach

                <p class="rounded-md bg-ink-50 px-3 py-2 text-2xs text-ink-500">
                    {{ __('form.weights_total') }}:
                    <span class="tnum font-semibold text-ink-800">
                        {{ number_format(array_sum(array_map('floatval', $weights)), 2) }}
                    </span>
                </p>
            </div>
        </x-ui.card>

        <x-ui.card :title="__('app.nav.deliveries')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field :label="__('form.offer_timeout')" name="offerTimeout"
                            :hint="__('form.offer_timeout_hint')">
                    <input type="number" min="30" max="600" step="10" wire:model="offerTimeout"
                           class="field-input tnum">
                </x-ui.field>
                <x-ui.field :label="__('form.companies_per_round')" name="companiesPerRound" hint="1–10">
                    <input type="number" min="1" max="10" wire:model="companiesPerRound" class="field-input tnum">
                </x-ui.field>
                <x-ui.field :label="__('form.max_rounds')" name="maxRounds" hint="1–10">
                    <input type="number" min="1" max="10" wire:model="maxRounds" class="field-input tnum">
                </x-ui.field>
                <x-ui.field :label="__('form.rider_offer_timeout')" name="riderOfferTimeout" hint="20–300s">
                    <input type="number" min="20" max="300" step="10" wire:model="riderOfferTimeout"
                           class="field-input tnum">
                </x-ui.field>
            </div>
        </x-ui.card>

        <x-ui.card :title="__('app.nav.live')">
            <x-ui.field :label="__('form.ping_interval')" name="pingInterval"
                        :hint="__('form.ping_interval_hint')">
                <input type="number" min="5" max="120" step="5" wire:model="pingInterval" class="field-input tnum">
            </x-ui.field>

        </x-ui.card>

        <x-ui.card :title="__('app.nav.finance')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field :label="__('form.platform_fee')" name="platformFeeBps"
                            :hint="__('form.commission_hint')">
                    <input type="number" min="0" max="5000" step="50" wire:model="platformFeeBps"
                           class="field-input tnum">
                </x-ui.field>
                <x-ui.field :label="__('form.rider_share')" name="riderShareBps"
                            :hint="__('form.commission_hint')">
                    <input type="number" min="0" max="10000" step="100" wire:model="riderShareBps"
                           class="field-input tnum">
                </x-ui.field>
            </div>
        </x-ui.card>

        <div class="xl:col-span-2">
            <x-ui.button type="submit">{{ __('app.common.save') }}</x-ui.button>
        </div>
    </form>
</div>
