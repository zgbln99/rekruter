import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import type { MaybeRefOrGetter } from 'vue'
import type { Candidate, JobPosting, Paginated } from '~/types'

export function useJobOffersQuery(
  companyId: MaybeRefOrGetter<string> = '',
  archived: MaybeRefOrGetter<boolean> = false,
) {
  const api = useApi()
  return useQuery({
    queryKey: ['job-offers', () => toValue(companyId), () => toValue(archived)] as const,
    queryFn: () =>
      api<Paginated<JobPosting>>('/job-offers', {
        query: {
          company_id: toValue(companyId) || undefined,
          archived: toValue(archived) ? '1' : undefined,
        },
      }),
  })
}

export function useArchiveJobOffer(id: MaybeRefOrGetter<string>) {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (archived: boolean) =>
      api<JobPosting>(`/job-offers/${toValue(id)}/${archived ? 'archive' : 'unarchive'}`, { method: 'POST' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['job-offer', toValue(id)] })
      queryClient.invalidateQueries({ queryKey: ['job-offers'] })
    },
  })
}

export function useJobOfferQuery(id: MaybeRefOrGetter<string>) {
  const api = useApi()
  return useQuery({
    queryKey: ['job-offer', () => toValue(id)] as const,
    queryFn: () => api<JobPosting>(`/job-offers/${toValue(id)}`),
  })
}

export function useCreateJobOffer() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (payload: Partial<JobPosting>) =>
      api<JobPosting>('/job-offers', { method: 'POST', body: payload }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['job-offers'] })
      queryClient.invalidateQueries({ queryKey: ['company'] })
    },
  })
}

export function useUpdateJobOffer(id: MaybeRefOrGetter<string>) {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (payload: Partial<JobPosting>) =>
      api<JobPosting>(`/job-offers/${toValue(id)}`, { method: 'PUT', body: payload }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['job-offer', toValue(id)] })
      queryClient.invalidateQueries({ queryKey: ['job-offers'] })
    },
  })
}

/** Pobiera zdjęcie europejskiej ciężarówki (Unsplash) i ustawia jako okładkę. */
export function useFetchOfferCover(id: MaybeRefOrGetter<string>) {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: () =>
      api<JobPosting>(`/job-offers/${toValue(id)}/fetch-cover`, { method: 'POST' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['job-offer', toValue(id)] })
      queryClient.invalidateQueries({ queryKey: ['job-offers'] })
    },
  })
}

/** AI (ChatGPT): generuje opis ogłoszenia na podstawie danych formularza. */
export async function generateOfferDescription(
  payload: Record<string, unknown>,
): Promise<string> {
  const api = useApi()
  const res = await api<{ description: string }>('/ai/offer-description', {
    method: 'POST',
    body: payload,
  })
  return res.description
}

/** Duplikuje ogłoszenie — zwraca nowy rekord (kopia). */
export function useDuplicateJobOffer() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (id: string) =>
      api<JobPosting>(`/job-offers/${id}/duplicate`, { method: 'POST' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['job-offers'] })
      queryClient.invalidateQueries({ queryKey: ['company'] })
    },
  })
}

export function useDeleteJobOffer() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (id: string) => api(`/job-offers/${id}`, { method: 'DELETE' }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['job-offers'] }),
  })
}

/** Szybkie tworzenie kandydata z ogłoszenia (krok 1). */
export function useCreateCandidateFromOffer(offerId: MaybeRefOrGetter<string>) {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (payload: { first_name: string; last_name?: string; phone: string }) =>
      api<Candidate & { duplicate: boolean }>(
        `/job-offers/${toValue(offerId)}/create-candidate`,
        { method: 'POST', body: payload },
      ),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['candidates'] })
      queryClient.invalidateQueries({ queryKey: ['pipeline'] })
    },
  })
}
