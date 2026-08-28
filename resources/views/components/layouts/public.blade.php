@props([
    'title' => null,
    'description' => null,
    'noindex' => true,
    // The marketing page runs on a dark ground; tracking pages stay light.
    'ground' => 'light',
])

@php
    $locale = app()->getLocale();

    $structuredData = $noindex ? null : json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => url('/#organization'),
                'name' => __('app.name'),
                'url' => url('/'),
                'logo' => url('/icons/icon-512.png'),
                'image' => url('/og-image.png'),
                'description' => $description,
                'areaServed' => [
                    '@type' => 'City',
                    'name' => __('app.city'),
                    'addressCountry' => 'EG',
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/#website'),
                'url' => url('/'),
                'name' => __('app.name'),
                'inLanguage' => $locale === 'ar' ? 'ar-EG' : 'en',
                'publisher' => ['@id' => url('/#organization')],
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — '.__('app.name') : __('app.name') }}</title>

    @if ($noindex)
        {{-- Tracking pages carry a customer's address. They must never be
             indexed, and the referrer must not leak the token to a map tile
             host or any other third party. --}}
        <meta name="robots" content="noindex, nofollow, noarchive">
        <meta name="referrer" content="no-referrer">
    @else
        <meta name="description" content="{{ $description }}">
        <meta name="robots" content="index, follow, max-image-preview:large">

        {{-- The canonical is built from the path only. Campaign parameters and
             the locale switch would otherwise split one page into a dozen
             addresses competing with each other. --}}
        <link rel="canonical" href="{{ url()->current() }}">

        {{-- Both locales are the same page, so each declares the other and
             x-default points at the Arabic one the city actually reads. --}}
        <link rel="alternate" hreflang="ar-EG" href="{{ url()->current() }}">
        <link rel="alternate" hreflang="en" href="{{ route('locale.switch', ['locale' => 'en']) }}">
        <link rel="alternate" hreflang="x-default" href="{{ url('/') }}">

        <meta property="og:site_name" content="{{ __('app.name') }}">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_EG' : 'en_US' }}">
        <meta property="og:locale:alternate" content="{{ $locale === 'ar' ? 'en_US' : 'ar_EG' }}">
        <meta property="og:image" content="{{ url('/og-image.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ __('app.name') }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $title }}">
        <meta name="twitter:description" content="{{ $description }}">
        <meta name="twitter:image" content="{{ url('/og-image.png') }}">

        {{-- Structured data, stated once. The network is a real business in
             a real city, so it is described as one rather than as a generic
             website — that is what earns the city and the service area a
             place in a result.

             Built in PHP rather than with @json: the schema.org keys begin
             with an @, which Blade reads as its own directives and mangles. --}}
        <script type="application/ld+json">{!! $structuredData !!}</script>
    @endif

    <meta name="theme-color" content="#0d1117">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body @class(['min-h-dvh', 'bg-ink-100' => $ground === 'light', 'bg-ink-950' => $ground === 'dark'])>
    {{ $slot }}
    @livewireScripts
</body>
</html>
