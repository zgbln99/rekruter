import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import type { MaybeRefOrGetter } from 'vue'
import type { Company, Paginated } from '~/types'

export function useCompaniesQuery(search: MaybeRefOrGetter<string> = '') {
  const api = useApi()
  return useQuery({
    queryKey: ['companies', () => toValue(search)] as const,
    queryFn: () =>
      api<Paginated<Company>>('/companies', {
        query: { q: toValue(search) || undefined },
      }),
  })
}

export function useCompanyQuery(id: MaybeRefOrGetter<string>) {
  const api = useApi()
  return useQuery({
    queryKey: ['company', () => toValue(id)] as const,
    queryFn: () => api<Company>(`/companies/${toValue(id)}`),
  })
}

export function useCreateCompany() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (payload: Partial<Company>) =>
      api<Company>('/companies', { method: 'POST', body: payload }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['companies'] }),
  })
}

export function useUpdateCompany(id: MaybeRefOrGetter<string>) {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (payload: Partial<Company>) =>
      api<Company>(`/companies/${toValue(id)}`, { method: 'PUT', body: payload }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['company', toValue(id)] })
      queryClient.invalidateQueries({ queryKey: ['companies'] })
    },
  })
}
