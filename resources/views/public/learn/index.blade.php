{{--
    The hub. One job: get somebody into their own guide in a single click.
--}}
<x-layouts.public
    :title="__('learn.hub.eyebrow')"
    :description="__('learn.hub.title')"
    :noindex="false"
    ground="dark">

    <x-marketing.public-header />

    <main class="bg-ink-950">
        <section class="grid-field accent-pool relative overflow-hidden">
            <div class="relative z-10 mx-auto max-w-5xl px-5 py-20 text-center lg:py-24">
                <p class="text-xs font-bold uppercase tracking-widest text-ember-400">
                    {{ __('learn.hub.eyebrow') }}
                </p>
                <h1 class="mx-auto mt-4 max-w-3xl text-4xl font-bold leading-[1.15] tracking-tight
                           text-white lg:text-5xl">
                    {{ __('learn.hub.title') }}
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-base leading-relaxed text-ink-300">
                    {{ __('learn.hub.body') }}
                </p>

                <x-marketing.hero-scene class="mx-auto mt-10 max-w-2xl" />
            </div>
        </section>

        <section class="border-t border-white/10">
            <div class="mx-auto max-w-6xl px-5 pb-24">
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($audiences as $audience)
                        @php
                            $tone = [
                                'signal' => ['chip' => 'border-signal-500/30 bg-signal-500/10 text-signal-300', 'hover' => 'hover:border-signal-500/50'],
                                'ember' => ['chip' => 'border-ember-500/30 bg-ember-500/10 text-ember-400', 'hover' => 'hover:border-ember-500/50'],
                                'emerald' => ['chip' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300', 'hover' => 'hover:border-emerald-500/50'],
                            ][$audience['accent']];
                        @endphp

                        <a href="{{ route('learn.show', $audience['key']) }}"
                           class="group panel flex flex-col p-6 transition {{ $tone['hover'] }} hover:bg-white/[0.055]">
                            <span class="flex size-12 items-center justify-center rounded-xl border {{ $tone['chip'] }}">
                                <x-ui.icon :name="$audience['icon']" class="size-6" />
                            </span>

                            <h2 class="mt-5 text-xl font-bold text-white">
                                {{ __("learn.{$audience['key']}.label") }}
                            </h2>
                            <p class="mt-1.5 text-sm text-ink-400">
                                {{ __("learn.{$audience['key']}.tagline") }}
                            </p>
                            <p class="mt-4 flex-1 text-sm leading-relaxed text-ink-300">
                                {{ __("learn.{$audience['key']}.intro") }}
                            </p>

                            <span class="mt-6 flex items-center justify-between gap-3 border-t border-white/10 pt-5">
                                <span class="inline-flex items-center gap-2 text-sm font-bold text-white">
                                    {{ __('learn.hub.cta') }}
                                    <x-ui.icon name="chevron-end"
                                               class="size-4 transition group-hover:translate-x-0.5
                                                      rtl:rotate-180 rtl:group-hover:-translate-x-0.5" />
                                </span>
                                <span class="tnum text-xs text-ink-500">
                                    {{ $audience['steps'] }} {{ __('learn.hub.steps_word') }}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    </main>

    <x-marketing.public-footer />
</x-layouts.public>
