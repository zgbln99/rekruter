// Globalny strażnik tras — wymaga zalogowania poza stroną /login.
export default defineNuxtRouteMiddleware((to) => {
  const auth = useAuthStore()
  const publicRoutes = ['/login']

  if (!auth.isAuthenticated && !publicRoutes.includes(to.path)) {
    return navigateTo('/login')
  }

  if (auth.isAuthenticated && to.path === '/login') {
    return navigateTo('/')
  }
})
