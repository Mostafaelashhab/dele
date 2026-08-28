<x-layouts.public
    :title="__('app.tagline')"
    :description="__('marketing.hero_body')"
    :noindex="false"
    ground="dark">

    {{--
        The only indexable page, and the only place the product has to look
        like something before anyone can use it.

        Structure follows a single argument rather than a feature list: name
        the problem, show the mechanism, show what each of the three people
        involved gets out of it (the shop, its customer, the delivery
        company), answer what it costs, then handle the objections. A reader
        who stops anywhere in that sequence has still been told something
        complete.

        Visually the page is dark and the product is light, and that inversion
        is deliberate: every screen, chart, map and diagram here is the real
        interface, so a white surface on a near-black ground reads as a screen
        rather than as a decorated box. Ember is the hot accent and is
        rationed to roughly one pool per section; signal blue belongs to the
        product and its data.
    --}}

    <header x-data="{ scrolled: false, open: false }"
            @scroll.window="scrolled = window.scrollY > 24"
            :class="scrolled ? 'border-white/10 bg-ink-950/85 backdrop-blur' : 'border-transparent'"
            class="sticky top-0 z-40 border-b transition-colors">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-5">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
                <span class="flex size-8 items-center justify-center rounded-lg bg-ember-500 text-white">
                    <x-ui.icon name="truck" class="size-5" />
                </span>
                <span class="text-base font-bold tracking-tight text-white">{{ __('app.name') }}</span>
            </a>

            {{-- Short labels only: full section titles do not survive a
                 laptop-width header, and these are the questions people
                 actually arrive with. --}}
            <nav class="ms-auto hidden items-center gap-0.5 lg:flex">
                @foreach ([
                    ['href' => '#how', 'label' => __('marketing.how.title')],
                    ['href' => '#customer', 'label' => __('marketing.customer.eyebrow')],
                    ['href' => '#protection', 'label' => __('marketing.protection.eyebrow')],
                    ['href' => '#fees', 'label' => __('marketing.cost.eyebrow')],
                    ['href' => '#coverage', 'label' => __('marketing.zones.title')],
                    ['href' => '#companies', 'label' => __('marketing.companies.eyebrow')],
                    ['href' => '#faq', 'label' => __('marketing.faq.eyebrow')],
                ] as $link)
                    <a href="{{ $link['href'] }}"
                       class="rounded-md px-3 py-2 text-sm font-medium text-ink-400 transition
                              hover:bg-white/5 hover:text-white">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="ms-auto flex items-center gap-1.5 lg:ms-4">
                <a href="{{ route('locale.switch', ['locale' => app()->getLocale() === 'ar' ? 'en' : 'ar']) }}"
                   class="rounded-md px-2.5 py-1.5 text-xs font-semibold text-ink-400 transition hover:text-white">
                    {{ app()->getLocale() === 'ar' ? 'EN' : 'ع' }}
                </a>
                <a href="{{ route('login') }}"
                   class="hidden rounded-md px-3 py-2 text-sm font-semibold text-ink-300 transition
                          hover:text-white sm:block">
                    {{ __('marketing.cta_login') }}
                </a>
                <a href="{{ route('register') }}"
                   class="rounded-lg bg-white px-4 py-2.5 text-sm font-bold text-ink-950
                          transition hover:bg-ink-200">
                    {{ __('app.auth.register') }}
                </a>
            </div>
        </div>
    </header>

    <main class="bg-ink-950">
        {{-- ================================================================
             Hero
        ================================================================= --}}
        <section class="grid-field accent-pool relative overflow-hidden">
            <div class="relative z-10 mx-auto max-w-6xl px-5 pb-16 pt-12 lg:pb-24 lg:pt-20">
                <div class="grid items-center gap-14 lg:grid-cols-[1fr_1.1fr]">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15
                                     bg-white/5 px-3 py-1.5 text-xs font-semibold text-ink-200
                                     backdrop-blur">
                            <span class="relative flex size-1.5">
                                <span class="absolute inline-flex size-full animate-ping rounded-full
                                             bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex size-1.5 rounded-full bg-emerald-400"></span>
                            </span>
                            {{ __('marketing.badge') }}
                        </span>

                        <h1 class="mt-7 text-4xl font-bold leading-[1.1] tracking-tight text-white
                                   sm:text-5xl lg:text-[3.4rem]">
                            {{ __('marketing.headline.one') }}
                            <span class="text-ember-400">{{ __('marketing.headline.accent') }}</span>
                            {{ __('marketing.headline.two') }}
                        </h1>

                        <p class="mt-6 max-w-lg text-base leading-relaxed text-ink-300 lg:text-lg">
                            {{ __('marketing.hero_body') }}
                        </p>

                        <div class="mt-9 flex flex-wrap gap-3">
                            <a href="{{ route('register.business') }}"
                               class="inline-flex items-center gap-2 rounded-lg bg-ember-500 px-6 py-3.5
                                      text-base font-bold text-white shadow-lg shadow-ember-500/25
                                      transition hover:bg-ember-600">
                                {{ __('marketing.cta_business') }}
                                <x-ui.icon name="chevron-end" class="size-4 rtl:rotate-180" />
                            </a>
                            <a href="{{ route('register.company') }}"
                               class="inline-flex items-center gap-2 rounded-lg border border-white/20
                                      bg-white/5 px-6 py-3.5 text-base font-semibold text-white
                                      backdrop-blur transition hover:border-white/30 hover:bg-white/10">
                                {{ __('marketing.choose.company_cta') }}
                            </a>
                        </div>

                        {{-- Three columns is a third of a phone screen each, which
                             leaves these labels four words tall and ragged. On a
                             narrow screen they read as rows instead. --}}
                        <dl class="mt-11 grid gap-3 border-t border-white/10 pt-7 sm:grid-cols-3 sm:gap-6">
                            @foreach ([
                                ['v' => __('marketing.stat_one_value'), 'l' => __('marketing.stat_one_label')],
                                ['v' => __('marketing.stat_two_value'), 'l' => __('marketing.stat_two_label')],
                                ['v' => __('marketing.stat_three_value'), 'l' => __('marketing.stat_three_label')],
                            ] as $stat)
                                <div class="flex items-baseline gap-2.5 sm:block">
                                    <dt class="shrink-0 text-lg font-bold text-white">{{ $stat['v'] }}</dt>
                                    <dd class="text-xs leading-relaxed text-ink-400 sm:mt-1.5">{{ $stat['l'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    {{-- The hero visual: the real dispatch board, with live
                         status cards floating over it the way they would in
                         the running product. --}}
                    <div class="relative">
                        <x-marketing.browser-frame label="banha-delivery.app/company/offers">
                            <x-marketing.mock-dispatch />
                        </x-marketing.browser-frame>

                        {{-- The overhang past the frame is a wide-screen flourish. Within a
                             phone's 20px gutter it would be clipped by the section, so
                             on narrow screens the cards tuck inside the frame instead. --}}
                        <div class="float-card absolute -top-3 ltr:right-2 rtl:left-2
                                    sm:-top-4 sm:ltr:-right-4 sm:rtl:-left-4">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg
                                         bg-signal-100 text-signal-700">
                                <x-ui.icon name="pin" class="size-4" />
                            </span>
                            <div class="leading-tight">
                                <p class="text-[10px] text-ink-500">{{ __('marketing.float.location') }}</p>
                                <p class="text-xs font-bold text-ink-900">{{ __('marketing.float.location_value') }}</p>
                            </div>
                        </div>

                        <div class="float-card absolute -bottom-4 ltr:left-2 rtl:right-2
                                    sm:-bottom-5 sm:ltr:-left-6 sm:rtl:-right-6">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg
                                         bg-ember-100 text-ember-700">
                                <x-ui.icon name="clock" class="size-4" />
                            </span>
                            <div class="leading-tight">
                                <p class="text-[10px] text-ink-500">{{ __('marketing.float.eta') }}</p>
                                <p class="text-xs font-bold text-ink-900">{{ __('marketing.float.eta_value') }}</p>
                            </div>
                        </div>

                        <div class="float-card absolute bottom-16 hidden lg:flex ltr:-right-8 rtl:-left-8">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg
                                         bg-emerald-100 text-emerald-700">
                                <x-ui.icon name="check" class="size-4" />
                            </span>
                            <div class="leading-tight">
                                <p class="text-[10px] text-ink-500">{{ __('marketing.float.accepted') }}</p>
                                <p class="text-xs font-bold text-ink-900">{{ __('marketing.float.accepted_value') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================================================================
             01 · The problem.

             The page used to open straight into a solution, which meant a
             reader never got the "that is my day" moment that makes
             everything after it land. Both columns describe situations rather
             than quoting invented statistics.
        ================================================================= --}}
        <section id="problem" class="scroll-mt-20 border-t border-white/10">
            <div class="mx-auto max-w-6xl px-5 py-20">
                <x-marketing.section-head
                    index="01"
                    :eyebrow="__('marketing.problem.eyebrow')"
                    :title="__('marketing.problem.title')"
                    :body="__('marketing.problem.body')" />

                <div class="mt-12 grid gap-4 lg:grid-cols-2">
                    @foreach ([
                        ['key' => 'before', 'icon' => 'phone', 'tone' => 'dim'],
                        ['key' => 'after', 'icon' => 'check', 'tone' => 'live'],
                    ] as $side)
                        <div @class([
                            'rounded-2xl border p-6 sm:p-7',
                            'border-white/10 bg-white/[0.02]' => $side['tone'] === 'dim',
                            'border-emerald-500/30 bg-emerald-500/[0.06]' => $side['tone'] === 'live',
                        ])>
                            <p @class([
                                'flex items-center gap-2.5 text-sm font-bold',
                                'text-ink-400' => $side['tone'] === 'dim',
                                'text-emerald-300' => $side['tone'] === 'live',
                            ])>
                                <x-ui.icon :name="$side['icon']" class="size-4 shrink-0" />
                                {{ __('marketing.problem.'.$side['key'].'_title') }}
                            </p>

                            {{-- Paired rows: each line on one side answers the
                                 line at the same height on the other, so the
                                 two lists are read across rather than down. --}}
                            <ul class="mt-5 space-y-px">
                                @foreach (__('marketing.problem.'.$side['key']) as $line)
                                    <li @class([
                                        'flex min-h-14 items-center gap-3 rounded-lg px-3 py-2.5 text-sm leading-relaxed',
                                        'text-ink-400' => $side['tone'] === 'dim',
                                        'bg-white/[0.03] text-ink-200' => $side['tone'] === 'live',
                                    ])>
                                        <span @class([
                                            'size-1.5 shrink-0 rounded-full',
                                            'bg-ink-600' => $side['tone'] === 'dim',
                                            'bg-emerald-400' => $side['tone'] === 'live',
                                        ])></span>
                                        <span>{{ $line }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="how" class="scroll-mt-20 border-t border-white/10">
            <div class="mx-auto max-w-6xl px-5 py-20">
                <x-marketing.section-head
                    index="02"
                    :eyebrow="__('marketing.how.eyebrow')"
                    :title="__('marketing.how.title')"
                    :body="__('marketing.how.subtitle')" />

                <ol class="mt-14 grid gap-px overflow-hidden rounded-2xl border border-white/10
                           bg-white/10 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach (__('marketing.how.steps') as $stepIndex => $step)
                        {{-- One hairline grid: the gap-px over a lit background
                             draws the dividers, so there are no borders to
                             collide at the corners. --}}
                        <li class="group relative overflow-hidden bg-ink-950 p-7 transition hover:bg-white/[0.04]">
                            {{-- A large ghost numeral rather than the section
                                 marker's small one: these are steps in a
                                 sequence, and they should not read as another
                                 set of section numbers. --}}
                            <span class="tnum pointer-events-none absolute -top-2 select-none text-7xl
                                         font-bold leading-none text-white/[0.05]
                                         ltr:right-4 rtl:left-4"
                                  aria-hidden="true">{{ $stepIndex + 1 }}</span>

                            <span class="relative flex size-12 items-center justify-center rounded-xl
                                         border border-white/10 bg-white/5 text-ember-400
                                         transition group-hover:border-ember-500/40 group-hover:bg-ember-500/10">
                                <x-ui.icon :name="['receipt', 'search', 'check', 'pin'][$stepIndex] ?? 'package'"
                                           class="size-5" />
                            </span>

                            <h3 class="mt-5 text-base font-bold text-white">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-ink-400">{{ $step['body'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- ================================================================
             03 · What the shop gets.

             The old "features" grid was a bucket of five capabilities that
             answered no particular question. Same content, now anchored to
             the screen a shop owner spends their day in, and to the moment
             that actually sells it: the price forming while they type.
        ================================================================= --}}
        <section id="shop" class="scroll-mt-20 border-t border-white/10">
            <div class="mx-auto max-w-6xl px-5 py-20">
                <x-marketing.section-head
                    index="03"
                    :eyebrow="__('marketing.forshop.eyebrow')"
                    :title="__('marketing.forshop.title')"
                    :body="__('marketing.forshop.body')" />

                <div class="mt-12 grid gap-8 lg:grid-cols-[1fr_1.05fr] lg:items-start">
                    <div class="lg:sticky lg:top-24">
                        <x-marketing.browser-frame label="banha-delivery.app/business/orders/create">
                            <x-marketing.mock-order-form />
                        </x-marketing.browser-frame>
                        <p class="mt-3 text-center text-xs text-ink-400">
                            {{ __('marketing.forshop.screen_caption') }}
                        </p>
                    </div>

                    <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        @foreach (__('marketing.features.items') as $itemIndex => $item)
                            @php
                                $accent = ['ember', 'signal', 'emerald', 'ember', 'signal'][$itemIndex] ?? 'signal';
                                $icons = ['bell', 'money', 'pin', 'receipt', 'code'];
                            @endphp

                            <li class="panel panel-hover flex gap-4 p-5">
                                <span @class([
                                    'flex size-10 shrink-0 items-center justify-center rounded-xl border',
                                    'border-ember-500/30 bg-ember-500/10 text-ember-400' => $accent === 'ember',
                                    'border-signal-500/30 bg-signal-500/10 text-signal-300' => $accent === 'signal',
                                    'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' => $accent === 'emerald',
                                ])>
                                    <x-ui.icon :name="$icons[$itemIndex] ?? 'package'" class="size-5" />
                                </span>
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-white">{{ $item['title'] }}</h3>
                                    <p class="mt-1.5 text-sm leading-relaxed text-ink-400">{{ $item['body'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        {{-- ================================================================
             04 · What the customer gets.

             The end customer had no section at all, even though their
             experience is the strongest thing a shop is actually buying — and
             the tracking screen they receive had been built and never shown.
        ================================================================= --}}
        <section id="customer" class="scroll-mt-20 border-t border-white/10">
            <div class="mx-auto max-w-6xl px-5 py-20">
                <x-marketing.section-head
                    index="04"
                    :eyebrow="__('marketing.customer.eyebrow')"
                    :title="__('marketing.customer.title')"
                    :body="__('marketing.customer.body')" />

                <div class="mt-12 grid gap-10 lg:grid-cols-[1.05fr_auto] lg:items-center">
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach (__('marketing.customer.points') as $pointIndex => $point)
                            <div class="panel p-5">
                                <span class="flex size-10 items-center justify-center rounded-xl
                                             border border-signal-500/30 bg-signal-500/10 text-signal-300">
                                    <x-ui.icon :name="['link', 'navigation', 'shield', 'clock'][$pointIndex] ?? 'pin'"
                                               class="size-5" />
                                </span>
                                <h3 class="mt-4 text-base font-bold text-white">{{ $point['title'] }}</h3>
                                <p class="mt-1.5 text-sm leading-relaxed text-ink-400">{{ $point['body'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    {{-- A phone frame, because this is the only screen on the
                         page that is never seen on a desktop. --}}
                    <div class="justify-self-center">
                        <x-marketing.phone-frame :width="260">
                            <x-marketing.mock-tracking />
                        </x-marketing.phone-frame>
                        <p class="mt-3 max-w-[16rem] text-center text-xs text-ink-400">
                            {{ __('marketing.customer.screen_caption') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section id="protection" class="scroll-mt-20 border-t border-white/10">
            <div class="mx-auto max-w-6xl px-5 py-20">
                <x-marketing.section-head
                    index="05"
                    :eyebrow="__('marketing.protection.eyebrow')"
                    :title="__('marketing.protection.title')"
                    :body="__('marketing.protection.subtitle')" />

                <div class="panel-lit mt-12 bg-ink-50 p-6 sm:p-8">
                    <x-marketing.handover-diagram />
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    @foreach ([
                        ['key' => 'code', 'icon' => 'shield', 'accent' => 'signal'],
                        ['key' => 'photo', 'icon' => 'camera', 'accent' => 'ember'],
                    ] as $method)
                        <article class="panel p-6">
                            <span @class([
                                'flex size-11 items-center justify-center rounded-xl border',
                                'border-signal-500/30 bg-signal-500/10 text-signal-300' => $method['accent'] === 'signal',
                                'border-ember-500/30 bg-ember-500/10 text-ember-400' => $method['accent'] === 'ember',
                            ])>
                                <x-ui.icon :name="$method['icon']" class="size-6" />
                            </span>

                            <h3 class="mt-4 text-lg font-bold text-white">
                                {{ __('marketing.protection.'.$method['key'].'_title') }}
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-ink-400">
                                {{ __('marketing.protection.'.$method['key'].'_body') }}
                            </p>

                            <ul class="mt-5 space-y-2.5 border-t border-white/10 pt-5">
                                @foreach (__('marketing.protection.'.$method['key'].'_points') as $point)
                                    <li class="flex items-start gap-2.5 text-sm leading-relaxed text-ink-300">
                                        <x-ui.icon name="check" @class([
                                            'mt-0.5 size-4 shrink-0',
                                            'text-signal-400' => $method['accent'] === 'signal',
                                            'text-ember-400' => $method['accent'] === 'ember',
                                        ]) />
                                        <span>{{ $point }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>

                <p class="mt-8 border-s-2 border-ember-500 ps-4 text-base font-semibold
                          leading-relaxed text-white">
                    {{ __('marketing.protection.closer') }}
                </p>
            </div>
        </section>

        {{-- ================================================================
             06 · Cost.

             The platform fee and the delivery tiers used to be two sections
             four apart, which meant "what will this cost me?" was answered
             twice and completely in neither place. They are two halves of one
             answer, so they are one section: the price of a delivery, then
             the platform's share of it.
        ================================================================= --}}
        {{-- ================================================================
             06 · Cost.

             The platform charges nothing, so this section's job is to say so
             plainly and then get out of the way. What remains is the delivery
             price itself, which is money between a shop and a delivery
             company and never passes through here.

             The fee table, the worked example and the fee-by-volume chart
             that used to live here are gone with the fee: a page that shows a
             12% line while charging 0% is worse than one that shows nothing.
        ================================================================= --}}
        <section id="fees" class="scroll-mt-20 border-t border-white/10">
            <div class="mx-auto max-w-6xl px-5 py-20">
                {{-- The free claim is conditional on the engine actually
                     charging nothing. If a fee is ever configured this section
                     states the rate instead, so the page cannot advertise
                     "free" while money is being taken. --}}
                <x-marketing.section-head
                    index="06"
                    :eyebrow="__('marketing.free.eyebrow')"
                    :title="$fees['charges'] ? __('marketing.fees.rate_label') : __('marketing.free.title')"
                    :body="$fees['charges'] ? __('marketing.fees.rate_note') : __('marketing.free.body')" />

                @if ($fees['charges'])
                    <p class="tnum mt-8 flex items-baseline gap-1">
                        <span class="text-6xl font-bold leading-none tracking-tight text-white">
                            {{ $fees['rate_percent'] }}
                        </span>
                        <span class="text-2xl font-bold text-ink-500">%</span>
                    </p>
                @endif

                <ul class="mt-12 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @unless ($fees['charges'])
                    @foreach (__('marketing.free.points') as $point)
                        <li class="panel p-5">
                            <span class="flex size-10 items-center justify-center rounded-xl
                                         border border-emerald-500/30 bg-emerald-500/10 text-emerald-300">
                                <x-ui.icon name="check" class="size-5" />
                            </span>
                            <h3 class="mt-4 text-base font-bold text-white">{{ $point['title'] }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-ink-400">{{ $point['body'] }}</p>
                        </li>
                    @endforeach
                    @endunless
                </ul>

                {{-- The one price on the page, and it is not the platform's.

                     Presented as a single scale rather than three cards with
                     badges and a button each: these are not plans anybody
                     subscribes to, they are a choice made per order, and
                     dressing them as pricing tiers made the section read as
                     "pick your package" immediately after the page said the
                     network is free. --}}
                <div class="mt-16 border-t border-white/10 pt-14">
                    <h3 class="text-lg font-bold text-white">{{ __('marketing.free.tiers_title') }}</h3>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-ink-300">
                        {{ __('marketing.free.tiers_lede') }}
                    </p>
                    <p class="mt-1.5 flex items-center gap-2 text-sm text-ink-400">
                        <x-ui.icon name="money" class="size-4 shrink-0 text-emerald-400" />
                        {{ __('marketing.free.tiers_goes_to') }}
                    </p>

                    {{-- One surface, three rows: slowest and cheapest at the
                         top, fastest and dearest at the bottom, so the eye
                         reads it as a single dial. --}}
                    <div class="mt-8 overflow-hidden rounded-2xl border border-white/10">
                        @foreach ($tiers as $tier)
                            @php $delta = $tier['delta_minor']; @endphp

                            <div @class([
                                'grid gap-x-6 gap-y-4 p-6 sm:grid-cols-[13rem_1fr] sm:items-start',
                                'border-t border-white/10' => ! $loop->first,
                                'bg-white/[0.04]' => $tier['default'],
                            ])>
                                <div class="flex items-start gap-3.5">
                                    <span @class([
                                        'flex size-10 shrink-0 items-center justify-center rounded-xl border',
                                        'border-ember-500/40 bg-ember-500/10 text-ember-400' => $tier['default'],
                                        'border-white/10 bg-white/5 text-ink-300' => ! $tier['default'],
                                    ])>
                                        <x-ui.icon :name="$tier['icon']" class="size-5" />
                                    </span>

                                    <div class="min-w-0">
                                        <p class="flex flex-wrap items-center gap-2">
                                            <span class="text-base font-bold text-white">{{ $tier['name'] }}</span>
                                            @if ($tier['default'])
                                                <span class="rounded-full bg-ember-500/15 px-2 py-0.5 text-2xs
                                                             font-bold text-ember-300">
                                                    {{ __('marketing.free.tier_default') }}
                                                </span>
                                            @endif
                                        </p>

                                        <p class="tnum mt-1.5 flex items-baseline gap-1.5">
                                            <span class="text-3xl font-bold tracking-tight text-white">
                                                {{ $tier['price'] }}
                                            </span>
                                            <span class="text-xs text-ink-400">
                                                {{ config('platform.currency.symbol') }}
                                            </span>
                                        </p>

                                        {{-- The comparison against the default
                                             is what turns three numbers into a
                                             scale you can read at a glance. --}}
                                        <p @class([
                                            'mt-1 text-xs font-semibold',
                                            'text-emerald-400' => $delta < 0,
                                            'text-ember-400' => $delta > 0,
                                            'text-ink-500' => $delta === 0,
                                        ])>
                                            @if ($delta === 0)
                                                {{ __('marketing.free.tier_same') }}
                                            @else
                                                {{ __(
                                                    $delta < 0 ? 'marketing.free.tier_cheaper' : 'marketing.free.tier_dearer',
                                                    ['amount' => number_format(abs($delta) / 100, 2).' '.config('platform.currency.symbol')]
                                                ) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="min-w-0">
                                    <p class="text-sm leading-relaxed text-ink-300">{{ $tier['body'] }}</p>

                                    <ul class="mt-3 grid gap-x-6 gap-y-2 sm:grid-cols-2">
                                        @foreach ($tier['points'] as $point)
                                            <li class="flex items-start gap-2 text-xs leading-relaxed text-ink-400">
                                                <x-ui.icon name="check" class="mt-0.5 size-3.5 shrink-0 text-emerald-400" />
                                                <span>{{ $point }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <p class="mt-4 text-xs leading-relaxed text-ink-400">
                        {{ __('marketing.pricing.note') }}
                    </p>

                    {{-- One call to action for the section, not one per row.
                         Three identical buttons is what made these look like
                         packages to choose between. --}}
                    <div class="mt-8">
                        <a href="{{ route('register.business') }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-ember-500 px-7 py-3.5
                                  text-sm font-bold text-white shadow-lg shadow-ember-500/25
                                  transition hover:bg-ember-600">
                            {{ __('marketing.cta_business') }}
                            <x-ui.icon name="chevron-end" class="size-4 rtl:rotate-180" />
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section id="coverage" class="scroll-mt-20 border-t border-white/10">
            <div class="mx-auto max-w-6xl px-5 py-20">
                <x-marketing.section-head
                    index="07"
                    :eyebrow="__('marketing.zones.eyebrow')"
                    :title="__('marketing.zones.title')"
                    :body="__('marketing.zones.diagram_hint')" />

                <div class="panel-lit mt-12 bg-ink-50 p-5 sm:p-7">
                    <x-marketing.coverage-explorer :zones="$zones" />
                </div>

                <div class="panel mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 px-5 py-4">
                    <p class="flex items-center gap-2 text-sm text-ink-300">
                        <x-ui.icon name="truck" class="size-4 shrink-0 text-ink-500" />
                        <span class="tnum font-semibold text-white">{{ $companyCount }}</span>
                        {{ __('app.nav.companies') }}
                    </p>
                    <p class="flex items-center gap-2 text-sm text-ink-300">
                        <x-ui.icon name="pin" class="size-4 shrink-0 text-ink-500" />
                        <span class="tnum font-semibold text-white">{{ $zones->count() }}</span>
                        {{ __('marketing.zones.total') }}
                    </p>
                    <p class="text-sm text-ink-400 sm:ms-auto">
                        {{ __('marketing.zones.outside') }}
                    </p>
                </div>
            </div>
        </section>

        <section id="companies" class="relative scroll-mt-20 overflow-hidden
                                        border-t border-ember-500/20 bg-ink-900/40">
            <span class="accent-pool pointer-events-none absolute inset-0" aria-hidden="true"></span>

            <div class="relative mx-auto max-w-6xl px-5 py-20 lg:py-24">
                <div class="grid gap-12 lg:grid-cols-[1.05fr_1fr] lg:items-start">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="section-index">08</span>
                            <span class="h-px w-8 bg-white/15" aria-hidden="true"></span>
                            <p class="text-xs font-bold uppercase tracking-widest text-ember-400">
                                {{ __('marketing.companies.eyebrow') }}
                            </p>
                        </div>

                        <h2 class="mt-4 text-4xl font-bold leading-[1.12] tracking-tight text-white lg:text-5xl">
                            {{ __('marketing.companies.headline_one') }}
                            <span class="block text-ember-400">{{ __('marketing.companies.headline_two') }}</span>
                        </h2>

                        <p class="mt-6 max-w-xl text-base leading-relaxed text-ink-300 lg:text-lg">
                            {{ __('marketing.companies.body') }}
                        </p>

                        {{-- On the network against outside it. --}}
                        <div class="mt-10 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl border border-emerald-500/25 bg-emerald-500/5 p-5">
                                <p class="flex items-center gap-2 text-sm font-bold text-emerald-300">
                                    <x-ui.icon name="check" class="size-4 shrink-0" />
                                    {{ __('marketing.companies.in_title') }}
                                </p>
                                <ul class="mt-4 space-y-2.5">
                                    @foreach (__('marketing.companies.in_points') as $point)
                                        <li class="text-sm leading-relaxed text-ink-300">{{ $point }}</li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="panel p-5">
                                <p class="flex items-center gap-2 text-sm font-bold text-ink-400">
                                    <x-ui.icon name="x" class="size-4 shrink-0" />
                                    {{ __('marketing.companies.out_title') }}
                                </p>
                                <ul class="mt-4 space-y-2.5">
                                    @foreach (__('marketing.companies.out_points') as $point)
                                        <li class="text-sm leading-relaxed text-ink-400">{{ $point }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <p class="mt-8 border-s-2 border-ember-500 ps-4 text-base font-semibold
                                  leading-relaxed text-white">
                            {{ __('marketing.companies.closer') }}
                        </p>

                        <div class="mt-8">
                            <a href="{{ route('register.company') }}"
                               class="inline-flex items-center gap-2 rounded-lg bg-ember-500 px-7 py-4
                                      text-base font-bold text-white shadow-lg shadow-ember-500/25
                                      transition hover:bg-ember-600">
                                {{ __('marketing.companies.cta') }}
                                <x-ui.icon name="chevron-end" class="size-4 rtl:rotate-180" />
                            </a>
                            <p class="mt-3 text-xs text-ink-400">
                                {{ __('marketing.companies.cta_note') }}
                            </p>
                        </div>
                    </div>

                    {{-- The ranking panel: the live weights, shown to the
                         people being ranked by them. --}}
                    <div class="panel p-6 lg:sticky lg:top-24">
                        <h3 class="text-lg font-bold text-white">
                            {{ __('marketing.companies.ranking_title') }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-400">
                            {{ __('marketing.companies.ranking_body') }}
                        </p>

                        <ul class="mt-7 space-y-4">
                            @foreach ($rankingWeights as $factor)
                                <li>
                                    <div class="mb-1.5 flex items-baseline justify-between gap-3">
                                        <span class="min-w-0 text-sm font-medium text-ink-200">
                                            {{ $factor['label'] }}
                                        </span>
                                        <span class="tnum shrink-0 text-sm font-bold text-white">
                                            {{ $factor['percentage'] }}%
                                        </span>
                                    </div>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-white/10">
                                        {{-- Width has to be inline, but the colour stays on the
                                             theme tokens so a palette change reaches this bar. --}}
                                        <div @class([
                                                 'h-full rounded-full',
                                                 'bg-ember-500' => $loop->first,
                                                 'bg-signal-500' => ! $loop->first,
                                             ])
                                             style="width: {{ $factor['percentage'] }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <p class="mt-7 flex items-start gap-2 border-t border-white/10 pt-5 text-xs
                                  leading-relaxed text-ink-400">
                            <x-ui.icon name="shield" class="mt-0.5 size-3.5 shrink-0" />
                            {{ __('marketing.companies.ranking_note') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================================================================
             09 · Questions.

             Objection handling, which the page had none of. Every answer
             describes what the system does rather than promising something,
             so none of them can go stale independently of the product.
        ================================================================= --}}
        <section id="faq" class="scroll-mt-20 border-t border-white/10">
            <div class="mx-auto max-w-3xl px-5 py-20">
                <x-marketing.section-head
                    index="09"
                    align="center"
                    :eyebrow="__('marketing.faq.eyebrow')"
                    :title="__('marketing.faq.title')" />

                {{-- Native <details>: keyboard accessible, findable by the
                     browser's own in-page search, and needs no script. --}}
                <div class="mt-12 space-y-3">
                    @foreach (__('marketing.faq.items') as $item)
                        <details class="panel group px-5 py-4 [&[open]]:bg-white/[0.055]">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                                <span class="text-base font-semibold text-white">{{ $item['q'] }}</span>
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-full
                                             border border-white/15 text-ink-300 transition
                                             group-open:rotate-45 group-open:border-ember-500/50
                                             group-open:text-ember-400">
                                    <x-ui.icon name="plus" class="size-4" />
                                </span>
                            </summary>
                            <p class="mt-4 border-t border-white/10 pt-4 text-sm leading-relaxed text-ink-300">
                                {{ $item['a'] }}
                            </p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ================================================================
             10 · Act.

             The tracking lookup, the network's real counts and the two
             registration doors were three separate closing sections competing
             for the same scroll. One closing section instead: what the
             network is, what you can do without an account, and the two ways
             in.
        ================================================================= --}}
        <section id="track" class="grid-field accent-pool accent-pool-cool relative
                                   scroll-mt-20 overflow-hidden border-t border-white/10">
            <div class="relative z-10 mx-auto max-w-6xl px-5 py-20">
                <div class="text-center">
                    <p class="text-xs font-bold uppercase tracking-widest text-ember-400">
                        {{ __('marketing.network.eyebrow') }}
                    </p>
                    <h2 class="mx-auto mt-4 max-w-2xl text-3xl font-bold leading-snug tracking-tight
                               text-white lg:text-4xl">
                        {{ __('marketing.network.title') }}
                    </h2>
                </div>

                <dl class="mt-12 grid gap-px overflow-hidden rounded-2xl border border-white/10
                           bg-white/10 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($networkStats as $stat)
                        <div class="bg-ink-950 p-7 text-center">
                            <dt class="text-xs font-medium text-ink-400">{{ $stat['label'] }}</dt>
                            <dd class="tnum mt-2 text-4xl font-bold tracking-tight text-white">
                                {{ $stat['value'] }}
                            </dd>
                        </div>
                    @endforeach
                </dl>

                <div class="mt-14 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('register.business') }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-ember-500 px-8 py-4 text-base
                              font-bold text-white shadow-lg shadow-ember-500/25 transition hover:bg-ember-600">
                        {{ __('marketing.cta_business') }}
                        <x-ui.icon name="chevron-end" class="size-4 rtl:rotate-180" />
                    </a>
                    <a href="{{ route('register.company') }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-white/20 bg-white/5
                              px-8 py-4 text-base font-semibold text-white transition hover:bg-white/10">
                        {{ __('marketing.choose.company_cta') }}
                    </a>
                </div>
            </div>
        </section>

                {{-- The one thing on this page you can do without an account. --}}
                <div class="mx-auto mt-16 max-w-3xl border-t border-white/10 pt-14">
                    <div class="text-center">
                        <h3 class="text-xl font-bold text-white">{{ __('tracking.lookup.title') }}</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm text-ink-400">
                            {{ __('tracking.lookup.subtitle') }}
                        </p>
                    </div>

                <form method="POST" action="{{ route('tracking.lookup') }}" class="mt-8">
                    @csrf

                    <div class="panel-lit flex flex-col gap-2.5 p-2.5 sm:flex-row">
                        <input type="text" name="number" value="{{ old('number') }}"
                               placeholder="{{ __('tracking.lookup.number_placeholder') }}"
                               class="tnum min-w-0 flex-1 rounded-lg border-0 bg-transparent px-3 py-3
                                      text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none"
                               dir="ltr" required>

                        <span class="hidden w-px self-stretch bg-ink-200 sm:block"></span>

                        <input type="tel" name="phone" value="{{ old('phone') }}"
                               placeholder="{{ __('tracking.lookup.phone_placeholder') }}"
                               class="tnum min-w-0 flex-1 rounded-lg border-0 bg-transparent px-3 py-3
                                      text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none"
                               dir="ltr" inputmode="numeric" required>

                        <button type="submit"
                                class="shrink-0 rounded-lg bg-ember-500 px-6 py-3 text-sm font-bold
                                       text-white transition hover:bg-ember-600">
                            {{ __('tracking.lookup.submit') }}
                        </button>
                    </div>

                    @error('number')
                        <p class="mt-3 text-center text-sm font-medium text-red-300">{{ $message }}</p>
                    @enderror
                    @error('phone')
                        <p class="mt-3 text-center text-sm font-medium text-red-300">{{ $message }}</p>
                    @enderror

                    <p class="mt-4 text-center text-xs text-ink-400">{{ __('tracking.lookup.hint') }}</p>
                </form>
            </div>
        </section>

                </div>

                <div class="mt-16 flex flex-wrap justify-center gap-3 border-t border-white/10 pt-14">
                    <a href="{{ route('register.business') }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-ember-500 px-8 py-4 text-base
                              font-bold text-white shadow-lg shadow-ember-500/25 transition hover:bg-ember-600">
                        {{ __('marketing.cta_business') }}
                        <x-ui.icon name="chevron-end" class="size-4 rtl:rotate-180" />
                    </a>
                    <a href="{{ route('register.company') }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-white/20 bg-white/5
                              px-8 py-4 text-base font-semibold text-white transition hover:bg-white/10">
                        {{ __('marketing.choose.company_cta') }}
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-white/10 bg-ink-950">
        <div class="mx-auto max-w-6xl px-5 py-14">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-ember-500 text-white">
                            <x-ui.icon name="truck" class="size-5" />
                        </span>
                        <span class="text-base font-bold text-white">{{ __('app.name') }}</span>
                    </div>
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-ink-400">
                        {{ __('marketing.hero_body') }}
                    </p>
                </div>

                @foreach (__('marketing.footer.columns') as $column)
                    <div>
                        <p class="text-sm font-bold text-white">{{ $column['title'] }}</p>
                        <ul class="mt-4 space-y-2.5">
                            @foreach ($column['links'] as $link)
                                <li>
                                    <span class="text-sm text-ink-400">{{ $link }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach

                <div>
                    <p class="text-sm font-bold text-white">{{ __('marketing.footer.contact') }}</p>
                    <ul class="mt-4 space-y-3">
                        <li class="flex items-center gap-2.5 text-sm text-ink-400">
                            <x-ui.icon name="pin" class="size-4 shrink-0 text-ink-500" />
                            {{ __('app.city') }}، {{ __('marketing.footer.governorate') }}
                        </li>
                        <li class="flex items-center gap-2.5 text-sm text-ink-400">
                            <x-ui.icon name="money" class="size-4 shrink-0 text-ink-500" />
                            {{ config('platform.currency.code') }}
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-6">
                <p class="text-xs text-ink-400">
                    © {{ now()->year }} {{ __('app.name') }}. {{ __('marketing.footer.rights') }}
                </p>
                <nav class="flex gap-5 text-xs text-ink-400">
                    <a href="{{ route('login') }}" class="transition hover:text-white">
                        {{ __('marketing.cta_login') }}
                    </a>
                    <a href="{{ route('register') }}" class="transition hover:text-white">
                        {{ __('app.auth.register') }}
                    </a>
                </nav>
            </div>
        </div>
    </footer>
</x-layouts.public>
