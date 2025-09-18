/* ===== Menu App Service Worker ===== */
const VERSION = 'v1.0.0';
const STATIC_CACHE = `menuapp-static-${VERSION}`;
const RUNTIME_CACHE = `menuapp-runtime-${VERSION}`;

const STATIC_ASSETS = [
  // ルート
  '/menuapp/public/',
  '/menuapp/public/week.php',
  '/menuapp/public/recipes/',
  '/menuapp/public/shopping_list.php',
  '/menuapp/public/pantry/',
  // CSS / JS
  '/menuapp/public/assets/css/style.css',
  '/menuapp/public/assets/js/week.js',
  '/menuapp/public/assets/js/recipe_form.js',
  '/menuapp/public/icons/icon-192.png',
  '/menuapp/public/icons/icon-512.png',
  // フォント等（使っていれば追加）
  // 予備のオフラインページ
  '/menuapp/public/offline.html'
];

// インストール：静的アセットをキャッシュ
self.addEventListener('install', (e) => {
  self.skipWaiting();
  e.waitUntil(
    caches.open(STATIC_CACHE).then((cache) => cache.addAll(STATIC_ASSETS))
  );
});

// 古いキャッシュを削除
self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys
          .filter(k => ![STATIC_CACHE, RUNTIME_CACHE].includes(k))
          .map(k => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

// フェッチ戦略
self.addEventListener('fetch', (e) => {
  const req = e.request;
  const url = new URL(req.url);

  // 同一オリジン以外は触らない
  if (url.origin !== self.location.origin) return;

  // POST/PUT等はキャッシュしない（そのままネットへ）
  if (req.method !== 'GET') return;

  // HTMLナビゲーションは Network First（オフライン時はキャッシュ→offline）
  const isHTML =
    req.headers.get('accept')?.includes('text/html') ||
    req.mode === 'navigate';

  if (isHTML) {
    e.respondWith(
      fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(RUNTIME_CACHE).then(c => c.put(req, copy));
          return res;
        })
        .catch(async () => {
          const cache = await caches.open(RUNTIME_CACHE);
          const cached = await cache.match(req);
          return (
            cached ||
            (await caches.match('/menuapp/public/offline.html')) ||
            new Response('Offline', { status: 503, statusText: 'Offline' })
          );
        })
    );
    return;
  }

  // CSS/JS/画像などは Stale-While-Revalidate
  e.respondWith(
    caches.match(req).then((cached) => {
      const fetchPromise = fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(RUNTIME_CACHE).then((c) => c.put(req, copy));
          return res;
        })
        .catch(() => cached); // ネット失敗時はキャッシュ
      return cached || fetchPromise;
    })
  );
});
