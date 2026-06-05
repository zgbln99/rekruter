import { useQuery } from '@tanstack/vue-query'
import type { DashboardStats } from '~/types'

export function useDashboardQuery() {
  const api = useApi()
  return useQuery({
    queryKey: ['dashboard'] as const,
    queryFn: () => api<DashboardStats>('/dashboard'),
  })
}
