// Arquivo sw.js (Service Worker)
// Este é um placeholder básico.
// Para funcionalidade PWA (Progressive Web App) offline,
// você precisaria adicionar estratégias de cache aqui.

self.addEventListener('install', (event) => {
  console.log('Service Worker instalado.');
});

self.addEventListener('fetch', (event) => {
  // Não faz cache por padrão, apenas passa a requisição
  event.respondWith(fetch(event.request));
});