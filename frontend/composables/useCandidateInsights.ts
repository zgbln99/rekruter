import { useQuery } from '@tanstack/vue-query'
import type { MaybeRefOrGetter } from 'vue'
import type { Completeness, MatchResult, TimelineItem } from '~/types'

export function useCompletenessQuery(candidateId: MaybeRefOrGetter<string>) {
  const api = useApi()
  return useQuery({
    queryKey: ['completeness', () => toValue(candidateId)] as const,
    queryFn: () => api<Completeness>(`/candidates/${toValue(candidateId)}/completeness`),
  })
}

export function useTimelineQuery(candidateId: MaybeRefOrGetter<string>) {
  const api = useApi()
  return useQuery({
    queryKey: ['timeline', () => toValue(candidateId)] as const,
    queryFn: () => api<TimelineItem[]>(`/candidates/${toValue(candidateId)}/timeline`),
  })
}

/** Dopasowanie kandydata do ogłoszenia (na żądanie). */
export async function matchCandidate(
  candidateId: string,
  jobOfferId: string,
): Promise<MatchResult> {
  const api = useApi()
  return api<MatchResult>(`/candidates/${candidateId}/match/${jobOfferId}`)
}
