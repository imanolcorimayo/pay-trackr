// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  devtools: { enabled: true },
  css: ["~/assets/css/main.css", "~/assets/css/style.css", "vue3-toastify/dist/index.css"],

  postcss: {
    plugins: {
      tailwindcss: {},
      autoprefixer: {}
    }
  },

  modules: [
    "@samk-dev/nuxt-vcalendar",
    "dayjs-nuxt",
    "@vueuse/nuxt",
    "@pinia/nuxt",
    "@vite-pwa/nuxt",
    "unplugin-icons/nuxt"
  ],

  dayjs: {
    locales: ['es'],
    defaultLocale: 'es',
  },

  ssr: false,

  runtimeConfig: {
    public: {
      contactEmail: process.env.CONTACT_EMAIL || 'contact@wiseutils.com',
      // Firebase configuration
      firebaseApiKey: process.env.FIREBASE_API_KEY,
      firebaseAuthDomain: "pay-tracker-7a5a6.firebaseapp.com",
      firebaseProjectId: process.env.FIREBASE_PROJECT_ID,
      firebaseStorageBucket: "pay-tracker-7a5a6.appspot.com",
      firebaseMessagingSenderId: "16390920244",
      firebaseAppId: "1:16390920244:web:adc5a4919d9dd457705261"
    }
  },

  // @ts-ignore
  pwa: {
    registerType: "autoUpdate",
    manifest: {
      name: "PayTrackr",
      short_name: "PayTrackr",
      description: "Track and manage your recurring and one-time payments",
      theme_color: "#27292D", // Assuming this is your primary color
      background_color: "#27292D",
      display: "standalone",
      orientation: "portrait",
      start_url: "/",
      icons: [
        {
          src: "/img/new-logo.png",
          sizes: "192x192",
          type: "image/png"
        },
        {
          src: "/img/new-logo.png",
          sizes: "512x512",
          type: "image/png"
        },
        {
          src: "/img/new-logo.png",
          sizes: "512x512",
          type: "image/png",
          purpose: "any maskable"
        }
      ]
    },
    workbox: {
      navigateFallback: "/",
      globPatterns: ['**/*.{js,css,html,png,svg,ico}'],
      runtimeCaching: [
        {
          urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
          handler: 'CacheFirst',
          options: {
            cacheName: 'google-fonts-cache',
            expiration: {
              maxEntries: 10,
              maxAgeSeconds: 60 * 60 * 24 * 365 // <== 365 days
            },
            cacheableResponse: {
              statuses: [0, 200]
            }
          }
        },
        {
          urlPattern: /^https:\/\/fonts\.gstatic\.com\/.*/i,
          handler: 'CacheFirst',
          options: {
            cacheName: 'gstatic-fonts-cache',
            expiration: {
              maxEntries: 10,
              maxAgeSeconds: 60 * 60 * 24 * 365 // <== 365 days
            },
            cacheableResponse: {
              statuses: [0, 200]
            },
          }
        }
      ]
    },
    client: {
      installPrompt: true,
      periodicSyncForUpdates: 20 // check for updates every 20 minutes
    },
    devOptions: {
      enabled: true,
      type: "module"
    }
  },

  app: {
    head: {
      htmlAttrs: { dir: "ltr", lang: "es" },
      link: [{ rel: "icon", type: "image/png", href: "/img/new-logo.png" }],
      meta: [
        {
          name: "google-site-verification",
          content: "E0U6Yf1iG222FwlRLisvf7JLYZLZQnT8CLJ3QKo4tjQ"
        },
        {
          name: "viewport",
          content: "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
        }
      ]
    }
  },

  compatibilityDate: "2024-07-13"
});
