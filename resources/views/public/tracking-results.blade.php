{{--
    What a phone number alone is allowed to show.

    Deliberately thin: order number, shop, status. No address, no recipient
    name, no rider. Somebody who never received a link needs to find their own
    parcel, but a phone number is not a password — anyone who knows yours could
    type it here — so this says just enough to recognise your own order.
--}}
<x-layouts.public :title="__('tracking.lookup.results_title')" ground="dark">

    <x-marketing.public-header />

    <main class="bg-ink-950">
        <section class="grid-field accent-pool relative overflow-hidden">
            <div class="relative z-10 mx-auto max-w-3xl px-5 py-16 lg:py-20">
                <h1 class="text-3xl font-bold tracking-tight text-white lg:text-4xl">
                    {{ __('tracking.lookup.results_title') }}
                </h1>
                <p class="mt-3 text-sm leading-relaxed text-ink-300" dir="auto">
                    {{ __('tracking.lookup.results_body', ['phone' => $phone]) }}
                </p>

                @if ($orders->isEmpty())
                    <div class="panel mt-10 p-8 text-center">
                        <span class="mx-auto flex size-12 items-center justify-center rounded-xl
                                     border border-white/10 bg-white/5 text-ink-400">
                            <x-ui.icon name="package" class="size-6" />
                        </span>
                        <p class="mt-4 text-base font-semibold text-white">
                            {{ __('tracking.lookup.results_empty') }}
                        </p>
                        <p class="mt-2 text-sm text-ink-400">
                            {{ __('tracking.lookup.results_empty_hint') }}
                        </p>
                    </div>
                @else
                    <ul class="mt-10 space-y-3">
                        @foreach ($orders as $order)
                            <li>
                                <a href="{{ route('tracking.show', $order['token']) }}"
                                   class="panel panel-hover flex items-center gap-4 p-5">
                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="tnum text-sm font-bold text-white" dir="ltr">
                                                {{ $order['number'] }}
                                            </span>
                                            <x-ui.badge :tone="$order['tone']" dot>
                                                {{ $order['status'] }}
                                            </x-ui.badge>
                                        </span>
                                        <span class="mt-1.5 block truncate text-xs text-ink-400">
                                            {{ __('tracking.lookup.results_from') }}
                                            {{ $order['business'] }}
                                            <span class="mx-1 text-ink-600">·</span>
                                            {{ $order['placed']->diffForHumans() }}
                                        </span>
                                    </span>

                                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-lg
                                                 bg-ember-500 px-4 py-2.5 text-xs font-bold text-white">
                                        {{ __('tracking.lookup.results_open') }}
                                        <x-ui.icon name="chevron-end" class="size-3.5 rtl:rotate-180" />
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <p class="mt-6 flex items-start gap-2 text-xs leading-relaxed text-ink-400">
                        <x-ui.icon name="shield" class="mt-0.5 size-3.5 shrink-0" />
                        {{ __('tracking.lookup.results_privacy') }}
                    </p>
                @endif

                <a href="{{ route('home') }}#track"
                   class="mt-8 inline-flex items-center gap-1.5 text-sm font-semibold text-ink-300
                          transition hover:text-white">
                    <x-ui.icon name="chevron-end" class="size-3.5 ltr:rotate-180 rtl:rotate-0" />
                    {{ __('tracking.lookup.search_again') }}
                </a>
            </div>
        </section>
    </main>

    <x-marketing.public-footer />
</x-layouts.public>
