/**
 * Banha Rider — offline shell.
 *
 * The rider app must open on a lift, in a basement, or on a dead cell patch,
 * so the shell and its assets are cached and served offline. Data requests are
 * deliberately never cached: a stale delivery status is worse than no status,
 * because a rider acting on one delivers to the wrong place.
 */

const CACHE = 'bdn-rider-v1';

const PRECACHE = ['/rider', '/favicon.svg'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
            .catch(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    // Livewire updates, API calls and location pings must always hit the
    // network. Serving any of these from a cache would show a rider a
    // delivery state that is no longer true.
    if (
        url.pathname.startsWith('/livewire') ||
        url.pathname.startsWith('/api') ||
        url.pathname.startsWith('/rider/location')
    ) {
        return;
    }

    // Build assets are content-hashed, so cache-first is safe and instant.
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetchAndCache(request)),
        );

        return;
    }

    // Everything else: network first, with the cached shell as the fallback
    // when the connection has dropped.
    event.respondWith(
        fetchAndCache(request).catch(() =>
            caches.match(request).then((cached) => cached || caches.match('/rider')),
        ),
    );
});

function fetchAndCache(request) {
    return fetch(request).then((response) => {
        if (response.ok && response.type === 'basic') {
            const copy = response.clone();
            caches.open(CACHE).then((cache) => cache.put(request, copy));
        }

        return response;
    });
}
