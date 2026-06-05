import { defineStore } from 'pinia'
import type { LoginResponse, User } from '~/types'

const TOKEN_KEY = 'rekruter_token'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: null as string | null,
    user: null as User | null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.role === 'admin',
  },

  actions: {
    /** Wczytuje token z localStorage (start aplikacji). */
    init() {
      if (import.meta.client) {
        this.token = localStorage.getItem(TOKEN_KEY)
      }
    },

    async login(email: string, password: string) {
      const data = await $fetch<LoginResponse>('/auth/login', {
        method: 'POST',
        baseURL: useRuntimeConfig().public.apiBase,
        body: { email, password, device_name: 'pwa' },
      })
      this.token = data.token
      this.user = data.user
      if (import.meta.client) {
        localStorage.setItem(TOKEN_KEY, data.token)
      }
    },

    async fetchMe() {
      if (!this.token) return
      this.user = await useApi()<User>('/auth/me')
    },

    async logout() {
      try {
        await useApi()('/auth/logout', { method: 'POST' })
      } catch {
        // ignorujemy błąd — i tak czyścimy sesję lokalnie
      }
      this.clear()
    },

    clear() {
      this.token = null
      this.user = null
      if (import.meta.client) {
        localStorage.removeItem(TOKEN_KEY)
      }
    },
  },
})
