import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import type { MaybeRefOrGetter } from 'vue'
import type {
  Candidate,
  LookupResponse,
  Paginated,
  QuickAddInput,
} from '~/types'

export function useCandidatesQuery(
  search: MaybeRefOrGetter<string>,
  status: MaybeRefOrGetter<string> = '',
) {
  const api = useApi()
  return useQuery({
    queryKey: ['candidates', () => toValue(search), () => toValue(status)] as const,
    queryFn: () =>
      api<Paginated<Candidate>>('/candidates', {
        query: {
          q: toValue(search) || undefined,
          status: toValue(status) || undefined,
        },
      }),
  })
}

export function useCandidateQuery(id: MaybeRefOrGetter<string>) {
  const api = useApi()
  return useQuery({
    queryKey: ['candidate', () => toValue(id)] as const,
    queryFn: () => api<Candidate>(`/candidates/${toValue(id)}`),
  })
}

/** Deduplikacja w locie — zwraca istniejącego kandydata lub null. */
export async function lookupPhone(phone: string): Promise<LookupResponse> {
  const api = useApi()
  return api<LookupResponse>('/candidates/lookup', { query: { phone } })
}

export function useUpdateCandidate(id: MaybeRefOrGetter<string>) {
  const api = useApi()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: Partial<Candidate>) =>
      api<Candidate>(`/candidates/${toValue(id)}`, { method: 'PATCH', body: payload }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['candidate', toValue(id)] })
      queryClient.invalidateQueries({ queryKey: ['candidates'] })
      queryClient.invalidateQueries({ queryKey: ['completeness'] })
    },
  })
}

export function useDeleteCandidate() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (id: string) => api(`/candidates/${id}`, { method: 'DELETE' }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['candidates'] }),
  })
}

export function useCreateCandidate() {
  const api = useApi()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: QuickAddInput) =>
      api<Candidate>('/candidates', { method: 'POST', body: payload }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['candidates'] })
      queryClient.invalidateQueries({ queryKey: ['tasks'] })
    },
  })
}
