import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import type { Settings } from '~/types'

export function useSettingsQuery() {
  const api = useApi()
  return useQuery({
    queryKey: ['settings'] as const,
    queryFn: () => api<Settings>('/settings'),
  })
}

export function useUpdateSettings() {
  const api = useApi()
  const queryClient = useQueryClient()
  const auth = useAuthStore()
  return useMutation({
    mutationFn: (payload: Partial<Settings> & { openai_api_key?: string }) =>
      api<Settings>('/settings', { method: 'PUT', body: payload }),
    onSuccess: async () => {
      queryClient.invalidateQueries({ queryKey: ['settings'] })
      // Odśwież dane użytkownika (nazwa agencji w nagłówkach).
      await auth.fetchMe()
    },
  })
}
