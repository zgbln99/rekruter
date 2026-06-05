import type { $Fetch } from 'nitropack'

/**
 * Klient API z dołączonym tokenem Bearer i bazowym URL z runtimeConfig.
 * Na 401 czyści sesję i przekierowuje do logowania.
 */
export const useApi = (): $Fetch => {
  const config = useRuntimeConfig()
  const auth = useAuthStore()

  return $fetch.create({
    baseURL: config.public.apiBase,
    headers: { Accept: 'application/json' },
    onRequest({ options }) {
      if (auth.token) {
        options.headers.set('Authorization', `Bearer ${auth.token}`)
      }
    },
    onResponseError({ response }) {
      if (response.status === 401) {
        auth.clear()
        if (import.meta.client) {
          navigateTo('/login')
        }
      }
    },
  })
}
