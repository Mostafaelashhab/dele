@props(['title' => null])

@php $locale = app()->getLocale(); @endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    {{-- viewport-fit=cover so the sticky action bar can clear the home
         indicator; user-scalable=no because a rider tapping a large button
         while riding must never zoom the page by accident. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0d1117">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>{{ $title ? $title.' — '.__('rider.app.name') : __('rider.app.name') }}</title>
    <link rel="manifest" href="{{ route('rider.manifest') }}">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icons/rider-192.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh bg-ink-100">
<div class="mx-auto flex min-h-dvh max-w-lg flex-col bg-ink-100 shadow-xl">
    {{ $slot }}
</div>

@livewireScripts

@auth
    <script>
        // The reporting interval is server-controlled so the platform can
        // throttle every rider's phone at once without shipping new code.
        document.addEventListener('livewire:navigated', () => window.__startReporter?.());

        window.__startReporter = () => {
            if (window.__riderReporter) {
                return;
            }

            window.__riderReporter = new window.RiderLocationReporter({
                endpoint: @json(route('rider.location.store')),
                intervalSeconds: @json((int) config('platform.tracking.ping_interval_seconds')),
                csrfToken: document.querySelector('meta[name="csrf-token"]').content,
            });

            window.__riderReporter.start();
        };

        if (@json(auth()->user()?->rider?->isOnline() ?? false)) {
            window.__startReporter();
        }
    </script>
@endauth
</body>
</html>
