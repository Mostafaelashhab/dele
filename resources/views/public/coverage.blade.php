{{--
    Lifted out of the landing page onto its own address, so it can be linked
    to, bookmarked, and found from a search — which a fragment on one long
    page never could.
--}}
<x-layouts.public
    :title="__('marketing.zones.title')"
    :description="__('marketing.zones.diagram_hint')"
    :noindex="false"
    ground="dark">

    <x-marketing.public-header />

    @php
    // The page renders its own data: a Route::view has no controller.
    $zones = app(\App\Domain\Zones\ZoneResolver::class)->activeZones();
    $companyCount = \App\Models\DeliveryCompany::query()
        ->where('status', \App\Enums\AccountStatus::Active)->count();
@endphp

<main class="bg-ink-950">
        <section id="coverage" class="scroll-mt-20 border-t border-white/10">
            <div class="mx-auto max-w-6xl px-5 py-20">
                <x-marketing.section-head
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
    </main>

    <x-marketing.public-footer />
</x-layouts.public>
