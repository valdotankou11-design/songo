/**
 * SONGO — Service Worker (PWA)
 * Règle simple et robuste :
 *   - ajax.php → TOUJOURS réseau, jamais de cache
 *   - Fonts    → Cache-First
 *   - Reste    → Network-First avec fallback cache
 */

const CACHE_NAME = 'songo-v2.0.0';

const ASSETS_STATIQUES = [
  '/',
  '/index.php',
  '/manifest.json',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
];

/* ── INSTALLATION ─────────────────────────────────────────────────────────── */
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache =>
      Promise.allSettled(
        ASSETS_STATIQUES.map(url =>
          cache.add(url).catch(e => console.warn('[SW] Cache raté:', url))
        )
      )
    ).then(() => self.skipWaiting())
  );
});

/* ── ACTIVATION — purge anciens caches ───────────────────────────────────── */
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(
        keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

/* ── FETCH ────────────────────────────────────────────────────────────────── */
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // ── ajax.php → RÉSEAU PUR, jamais de cache ────────────────────────────
  // C'est la règle la plus importante : on ne cache JAMAIS les réponses Ajax
  if (url.pathname.includes('ajax.php')) {
    event.respondWith(
      fetch(event.request).catch(() =>
        new Response(
          JSON.stringify({ succes: false, message: 'Hors ligne.', offline: true }),
          { status: 503, headers: { 'Content-Type': 'application/json; charset=utf-8' } }
        )
      )
    );
    return;
  }

  // ── Fonts Google → Cache-First ────────────────────────────────────────
  if (url.hostname.includes('fonts.googleapis.com') ||
      url.hostname.includes('fonts.gstatic.com')) {
    event.respondWith(cacheFirst(event.request));
    return;
  }

  // ── Tout le reste → Network-First ────────────────────────────────────
  event.respondWith(networkFirst(event.request));
});

/* ── STRATÉGIES ──────────────────────────────────────────────────────────── */
async function networkFirst(req) {
  try {
    const res = await fetch(req);
    if (res.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(req, res.clone());
    }
    return res;
  } catch {
    const cached = await caches.match(req);
    return cached || new Response('Hors ligne', { status: 503 });
  }
}

async function cacheFirst(req) {
  const cached = await caches.match(req);
  if (cached) return cached;
  try {
    const res = await fetch(req);
    if (res.ok) (await caches.open(CACHE_NAME)).put(req, res.clone());
    return res;
  } catch {
    return new Response('Hors ligne', { status: 503 });
  }
}

/* ── MESSAGES ────────────────────────────────────────────────────────────── */
self.addEventListener('message', event => {
  if (event.data?.type === 'SKIP_WAITING') self.skipWaiting();
});
