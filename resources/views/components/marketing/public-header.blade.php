@php
    /**
     * The public header, shared by every page outside the portals.
     *
     * The nav points at real pages rather than at fragments of one long
     * landing — the guides, the coverage map and the questions each have an
     * address now, so a link here works from every page rather than only from
     * the one the section happens to live on.
     */
    $links = [
        ['route' => 'learn', 'label' => __('learn.hub.eyebrow')],
        ['route' => 'coverage', 'label' => __('marketing.zones.title')],
        ['route' => 'faq', 'label' => __('marketing.faq.eyebrow')],
    ];

    $locale = app()->getLocale();
@endphp

<header x-data="{ scrolled: false, open: false }"
        @scroll.window="scrolled = window.scrollY > 24"
        @keydown.escape.window="open = false"
        :class="scrolled || open
            ? 'border-white/10 bg-ink-950/90 backdrop-blur'
            : 'border-transparent'"
        class="sticky top-0 z-40 border-b transition-colors">

    <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-5">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
            <x-ui.logo wordmark class="text-white" />
        </a>

        <nav class="ms-auto hidden items-center gap-0.5 lg:flex">
            @foreach ($links as $link)
                {{-- routeIs matches the guides too, so "learn" stays lit while
                     the reader is inside /learn/company. --}}
                @php $active = request()->routeIs($link['route'], $link['route'].'.*'); @endphp

                <a href="{{ route($link['route']) }}"
                   @if ($active) aria-current="page" @endif
                   @class([
                       'rounded-md px-3 py-2 text-sm font-medium transition',
                       'bg-white/10 text-white' => $active,
                       'text-ink-400 hover:bg-white/5 hover:text-white' => ! $active,
                   ])>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="ms-auto flex items-center gap-1.5 lg:ms-4">
            <a href="{{ route('locale.switch', ['locale' => $locale === 'ar' ? 'en' : 'ar']) }}"
               class="rounded-md px-2.5 py-1.5 text-xs font-semibold text-ink-400 transition hover:text-white">
                {{ $locale === 'ar' ? 'EN' : 'ع' }}
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

            {{-- The nav is hidden below lg, and before this there was nothing
                 in its place: from a phone the guides, the coverage map and
                 the questions were simply unreachable. --}}
            <button type="button"
                    @click="open = ! open"
                    :aria-expanded="open ? 'true' : 'false'"
                    aria-controls="public-menu"
                    class="flex size-10 items-center justify-center rounded-lg border border-white/15
                           text-ink-300 transition hover:bg-white/5 hover:text-white lg:hidden">
                <span class="sr-only">{{ __('marketing.menu') }}</span>
                <x-ui.icon x-show="! open" name="menu" class="size-5" />
                <x-ui.icon x-show="open" x-cloak name="x" class="size-5" />
            </button>
        </div>
    </div>

    <div id="public-menu"
         x-show="open"
         x-cloak
         x-transition:enter="transition duration-150 ease-out"
         x-transition:enter-start="opacity-0 -translate-y-2"
         @click.outside="open = false"
         class="border-t border-white/10 bg-ink-950/95 backdrop-blur lg:hidden">
        <nav class="mx-auto max-w-6xl px-5 py-4">
            <ul class="space-y-1">
                @foreach ($links as $link)
                    @php $active = request()->routeIs($link['route'], $link['route'].'.*'); @endphp

                    <li>
                        <a href="{{ route($link['route']) }}"
                           @if ($active) aria-current="page" @endif
                           @class([
                               'block rounded-lg px-3 py-3 text-sm font-semibold transition',
                               'bg-white/10 text-white' => $active,
                               'text-ink-300 hover:bg-white/5 hover:text-white' => ! $active,
                           ])>
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach

                {{-- Login is hidden beside the register button on a narrow
                     screen, so it belongs here or it belongs nowhere. --}}
                <li class="border-t border-white/10 pt-2 sm:hidden">
                    <a href="{{ route('login') }}"
                       class="block rounded-lg px-3 py-3 text-sm font-semibold text-ink-300
                              transition hover:bg-white/5 hover:text-white">
                        {{ __('marketing.cta_login') }}
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</header>
