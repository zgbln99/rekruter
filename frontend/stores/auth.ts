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
    /** Wczytuje token (start aplikacji) — trwały (localStorage) lub sesyjny. */
    init() {
      if (import.meta.client) {
        this.token = localStorage.getItem(TOKEN_KEY) || sessionStorage.getItem(TOKEN_KEY)
      }
    },

    async login(email: string, password: string, remember = true) {
      const data = await $fetch<LoginResponse>('/auth/login', {
        method: 'POST',
        baseURL: useRuntimeConfig().public.apiBase,
        body: { email, password, device_name: 'pwa' },
      })
      this.token = data.token
      this.user = data.user
      if (import.meta.client) {
        // „Zapamiętaj mnie": localStorage (trwałe) vs sessionStorage (do zamknięcia karty).
        const store = remember ? localStorage : sessionStorage
        const other = remember ? sessionStorage : localStorage
        store.setItem(TOKEN_KEY, data.token)
        other.removeItem(TOKEN_KEY)
      }
    },

    async fetchMe() {
      if (!this.token) return
      this.user = await useApi()<User>('/auth/me')
    },

    async logout() {
      // Czyścimy sesję od razu (UI reaguje natychmiast), token unieważniamy
      // w tle, a następnie przekierowujemy na ekran logowania.
      const token = this.token
      this.clear()
      if (import.meta.client) {
        if (token) {
          $fetch('/auth/logout', {
            method: 'POST',
            baseURL: useRuntimeConfig().public.apiBase,
            headers: { Authorization: `Bearer ${token}` },
          }).catch(() => {})
        }
        await navigateTo('/login')
      }
    },

    clear() {
      this.token = null
      this.user = null
      if (import.meta.client) {
        localStorage.removeItem(TOKEN_KEY)
        sessionStorage.removeItem(TOKEN_KEY)
      }
    },
  },
})
