import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import type { MaybeRefOrGetter } from 'vue'
import type {
  Application,
  JobPosting,
  Paginated,
  PipelineBoard,
} from '~/types'

export function useJobPostingsQuery(companyId: MaybeRefOrGetter<string> = '') {
  const api = useApi()
  return useQuery({
    queryKey: ['job-postings', () => toValue(companyId)] as const,
    queryFn: () =>
      api<Paginated<JobPosting>>('/job-postings', {
        query: { company_id: toValue(companyId) || undefined },
      }),
  })
}

export function useCreateJobPosting() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (payload: Partial<JobPosting>) =>
      api<JobPosting>('/job-postings', { method: 'POST', body: payload }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['job-postings'] })
      queryClient.invalidateQueries({ queryKey: ['company'] })
    },
  })
}

export function usePipelineBoardQuery(jobPostingId: MaybeRefOrGetter<string>) {
  const api = useApi()
  return useQuery({
    queryKey: ['pipeline', () => toValue(jobPostingId)] as const,
    queryFn: () =>
      api<PipelineBoard>(`/job-postings/${toValue(jobPostingId)}/pipeline`),
  })
}

export function useMoveApplication(jobPostingId: MaybeRefOrGetter<string>) {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (input: { id: string; status: string }) =>
      api<Application>(`/applications/${input.id}`, {
        method: 'PATCH',
        body: { status: input.status },
      }),
    onSuccess: () =>
      queryClient.invalidateQueries({
        queryKey: ['pipeline', toValue(jobPostingId)],
      }),
  })
}

export function useAddApplication() {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (input: { candidate_id: string; job_posting_id: string }) =>
      api<Application>('/applications', { method: 'POST', body: input }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['pipeline'] }),
  })
}
