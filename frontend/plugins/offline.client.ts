// Po odzyskaniu połączenia wysyła zaległe zgłoszenia kandydatów (offline queue).
export default defineNuxtPlugin(() => {
  const auth = useAuthStore()

  const flush = async () => {
    if (!auth.token) return
    const synced = await flushOfflineQueue()
    if (synced > 0) {
      // Odśwież dane po synchronizacji.
      window.dispatchEvent(new CustomEvent('offline-synced', { detail: synced }))
    }
  }

  window.addEventListener('online', flush)
  // Próba synchronizacji także przy starcie (np. po ponownym otwarciu PWA).
  if (navigator.onLine) flush()
})
