@props([
    'title' => null,
    // Which side of the network this page is for: business · company · both.
    // The panel beside the form argues that audience's case rather than an
    // average of the two, because a shop and a courier fleet are not weighing
    // the same decision.
    'audience' => 'both',
    'wide' => false,
    // Renders the slot straight onto the dark ground instead of on a lit
    // panel — for pages that are a set of choices rather than a form.
    'bare' => false,
])

@php
    $locale = app()->getLocale();

    $pitch = match ($audience) {
        'business' => ['title' => __('marketing.pitch.business_title'), 'body' => __('marketing.pitch.business_body')],
        'company' => ['title' => __('marketing.pitch.company_title'), 'body' => __('marketing.pitch.company_body')],
        default => ['title' => __('marketing.pitch.both_title'), 'body' => __('marketing.pitch.both_body')],
    };

    /**
     * Reassurance specific to the door being walked through.
     *
     * A shop starts trading immediately; a company waits for review. Saying
     * which of those applies before the form rather than after it is the
     * difference between a promise and a surprise.
     */
    $assurances = match ($audience) {
        'business' => [
            ['icon' => 'check', 'text' => __('marketing.choose.business_note')],
            ['icon' => 'money', 'text' => __('marketing.fees.zero_note')],
        ],
        'company' => [
            ['icon' => 'shield', 'text' => __('marketing.choose.company_note')],
            ['icon' => 'money', 'text' => __('marketing.fees.zero_note')],
        ],
        default => [
            ['icon' => 'money', 'text' => __('marketing.fees.zero_note')],
        ],
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' — '.__('app.name') : __('app.name') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

{{--
    Same inversion as the public page: a dark ground, with the thing you
    actually interact with lifted onto a light surface. A sign-in form is a
    piece of the product, so it gets the product's treatment rather than
    sitting in a flat white half of the screen.
--}}
<body class="grid-field min-h-dvh bg-ink-950">
    <div class="relative z-10 flex min-h-dvh flex-col">

        <header class="mx-auto flex w-full max-w-6xl items-center gap-3 px-5 py-5">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <x-ui.logo wordmark class="text-white" />
            </a>

            <div class="ms-auto flex items-center gap-1">
                <a href="{{ route('home') }}"
                   class="hidden items-center gap-1.5 rounded-md px-3 py-2 text-xs font-semibold
                          text-ink-400 transition hover:text-white sm:flex">
                    <x-ui.icon name="chevron-end" class="size-3.5 ltr:rotate-180 rtl:rotate-0" />
                    {{ __('app.name') }}
                </a>
                <a href="{{ route('locale.switch', ['locale' => $locale === 'ar' ? 'en' : 'ar']) }}"
                   class="rounded-md px-2.5 py-1.5 text-xs font-semibold text-ink-400
                          transition hover:text-white">
                    {{ $locale === 'ar' ? 'English' : 'العربية' }}
                </a>
            </div>
        </header>

        <main class="mx-auto flex w-full max-w-6xl flex-1 items-center px-5 pb-12 pt-2">
            <div @class([
                'grid w-full items-center gap-12',
                'lg:grid-cols-[1fr_28rem]' => ! $wide,
            ])>
                {{-- The argument. Hidden on narrow screens where it would
                     push the form below the fold, but its assurances are not:
                     they move under the form instead. --}}
                @unless ($wide)
                    <div class="hidden lg:block">
                        <h1 class="text-4xl font-bold leading-[1.15] tracking-tight text-white xl:text-5xl">
                            {{ $pitch['title'] }}
                        </h1>
                        <p class="mt-5 max-w-md text-base leading-relaxed text-ink-300">
                            {{ $pitch['body'] }}
                        </p>

                        <dl class="mt-10 grid max-w-md gap-3 border-t border-white/10 pt-8 xl:grid-cols-3 xl:gap-6">
                            @foreach ([
                                ['value' => __('marketing.stat_one_value'), 'label' => __('marketing.stat_one_label')],
                                ['value' => __('marketing.stat_two_value'), 'label' => __('marketing.stat_two_label')],
                                ['value' => __('marketing.stat_three_value'), 'label' => __('marketing.stat_three_label')],
                            ] as $stat)
                                <div class="flex items-baseline gap-2.5 xl:block">
                                    <dt class="shrink-0 text-lg font-bold text-white">{{ $stat['value'] }}</dt>
                                    <dd class="text-xs leading-relaxed text-ink-400 xl:mt-1.5">{{ $stat['label'] }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        <ul class="mt-8 max-w-md space-y-2.5">
                            @foreach ($assurances as $assurance)
                                <li class="flex items-start gap-2.5 text-sm leading-relaxed text-ink-400">
                                    <x-ui.icon :name="$assurance['icon']"
                                               class="mt-0.5 size-4 shrink-0 text-emerald-400" />
                                    <span>{{ $assurance['text'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endunless

                <div class="w-full">
                    @if ($bare)
                        {{ $slot }}
                    @else
                        <div class="panel-lit p-6 sm:p-8">
                            {{ $slot }}
                        </div>
                    @endif

                    {{-- The narrow-screen home for the assurances the pitch
                         panel carries on a wide one. --}}
                    @unless ($wide)
                        <ul class="mt-5 space-y-2.5 lg:hidden">
                            @foreach ($assurances as $assurance)
                                <li class="flex items-start gap-2.5 text-xs leading-relaxed text-ink-400">
                                    <x-ui.icon :name="$assurance['icon']"
                                               class="mt-0.5 size-3.5 shrink-0 text-emerald-400" />
                                    <span>{{ $assurance['text'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endunless
                </div>
            </div>
        </main>

        <footer class="mx-auto w-full max-w-6xl px-5 pb-6">
            <p class="text-xs text-ink-400">
                © {{ now()->year }} {{ __('app.name') }} · {{ config('platform.city') }}
            </p>
        </footer>
    </div>
    @livewireScripts
</body>
</html>
