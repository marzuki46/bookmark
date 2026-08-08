const CACHE_NAME = 'clips-keluarga-v1';
const APP_SHELL = [
    '/keluarga-app',
    '/manifest-keluarga.json',
    '/icons/keluarga/icon-192.png',
    '/icons/keluarga/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    if (event.request.method !== 'GET' || url.origin !== location.origin) {
        return;
    }

    if (url.pathname.startsWith('/keluarga-app') || url.pathname.startsWith('/sw-keluarga.js')) {
        event.respondWith(
            caches.match(event.request).then((cached) => cached || fetch(event.request))
        );
        return;
    }

    event.respondWith(
        caches.open(CACHE_NAME).then((cache) =>
            fetch(event.request)
                .then((response) => {
                    cache.put(event.request, response.clone());
                    return response;
                })
                .catch(() => cache.match(event.request))
        )
    );
});
