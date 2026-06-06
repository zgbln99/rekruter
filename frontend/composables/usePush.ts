// Web Push (VAPID): włączanie/wyłączanie powiadomień push na urządzeniu.

function urlBase64ToUint8Array(base64String: string): Uint8Array {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const raw = atob(base64)
  const arr = new Uint8Array(raw.length)
  for (let i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i)
  return arr
}

export function usePush() {
  const api = useApi()
  const supported = ref(false)
  const enabled = ref(false) // subskrybowane na tym urządzeniu
  const available = ref(false) // skonfigurowane klucze VAPID na serwerze
  const busy = ref(false)

  onMounted(async () => {
    supported.value =
      typeof window !== 'undefined' &&
      'serviceWorker' in navigator &&
      'PushManager' in window &&
      'Notification' in window
    if (!supported.value) return
    try {
      const reg = await navigator.serviceWorker.ready
      const sub = await reg.pushManager.getSubscription()
      enabled.value = !!sub
      const info = await api<{ enabled: boolean; key: string | null }>('/push/public-key')
      available.value = !!info.enabled
    } catch {
      /* ignoruj */
    }
  })

  async function enable(): Promise<string | null> {
    if (!supported.value) return 'Twoja przeglądarka nie obsługuje powiadomień push.'
    busy.value = true
    try {
      const perm = await Notification.requestPermission()
      if (perm !== 'granted') return 'Brak zgody na powiadomienia.'

      const info = await api<{ enabled: boolean; key: string | null }>('/push/public-key')
      if (!info.enabled || !info.key) return 'Push nie jest skonfigurowany na serwerze (brak kluczy VAPID).'

      const reg = await navigator.serviceWorker.ready
      const sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(info.key) as unknown as BufferSource,
      })

      await api('/push/subscribe', { method: 'POST', body: sub.toJSON() })
      enabled.value = true
      return null
    } catch (e: any) {
      return e?.message || 'Nie udało się włączyć powiadomień.'
    } finally {
      busy.value = false
    }
  }

  async function disable(): Promise<void> {
    busy.value = true
    try {
      const reg = await navigator.serviceWorker.ready
      const sub = await reg.pushManager.getSubscription()
      if (sub) {
        await api('/push/unsubscribe', { method: 'DELETE', body: { endpoint: sub.endpoint } })
        await sub.unsubscribe()
      }
      enabled.value = false
    } catch {
      /* ignoruj */
    } finally {
      busy.value = false
    }
  }

  return { supported, enabled, available, busy, enable, disable }
}
