const STATIC_CACHE = 'cleanflow-static-v2';
const RUNTIME_CACHE = 'cleanflow-runtime-v1';
const APP_SHELL_FILES = [
    '/offline.html',
    '/manifest.webmanifest',
    '/favicon.ico',
    '/images/logo.png',
    '/icons/apple-touch-icon.png',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-maskable-512.png',
    '/js/main.js',
    '/js/pwa.js',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(APP_SHELL_FILES))
    );

    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => ![STATIC_CACHE, RUNTIME_CACHE].includes(key))
                    .map((key) => caches.delete(key))
            )
        )
    );

    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const requestUrl = new URL(event.request.url);

    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => caches.match('/offline.html'))
        );

        return;
    }

    const isStaticAsset = ['script', 'style', 'image', 'font'].includes(event.request.destination)
        || requestUrl.pathname.startsWith('/build/');

    if (!isStaticAsset) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
            const networkFetch = fetch(event.request)
                .then((response) => {
                    if (!response || response.status !== 200 || response.type === 'opaque') {
                        return response;
                    }

                    const responseClone = response.clone();
                    caches.open(RUNTIME_CACHE).then((cache) => cache.put(event.request, responseClone));

                    return response;
                })
                .catch(() => cachedResponse);

            return cachedResponse || networkFetch;
        })
    );
});
