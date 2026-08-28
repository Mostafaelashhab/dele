@props([
    'portal' => 'business',
    'title' => null,
    'context' => null,
])

@php
    $navigation = app(\App\Support\NavigationBuilder::class)->for($portal, auth()->user());
    $locale = app()->getLocale();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' — '.__('app.name') : __('app.name') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{-- One ground for the whole product: the portals used to be a light theme
     behind a dark marketing site, so signing in felt like a different app. --}}
<body class="min-h-dvh bg-ink-950">
<div x-data="{ mobileNav: false }" class="lg:flex lg:min-h-dvh">

    {{-- Mobile navigation backdrop --}}
    <div x-show="mobileNav" x-cloak
         @click="mobileNav = false"
         class="fixed inset-0 z-30 bg-ink-950/40 lg:hidden"></div>

    {{-- The drawer transform is scoped to max-lg on purpose.

         It used to be an unscoped `rtl:translate-x-full` cancelled by
         `lg:translate-x-0`, but direction variants compile after breakpoint
         variants, so the off-screen transform won at every width and the
         desktop sidebar was never visible in either direction. Confining the
         transform to below `lg` means there is nothing left to cancel. --}}
    <aside :class="mobileNav ? '' : 'max-lg:ltr:-translate-x-full max-lg:rtl:translate-x-full'"
           class="fixed inset-y-0 z-40 flex w-64 shrink-0 flex-col border-e border-white/10 bg-ink-900/60 transition-transform
                  lg:static ltr:left-0 rtl:right-0">

        <div class="flex h-14 items-center gap-2.5 border-b border-white/10 px-4">
            <x-ui.logo class="text-white" />
            <div class="min-w-0 leading-tight">
                <p class="truncate text-sm font-semibold text-white">{{ __('app.name') }}</p>
                <p class="truncate text-2xs text-ink-400">{{ $context ?? ucfirst($portal) }}</p>
            </div>
            <button type="button" @click="mobileNav = false"
                    class="ms-auto text-ink-400 hover:text-white lg:hidden"
                    aria-label="{{ __('app.common.close') }}">
                <x-ui.icon name="x" class="size-5" />
            </button>
        </div>

        <nav class="no-scrollbar flex-1 space-y-5 overflow-y-auto px-2.5 py-4">
            @foreach ($navigation as $group)
                <div>
                    @if ($group['label'])
                        <p class="px-2.5 pb-1.5 text-2xs font-semibold uppercase tracking-wider text-ink-500">
                            {{ $group['label'] }}
                        </p>
                    @endif
                    <ul class="space-y-0.5">
                        @foreach ($group['items'] as $item)
                            @continue(! \Illuminate\Support\Facades\Route::has($item['route']))
                            @php $isActive = request()->routeIs($item['route'].'*'); @endphp
                            <li>
                                <a href="{{ route($item['route']) }}" wire:navigate
                                   @class([
                                       'flex items-center gap-2.5 rounded-md px-2.5 py-2 text-sm font-medium transition',
                                       'bg-ember-500 text-white shadow-sm' => $isActive,
                                       'text-ink-300 hover:bg-white/5 hover:text-white' => ! $isActive,
                                   ])
                                   @if ($isActive) aria-current="page" @endif>
                                    <x-ui.icon :name="$item['icon']" class="size-4 shrink-0" />
                                    <span class="truncate">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-2.5">
            <div class="flex items-center gap-2.5 rounded-md px-2.5 py-2">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-white/10
                             text-xs font-semibold text-white">
                    {{ auth()->user()?->initials() }}
                </span>
                <div class="min-w-0 flex-1 leading-tight">
                    <p class="truncate text-xs font-semibold text-white">{{ auth()->user()?->name }}</p>
                    <p class="truncate text-2xs text-ink-400">{{ auth()->user()?->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-ink-400 transition hover:text-white"
                            title="{{ __('app.nav.logout') }}">
                        <x-ui.icon name="logout" class="size-4 rtl-flip" />
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-white/10
                       bg-ink-950/85 px-4 backdrop-blur">
            <button type="button" @click="mobileNav = true"
                    class="-ms-1 rounded-md p-1.5 text-ink-300 hover:bg-white/5 lg:hidden"
                    aria-label="{{ __('app.nav.dashboard') }}">
                <x-ui.icon name="menu" class="size-5" />
            </button>

            <div class="min-w-0 flex-1">
                @isset($header)
                    {{ $header }}
                @endisset
            </div>

            <livewire:shared.notification-bell />

            <a href="{{ route('locale.switch', ['locale' => $locale === 'ar' ? 'en' : 'ar']) }}"
               class="rounded-md px-2 py-1 text-xs font-semibold text-ink-300 ring-1 ring-inset ring-white/15
                      transition hover:bg-white/5">
                {{ $locale === 'ar' ? 'EN' : 'ع' }}
            </a>
        </header>

        <main class="flex-1 p-4 lg:p-6">
            {{-- Content is capped: a data table stretched across an ultrawide
                 monitor is unreadable, and the eye loses the row it is on. --}}
            <div class="mx-auto max-w-[92rem]">
            @if (session('status'))
                <div class="mb-4 flex items-start gap-2.5 rounded-lg border border-emerald-500/30
                            bg-emerald-500/10 px-4 py-3">
                    <x-ui.icon name="check" class="mt-0.5 size-4 shrink-0 text-emerald-400" />
                    <p class="text-sm text-emerald-200">{{ session('status') }}</p>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 flex items-start gap-2.5 rounded-lg border border-red-500/30
                            bg-red-500/10 px-4 py-3">
                    <x-ui.icon name="alert" class="mt-0.5 size-4 shrink-0 text-red-400" />
                    <p class="text-sm text-red-200">{{ session('error') }}</p>
                </div>
            @endif

            {{ $slot }}
            </div>
        </main>
    </div>
</div>
@livewireScripts
</body>
</html>
