<div>
    <x-ui.page-header :title="__('app.dashboard.quick_create')" :subtitle="$this->tenantLabel()" />

    <form wire:submit="save" class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">

            <x-ui.card :title="__('delivery.labels.pickup')">
                @if ($this->savedAddresses->isNotEmpty())
                    <x-ui.field :label="__('form.saved_address')" class="mb-4">
                        <select wire:model.live="pickupAddressId" class="field-input">
                            <option value="">{{ __('app.common.none') }}</option>
                            @foreach ($this->savedAddresses as $address)
                                <option value="{{ $address->id }}">
                                    {{ $address->label ?? $address->address_line }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field :label="__('address.contact_name')" name="pickupName" required>
                        <input type="text" wire:model="pickupName" class="field-input">
                    </x-ui.field>
                    <x-ui.field :label="__('address.contact_phone')" name="pickupPhone" required>
                        <input type="tel" wire:model="pickupPhone" class="field-input tnum" dir="ltr"
                               inputmode="numeric" placeholder="01xxxxxxxxx">
                    </x-ui.field>
                    <x-ui.field :label="__('address.address_line')" name="pickupAddress" class="sm:col-span-2" required>
                        <input type="text" wire:model="pickupAddress" class="field-input">
                    </x-ui.field>
                    <x-ui.field :label="__('address.zone')" name="pickupZoneId">
                        <select wire:model.live="pickupZoneId" class="field-input">
                            <option value="">{{ __('app.common.none') }}</option>
                            @foreach ($this->zones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->displayName() }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card :title="__('delivery.labels.customer')">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field :label="__('address.contact_name')" name="dropoffName" required>
                        <input type="text" wire:model="dropoffName" class="field-input">
                    </x-ui.field>
                    <x-ui.field :label="__('address.contact_phone')" name="dropoffPhone" required>
                        <input type="tel" wire:model="dropoffPhone" class="field-input tnum" dir="ltr"
                               inputmode="numeric" placeholder="01xxxxxxxxx">
                    </x-ui.field>
                    <x-ui.field :label="__('address.address_line')" name="dropoffAddress" class="sm:col-span-2" required>
                        <input type="text" wire:model="dropoffAddress" class="field-input">
                    </x-ui.field>
                    <x-ui.field :label="__('address.zone')" name="dropoffZoneId" required>
                        <select wire:model.live="dropoffZoneId" class="field-input">
                            <option value="">{{ __('app.common.none') }}</option>
                            @foreach ($this->zones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->displayName() }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                    <x-ui.field :label="__('address.landmark')" name="dropoffLandmark">
                        <input type="text" wire:model="dropoffLandmark" class="field-input">
                    </x-ui.field>
                </div>

                {{-- Point at the door.

                     Most addresses here are landmarks rather than street
                     numbers, so a pin is both quicker to give and more useful
                     to the rider than anything typed into a field. --}}
                <div class="mt-4">
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <span class="field-label mb-0">{{ __('form.pin_location') }}</span>
                        @if ($dropoffLat !== null)
                            <span class="inline-flex items-center gap-1 text-2xs font-medium text-emerald-700">
                                <x-ui.icon name="check" class="size-3" />
                                {{ __('form.pin_done') }}
                            </span>
                        @else
                            <span class="text-2xs text-ink-400">{{ __('form.pin_hint') }}</span>
                        @endif
                    </div>

                    <x-ui.map
                style="dark"
                        :id="\App\Livewire\Business\Orders\CreateOrder::MAP_ID"
                        :markers="$this->mapConfig['markers']"
                        :route="$this->mapConfig['route']"
                        :zones="$this->mapConfig['zones']"
                        :height="280"
                        pickable
                        scroll-zoom
                        @map-picked="$wire.placeDropoff($event.detail.lat, $event.detail.lng)" />
                </div>
            </x-ui.card>

            <x-ui.card :title="__('form.order_items')">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-ui.field :label="__('form.priority')" name="priority">
                        <select wire:model.live="priority" class="field-input">
                            @foreach ($priorities as $case)
                                <option value="{{ $case->value }}">{{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                    <x-ui.field :label="__('form.package_size')" name="packageSize">
                        <select wire:model.live="packageSize" class="field-input">
                            @foreach ($sizes as $case)
                                <option value="{{ $case->value }}">{{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                    <x-ui.field :label="__('form.payment_method')" name="paymentType">
                        <select wire:model.live="paymentType" class="field-input">
                            @foreach ($payments as $case)
                                <option value="{{ $case->value }}">{{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    @if ($paymentType === \App\Enums\PaymentType::CashOnDelivery->value)
                        <x-ui.field :label="__('form.cod_amount')" name="codAmount" required>
                            <input type="number" step="0.5" min="0" inputmode="decimal"
                                   wire:model.live.debounce.500ms="codAmount" class="field-input tnum">
                        </x-ui.field>
                    @endif

                    <x-ui.field :label="__('form.reference')" name="reference"
                                :hint="__('form.reference_hint')">
                        <input type="text" wire:model="reference" class="field-input">
                    </x-ui.field>

                    <x-ui.field :label="__('form.order_notes')" name="notes" class="sm:col-span-3">
                        <textarea wire:model="notes" rows="2" class="field-input"></textarea>
                    </x-ui.field>
                </div>
            </x-ui.card>
        </div>

        {{-- The live quote. Sticky so it stays visible as the form scrolls —
             the price is the thing the shop owner is watching. --}}
        <div class="lg:sticky lg:top-20 lg:self-start">
            <x-ui.card :title="__('delivery.labels.price')">
                @if ($this->quote === null)
                    <p class="py-6 text-center text-sm text-ink-400">
                        {{ __('address.zone') }} — {{ __('app.common.required') }}
                    </p>
                @else
                    <p class="tnum text-3xl font-bold tracking-tight text-white">
                        {{ $this->quote->total->format() }}
                    </p>

                    <dl class="mt-4 space-y-1.5 border-t border-white/5 pt-3 text-xs">
                        @foreach ($this->quote->visibleLines() as $line)
                            <div class="flex justify-between gap-3">
                                <dt class="text-ink-400">{{ $line->label }}</dt>
                                <dd class="tnum shrink-0 text-ink-100">{{ $line->amount->format(false) }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <dl class="mt-3 space-y-1.5 border-t border-white/5 pt-3 text-xs">
                        <div class="flex justify-between">
                            <dt class="text-ink-400">{{ __('delivery.labels.distance') }}</dt>
                            <dd class="tnum text-ink-100">
                                {{ number_format($this->quote->distanceMeters / 1000, 1) }} {{ __('app.common.km') }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-400">{{ __('delivery.labels.eta') }}</dt>
                            <dd class="tnum text-ink-100">
                                ~{{ $this->quote->estimatedMinutes }} {{ __('app.common.minutes') }}
                            </dd>
                        </div>
                    </dl>
                @endif

                <x-ui.button type="submit" size="lg" class="mt-5 w-full"
                             wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">{{ __('app.dashboard.quick_create') }}</span>
                    <span wire:loading wire:target="save">{{ __('app.common.loading') }}</span>
                </x-ui.button>

                <p class="mt-3 text-2xs leading-relaxed text-ink-400">
                    {{ __('app.tagline') }}
                </p>
            </x-ui.card>
        </div>
    </form>
</div>
