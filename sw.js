// Service Worker für Labor Virus-Jagd PWA – Mobile-Fit + Robust Audio + Colored Viruses Patch
// Strategie: Cache-First für statische Assets, mit Hintergrund-Update der HTML.
// Cache-Name muss bei jedem Inhalts-Update inkrementiert werden, damit alte Versionen
// beim nächsten Start verworfen werden.

const CACHE_VERSION = 'v34-mobile-overlay-desktop-full';
const CACHE_NAME = 'virus-jagd-' + CACHE_VERSION;

// Alle Assets relativ zum SW-Scope. Das Spiel selbst ist eine einzige HTML-Datei,
// das Hintergrundbild ist bereits als Data-URL eingebettet.
const PRECACHE_URLS = [
  './',
  './index.html',
  './manifest.webmanifest',
  './icon-192.png',
  './icon-512.png',
  './icon-maskable-512.png',
  './apple-touch-icon.png',
  './favicon-64.png',
  './intro/virus_invasion_intro.png',
  './intro/virus_invasion_portrait_intro.png',
  './intro/virus_invasion_desktop_intro.png',
  './ranks/rank_assistent.png',
  './ranks/rank_analyst.png',
  './ranks/rank_virologe.png',
  './ranks/rank_seuchenjaeger.png',
  './ranks/rank_elite.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(PRECACHE_URLS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;

  // Nur GET-Requests behandeln
  if (req.method !== 'GET') return;

  // Cross-Origin Requests durchreichen
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // Navigation-Requests (HTML): Network-First mit Cache-Fallback,
  // damit Updates sichtbar werden, ohne dass Offline-Betrieb leidet.
  if (req.mode === 'navigate' || (req.headers.get('accept') || '').includes('text/html')) {
    event.respondWith(
      fetch(req)
        .then((res) => {
          const clone = res.clone();
          caches.open(CACHE_NAME).then((c) => c.put(req, clone)).catch(() => {});
          return res;
        })
        .catch(() => caches.match(req).then((cached) => cached || caches.match('./index.html')))
    );
    return;
  }

  // Alle anderen Same-Origin Requests: Cache-First mit Hintergrund-Refresh
  event.respondWith(
    caches.match(req).then((cached) => {
      const fetchPromise = fetch(req).then((res) => {
        if (res && res.status === 200 && res.type === 'basic') {
          const clone = res.clone();
          caches.open(CACHE_NAME).then((c) => c.put(req, clone)).catch(() => {});
        }
        return res;
      }).catch(() => cached);
      return cached || fetchPromise;
    })
  );
});

// Manueller Trigger für sofortige Aktivierung einer neuen SW-Version
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
