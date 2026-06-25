// Animal Mart - Service Worker (PWA offline caching)
const CACHE_NAME = 'animal-mart-v1';

// Core assets to cache on install
const PRECACHE_URLS = [
    '/Project/frontend/index.html',
    '/Project/frontend/animals.html',
    '/Project/frontend/about.html',
    '/Project/frontend/contact.html',
    '/Project/frontend/login.html',
    '/Project/frontend/register.html',
    '/Project/frontend/cart.html',
    '/Project/frontend/css/style.css',
    '/Project/frontend/js/main.js',
    '/Project/frontend/js/api.js',
    '/Project/frontend/js/cart.js',
    '/Project/frontend/js/chatbot.js'
];

// Install - pre-cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[SW] Pre-caching static assets');
            return cache.addAll(PRECACHE_URLS);
        })
    );
    self.skipWaiting();
});

// Activate - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter(name => name !== CACHE_NAME)
                    .map(name => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

// Fetch - network-first with fallback to cache for HTML/CSS/JS
self.addEventListener('fetch', (event) => {
    // Skip API requests - always use network for them (no caching)
    if (event.request.url.includes('/backend/api/')) {
        event.respondWith(fetch(event.request));
        return;
    }

    // Skip cross-origin requests (CDNs etc.)
    if (!event.request.url.startsWith(self.location.origin)) {
        event.respondWith(fetch(event.request));
        return;
    }

    // Network-first strategy for pages
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Cache successful GET responses
                if (event.request.method === 'GET' && response.status === 200) {
                    const cloned = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, cloned));
                }
                return response;
            })
            .catch(() => {
                // Fallback to cache when network fails
                return caches.match(event.request).then(cached => {
                    if (cached) return cached;
                    // Return offline fallback for HTML pages
                    if (event.request.headers.get('accept').includes('text/html')) {
                        return caches.match('/Project/frontend/index.html');
                    }
                });
            })
    );
});

// Listen for push notifications (placeholder — extend with your push server)
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Animal Mart';
    const options = {
        body: data.body || 'You have a new notification.',
        icon: 'https://img.icons8.com/fluency/192/dog.png',
        badge: 'https://img.icons8.com/fluency/96/dog.png',
        data: { url: data.url || '/Project/frontend/index.html' }
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

// Handle notification click — navigate to linked page
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});
