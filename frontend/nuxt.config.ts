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
    pageTransition: { name: 'page', mode: 'out-in' },
    layoutTransition: { name: 'page', mode: 'out-in' },
    head: {
      htmlAttrs: { lang: 'pl' },
      title: 'edge recruiting',
      meta: [
        { charset: 'utf-8' },
        {
          name: 'viewport',
          content:
            'width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1',
        },
        { name: 'theme-color', content: '#dc2626' },
      ],
      link: [
        { rel: 'icon', type: 'image/png', sizes: '32x32', href: '/favicon-32x32.png' },
        { rel: 'icon', type: 'image/png', sizes: '16x16', href: '/favicon-16x16.png' },
        { rel: 'apple-touch-icon', sizes: '180x180', href: '/apple-touch-icon.png' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        {
          rel: 'preconnect',
          href: 'https://fonts.gstatic.com',
          crossorigin: '',
        },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        },
      ],
    },
  },

  // PWA — instalowalna, mobile-first. Pełna strategia offline: Faza 4.
  pwa: {
    registerType: 'autoUpdate',
    manifest: {
      name: 'edge recruiting',
      short_name: 'edge',
      description: 'edge recruiting — system rekrutacji kierowców zawodowych',
      lang: 'pl',
      display: 'standalone',
      orientation: 'portrait',
      background_color: '#ffffff',
      theme_color: '#dc2626',
      icons: [
        { src: 'pwa-192x192.png', sizes: '192x192', type: 'image/png' },
        { src: 'pwa-512x512.png', sizes: '512x512', type: 'image/png' },
        { src: 'pwa-maskable-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
      ],
    },
    workbox: {
      navigateFallback: '/',
      // Dołącz handler Web Push do service workera.
      importScripts: ['/push-sw.js'],
    },
    devOptions: {
      enabled: false,
    },
  },
})
