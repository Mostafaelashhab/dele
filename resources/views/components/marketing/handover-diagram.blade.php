@props(['code' => '4821'])

@php
    /**
     * The handover, drawn.
     *
     * Three states of the same code: issued with the order, shown to the
     * customer while the parcel is moving, typed in by the rider at the door.
     * Built from real markup rather than an image so it stays sharp, respects
     * the reader's theme, and needs no asset pipeline.
     *
     * The arrows are the one part that has to flip for Arabic — everything
     * else is laid out with logical properties and flips itself.
     */
    $digits = str_split($code);

    $steps = [
        ['n' => 1, 'key' => 'issued'],
        ['n' => 2, 'key' => 'shown'],
        ['n' => 3, 'key' => 'entered'],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'relative']) }}>
    <div class="grid items-stretch gap-4 lg:grid-cols-[1fr_auto_1fr]">

        {{-- The customer's phone. --}}
        <figure class="flex flex-col rounded-2xl border border-ink-200 bg-white p-5 shadow-sm">
            <figcaption class="flex items-center gap-2">
                <span class="flex size-7 items-center justify-center rounded-lg bg-signal-100
                             text-xs font-bold text-signal-700">1</span>
                <span class="text-sm font-bold text-ink-900">{{ __('marketing.protection.code_title') }}</span>
            </figcaption>

            <div class="mt-4 flex-1 rounded-xl border-2 border-signal-600 bg-signal-50/60 p-4 text-center">
                <p class="text-2xs font-semibold uppercase tracking-wider text-signal-700">
                    {{ __('tracking.code.title') }}
                </p>
                <div class="mt-2.5 flex justify-center gap-1.5" dir="ltr">
                    @foreach ($digits as $digit)
                        <span class="tnum flex size-10 items-center justify-center rounded-lg border
                                     border-signal-300 bg-white text-xl font-bold text-signal-800">
                            {{ $digit }}
                        </span>
                    @endforeach
                </div>
                <p class="mt-3 text-xs leading-relaxed text-ink-600">
                    {{ __('tracking.code.body') }}
                </p>
            </div>
        </figure>

        {{-- The hand-off itself. Horizontal on wide screens, vertical when
             the columns stack, so the arrow never points sideways into a
             card that is now underneath it. --}}
        <div class="flex items-center justify-center lg:flex-col">
            <span class="h-px flex-1 bg-ink-200 lg:h-auto lg:w-px lg:flex-1"></span>
            <span class="mx-3 flex size-11 shrink-0 items-center justify-center rounded-full
                         border border-ink-200 bg-white text-ink-500 shadow-sm lg:mx-0 lg:my-3">
                <x-ui.icon name="chevron-end" class="size-5 rtl:rotate-180 lg:rotate-90 lg:rtl:rotate-90" />
            </span>
            <span class="h-px flex-1 bg-ink-200 lg:h-auto lg:w-px lg:flex-1"></span>
        </div>

        {{-- The rider's screen. --}}
        <figure class="flex flex-col rounded-2xl border border-ink-200 bg-white p-5 shadow-sm">
            <figcaption class="flex items-center gap-2">
                <span class="flex size-7 items-center justify-center rounded-lg bg-emerald-100
                             text-xs font-bold text-emerald-700">2</span>
                <span class="text-sm font-bold text-ink-900">{{ __('rider.proof.code_label') }}</span>
            </figcaption>

            <div class="mt-4 flex-1 rounded-xl border border-ink-200 bg-ink-50 p-4">
                <div class="flex gap-2" dir="ltr">
                    <span class="tnum flex-1 rounded-lg border border-ink-300 bg-white px-3 py-2.5
                                 text-center text-lg font-bold tracking-[0.3em] text-ink-800">
                        {{ $code }}
                    </span>
                    <span class="flex shrink-0 items-center rounded-lg bg-signal-600 px-3
                                 text-xs font-bold text-white">
                        {{ __('rider.proof.code_check') }}
                    </span>
                </div>

                <p class="mt-3 flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2.5
                          text-sm font-bold text-emerald-800">
                    <x-ui.icon name="check" class="size-4 shrink-0" />
                    {{ __('rider.proof.code_verified') }}
                </p>

                <p class="mt-3 text-xs leading-relaxed text-ink-500">
                    {{ __('rider.proof.code_hint') }}
                </p>
            </div>
        </figure>
    </div>

    <p class="mt-4 flex items-center justify-center gap-1.5 text-xs text-ink-500">
        <x-ui.icon name="shield" class="size-3.5 shrink-0" />
        {{ __('marketing.protection.diagram_hint') }}
    </p>
</div>
