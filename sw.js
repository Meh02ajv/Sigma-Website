// Service Worker pour SIGMA Alumni PWA
// Version du cache - incrémenter pour forcer la mise à jour
const CACHE_VERSION = 'sigma-alumni-v1.0.0';

// Ressources à mettre en cache immédiatement
const STATIC_CACHE_URLS = [
  '/',
  '/dashboard.php',
  '/messaging.php',
  '/evenements.php',
  '/yearbook.php',
  '/offline.php',
  '/img/icon-192.png',
  '/img/icon-512.png',
  '/manifest.json'
];

// Ressources à mettre en cache dynamiquement
const DYNAMIC_CACHE = 'sigma-dynamic-v1';

// Taille maximale du cache dynamique
const DYNAMIC_CACHE_LIMIT = 50;

// Installation du Service Worker
self.addEventListener('install', (event) => {
  console.log('[SW] Installation du Service Worker...');
  
  event.waitUntil(
    caches.open(CACHE_VERSION)
      .then((cache) => {
        console.log('[SW] Mise en cache des ressources statiques');
        // Ne pas bloquer l'installation si certaines ressources échouent
        return Promise.allSettled(
          STATIC_CACHE_URLS.map(url => 
            cache.add(url).catch(err => console.log('[SW] Échec cache:', url))
          )
        );
      })
      .then(() => {
        console.log('[SW] Installation terminée');
        return self.skipWaiting();
      })
  );
});

// Activation du Service Worker
self.addEventListener('activate', (event) => {
  console.log('[SW] Activation du Service Worker...');
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        // Supprimer les anciens caches
        return Promise.all(
          cacheNames
            .filter(name => name !== CACHE_VERSION && name !== DYNAMIC_CACHE)
            .map(name => {
              console.log('[SW] Suppression ancien cache:', name);
              return caches.delete(name);
            })
        );
      })
      .then(() => {
        console.log('[SW] Activation terminée');
        return self.clients.claim();
      })
  );
});

// Interception des requêtes réseau
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorer les requêtes non-GET
  if (request.method !== 'GET') {
    return;
  }

  // Ignorer les requêtes WebSocket
  if (url.protocol === 'ws:' || url.protocol === 'wss:') {
    return;
  }

  // Ignorer les requêtes vers des domaines externes (sauf CDN connus)
  if (url.origin !== location.origin && !isTrustedCDN(url.origin)) {
    return;
  }

  // Stratégie: Network First pour les pages HTML et API
  if (request.headers.get('accept')?.includes('text/html') || 
      url.pathname.includes('.php')) {
    event.respondWith(networkFirstStrategy(request));
  }
  // Stratégie: Cache First pour les assets statiques
  else if (isStaticAsset(url.pathname)) {
    event.respondWith(cacheFirstStrategy(request));
  }
  // Par défaut: Network First
  else {
    event.respondWith(networkFirstStrategy(request));
  }
});

// Stratégie Network First (réseau prioritaire)
async function networkFirstStrategy(request) {
  try {
    const networkResponse = await fetch(request);
    
    // Mettre en cache si succès
    if (networkResponse && networkResponse.status === 200) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, networkResponse.clone());
      await limitCacheSize(DYNAMIC_CACHE, DYNAMIC_CACHE_LIMIT);
    }
    
    return networkResponse;
  } catch (error) {
    console.log('[SW] Réseau indisponible, tentative cache:', request.url);
    
    // Fallback sur le cache
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Si c'est une page HTML, retourner la page offline
    if (request.headers.get('accept')?.includes('text/html')) {
      return caches.match('/offline.php');
    }
    
    // Sinon, retourner une erreur
    return new Response('Contenu non disponible hors ligne', {
      status: 503,
      statusText: 'Service Unavailable',
      headers: new Headers({
        'Content-Type': 'text/plain'
      })
    });
  }
}

// Stratégie Cache First (cache prioritaire)
async function cacheFirstStrategy(request) {
  const cachedResponse = await caches.match(request);
  
  if (cachedResponse) {
    // Retourner le cache immédiatement
    // Mais mettre à jour en arrière-plan
    fetch(request).then(networkResponse => {
      if (networkResponse && networkResponse.status === 200) {
        caches.open(DYNAMIC_CACHE).then(cache => {
          cache.put(request, networkResponse);
        });
      }
    }).catch(() => {});
    
    return cachedResponse;
  }
  
  // Pas en cache, récupérer du réseau
  try {
    const networkResponse = await fetch(request);
    
    if (networkResponse && networkResponse.status === 200) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, networkResponse.clone());
      await limitCacheSize(DYNAMIC_CACHE, DYNAMIC_CACHE_LIMIT);
    }
    
    return networkResponse;
  } catch (error) {
    console.log('[SW] Impossible de récupérer:', request.url);
    throw error;
  }
}

// Limiter la taille du cache dynamique
async function limitCacheSize(cacheName, maxItems) {
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();
  
  if (keys.length > maxItems) {
    // Supprimer les plus anciennes entrées
    const itemsToDelete = keys.length - maxItems;
    for (let i = 0; i < itemsToDelete; i++) {
      await cache.delete(keys[i]);
    }
  }
}

// Vérifier si c'est un asset statique
function isStaticAsset(pathname) {
  const staticExtensions = ['.css', '.js', '.jpg', '.jpeg', '.png', '.gif', 
                           '.svg', '.woff', '.woff2', '.ttf', '.eot', '.ico'];
  return staticExtensions.some(ext => pathname.endsWith(ext));
}

// Vérifier si c'est un CDN de confiance
function isTrustedCDN(origin) {
  const trustedCDNs = [
    'https://cdnjs.cloudflare.com',
    'https://cdn.jsdelivr.net',
    'https://unpkg.com',
    'https://fonts.googleapis.com',
    'https://fonts.gstatic.com'
  ];
  return trustedCDNs.some(cdn => origin.startsWith(cdn));
}

// Messages depuis l'application
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (event.data && event.data.type === 'CACHE_URLS') {
    event.waitUntil(
      caches.open(DYNAMIC_CACHE).then(cache => {
        return cache.addAll(event.data.urls);
      })
    );
  }
  
  if (event.data && event.data.type === 'CLEAR_CACHE') {
    event.waitUntil(
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(name => caches.delete(name))
        );
      })
    );
  }
});

// Notifications Push (préparation pour future implémentation)
self.addEventListener('push', (event) => {
  if (!event.data) return;
  
  const data = event.data.json();
  const options = {
    body: data.body || 'Nouvelle notification SIGMA Alumni',
    icon: '/img/icon-192.png',
    badge: '/img/icon-192.png',
    vibrate: [200, 100, 200],
    data: {
      url: data.url || '/dashboard.php',
      dateOfArrival: Date.now()
    },
    actions: [
      {
        action: 'open',
        title: 'Ouvrir',
        icon: '/img/icon-192.png'
      },
      {
        action: 'close',
        title: 'Fermer'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title || 'SIGMA Alumni', options)
  );
});

// Gestion des clics sur les notifications
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  
  if (event.action === 'close') {
    return;
  }
  
  const urlToOpen = event.notification.data?.url || '/dashboard.php';
  
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        // Si une fenêtre est déjà ouverte, la focus
        for (let client of clientList) {
          if (client.url === urlToOpen && 'focus' in client) {
            return client.focus();
          }
        }
        // Sinon, ouvrir une nouvelle fenêtre
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
  );
});

// Synchronisation en arrière-plan (pour futures fonctionnalités)
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-messages') {
    event.waitUntil(syncMessages());
  }
});

async function syncMessages() {
  // Placeholder pour synchronisation future
  console.log('[SW] Synchronisation des messages...');
}

console.log('[SW] Service Worker chargé');
