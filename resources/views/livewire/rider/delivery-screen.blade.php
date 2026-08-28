@php
    $delivery = $this->delivery;
    $order = $delivery->order;
    $pickup = $order->pickupSnapshot();
    $dropoff = $order->dropoffSnapshot();
    $assignment = $this->assignment;
    $isOffered = $assignment?->status === \App\Enums\AssignmentStatus::Offered;
    $action = $this->nextAction;
    $atDestination = $delivery->status === \App\Enums\DeliveryStatus::ArrivedAtDestination;
@endphp

<div class="flex min-h-dvh flex-col bg-ink-100"
     x-data="{ toast: null, confirm: null }"
     @rider-error.window="toast = $event.detail.message; setTimeout(() => toast = null, 4000)">

    <header class="safe-top sticky top-0 z-10 flex items-center gap-3 border-b border-ink-200 bg-white px-4 py-3">
        <a href="{{ route('rider.home') }}" wire:navigate class="-ms-1 p-1 text-ink-500">
            <x-ui.icon name="chevron-end" class="size-5 rotate-180 rtl:rotate-0" />
        </a>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold text-ink-900">{{ $order->number }}</p>
            <p class="truncate text-2xs text-ink-500">{{ $delivery->business->displayName() }}</p>
        </div>
        <x-ui.badge :tone="$delivery->status->tone()" dot>{{ $delivery->status->label() }}</x-ui.badge>
    </header>

    <main class="flex-1 space-y-3 p-4 pb-6">

        {{-- Payout first: it is the fact a rider decides on. --}}
        <div class="flex items-center justify-between rounded-card bg-ink-900 px-4 py-3 text-white">
            <div>
                <p class="text-2xs text-ink-400">{{ __('rider.app.payout') }}</p>
                <p class="tnum text-2xl font-bold">
                    {{ ($assignment?->payout() ?? $delivery->riderPayout())->format(false) }}
                    <span class="text-sm font-medium text-ink-400">{{ $delivery->currency }}</span>
                </p>
            </div>
            <div class="text-end">
                <p class="text-2xs text-ink-400">{{ __('delivery.labels.distance') }}</p>
                <p class="tnum text-lg font-semibold">
                    {{ number_format($delivery->distance_meters / 1000, 1) }} {{ __('app.common.km') }}
                </p>
            </div>
        </div>

        @if ($isOffered && $assignment?->expires_at)
            <div class="flex items-center justify-center gap-2 rounded-md bg-amber-50 px-4 py-2.5
                        ring-1 ring-inset ring-amber-200">
                <x-ui.icon name="clock" class="size-4 text-amber-700" />
                <span class="tnum text-sm font-semibold text-amber-900"
                      x-data="{ left: {{ $assignment->secondsRemaining() }} }"
                      x-init="setInterval(() => left > 0 && left--, 1000)">
                    <span x-text="left"></span>s
                </span>
            </div>
        @endif

        {{-- The leg, drawn.

             A rider glances at this once to orient themselves, then taps
             Navigate for turn-by-turn. It answers "which direction am I
             heading" faster than two addresses can. --}}
        @if ($this->hasMap())
            <x-ui.map
                :id="\App\Livewire\Rider\DeliveryScreen::MAP_ID"
                :markers="$this->mapConfig['markers']"
                :route="$this->mapConfig['route']"
                :height="180"
                :max-zoom="15" />
        @endif

        {{-- Pickup --}}
        <section class="rounded-card border border-ink-200 bg-white">
            <div class="flex items-center gap-2 border-b border-ink-100 px-4 py-2.5">
                <span class="flex size-6 items-center justify-center rounded-full bg-signal-100">
                    <x-ui.icon name="store" class="size-3.5 text-signal-700" />
                </span>
                <h2 class="text-xs font-bold uppercase tracking-wide text-ink-500">
                    {{ __('delivery.labels.pickup') }}
                </h2>
            </div>
            <div class="px-4 py-3">
                <p class="text-base font-semibold text-ink-900">{{ $pickup->contactName }}</p>
                <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $pickup->fullAddress() }}</p>
                @if ($pickup->landmark)
                    <p class="mt-1 text-xs text-ink-500">{{ $pickup->landmark }}</p>
                @endif

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <a href="tel:{{ $pickup->contactPhone }}"
                       class="touch-target flex items-center justify-center gap-2 rounded-md bg-ink-100
                              text-sm font-semibold text-ink-800">
                        <x-ui.icon name="phone" class="size-4" />
                        {{ __('rider.app.call_store') }}
                    </a>
                    @if ($pickup->hasCoordinates())
                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $pickup->latitude }},{{ $pickup->longitude }}"
                           target="_blank" rel="noopener noreferrer"
                           class="touch-target flex items-center justify-center gap-2 rounded-md bg-ink-100
                                  text-sm font-semibold text-ink-800">
                            <x-ui.icon name="navigation" class="size-4" />
                            {{ __('rider.app.navigate') }}
                        </a>
                    @endif
                </div>
            </div>
        </section>

        {{-- Customer --}}
        <section class="rounded-card border border-ink-200 bg-white">
            <div class="flex items-center gap-2 border-b border-ink-100 px-4 py-2.5">
                <span class="flex size-6 items-center justify-center rounded-full bg-emerald-100">
                    <x-ui.icon name="pin" class="size-3.5 text-emerald-700" />
                </span>
                <h2 class="text-xs font-bold uppercase tracking-wide text-ink-500">
                    {{ __('delivery.labels.customer') }}
                </h2>
            </div>
            <div class="px-4 py-3">
                <p class="text-base font-semibold text-ink-900">{{ $dropoff->contactName }}</p>
                <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $dropoff->fullAddress() }}</p>
                @if ($dropoff->landmark)
                    <p class="mt-1 text-xs text-ink-500">{{ $dropoff->landmark }}</p>
                @endif

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <a href="tel:{{ $dropoff->contactPhone }}"
                       class="touch-target flex items-center justify-center gap-2 rounded-md bg-ink-100
                              text-sm font-semibold text-ink-800">
                        <x-ui.icon name="phone" class="size-4" />
                        {{ __('rider.app.call_customer') }}
                    </a>
                    @if ($dropoff->hasCoordinates())
                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $dropoff->latitude }},{{ $dropoff->longitude }}"
                           target="_blank" rel="noopener noreferrer"
                           class="touch-target flex items-center justify-center gap-2 rounded-md bg-ink-100
                                  text-sm font-semibold text-ink-800">
                            <x-ui.icon name="navigation" class="size-4" />
                            {{ __('rider.app.navigate') }}
                        </a>
                    @endif
                </div>
            </div>
        </section>

        @if ($order->payment_type->requiresCollection())
            <div class="flex items-center justify-between rounded-card border-2 border-amber-300 bg-amber-50 px-4 py-3">
                <span class="text-sm font-bold text-amber-900">{{ __('rider.app.collect_cod') }}</span>
                <span class="tnum text-xl font-bold text-amber-900">
                    {{ $order->cod_amount_minor->format() }}
                </span>
            </div>
        @endif

        @if (filled($order->notes))
            <div class="rounded-card border border-ink-200 bg-white px-4 py-3">
                <p class="text-2xs font-semibold uppercase tracking-wide text-ink-500">
                    {{ __('delivery.labels.notes') }}
                </p>
                <p class="mt-1 text-sm leading-relaxed text-ink-700">{{ $order->notes }}</p>
            </div>
        @endif
    </main>

    {{-- The action bar. Never more than one primary action, always the same
         place, always thumb-sized. --}}
    <div class="safe-bottom sticky bottom-0 space-y-2 border-t border-ink-200 bg-white p-4">
        @if ($isOffered)
            <button type="button" wire:click="acceptAssignment"
                    class="touch-target w-full rounded-lg bg-emerald-600 text-lg font-bold text-white">
                {{ __('rider.app.accept') }}
            </button>
            <button type="button"
                    {{-- window.confirm, not confirm: a bare name is resolved
                         against Livewire's $wire proxy first, which answers
                         null for anything that is not a component property. --}}
                    x-on:click="window.confirm(@js(__('rider.app.confirm_reject'))) && $wire.rejectAssignment()"
                    class="touch-target w-full rounded-lg bg-ink-100 text-base font-semibold text-ink-700">
                {{ __('rider.app.reject') }}
            </button>

        @elseif ($atDestination)
            <form wire:submit="confirmDelivered" class="space-y-3">
                <x-ui.field :label="__('rider.app.received_by')" name="receivedBy">
                    <input type="text" wire:model="receivedBy" class="field-input"
                           placeholder="{{ $dropoff->contactName }}">
                </x-ui.field>

                @if ($order->payment_type->requiresCollection())
                    <x-ui.field :label="__('rider.app.collect_cod')" name="codCollected" required>
                        <input type="number" step="0.5" min="0" inputmode="decimal"
                               wire:model="codCollected" class="field-input tnum">
                    </x-ui.field>
                @endif

                {{-- Proof of delivery: the code first, because it is faster
                     at a doorstep and proves more — it shows the parcel
                     reached the person holding the tracking link, not just an
                     address. The photo stays as the fallback for when nobody
                     can read a code out. --}}
                <div class="rounded-xl border border-ink-200 bg-ink-50 p-3">
                    <p class="text-sm font-bold text-ink-900">{{ __('rider.proof.title') }}</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-ink-500">{{ __('rider.proof.subtitle') }}</p>

                    @if ($this->delivery()->confirmation_code_verified_at !== null)
                        <p class="mt-3 flex items-center gap-2 rounded-lg bg-emerald-50 p-3
                                  text-sm font-bold text-emerald-800">
                            <x-ui.icon name="check" class="size-4 shrink-0" />
                            {{ __('rider.proof.code_verified') }}
                        </p>
                    @elseif ($this->delivery()->confirmationCodeAvailable())
                        <div class="mt-3">
                            <label for="confirmationCode" class="field-label">
                                {{ __('rider.proof.code_label') }}
                            </label>
                            <p class="mb-1.5 text-xs leading-relaxed text-ink-500">
                                {{ __('rider.proof.code_hint') }}
                            </p>
                            <div class="flex gap-2">
                                <input type="text" id="confirmationCode" wire:model="confirmationCode"
                                       inputmode="numeric" autocomplete="one-time-code" dir="ltr"
                                       maxlength="{{ (int) config('platform.proof.code_digits', 4) }}"
                                       placeholder="{{ __('rider.proof.code_placeholder') }}"
                                       class="field-input tnum text-center text-2xl font-bold tracking-[0.4em]">
                                <button type="button" wire:click="verifyConfirmationCode"
                                        wire:loading.attr="disabled" wire:target="verifyConfirmationCode"
                                        class="touch-target shrink-0 rounded-lg bg-signal-600 px-4
                                               text-sm font-bold text-white disabled:opacity-60">
                                    {{ __('rider.proof.code_check') }}
                                </button>
                            </div>
                        </div>
                    @endif

                    @if ($codeFeedback !== null)
                        <p @class([
                            'mt-2 rounded-lg p-2.5 text-xs font-semibold leading-relaxed',
                            'bg-emerald-50 text-emerald-800' => $codeAccepted,
                            'bg-red-50 text-red-700' => ! $codeAccepted,
                        ])>{{ $codeFeedback }}</p>
                    @endif

                    <p class="my-3 flex items-center gap-3 text-xs font-semibold uppercase text-ink-400">
                        <span class="h-px flex-1 bg-ink-200"></span>
                        {{ __('rider.proof.or') }}
                        <span class="h-px flex-1 bg-ink-200"></span>
                    </p>

                    <span class="field-label">{{ __('rider.app.proof_photo') }}</span>
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.image-upload
                            property="proofPhoto"
                            icon="camera"
                            :max-edge="(int) config('platform.media.proof_max_edge')"
                            :hint="__('form.proof_primary')" />
                        <x-ui.image-upload
                            property="proofPhotoSecondary"
                            icon="camera"
                            :max-edge="(int) config('platform.media.proof_max_edge')"
                            :hint="__('form.proof_secondary')" />
                    </div>
                </div>

                <button type="submit"
                        wire:loading.attr="disabled"
                        class="touch-target w-full rounded-lg bg-emerald-600 text-lg font-bold text-white
                               disabled:opacity-60">
                    <span wire:loading.remove wire:target="confirmDelivered, proofPhoto, proofPhotoSecondary">
                        {{ __('rider.app.delivered') }}
                    </span>
                    <span wire:loading wire:target="confirmDelivered, proofPhoto, proofPhotoSecondary">
                        {{ __('app.common.loading') }}
                    </span>
                </button>
            </form>

            <details class="pt-1">
                <summary class="cursor-pointer py-2 text-center text-sm font-semibold text-red-700">
                    {{ __('rider.app.fail') }}
                </summary>
                <form wire:submit="reportFailure" class="mt-2 space-y-2">
                    <x-ui.field name="failureReason">
                        <input type="text" wire:model="failureReason" class="field-input"
                               placeholder="{{ __('rider.app.fail_reason') }}">
                    </x-ui.field>
                    <button type="submit"
                            class="touch-target w-full rounded-lg bg-red-600 text-base font-bold text-white">
                        {{ __('app.common.confirm') }}
                    </button>
                </form>
            </details>

        @elseif ($action)
            <button type="button"
                    @if ($action['confirm'])
                        x-on:click="window.confirm(@js($action['confirm'])) && $wire.{{ $action['method'] }}()"
                    @else
                        wire:click="{{ $action['method'] }}"
                    @endif
                    class="touch-target w-full rounded-lg bg-signal-600 text-lg font-bold text-white">
                {{ $action['label'] }}
            </button>

        @else
            <a href="{{ route('rider.home') }}" wire:navigate
               class="touch-target flex w-full items-center justify-center rounded-lg bg-ink-100
                      text-base font-semibold text-ink-700">
                {{ __('app.common.back') }}
            </a>
        @endif
    </div>

    <div x-show="toast" x-cloak x-transition
         class="fixed inset-x-4 bottom-32 z-50 rounded-md bg-red-700 px-4 py-3 text-sm text-white shadow-lg">
        <span x-text="toast"></span>
    </div>
</div>
