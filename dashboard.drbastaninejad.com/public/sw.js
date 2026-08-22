/* SW-PURGE: unregister this worker and delete any previously cached API/HTML. */
self.addEventListener('install', function (event) {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil((async function () {
    var keys = await caches.keys();
    await Promise.all(keys.map(function (key) { return caches.delete(key); }));
    await self.registration.unregister();
    var windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    windows.forEach(function (client) {
      if (client && client.navigate) {
        client.navigate(client.url);
      }
    });
  })());
});

self.addEventListener('fetch', function () {
  // Network-only. Never cache.
});
