/*
 * TAP ERP service worker.
 *
 * This is an authenticated ERP, so the caching policy is deliberately narrow:
 * only immutable, same-origin, public static assets are ever stored. HTML is
 * never cached — a logged-in page sitting in the cache would be replayed to
 * whoever opens the app next on that device, which at a shared sales counter
 * means one cashier seeing another's data.
 */

const VERSION = 'v1';
const STATIC_CACHE = `tap-erp-static-${VERSION}`;
const OFFLINE_URL = '/offline.html';

// Prefixes of same-origin paths that hold static assets. Anything outside this
// list (every application route) is treated as authenticated and never cached.
const STATIC_PREFIXES = ['/css/', '/js/', '/build/', '/img/', '/fonts/', '/webfonts/', '/vendor/'];

const STATIC_EXTENSIONS = /\.(?:css|js|mjs|png|jpe?g|gif|svg|webp|avif|ico|woff2?|ttf|otf|eot)$/i;

self.addEventListener('install', (event) => {
    // Pre-cache only the offline fallback; everything else fills in on demand.
    event.waitUntil(
        caches
            .open(STATIC_CACHE)
            .then((cache) => cache.add(new Request(OFFLINE_URL, { cache: 'reload' })))
            .catch(() => undefined)
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => key.startsWith('tap-erp-static-') && key !== STATIC_CACHE)
                        .map((key) => caches.delete(key))
                )
            )
            .then(() => self.clients.claim())
    );
});

/** A response is only safe to store if it is a complete, same-origin 200. */
function isCacheable(response) {
    return Boolean(response) && response.status === 200 && response.type === 'basic';
}

function isStaticAsset(url) {
    if (url.origin !== self.location.origin) {
        return false;
    }

    if (url.search) {
        // Keep versioned asset URLs (?id=...) cacheable, but nothing else.
        const onlyVersionQuery = /^\?(?:id|v|ver)=[^&]*$/.test(url.search);
        if (!onlyVersionQuery) {
            return false;
        }
    }

    const path = url.pathname;
    const underStaticPrefix = STATIC_PREFIXES.some((prefix) => path.startsWith(prefix));

    return underStaticPrefix && STATIC_EXTENSIONS.test(path);
}

/**
 * Cache-first with a background refresh. The cached copy answers immediately
 * and a fresh one is fetched and stored for the next load.
 */
async function staleWhileRevalidate(request) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(request);

    const network = fetch(request)
        .then((response) => {
            if (isCacheable(response)) {
                cache.put(request, response.clone()).catch(() => undefined);
            }

            return response;
        })
        .catch(() => undefined);

    if (cached) {
        // Do not await: the refresh runs behind the response already served.
        network.catch(() => undefined);

        return cached;
    }

    const fresh = await network;

    if (fresh) {
        return fresh;
    }

    return Response.error();
}

/** Navigations are network-only; offline.html is the only fallback. */
async function networkOnlyWithOfflineFallback(request) {
    try {
        return await fetch(request);
    } catch (error) {
        const cached = await caches.match(OFFLINE_URL);

        if (cached) {
            return cached;
        }

        return new Response('Sin conexión', {
            status: 503,
            headers: { 'Content-Type': 'text/plain; charset=utf-8' },
        });
    }
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Never touch writes: POST/PUT/PATCH/DELETE go straight to the network.
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkOnlyWithOfflineFallback(request));

        return;
    }

    if (isStaticAsset(url)) {
        event.respondWith(staleWhileRevalidate(request));
    }

    // Everything else (XHR/fetch to application endpoints) is left alone.
});
