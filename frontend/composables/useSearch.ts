import { useQuery } from '@tanstack/vue-query'
import type { MaybeRefOrGetter } from 'vue'
import type { SearchResults } from '~/types'

/** Globalna wyszukiwarka (kandydaci + ogłoszenia). Aktywna od 2 znaków. */
export function useSearchQuery(q: MaybeRefOrGetter<string>) {
  const api = useApi()
  return useQuery({
    queryKey: ['search', () => toValue(q)] as const,
    queryFn: () => api<SearchResults>('/search', { query: { q: toValue(q) } }),
    enabled: () => (toValue(q) || '').trim().length >= 2,
    placeholderData: (prev) => prev,
  })
}
