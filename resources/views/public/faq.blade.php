{{--
    Lifted out of the landing page onto its own address, so it can be linked
    to, bookmarked, and found from a search — which a fragment on one long
    page never could.
--}}
<x-layouts.public
    :title="__('marketing.faq.title')"
    :description="__('marketing.faq.eyebrow')"
    :noindex="false"
    ground="dark">

    <x-marketing.public-header />

    <main class="bg-ink-950">
        <section id="faq" class="scroll-mt-20 border-t border-white/10">
            <div class="mx-auto max-w-3xl px-5 py-20">
                <x-marketing.section-head
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
    </main>

    <x-marketing.public-footer />
</x-layouts.public>
