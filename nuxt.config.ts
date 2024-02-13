// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  devtools: { enabled: true },
  css: ['~/assets/css/main.css', '~/assets/css/style.css', "vue3-toastify/dist/index.css"],
  postcss: {
    plugins: {
      tailwindcss: {},
      autoprefixer: {},
    },
  },
  modules: [
    '@samk-dev/nuxt-vcalendar',
    'dayjs-nuxt',
    '@vueuse/nuxt',
    'nuxt-vuefire',
    '@pinia/nuxt',
  ],
  ssr: false,

  vuefire: {
    // ensures the auth module is enabled
    auth: {
      enabled: true
    },
    config: {
      apiKey: process.env.FIREBASE_API_KEY,
      authDomain: "pay-tracker-7a5a6.firebaseapp.com",
      projectId: process.env.FIREBASE_PROJECT_ID,
      storageBucket: "pay-tracker-7a5a6.appspot.com",
      messagingSenderId: "16390920244",
      appId: "1:16390920244:web:adc5a4919d9dd457705261"
    },
  },

  app: {
    head: {
        htmlAttrs: { dir: 'ltr', lang: 'en' },
        link: [{ rel: 'icon', type: 'image/png', href: "/img/logo.png" }],
        meta: [{
          name: 'google-site-verification',
          content: "E0U6Yf1iG222FwlRLisvf7JLYZLZQnT8CLJ3QKo4tjQ"
        }]
    },
},
})
