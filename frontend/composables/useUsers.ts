import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import type { User } from '~/types'

export function useUsersQuery() {
  const api = useApi()
  return useQuery({
    queryKey: ['users'] as const,
    queryFn: () => api<User[]>('/users'),
  })
}

export function useCreateUser() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (payload: {
      name: string
      email: string
      password: string
      role: string
      phone?: string
    }) => api<User>('/users', { method: 'POST', body: payload }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['users'] }),
  })
}

export function useUpdateUser() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (input: { id: string } & Partial<User> & { password?: string }) => {
      const { id, ...body } = input
      return api<User>(`/users/${id}`, { method: 'PATCH', body })
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['users'] }),
  })
}

export function useDeleteUser() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (id: string) => api(`/users/${id}`, { method: 'DELETE' }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['users'] }),
  })
}
