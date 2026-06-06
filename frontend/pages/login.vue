<script setup lang="ts">
definePageMeta({ layout: 'blank' })

const auth = useAuthStore()
const config = useRuntimeConfig()
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

// Logo agencji z publicznego endpointu (działa przed zalogowaniem).
const logoSrc = `${config.public.apiBase}/branding/logo`
const logoOk = ref(true)
const year = new Date().getFullYear()

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    await navigateTo('/')
  } catch {
    error.value = 'Nieprawidłowy e-mail lub hasło.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-surface-soft px-6 py-12">
    <div class="w-full max-w-[380px]">
      <!-- Logo / marka -->
      <div class="mb-9 flex flex-col items-center text-center">
        <img
          v-if="logoOk"
          :src="logoSrc"
          alt="Logo"
          class="h-12 max-w-[240px] object-contain"
          @error="logoOk = false"
        />
        <h1 v-else class="text-3xl font-bold tracking-tight text-ink">edge recruiting</h1>
        <p class="mt-3 text-[13px] text-stone">System rekrutacji kierowców zawodowych</p>
      </div>

      <!-- Karta logowania -->
      <div class="rounded-2xl border border-hairline bg-canvas p-7 shadow-card">
        <h2 class="text-lg font-semibold tracking-tight text-ink">Zaloguj się</h2>
        <p class="mt-1 text-[13px] text-stone">Wprowadź dane, aby przejść do panelu.</p>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
          <div>
            <label class="field-label">E-mail</label>
            <input
              v-model="email"
              type="email"
              autocomplete="username"
              inputmode="email"
              placeholder="ty@agencja.pl"
              class="input-field"
              required
            />
          </div>

          <div>
            <label class="field-label">Hasło</label>
            <input
              v-model="password"
              type="password"
              autocomplete="current-password"
              placeholder="••••••••"
              class="input-field"
              required
            />
          </div>

          <p v-if="error" class="rounded-lg bg-brand-soft px-3 py-2 text-[13px] text-brand-deep">
            {{ error }}
          </p>

          <button type="submit" class="btn-primary !h-11 w-full text-[14px]" :disabled="loading">
            {{ loading ? 'Logowanie…' : 'Zaloguj się' }}
          </button>
        </form>
      </div>

      <p class="mt-8 text-center text-[12px] text-muted">© {{ year }} edge recruiting</p>
    </div>
  </div>
</template>
