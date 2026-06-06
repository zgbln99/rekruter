import { useQuery } from '@tanstack/vue-query'
import type { NotificationsResponse } from '~/types'

/** Powiadomienia „na żywo" — odświeżane co 60 s. */
export function useNotificationsQuery() {
  const api = useApi()
  return useQuery({
    queryKey: ['notifications'] as const,
    queryFn: () => api<NotificationsResponse>('/notifications'),
    refetchInterval: 60_000,
    refetchOnWindowFocus: true,
  })
}
