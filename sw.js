const CACHE_NAME = 'memo-app-v1';
const STATIC_ASSETS = [
    './',
    './index.html',
    'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css',
    'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // PHP APIはネットワーク優先
    if (url.pathname.endsWith('.php')) {
        event.respondWith(
            fetch(event.request).catch(() =>
                new Response(JSON.stringify({ error: 'オフラインです' }), {
                    headers: { 'Content-Type': 'application/json' }
                })
            )
        );
        return;
    }

    // 静的リソースはキャッシュ優先
    event.respondWith(
        caches.match(event.request).then(cached => {
            if (cached) return cached;
            return fetch(event.request).then(response => {
                if (!response || response.status !== 200 || response.type === 'opaque') {
                    return response;
                }
                const toCache = response.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(event.request, toCache));
                return response;
            });
        })
    );
});
