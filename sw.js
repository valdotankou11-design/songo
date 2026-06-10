/**
 * SONGO — Service Worker (PWA)
 * Stratégie : Cache-First pour les assets statiques,
 *             Network-First pour les requêtes Ajax (état du jeu).
 */

const CACHE_NAME    = 'songo-v1.0.0';
const AJAX_URL      = 'ajax.php';

// Assets statiques à mettre en cache à l'installation
const ASSETS_STATIQUES = [
  '/songo_v2/',
  '/songo_v2/index.php',
  '/songo_v2/jeu.php',
  '/songo_v2/manifest.json',
  '/songo_v2/icons/icon-192.png',
  '/songo_v2/icons/icon-512.png',
  'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@400;500;600&display=swap',
];

/* ══════════════════════════════════════════════
   INSTALLATION — mise en cache des assets
   ══════════════════════════════════════════════ */
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('[SW] Installation — mise en cache des assets');
      // On met en cache les assets critiques, on ignore les erreurs individuelles
      return Promise.allSettled(
        ASSETS_STATIQUES.map(url =>
          cache.add(url).catch(e => console.warn('[SW] Impossible de cacher:', url, e.message))
        )
      );
    }).then(() => self.skipWaiting())
  );
});

/* ══════════════════════════════════════════════
   ACTIVATION — nettoyage des anciens caches
   ══════════════════════════════════════════════ */
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys
          .filter(key => key !== CACHE_NAME)
          .map(key => {
            console.log('[SW] Suppression ancien cache:', key);
            return caches.delete(key);
          })
      )
    ).then(() => self.clients.claim())
  );
});

/* ══════════════════════════════════════════════
   FETCH — stratégie de cache
   ══════════════════════════════════════════════ */
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // ── Requêtes Ajax (ajax.php) → Network-First ──────────────────────────
  // On veut toujours l'état le plus récent du jeu.
  // Si réseau indisponible, on retourne une réponse d'erreur JSON propre.
  if (url.pathname.includes(AJAX_URL)) {
    event.respondWith(networkFirst(event.request));
    return;
  }

  // ── Fonts Google → Cache-First (évite les requêtes réseau répétées) ───
  if (url.hostname.includes('fonts.googleapis.com') ||
      url.hostname.includes('fonts.gstatic.com')) {
    event.respondWith(cacheFirst(event.request));
    return;
  }

  // ── Pages PHP et assets locaux → Stale-While-Revalidate ───────────────
  // Sert depuis le cache immédiatement, met à jour en arrière-plan.
  if (url.pathname.startsWith('/songo_v2/')) {
    event.respondWith(staleWhileRevalidate(event.request));
    return;
  }
});

/* ══════════════════════════════════════════════
   STRATÉGIES DE CACHE
   ══════════════════════════════════════════════ */

/**
 * Network-First : essaie le réseau, fallback cache.
 * Pour ajax.php : si hors-ligne, retourne un JSON d'erreur clair.
 */
async function networkFirst(request) {
  try {
    const reponseReseau = await fetch(request);
    // Mettre en cache la réponse réussie
    if (reponseReseau.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, reponseReseau.clone());
    }
    return reponseReseau;
  } catch (err) {
    // Réseau indisponible
    const cached = await caches.match(request);
    if (cached) return cached;

    // Aucun cache dispo : retourner une réponse JSON d'erreur
    return new Response(
      JSON.stringify({
        succes: false,
        message: 'Hors ligne. Vérifiez votre connexion réseau.',
        offline: true,
      }),
      {
        status: 503,
        headers: { 'Content-Type': 'application/json; charset=utf-8' },
      }
    );
  }
}

/**
 * Cache-First : sert depuis le cache, réseau si absent.
 * Idéal pour les fonts et ressources immuables.
 */
async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;

  try {
    const reponseReseau = await fetch(request);
    if (reponseReseau.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, reponseReseau.clone());
    }
    return reponseReseau;
  } catch (err) {
    return new Response('Ressource indisponible hors ligne.', { status: 503 });
  }
}

/**
 * Stale-While-Revalidate : sert le cache immédiatement,
 * met à jour en arrière-plan pour la prochaine visite.
 */
async function staleWhileRevalidate(request) {
  const cache  = await caches.open(CACHE_NAME);
  const cached = await cache.match(request);

  const fetchPromise = fetch(request).then(reponse => {
    if (reponse.ok) cache.put(request, reponse.clone());
    return reponse;
  }).catch(() => null);

  return cached || fetchPromise;
}

/* ══════════════════════════════════════════════
   MESSAGES depuis le client
   ══════════════════════════════════════════════ */
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  if (event.data && event.data.type === 'GET_VERSION') {
    event.ports[0].postMessage({ version: CACHE_NAME });
  }
});
