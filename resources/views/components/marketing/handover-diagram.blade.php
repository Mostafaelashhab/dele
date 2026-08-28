@props(['code' => '4821'])

@php
    /**
     * The handover, shown as it happens.
     *
     * This section's whole argument is that a code travels from the person
     * receiving a parcel to the person carrying it. Two cards and an arrow
     * describe that; letting the code make the journey demonstrates it — and a
     * reader understands the mechanism before finishing the sentence beneath.
     *
     * Both ends are the real interface: the customer's tracking panel and the
     * rider's entry field, at the sizes they actually appear. The animation is
     * one shared loop, and it settles on its finished state for anyone who has
     * asked for less motion.
     */
    $digits = str_split($code);
@endphp

<div {{ $attributes->merge(['class' => 'handover relative']) }}>
    <div class="grid items-center gap-4 lg:grid-cols-[1fr_auto_1fr]">

        {{-- The customer's phone. --}}
        <figure class="relative flex flex-col rounded-2xl border border-ink-200 bg-white p-5 shadow-sm">
            <figcaption class="flex items-center gap-2.5">
                <span class="flex size-7 shrink-0 items-center justify-center rounded-lg
                             bg-signal-100 text-xs font-bold text-signal-700">1</span>
                <span class="text-sm font-bold text-ink-900">
                    {{ __('marketing.protection.code_title') }}
                </span>
            </figcaption>

            <div class="mt-4 flex-1 rounded-xl border-2 border-signal-600 bg-signal-50/60 p-4 text-center">
                <p class="text-2xs font-semibold uppercase tracking-wider text-signal-700">
                    {{ __('tracking.code.title') }}
                </p>

                <div class="mt-2.5 flex justify-center gap-1.5" dir="ltr">
                    @foreach ($digits as $index => $digit)
                        {{-- Each digit lands a beat after the one before it,
                             so the code assembles rather than blinking on. --}}
                        <span class="tnum handover-digit flex size-10 items-center justify-center
                                     rounded-lg border border-signal-300 bg-white text-xl
                                     font-bold text-signal-800"
                              style="animation-delay: {{ $index * 90 }}ms">
                            {{ $digit }}
                        </span>
                    @endforeach
                </div>

                <p class="mt-3 text-xs leading-relaxed text-ink-600">
                    {{ __('tracking.code.body') }}
                </p>
            </div>
        </figure>

        {{-- The journey.

             On a wide screen the code crosses horizontally; when the columns
             stack it travels down instead, which is why the distance is a
             custom property set per breakpoint rather than a fixed value. --}}
        <div class="relative flex items-center justify-center py-2 lg:h-full lg:w-40 lg:py-0"
             aria-hidden="true">
            <svg class="absolute inset-0 h-full w-full" viewBox="0 0 160 60"
                 preserveAspectRatio="none" fill="none">
                <path class="handover-trace" d="M8 30 H152" stroke="var(--color-signal-400)"
                      stroke-width="2" stroke-linecap="round" stroke-dasharray="4 6" opacity=".55"/>
            </svg>

            <span class="handover-travel tnum relative rounded-lg bg-signal-600 px-3 py-1.5
                         text-sm font-bold tracking-[0.2em] text-white shadow-lg shadow-signal-600/30"
                  dir="ltr">{{ $code }}</span>

            <span class="absolute inset-x-0 bottom-0 text-center text-2xs font-semibold
                         uppercase tracking-wider text-ink-400 lg:bottom-2">
                {{ __('marketing.protection.eyebrow') }}
            </span>
        </div>

        {{-- The rider's screen. --}}
        <figure class="flex flex-col rounded-2xl border border-ink-200 bg-white p-5 shadow-sm">
            <figcaption class="flex items-center gap-2.5">
                <span class="flex size-7 shrink-0 items-center justify-center rounded-lg
                             bg-emerald-100 text-xs font-bold text-emerald-700">2</span>
                <span class="text-sm font-bold text-ink-900">
                    {{ __('rider.proof.code_label') }}
                </span>
            </figcaption>

            <div class="mt-4 flex-1 rounded-xl border border-ink-200 bg-ink-50 p-4">
                <div class="flex gap-2" dir="ltr">
                    <span class="relative flex-1 rounded-lg border border-ink-300 bg-white px-3 py-2.5
                                 text-center text-lg font-bold tracking-[0.3em] text-ink-800">
                        {{-- The field is empty until the code arrives in it. --}}
                        <span class="handover-typed tnum">{{ $code }}</span>
                    </span>

                    <span class="flex shrink-0 items-center rounded-lg bg-signal-600 px-3
                                 text-xs font-bold text-white">
                        {{ __('rider.proof.code_check') }}
                    </span>
                </div>

                <p class="handover-verified mt-3 flex items-center gap-2 rounded-lg bg-emerald-50
                          px-3 py-2.5 text-sm font-bold text-emerald-800">
                    <x-ui.icon name="check" class="size-4 shrink-0" />
                    {{ __('rider.proof.code_verified') }}
                </p>

                <p class="mt-3 text-xs leading-relaxed text-ink-500">
                    {{ __('rider.proof.code_hint') }}
                </p>
            </div>
        </figure>
    </div>

    <p class="mt-5 flex items-center justify-center gap-1.5 text-xs text-ink-500">
        <x-ui.icon name="shield" class="size-3.5 shrink-0" />
        {{ __('marketing.protection.diagram_hint') }}
    </p>
</div>
