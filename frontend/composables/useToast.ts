// Globalne powiadomienia toast (potwierdzenia akcji / błędy).
// Użycie: const toast = useToast(); toast.success('Zapisano')

export interface ToastItem {
  id: number
  type: 'success' | 'error' | 'info'
  message: string
}

let nextId = 1

export function useToast() {
  const items = useState<ToastItem[]>('toasts', () => [])

  function push(type: ToastItem['type'], message: string) {
    const id = nextId++
    items.value = [...items.value, { id, type, message }]
    setTimeout(() => dismiss(id), 3500)
  }

  function dismiss(id: number) {
    items.value = items.value.filter((t) => t.id !== id)
  }

  return {
    items,
    dismiss,
    success: (msg: string) => push('success', msg),
    error: (msg: string) => push('error', msg),
    info: (msg: string) => push('info', msg),
  }
}
