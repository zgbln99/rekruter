// Inicjalizacja sesji przy starcie aplikacji (klient): wczytanie tokenu
// z localStorage i pobranie profilu użytkownika.
export default defineNuxtPlugin(async () => {
  const auth = useAuthStore()
  auth.init()
  if (auth.token && !auth.user) {
    try {
      await auth.fetchMe()
    } catch {
      auth.clear()
    }
  }
})
