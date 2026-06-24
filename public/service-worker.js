const CACHE_NAME = 'bidall-v12';

const PRECACHE_URLS = [
    '/favicon.svg',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/images/gavel-logo.svg'
];

// Install — precache essential assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

// Activate — delete old version caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => Promise.all(
                cacheNames
                    .filter((name) => name.startsWith('bidall-') && name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            ))
            .then(() => self.clients.claim())
    );
});

// Fetch — routing by request type
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Non-GET requests (POST, PUT, DELETE) — never intercept, let browser handle natively
    if (request.method !== 'GET') {
        return;
    }

    // Ignore non-http(s) schemes (chrome-extension://, etc.)
    if (!url.protocol.startsWith('http')) {
        return;
    }

    // Cross-origin requests (e.g. Vite dev server on :5173, third-party CDNs) — let the
    // browser handle them. Intercepting causes "Failed to fetch" / "Failed to convert value
    // to 'Response'" errors when the upstream is down.
    if (url.origin !== self.location.origin) {
        return;
    }

    // Auth pages — never intercept, let browser handle natively (CSRF tokens + cookies must be fresh)
    if (url.pathname === '/login' || url.pathname === '/register' || url.pathname.startsWith('/password/') || url.pathname === '/accept-terms') {
        return;
    }

    // API requests — network only, never cache
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(fetch(request));
        return;
    }

    // Page navigations — network first, fallback to cache
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .catch(() => caches.match(request))
        );
        return;
    }

    // Static assets (CSS, JS, images, fonts) — cache first, network fallback
    if (isStaticAsset(url.pathname)) {
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) return cached;
                return fetch(request).then((response) => {
                    // Only cache successful responses
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    return response;
                }).catch(() => Response.error());
            })
        );
        return;
    }

    // Everything else — network first
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});

// Push notification received
self.addEventListener('push', (event) => {
    console.log('[SW] Push event received, has data:', !!event.data);
    let data = {};
    if (event.data) {
        try {
            data = event.data.json();
            console.log('[SW] Parsed JSON payload:', JSON.stringify(data));
        } catch (e) {
            const text = event.data.text();
            console.log('[SW] JSON parse failed, raw text:', text);
            data = { title: 'BidAll', body: text || 'New notification' };
        }
    } else {
        console.log('[SW] No data in push event');
        data = { title: 'BidAll', body: 'New notification' };
    }
    // White-label notifications can pass brand icon + theme via the payload
    const icon = data.icon || '/icons/icon-192x192.png';
    const badge = data.badge || '/icons/icon-192x192.png';

    // Set the OS-level app icon badge count (WhatsApp/Facebook-style) if the backend sent one.
    // This works even when the PWA is closed, because it's handled by the service worker.
    if (typeof data.unread_count === 'number' && self.navigator.setAppBadge) {
        self.navigator.setAppBadge(data.unread_count).catch(() => {});
    }

    event.waitUntil(
        self.registration.showNotification(data.title || 'BidAll', {
            body: data.body || '',
            icon: icon,
            badge: badge,
            data: { url: data.url || '/' }
        }).then(() => {
            console.log('[SW] showNotification succeeded');
        }).catch((err) => {
            console.error('[SW] showNotification FAILED:', err);
        })
    );
});

// Notification click — focus an existing PWA window if open, otherwise open a new one.
// Avoids spawning duplicate tabs when a user already has the app open.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification.data?.url || '/';

    event.waitUntil((async () => {
        const allClients = await clients.matchAll({ type: 'window', includeUncontrolled: true });
        const targetOrigin = self.location.origin;

        // Prefer same-origin clients. If one is already on the target URL, just focus it.
        for (const client of allClients) {
            try {
                const u = new URL(client.url);
                if (u.origin !== targetOrigin) continue;
                if (u.pathname + u.search === targetUrl || client.url === targetOrigin + targetUrl) {
                    return client.focus();
                }
            } catch (_) { /* ignore malformed URLs */ }
        }

        // Otherwise focus the first same-origin client and navigate it to the target.
        for (const client of allClients) {
            try {
                const u = new URL(client.url);
                if (u.origin !== targetOrigin) continue;
                if ('navigate' in client) {
                    await client.navigate(targetUrl);
                }
                return client.focus();
            } catch (_) { /* ignore */ }
        }

        // No existing window — open a new one.
        return clients.openWindow(targetUrl);
    })());
});

function isStaticAsset(pathname) {
    return /\.(css|js|png|jpg|jpeg|gif|svg|webp|ico|woff|woff2|ttf|eot)(\?.*)?$/i.test(pathname)
        || pathname.startsWith('/build/')
        || pathname.startsWith('/icons/')
        || pathname.startsWith('/images/')
        || pathname.startsWith('/storage/');
}
