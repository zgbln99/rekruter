import {
  VueQueryPlugin,
  QueryClient,
  hydrate,
} from '@tanstack/vue-query'

// Konfiguracja TanStack Query (cache danych serwerowych — DESIGN.md sekcja 8).
export default defineNuxtPlugin((nuxt) => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 30_000,
        retry: 1,
        refetchOnWindowFocus: false,
      },
    },
  })

  nuxt.vueApp.use(VueQueryPlugin, { queryClient })

  if (import.meta.client) {
    const state = useState('vue-query')
    if (state.value) {
      hydrate(queryClient, state.value)
    }
  }
})
