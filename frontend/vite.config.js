import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'
import { fileURLToPath, URL } from 'node:url'

/**
 * Substitutes __SITE_URL__ in index.html.
 *
 * Open Graph images must be absolute — WhatsApp and Facebook do not resolve
 * relative paths, and a blank preview on WhatsApp is a real cost for a product
 * whose links are shared there. Falls back to a sane origin so an unset env var
 * leaves a working URL rather than a literal placeholder in the markup.
 */
function siteUrlPlugin(siteUrl) {
  return {
    name: 'nuvo-site-url',
    transformIndexHtml: (html) => html.replaceAll('__SITE_URL__', siteUrl),
  }
}

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const siteUrl = (env.VITE_SITE_URL || 'https://nuvo.app').replace(/\/$/, '')

  return {
  plugins: [
    vue(),
    siteUrlPlugin(siteUrl),
    VitePWA({
      registerType: 'autoUpdate',
      injectRegister: 'auto',

      // Fichiers à précacher
      includeAssets: ['favicon.svg', 'icons/*.svg', 'icons/*.png'],

      // Manifest de l'application
      manifest: {
        name: 'Nuvo — Réservations en ligne',
        short_name: 'Nuvo',
        description: 'Gérez vos réservations sans stress. Notifications WhatsApp, calendrier et tableau de bord pour commerçants et entreprises.',
        theme_color: '#14603C',
        background_color: '#ffffff',
        display: 'standalone',
        orientation: 'portrait-primary',
        start_url: '/dashboard',
        id: '/dashboard',
        scope: '/',
        lang: 'fr',
        categories: ['business', 'productivity'],
        icons: [
          {
            src: '/icons/icon-192.png',
            sizes: '192x192',
            type: 'image/png',
            purpose: 'any',
          },
          {
            src: '/icons/icon-512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any',
          },
          {
            src: '/icons/icon-maskable-512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'maskable',
          },
          // SVG fallback pour navigateurs compatibles
          {
            src: '/icons/icon-512.svg',
            sizes: 'any',
            type: 'image/svg+xml',
            purpose: 'any',
          },
        ],
        shortcuts: [
          {
            name: 'Tableau de bord',
            short_name: 'Dashboard',
            url: '/dashboard',
            icons: [{ src: '/icons/icon-192.png', sizes: '192x192' }],
          },
          {
            name: 'Réservations',
            short_name: 'Réservations',
            url: '/dashboard/bookings',
            icons: [{ src: '/icons/icon-192.png', sizes: '192x192' }],
          },
        ],
      },

      // Stratégies de cache Workbox
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}'],
        navigateFallback: '/offline.html',
        navigateFallbackDenylist: [/^\/api/, /^\/storage/],
        cleanupOutdatedCaches: true,
        skipWaiting: true,
        clientsClaim: true,
        runtimeCaching: [
          // Google Fonts — cache 1 an
          {
            urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
            handler: 'CacheFirst',
            options: {
              cacheName: 'google-fonts-stylesheets',
              expiration: { maxEntries: 5, maxAgeSeconds: 365 * 24 * 60 * 60 },
            },
          },
          {
            urlPattern: /^https:\/\/fonts\.gstatic\.com\/.*/i,
            handler: 'CacheFirst',
            options: {
              cacheName: 'google-fonts-webfonts',
              expiration: { maxEntries: 20, maxAgeSeconds: 365 * 24 * 60 * 60 },
            },
          },
          // API — Network First (données fraîches), fallback cache 5 min
          {
            urlPattern: /\/api\/(dashboard|bookings|services|business).*/i,
            handler: 'NetworkFirst',
            options: {
              cacheName: 'api-cache',
              networkTimeoutSeconds: 5,
              expiration: { maxEntries: 50, maxAgeSeconds: 5 * 60 },
              cacheableResponse: { statuses: [0, 200] },
            },
          },
          // Page publique de réservation — Network First
          {
            urlPattern: /\/api\/b\/.*/i,
            handler: 'NetworkFirst',
            options: {
              cacheName: 'public-booking-cache',
              networkTimeoutSeconds: 4,
              expiration: { maxEntries: 30, maxAgeSeconds: 10 * 60 },
              cacheableResponse: { statuses: [0, 200] },
            },
          },
          // Images/storage — Cache First
          {
            urlPattern: /\/storage\/.*/i,
            handler: 'CacheFirst',
            options: {
              cacheName: 'storage-images',
              expiration: { maxEntries: 60, maxAgeSeconds: 30 * 24 * 60 * 60 },
              cacheableResponse: { statuses: [0, 200] },
            },
          },
        ],
      },

      // Désactivé en dev pour éviter le cache du service worker
      devOptions: {
        enabled: false,
      },
    }),
  ],

  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },

  server: {
    port: 5173,
    proxy: {
      '/api': { target: 'http://localhost:8000', changeOrigin: true },
      '/storage': { target: 'http://localhost:8000', changeOrigin: true },
    },
  },

  build: {
    // Sourcemaps make a production stack trace readable without shipping the
    // original sources inline.
    sourcemap: true,
  },
  }
})
