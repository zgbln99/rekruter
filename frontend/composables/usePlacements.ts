import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import type { MaybeRefOrGetter } from 'vue'
import type { ArrivalStatus, Placement } from '~/types'

/** Skierowania danego kierowcy. */
export function useCandidatePlacements(candidateId: MaybeRefOrGetter<string>) {
  const api = useApi()
  return useQuery({
    queryKey: ['placements', () => toValue(candidateId)] as const,
    queryFn: () =>
      api<Placement[]>(`/candidates/${toValue(candidateId)}/placements`),
  })
}

export interface CreatePlacementInput {
  job_posting_id: string
  arrival_at: string
  total_amount?: number | string | null
  currency?: string
  notes?: string
}

export function useCreatePlacement(candidateId: MaybeRefOrGetter<string>) {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (payload: CreatePlacementInput) =>
      api<Placement>(`/candidates/${toValue(candidateId)}/placements`, {
        method: 'POST',
        body: payload,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['placements'] })
      queryClient.invalidateQueries({ queryKey: ['calendar'] })
    },
  })
}

export function useUpdateArrival() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (input: { placementId: string; status: ArrivalStatus }) =>
      api<Placement>(`/placements/${input.placementId}/arrival`, {
        method: 'PATCH',
        body: { status: input.status },
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['placements'] })
      queryClient.invalidateQueries({ queryKey: ['calendar'] })
    },
  })
}

export function useDeletePlacement() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (placementId: string) =>
      api(`/placements/${placementId}`, { method: 'DELETE' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['placements'] })
      queryClient.invalidateQueries({ queryKey: ['calendar'] })
    },
  })
}
