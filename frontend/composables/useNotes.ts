import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import type { Note } from '~/types'

export function useNotesQuery() {
  const api = useApi()
  return useQuery({
    queryKey: ['notes'] as const,
    queryFn: () => api<Note[]>('/notes'),
  })
}

export function useCreateNote() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (input: Partial<Pick<Note, 'title' | 'body' | 'pinned' | 'color'>>) =>
      api<Note>('/notes', { method: 'POST', body: input }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notes'] }),
  })
}

export function useUpdateNote() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (input: { id: string } & Partial<Pick<Note, 'title' | 'body' | 'pinned' | 'color'>>) => {
      const { id, ...body } = input
      return api<Note>(`/notes/${id}`, { method: 'PATCH', body })
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notes'] }),
  })
}

export function useDeleteNote() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (id: string) => api(`/notes/${id}`, { method: 'DELETE' }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notes'] }),
  })
}
