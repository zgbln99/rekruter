import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import type { MaybeRefOrGetter } from 'vue'
import type { Task, TaskStatus } from '~/types'

export function useTasksQuery(filter: MaybeRefOrGetter<string> = 'today') {
  const api = useApi()
  return useQuery({
    queryKey: ['tasks', () => toValue(filter)] as const,
    queryFn: () =>
      api<Task[]>('/tasks', { query: { filter: toValue(filter) } }),
  })
}

export function useUpdateTask() {
  const api = useApi()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (input: {
      id: string
      status?: TaskStatus
      due_at?: string
    }) => {
      const { id, ...body } = input
      return api<Task>(`/tasks/${id}`, { method: 'PATCH', body })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tasks'] })
    },
  })
}
