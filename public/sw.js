/* UKK SCP - Service Worker
   - Aset statis (css/js/gambar/CDN): cache-first
   - Halaman (HTML): network-first, fallback ke /offline.html
   - Request non-GET (login, simpan jurnal, dll.) TIDAK pernah di-cache.
   Naikkan VERSION setiap kali mengubah aset agar cache lama dibuang. */
const VERSION = 'scp-v1';
const STATIC_CACHE = VERSION + '-static';
const PRECACHE = ['/offline.html', '/images/logo-smkn1-bantul.png', '/css/style.css', '/css/mobile.css', '/js/mobile.js'];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(STATIC_CACHE).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  const sameOrigin = url.origin === self.location.origin;
  const isCdn = /cdn\.jsdelivr\.net|fonts\.(googleapis|gstatic)\.com/.test(url.hostname);

  // Jangan sentuh data dinamis / sesi
  if (sameOrigin && /^\/(notifikasi|logout|up)/.test(url.pathname)) return;

  // Navigasi halaman: network-first
  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
    return;
  }

  // Aset statis: cache-first
  const isStatic = (sameOrigin && /\.(css|js|png|jpe?g|svg|webp|ico|woff2?)$/.test(url.pathname)) || isCdn;
  if (isStatic) {
    e.respondWith(
      caches.match(req).then((hit) => hit || fetch(req).then((res) => {
        if (res && (res.ok || res.type === 'opaque')) {
          const copy = res.clone();
          caches.open(STATIC_CACHE).then((c) => c.put(req, copy));
        }
        return res;
      }))
    );
  }
});
