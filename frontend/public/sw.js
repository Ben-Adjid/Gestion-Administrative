/*
 * Mode hors connexion de DemarchesTchad.
 * - Pages publiques : réseau d'abord, copie de secours en cache (fiches déjà consultées lisibles sans internet).
 * - Fichiers /_next/static : cache d'abord (noms versionnés, donc jamais périmés).
 * - Lecture publique de l'API (fiches, catégories) : réseau d'abord, copie de secours.
 * Jamais mis en cache : administration, espace citoyen, authentification, requêtes avec jeton, envois (POST…).
 */
const VERSION = 'v1';
const PAGES = `dt-pages-${VERSION}`;
const ASSETS = `dt-assets-${VERSION}`;
const API = `dt-api-${VERSION}`;
const OFFLINE_URL = '/offline.html';
const PRIVATE_PREFIXES = ['/admin', '/dashboard', '/auth'];
const MAX_PAGES = 60;

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(PAGES).then((cache) => cache.addAll([OFFLINE_URL, '/icon.svg'])).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => ![PAGES, ASSETS, API].includes(key)).map((key) => caches.delete(key))))
      .then(() => self.clients.claim()),
  );
});

async function trim(cacheName, max) {
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();
  for (const key of keys.slice(0, Math.max(0, keys.length - max))) await cache.delete(key);
}

async function networkFirst(request, cacheName, fallbackUrl) {
  const cache = await caches.open(cacheName);
  try {
    const response = await fetch(request);
    if (response.ok && response.type !== 'opaqueredirect') {
      await cache.put(request, response.clone());
      trim(cacheName, MAX_PAGES);
    }
    return response;
  } catch (error) {
    const cached = await cache.match(request, { ignoreVary: true });
    if (cached) return cached;
    if (fallbackUrl) return (await caches.match(fallbackUrl)) || Response.error();
    throw error;
  }
}

async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;
  const response = await fetch(request);
  if (response.ok) (await caches.open(ASSETS)).put(request, response.clone());
  return response;
}

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET' || request.headers.has('Authorization')) return;

  const url = new URL(request.url);
  const sameOrigin = url.origin === self.location.origin;

  if (sameOrigin && PRIVATE_PREFIXES.some((prefix) => url.pathname.startsWith(prefix))) return;

  if (sameOrigin && url.pathname.startsWith('/_next/static/')) {
    event.respondWith(cacheFirst(request));
    return;
  }

  if (request.mode === 'navigate' && sameOrigin) {
    event.respondWith(networkFirst(request, PAGES, OFFLINE_URL));
    return;
  }

  // Lecture publique de l'API (autre origine) : fiches et catégories uniquement.
  if (!sameOrigin && /^\/api\/v1\/(demarches|categories)(\/|$)/.test(url.pathname)) {
    event.respondWith(networkFirst(request, API));
  }
});
