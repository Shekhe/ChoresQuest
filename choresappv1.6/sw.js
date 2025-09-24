// A service worker with a caching strategy to enable offline functionality.



// Give a unique name to your cache

const CACHE_NAME = 'choresquest-cache-v1.6'; // Incremented version



// List all the essential files (assets) that your app needs to function offline.

const URLS_TO_CACHE = [

  '/',

  '/index.php',

  '/style.css',

  '/js/main.js',

  '/js/api.js',

  '/js/auth.js',

  '/js/child.js',

  '/js/config.js',

  '/js/dom.js',

  '/js/parent.js',

  '/js/ui.js',

  '/js/utils.js',

  '/js/icon-library.js', // Added icon-library.js

  '/imgs/logo.png',

  '/imgs/favicon.png',

  // Template files (PHP includes, cached as HTML)

  '/templates/landing_page.php',

  '/templates/auth_modals.php',

  '/templates/policy_modal.php',

  '/templates/recovery_modals.php',

  '/templates/parent_dashboard.php',

  '/templates/kids_zone.php',

  '/templates/message_modals.php',

  // Legal files

  '/privacy.html',

  '/terms.html',

  // External resources

  'https://cdn.tailwindcss.com',

  'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',

  'https://fonts.googleapis.com/css2?family=Fredoka+One&display=swap',

  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'

];



// --- INSTALL Event ---

// This event fires when the service worker is first installed.

// We open our cache and add all the essential files to it.

self.addEventListener('install', (event) => {

  event.waitUntil(

    caches.open(CACHE_NAME)

      .then((cache) => {

        console.log('Opened cache and adding assets');

        return cache.addAll(URLS_TO_CACHE);

      })

  );

});



// --- FETCH Event ---

// This event fires every time the app requests a resource (like a CSS file, an image, etc.).

// We check if the requested file is in our cache. If it is, we serve it from the cache.

// If it's not in the cache, we fetch it from the network.

self.addEventListener('fetch', (event) => {

  event.respondWith(

    caches.match(event.request)

      .then((response) => {

        // If the response is in the cache, return it.

        if (response) {

          return response;

        }

        // If it's not in the cache, fetch it from the network.

        return fetch(event.request);

      })

  );

});