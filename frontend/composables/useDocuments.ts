import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import type { MaybeRefOrGetter } from 'vue'
import type { CandidateDocument, DocumentType, ProfileSend } from '~/types'

export function useDocumentsQuery(candidateId: MaybeRefOrGetter<string>) {
  const api = useApi()
  return useQuery({
    queryKey: ['documents', () => toValue(candidateId)] as const,
    queryFn: () =>
      api<CandidateDocument[]>(`/candidates/${toValue(candidateId)}/documents`),
  })
}

export function useUploadDocument(candidateId: MaybeRefOrGetter<string>) {
  const api = useApi()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (input: { file: File; type: DocumentType }) => {
      const form = new FormData()
      form.append('file', input.file)
      form.append('type', input.type)
      return api<CandidateDocument>(
        `/candidates/${toValue(candidateId)}/documents`,
        { method: 'POST', body: form },
      )
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['documents'] })
    },
  })
}

export function useUploadProfilePhoto(candidateId: MaybeRefOrGetter<string>) {
  const api = useApi()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (blob: Blob) => {
      const form = new FormData()
      form.append('photo', blob, 'profile.jpg')
      return api<CandidateDocument>(
        `/candidates/${toValue(candidateId)}/profile-photo`,
        { method: 'POST', body: form },
      )
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['documents'] })
      queryClient.invalidateQueries({ queryKey: ['candidate'] })
    },
  })
}

export function useDeleteDocument(candidateId: MaybeRefOrGetter<string>) {
  const api = useApi()
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (documentId: string) =>
      api(`/candidates/${toValue(candidateId)}/documents/${documentId}`, {
        method: 'DELETE',
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['documents'] })
      queryClient.invalidateQueries({ queryKey: ['candidate', toValue(candidateId)] })
    },
  })
}

export function useSendProfile(candidateId: MaybeRefOrGetter<string>) {
  const api = useApi()
  return useMutation({
    mutationFn: (recipientEmail: string) =>
      api<ProfileSend>(`/candidates/${toValue(candidateId)}/profile-send`, {
        method: 'POST',
        body: { recipient_email: recipientEmail },
      }),
  })
}

/** Pobiera plik przez uwierzytelniony endpoint (Bearer) jako Blob. */
export async function fetchBlob(path: string): Promise<Blob> {
  const api = useApi()
  return api<Blob>(path, { responseType: 'blob' })
}

/**
 * Pobiera lub otwiera Blob. Domyślnie pobiera (download) — działa też na mobile,
 * gdzie window.open po asynchronicznym fetchu bywa blokowane przez przeglądarkę.
 */
export function openBlob(blob: Blob, filename?: string) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.rel = 'noopener'
  if (filename) {
    a.download = filename
  } else {
    a.target = '_blank'
  }
  document.body.appendChild(a)
  a.click()
  a.remove()
  setTimeout(() => URL.revokeObjectURL(url), 30_000)
}
