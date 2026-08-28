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

    <x-marketing.public-header />

    <main class="bg-ink-950">

        {{-- ================================================================
             Hero
        ================================================================= --}}
        <section class="grid-field accent-pool relative overflow-hidden">
            <div class="relative z-10 mx-auto max-w-6xl px-5 pb-16 pt-12 lg:pb-24 lg:pt-20">
                <div class="grid items-center gap-14 lg:grid-cols-[1fr_1.1fr]">
                    <div>
                        {{-- The badge carries counts rather than a slogan.
                             "Live tracking for every shipment" is something
                             any site can print; the number of riders actually
                             available in this city right now is not. --}}
                        <span class="inline-flex flex-wrap items-center gap-x-2.5 gap-y-1 rounded-full
                                     border border-white/15 bg-white/5 px-3.5 py-2 text-xs
                                     font-semibold text-ink-200 backdrop-blur">
                            <span class="relative flex size-1.5">
                                <span class="absolute inline-flex size-full animate-ping rounded-full
                                             bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex size-1.5 rounded-full bg-emerald-400"></span>
                            </span>
                            <span>{{ __('marketing.badge') }}</span>
                            <span class="h-3 w-px bg-white/15" aria-hidden="true"></span>
                            <span class="tnum text-ink-300">
                                {{ $networkStats[2]['value'] ?? '0' }} {{ __('marketing.pressure.live_riders') }}
                            </span>
                        </span>

                        <h1 class="mt-7 text-4xl font-bold leading-[1.1] tracking-tight text-white
                                   sm:text-5xl lg:text-[3.4rem]">
                            {{ __('marketing.headline.one') }}
                            <span class="block text-ember-400">{{ __('marketing.headline.accent') }}</span>
                        </h1>

                        <p class="mt-6 max-w-lg text-base leading-relaxed text-ink-300 lg:text-lg">
                            {{ __('marketing.hero_body') }}
                        </p>

                        <div class="mt-9 flex flex-wrap gap-3">
                            {{-- The hero speaks to a person sending a parcel,
                                 so the first button is theirs. The other three
                                 audiences are one section down, in the
                                 switcher. --}}
                            <a href="{{ route('register.individual') }}"
                               class="inline-flex items-center gap-2 rounded-lg bg-ember-500 px-6 py-3.5
                                      text-base font-bold text-white shadow-lg shadow-ember-500/25
                                      transition hover:bg-ember-600">
                                {{ __('marketing.cta_business') }}
                                <x-ui.icon name="chevron-end" class="size-4 rtl:rotate-180" />
                            </a>
                            <a href="{{ route('learn') }}"
                               class="inline-flex items-center gap-2 rounded-lg border border-white/20
                                      bg-white/5 px-6 py-3.5 text-base font-semibold text-white
                                      backdrop-blur transition hover:border-white/30 hover:bg-white/10">
                                {{ __('learn.hub.eyebrow') }}
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

                    {{-- The hero visual.

                         An illustration above the real screen, not instead of
                         it: the drawing says what this is at a glance, and the
                         screen underneath proves it exists.

                         The screen is the order form, not the dispatch board —
                         this hero speaks to somebody sending a parcel, and the
                         board belongs to the company that carries it. --}}
                    <div class="relative">
                        <x-marketing.hero-scene class="mb-6 hidden lg:block" />

                        <x-marketing.browser-frame label="banha.shop/app/orders/create">
                            <x-marketing.mock-order-form />
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
             Tracking, high on the page.

             The person who needs this is not reading the page — they are
             waiting for a parcel and want one thing. It used to sit near the
             footer, below every argument aimed at somebody else.

             The order number is optional on purpose: this network sends no
             SMS today, so a recipient may never have been given one. The phone
             alone returns a list; the pair opens the parcel.
        ================================================================= --}}
        <section id="track" class="scroll-mt-20 border-t border-white/10 bg-white/[0.02]">
            <div class="mx-auto max-w-4xl px-5 py-14">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center">
                    <div class="lg:w-72 lg:shrink-0">
                        <h2 class="flex items-center gap-2.5 text-xl font-bold text-white">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg
                                         border border-ember-500/30 bg-ember-500/10 text-ember-400">
                                <x-ui.icon name="package" class="size-5" />
                            </span>
                            {{ __('tracking.lookup.title') }}
                        </h2>
                        <p class="mt-2 text-sm leading-relaxed text-ink-400">
                            {{ __('tracking.lookup.phone_only_hint') }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('tracking.lookup') }}" class="min-w-0 flex-1">
                        @csrf

                        <div class="panel-lit flex flex-col gap-2.5 p-2.5 sm:flex-row">
                            <input type="tel" name="phone" value="{{ old('phone') }}"
                                   placeholder="{{ __('tracking.lookup.phone_placeholder') }}"
                                   class="tnum min-w-0 flex-1 rounded-lg border-0 bg-transparent px-3 py-3
                                          text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none"
                                   dir="ltr" inputmode="numeric" required>

                            <span class="hidden w-px self-stretch bg-ink-200 sm:block"></span>

                            <input type="text" name="number" value="{{ old('number') }}"
                                   placeholder="{{ __('tracking.lookup.number_optional') }}"
                                   class="tnum min-w-0 flex-1 rounded-lg border-0 bg-transparent px-3 py-3
                                          text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none"
                                   dir="ltr">

                            <button type="submit"
                                    class="shrink-0 rounded-lg bg-ember-500 px-6 py-3 text-sm font-bold
                                           text-white transition hover:bg-ember-600">
                                {{ __('tracking.lookup.submit') }}
                            </button>
                        </div>

                        @error('number')
                            <p class="mt-2.5 text-sm font-medium text-red-300">{{ $message }}</p>
                        @enderror
                        @error('phone')
                            <p class="mt-2.5 text-sm font-medium text-red-300">{{ $message }}</p>
                        @enderror

                        <p class="mt-2.5 text-xs leading-relaxed text-ink-400">
                            {{ __('tracking.lookup.hint') }}
                        </p>
                    </form>
                </div>
            </div>
        </section>

        {{-- ================================================================
             01 · Who this is for.

             The spine of the landing page. Four people arrive looking for four
             different sentences and only one is theirs, so the page asks
             rather than stacking sections and hoping. Each door leads to that
             role's full guide as well as to its registration — the landing
             argues, the guide explains.
        ================================================================= --}}
        <section id="who" class="scroll-mt-20 border-t border-white/10">
            <div class="mx-auto max-w-6xl px-5 py-20">
                <x-marketing.section-head
                    index="01"
                    :eyebrow="__('marketing.who.eyebrow')"
                    :title="__('marketing.who.title')"
                    :body="__('marketing.who.body')" />

                <x-marketing.audience-switcher class="mt-12" :audiences="$audiences" />
            </div>
        </section>


        <section id="protection" class="scroll-mt-20 border-t border-white/10">
            <div class="mx-auto max-w-6xl px-5 py-20">
                <x-marketing.section-head
                    index="02"
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
                    index="03"
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

        {{-- ================================================================
             09 · What the network did without you.

             Aimed at a delivery company. Every figure is a count from the
             database — no company is named, because that would be telling one
             subscriber's business to another, and nothing is padded. If the
             network is quiet the section says so by being short.
        ================================================================= --}}
        <section id="pressure" class="grid-field relative scroll-mt-20 overflow-hidden border-t border-white/10">
            <div class="relative z-10 mx-auto max-w-6xl px-5 py-20">
                <x-marketing.section-head
                    index="04"
                    :eyebrow="__('marketing.pressure.eyebrow')"
                    :title="__('marketing.pressure.title')"
                    :body="__('marketing.pressure.body')" />

                <dl class="mt-12 grid gap-px overflow-hidden rounded-2xl border border-white/10
                           bg-white/10 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['value' => $networkStats[0]['value'] ?? '0', 'label' => __('marketing.pressure.live_orders')],
                        ['value' => number_format($companyCount), 'label' => __('marketing.pressure.live_companies')],
                        ['value' => $networkStats[2]['value'] ?? '0', 'label' => __('marketing.pressure.live_riders')],
                        ['value' => number_format($zones->count()), 'label' => __('marketing.pressure.live_zones')],
                    ] as $stat)
                        <div class="bg-ink-950 p-6">
                            <dd class="tnum text-4xl font-bold tracking-tight text-white">{{ $stat['value'] }}</dd>
                            <dt class="mt-1.5 text-xs font-medium text-ink-400">{{ $stat['label'] }}</dt>
                        </div>
                    @endforeach
                </dl>

                <div class="panel mt-4 p-6 sm:p-7">
                    <div class="flex flex-wrap items-baseline justify-between gap-3">
                        <h3 class="text-base font-bold text-white">{{ __('marketing.pressure.title') }}</h3>
                        <span class="text-xs font-semibold uppercase tracking-wider text-ink-500">
                            {{ __('marketing.pressure.window') }}
                        </span>
                    </div>

                    @if ($zoneActivity === [])
                        <p class="mt-6 text-sm text-ink-400">{{ __('marketing.pressure.empty') }}</p>
                    @else
                        <ul class="mt-6 space-y-3.5">
                            @foreach ($zoneActivity as $row)
                                <li>
                                    <div class="mb-1.5 flex items-baseline justify-between gap-3">
                                        <span class="min-w-0 truncate text-sm font-medium text-ink-200">
                                            {{ $row['zone'] }}
                                        </span>
                                        <span class="tnum shrink-0 text-sm font-bold text-white">
                                            {{ $row['orders'] }}
                                            <span class="text-xs font-normal text-ink-500">
                                                {{ __('marketing.pressure.orders_word') }}
                                            </span>
                                        </span>
                                    </div>
                                    {{-- Scaled against the busiest area, so the bars
                                         read whether the network did ten orders or
                                         ten thousand. --}}
                                    <div class="h-1.5 overflow-hidden rounded-full bg-white/10">
                                        <div class="h-full rounded-full bg-ember-500"
                                             style="width: {{ max($row['share'], 4) }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <p class="mt-7 border-s-2 border-ember-500 ps-4 text-sm font-semibold
                                  leading-relaxed text-white">
                            {{ __('marketing.pressure.closer') }}
                        </p>
                    @endif
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
                            <span class="section-index">05</span>
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
             05 · The close.

             Three things in one section, in the order they matter to a
             stranger: what the network actually is (counts, not adjectives),
             the one thing they can do here without an account, and the way in.

             It was in pieces before this — an earlier pass that removed
             sections from the page left an orphan closing tag, the lookup form
             outside any container and a duplicated pair of buttons, which is
             what produced the empty gaps and the floating CTAs.
        ================================================================= --}}
        <section id="join" class="grid-field accent-pool accent-pool-cool relative
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

                {{-- The way in. The hero speaks to a person sending a parcel,
                     so that door leads and the rest follow it. --}}
                <div class="mt-16 border-t border-white/10 pt-14 text-center">
                    <h3 class="text-2xl font-bold tracking-tight text-white">
                        {{ __('marketing.closing_title') }}
                    </h3>
                    <p class="mx-auto mt-2.5 max-w-lg text-sm leading-relaxed text-ink-400">
                        {{ __('marketing.closing_body') }}
                    </p>

                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('register.individual') }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-ember-500 px-8 py-4
                                  text-base font-bold text-white shadow-lg shadow-ember-500/25
                                  transition hover:bg-ember-600">
                            {{ __('marketing.cta_business') }}
                            <x-ui.icon name="chevron-end" class="size-4 rtl:rotate-180" />
                        </a>
                        <a href="{{ route('learn') }}"
                           class="inline-flex items-center gap-2 rounded-lg border border-white/20
                                  bg-white/5 px-8 py-4 text-base font-semibold text-white
                                  transition hover:bg-white/10">
                            {{ __('learn.hub.eyebrow') }}
                        </a>
                    </div>

                    <p class="mt-5 text-xs text-ink-400">
                        {{ __('marketing.closing_others') }}
                        <a href="{{ route('register') }}" class="font-semibold text-white hover:underline">
                            {{ __('app.auth.register') }}
                        </a>
                    </p>
                </div>
            </div>
        </section>

    </main>

    <x-marketing.public-footer />
</x-layouts.public>
