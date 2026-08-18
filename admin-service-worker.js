const ADMIN_CACHE = 'uppercrest-admin-v1';
const STATIC_ASSETS = [
  './manifest.json',
  './js/admin-app.js',
  './css/style.css',
  './images/logouppercrest.png'
];

self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(ADMIN_CACHE).then(function(cache) {
      return cache.addAll(STATIC_ASSETS);
    }).catch(function() {})
  );
  self.skipWaiting();
});

self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(keys) {
      return Promise.all(keys.map(function(key) {
        return key === ADMIN_CACHE ? null : caches.delete(key);
      }));
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', function(event) {
  if (event.request.method !== 'GET') return;
  const url = new URL(event.request.url);
  if (url.origin !== location.origin) return;

  const isStatic = /\.(css|js|png|jpg|jpeg|svg|webp|json)$/i.test(url.pathname);
  if (!isStatic) return;

  event.respondWith(
    caches.match(event.request).then(function(cached) {
      if (cached) return cached;
      return fetch(event.request).then(function(response) {
        const copy = response.clone();
        caches.open(ADMIN_CACHE).then(function(cache) {
          cache.put(event.request, copy);
        });
        return response;
      });
    })
  );
});
