{{--
    One role's manual.

    A numbered walkthrough with the real screens beside the steps that have
    one. The rail on the left is a table of contents that tracks where the
    reader is, because these are long by design — the point is completeness.
--}}
@php
    $tone = [
        'signal' => ['chip' => 'border-signal-500/30 bg-signal-500/10 text-signal-300', 'num' => 'text-signal-300', 'cta' => 'bg-signal-600 hover:bg-signal-700 shadow-signal-600/25', 'check' => 'text-signal-400'],
        'ember' => ['chip' => 'border-ember-500/30 bg-ember-500/10 text-ember-400', 'num' => 'text-ember-400', 'cta' => 'bg-ember-500 hover:bg-ember-600 shadow-ember-500/25', 'check' => 'text-ember-400'],
        'emerald' => ['chip' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300', 'num' => 'text-emerald-300', 'cta' => 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/25', 'check' => 'text-emerald-400'],
    ][$meta['accent']];
@endphp

<x-layouts.public
    :title="__('learn.'.$audience.'.label')"
    :description="__('learn.'.$audience.'.intro')"
    :noindex="false"
    ground="dark">

    <x-marketing.public-header />

    <main class="bg-ink-950">
        <section class="grid-field relative overflow-hidden border-b border-white/10">
            <div class="relative z-10 mx-auto max-w-6xl px-5 py-16">
                <a href="{{ route('learn') }}"
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-400
                          transition hover:text-white">
                    <x-ui.icon name="chevron-end" class="size-3.5 ltr:rotate-180 rtl:rotate-0" />
                    {{ __('learn.hub.back') }}
                </a>

                <div class="mt-6 flex items-start gap-4">
                    <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl border {{ $tone['chip'] }}">
                        <x-ui.icon :name="$meta['icon']" class="size-7" />
                    </span>
                    <div class="min-w-0">
                        <h1 class="text-3xl font-bold leading-tight tracking-tight text-white lg:text-4xl">
                            {{ __("learn.{$audience}.label") }}
                        </h1>
                        <p class="mt-1.5 text-base text-ink-400">{{ __("learn.{$audience}.tagline") }}</p>
                    </div>
                </div>

                <p class="mt-7 max-w-2xl text-base leading-relaxed text-ink-300">
                    {{ __("learn.{$audience}.intro") }}
                </p>
            </div>
        </section>

        <div class="mx-auto max-w-6xl px-5 py-16">
            <div class="grid gap-12 lg:grid-cols-[15rem_1fr] lg:items-start">

                {{-- Table of contents. Sticky on wide screens, because these
                     guides are long on purpose. --}}
                <nav class="hidden lg:sticky lg:top-24 lg:block" aria-label="{{ __('learn.hub.steps_word') }}">
                    <ol class="space-y-1">
                        @foreach ($steps as $step)
                            <li>
                                <a href="#step-{{ $step['number'] }}"
                                   class="flex items-start gap-3 rounded-lg px-3 py-2 text-sm
                                          text-ink-400 transition hover:bg-white/5 hover:text-white">
                                    <span class="tnum shrink-0 text-xs font-bold {{ $tone['num'] }}">
                                        {{ str_pad((string) $step['number'], 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span class="leading-snug">{{ $step['title'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </nav>

                <div class="min-w-0 space-y-4">
                    @foreach ($steps as $step)
                        <section id="step-{{ $step['number'] }}" class="panel scroll-mt-24 p-6 sm:p-8">
                            <div class="flex items-baseline gap-3">
                                <span class="tnum text-sm font-bold {{ $tone['num'] }}">
                                    {{ str_pad((string) $step['number'], 2, '0', STR_PAD_LEFT) }}
                                </span>
                                <span class="h-px flex-1 bg-white/10" aria-hidden="true"></span>
                            </div>

                            <h2 class="mt-4 text-xl font-bold leading-snug text-white lg:text-2xl">
                                {{ $step['title'] }}
                            </h2>
                            <p class="mt-3 text-base leading-relaxed text-ink-300">{{ $step['body'] }}</p>

                            <ul class="mt-5 grid gap-2.5 border-t border-white/10 pt-5 sm:grid-cols-2">
                                @foreach ($step['points'] as $point)
                                    <li class="flex items-start gap-2.5 text-sm leading-relaxed text-ink-300">
                                        <x-ui.icon name="check" class="mt-0.5 size-4 shrink-0 {{ $tone['check'] }}" />
                                        <span>{{ $point }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            {{-- The real screen, where this step has one. --}}
                            @if ($step['screen'])
                                <div class="mt-7 flex justify-center rounded-xl bg-ink-950/60 p-5">
                                    @if (in_array($step['screen'], ['tracking', 'rider'], true))
                                        <x-marketing.phone-frame :width="240">
                                            @if ($step['screen'] === 'tracking')
                                                <x-marketing.mock-tracking />
                                            @else
                                                <x-marketing.mock-rider />
                                            @endif
                                        </x-marketing.phone-frame>
                                    @else
                                        <div class="w-full max-w-lg">
                                            <x-marketing.browser-frame
                                                :label="$step['screen'] === 'order'
                                                    ? 'banha.shop/app/orders/create'
                                                    : 'banha.shop/company/offers'">
                                                @if ($step['screen'] === 'order')
                                                    <x-marketing.mock-order-form />
                                                @else
                                                    <x-marketing.mock-dispatch />
                                                @endif
                                            </x-marketing.browser-frame>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </section>
                    @endforeach

                    {{-- The company guide gets the live ranking weights: "how
                         am I judged" is the question that decides whether they
                         sign up, and the answer has to be the real one. --}}
                    @if ($weights !== [])
                        <section class="panel p-6 sm:p-8">
                            <h2 class="text-lg font-bold text-white">
                                {{ __('marketing.companies.ranking_title') }}
                            </h2>
                            <p class="mt-2 text-sm leading-relaxed text-ink-400">
                                {{ __('marketing.companies.ranking_body') }}
                            </p>

                            <ul class="mt-6 space-y-4">
                                @foreach ($weights as $factor)
                                    <li>
                                        <div class="mb-1.5 flex items-baseline justify-between gap-3">
                                            <span class="min-w-0 text-sm text-ink-200">{{ $factor['label'] }}</span>
                                            <span class="tnum shrink-0 text-sm font-bold text-white">
                                                {{ $factor['percentage'] }}%
                                            </span>
                                        </div>
                                        <div class="h-1.5 overflow-hidden rounded-full bg-white/10">
                                            <div @class(['h-full rounded-full', 'bg-ember-500' => $loop->first, 'bg-signal-500' => ! $loop->first])
                                                 style="width: {{ $factor['percentage'] }}%"></div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>

                            <p class="mt-6 flex items-start gap-2 border-t border-white/10 pt-5 text-xs
                                      leading-relaxed text-ink-400">
                                <x-ui.icon name="shield" class="mt-0.5 size-3.5 shrink-0" />
                                {{ __('marketing.companies.ranking_note') }}
                            </p>
                        </section>
                    @endif

                    {{-- Act. --}}
                    <div class="panel p-6 text-center sm:p-8">
                        <a href="{{ route($meta['route']) }}"
                           class="inline-flex items-center gap-2 rounded-lg px-7 py-4 text-base
                                  font-bold text-white shadow-lg transition {{ $tone['cta'] }}">
                            {{ __("marketing.choose.{$audience}_cta") }}
                            <x-ui.icon name="chevron-end" class="size-4 rtl:rotate-180" />
                        </a>
                        <p class="mt-3 text-xs text-ink-400">
                            {{ __("marketing.choose.{$audience}_note") }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- The other three guides. --}}
            <div class="mt-16 border-t border-white/10 pt-10">
                <p class="text-sm font-semibold text-ink-400">{{ __('learn.hub.switch') }}</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach ($others as $other)
                        <a href="{{ route('learn.show', $other['key']) }}"
                           class="panel panel-hover flex items-center gap-3 p-4">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg
                                         border border-white/10 bg-white/5 text-ink-300">
                                <x-ui.icon :name="$other['icon']" class="size-4" />
                            </span>
                            <span class="min-w-0 truncate text-sm font-semibold text-white">
                                {{ __("learn.{$other['key']}.label") }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </main>

    <x-marketing.public-footer />
</x-layouts.public>
