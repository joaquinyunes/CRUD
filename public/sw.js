/* Service worker del POS — contingencia offline.
   Cachea el shell del POS y los assets para que la caja siga funcionando
   sin internet. Las ventas se encolan en el cliente (localStorage) y se
   sincronizan al recuperar la conexión. */

const CACHE = 'pos-shell-v1';
const SHELL = ['/pos'];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // Navegación al POS: network-first, cae al cache si no hay red.
    if (request.mode === 'navigate' && url.pathname.startsWith('/pos')) {
        event.respondWith(
            fetch(request)
                .then((resp) => { caches.open(CACHE).then((c) => c.put('/pos', resp.clone())); return resp; })
                .catch(() => caches.match('/pos'))
        );
        return;
    }

    // Assets (build de Vite, css, fuentes, catálogo): cache-first con refresco.
    if (/\/(build|css|fonts)\//.test(url.pathname) || url.pathname === '/pos/catalogo') {
        event.respondWith(
            caches.match(request).then((cached) => {
                const fresh = fetch(request).then((resp) => {
                    if (resp.ok) caches.open(CACHE).then((c) => c.put(request, resp.clone()));
                    return resp;
                }).catch(() => cached);
                return cached || fresh;
            })
        );
    }
});
