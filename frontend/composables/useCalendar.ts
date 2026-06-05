import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import type { MaybeRefOrGetter } from 'vue'
import type { CalendarEvent, InstallmentStatus } from '~/types'

/** Wydarzenia kalendarza (przyjazdy + terminy rozliczeń) w zakresie dat. */
export function useCalendarQuery(
  from: MaybeRefOrGetter<string>,
  to: MaybeRefOrGetter<string>,
) {
  const api = useApi()
  return useQuery({
    queryKey: ['calendar', () => toValue(from), () => toValue(to)] as const,
    queryFn: () =>
      api<CalendarEvent[]>('/calendar', {
        query: { from: toValue(from), to: toValue(to) },
      }),
  })
}

/** Aktualizacja raty rozliczenia (tylko administrator). */
export function useUpdateInstallment() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (input: {
      installmentId: string
      status?: InstallmentStatus
      invoiced_at?: string | null
      paid_at?: string | null
    }) =>
      api(`/placement-installments/${input.installmentId}`, {
        method: 'PATCH',
        body: {
          status: input.status,
          invoiced_at: input.invoiced_at,
          paid_at: input.paid_at,
        },
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['calendar'] })
      queryClient.invalidateQueries({ queryKey: ['placements'] })
    },
  })
}
