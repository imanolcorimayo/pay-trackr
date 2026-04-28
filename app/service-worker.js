/**
 * mangos service worker.
 *
 * Strategy:
 *   - Pages (HTML navigations): network-first, fall back to last cached copy of
 *     that page, fall back to /offline.html.
 *   - Static assets under /assets/: cache-first (immutable per nginx rule).
 *   - Cross-origin API (mangos-api.*): never touched — passes through to the
 *     network. We never want to serve stale balances.
 *   - Non-GET requests: never touched.
 *
 * Cache versioning: bump CACHE_VERSION on deploy to invalidate old caches.
 * Same-origin only — the SW scope is `/`, so cross-origin requests bypass
 * the fetch handler unless we explicitly opt in.
 */

const CACHE_VERSION = 'v1';
const PAGES_CACHE   = `mangos-pages-${CACHE_VERSION}`;
const ASSETS_CACHE  = `mangos-assets-${CACHE_VERSION}`;

// Pre-cached on install so they're available even on the very first offline visit.
const PRECACHE = [
    '/offline.html',
    '/manifest.webmanifest',
    '/assets/img/favicon.svg',
    '/assets/img/icon-192.png',
    '/assets/img/icon-512.png',
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(ASSETS_CACHE).then(cache => cache.addAll(PRECACHE))
    );
    // Activate this worker as soon as it finishes installing — replaces the old SW
    // on the next page load instead of waiting for all tabs to close.
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        const expected = new Set([PAGES_CACHE, ASSETS_CACHE]);
        await Promise.all(keys.map(k => expected.has(k) ? null : caches.delete(k)));
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', event => {
    const req = event.request;

    // Never intercept non-GET — POSTs, PUTs and DELETEs always go straight to network.
    if (req.method !== 'GET') return;

    const url = new URL(req.url);

    // Cross-origin: ignore (the API lives on mangos-api.* — we don't want to
    // serve cached responses for it). The browser handles these normally.
    if (url.origin !== self.location.origin) return;

    // Static assets — cache-first.
    if (url.pathname.startsWith('/assets/')) {
        event.respondWith(cacheFirst(req, ASSETS_CACHE));
        return;
    }

    // Manifest — cache-first too (rarely changes; bump CACHE_VERSION to refresh).
    if (url.pathname === '/manifest.webmanifest') {
        event.respondWith(cacheFirst(req, ASSETS_CACHE));
        return;
    }

    // The SW file itself — always network.
    if (url.pathname === '/service-worker.js') return;

    // HTML page navigations — network-first.
    if (req.mode === 'navigate' || (req.headers.get('accept') || '').includes('text/html')) {
        event.respondWith(networkFirstPage(req));
        return;
    }

    // Anything else same-origin (e.g. JSON fetch from same origin) — network only.
});

async function cacheFirst(req, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(req);
    if (cached) return cached;
    try {
        const res = await fetch(req);
        if (res.ok) cache.put(req, res.clone());
        return res;
    } catch (err) {
        // No network and no cache — let the browser surface the error.
        throw err;
    }
}

async function networkFirstPage(req) {
    const cache = await caches.open(PAGES_CACHE);
    try {
        const res = await fetch(req);
        // Only cache successful, basic (non-redirected, non-opaque) responses.
        if (res.ok && res.type === 'basic') cache.put(req, res.clone());
        return res;
    } catch (err) {
        const cached = await cache.match(req);
        if (cached) return cached;
        const offline = await cache.match('/offline.html')
                     || await caches.match('/offline.html');
        if (offline) return offline;
        throw err;
    }
}
