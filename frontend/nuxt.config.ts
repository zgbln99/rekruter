// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-01-01',
  devtools: { enabled: true },

  // Aplikacja za loginem — SPA (bez SSR). Decyzja: DESIGN.md sekcja 8 / D3.
  ssr: false,

  modules: [
    '@nuxtjs/tailwindcss',
    '@pinia/nuxt',
    '@vite-pwa/nuxt',
  ],

  css: ['~/assets/css/main.css'],

  runtimeConfig: {
    public: {
      // Bazowy adres API; nadpisywany przez NUXT_PUBLIC_API_BASE w runtime.
      apiBase: 'http://localhost:8000/api/v1',
    },
  },

  app: {
    head: {
      htmlAttrs: { lang: 'pl' },
      title: 'Rekruter',
      meta: [
        { charset: 'utf-8' },
        {
          name: 'viewport',
          content:
            'width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1',
        },
        { name: 'theme-color', content: '#0f766e' },
      ],
    },
  },

  // PWA — instalowalna, mobile-first. Pełna strategia offline: Faza 4.
  pwa: {
    registerType: 'autoUpdate',
    manifest: {
      name: 'Rekruter',
      short_name: 'Rekruter',
      description: 'Recruitment Operating System dla kierowców zawodowych',
      lang: 'pl',
      display: 'standalone',
      orientation: 'portrait',
      background_color: '#ffffff',
      theme_color: '#0f766e',
      icons: [
        { src: 'pwa-192x192.png', sizes: '192x192', type: 'image/png' },
        { src: 'pwa-512x512.png', sizes: '512x512', type: 'image/png' },
      ],
    },
    workbox: {
      navigateFallback: '/',
    },
    devOptions: {
      enabled: false,
    },
  },
})
