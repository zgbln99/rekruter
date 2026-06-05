import type { QuickAddInput } from '~/types'

const QUEUE_KEY = 'rekruter_offline_candidates'

/** Stan połączenia (reaktywny). */
export function useOnline() {
  const online = useState('online', () => true)

  if (import.meta.client) {
    online.value = navigator.onLine
    window.addEventListener('online', () => (online.value = true))
    window.addEventListener('offline', () => (online.value = false))
  }

  return online
}

function readQueue(): QuickAddInput[] {
  if (!import.meta.client) return []
  try {
    return JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]')
  } catch {
    return []
  }
}

function writeQueue(items: QuickAddInput[]) {
  if (import.meta.client) localStorage.setItem(QUEUE_KEY, JSON.stringify(items))
}

/** Dodaje zgłoszenie kandydata do kolejki offline. */
export function enqueueCandidate(payload: QuickAddInput) {
  const queue = readQueue()
  queue.push(payload)
  writeQueue(queue)
}

export function offlineQueueSize(): number {
  return readQueue().length
}

/**
 * Wysyła zaległe zgłoszenia po odzyskaniu połączenia.
 * Zwraca liczbę pomyślnie zsynchronizowanych kandydatów.
 */
export async function flushOfflineQueue(): Promise<number> {
  const queue = readQueue()
  if (queue.length === 0) return 0

  const api = useApi()
  const remaining: QuickAddInput[] = []
  let synced = 0

  for (const payload of queue) {
    try {
      await api('/candidates', { method: 'POST', body: payload })
      synced++
    } catch (e: any) {
      // Duplikat (409) traktujemy jako zsynchronizowany; inne błędy zostawiamy.
      if (e?.response?.status === 409) {
        synced++
      } else {
        remaining.push(payload)
      }
    }
  }

  writeQueue(remaining)
  return synced
}
