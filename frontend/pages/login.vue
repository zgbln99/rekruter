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
  <div class="flex min-h-screen bg-canvas">
    <!-- Lewy panel marki (desktop) -->
    <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-ink p-12 text-white lg:flex xl:w-[55%]">
      <div class="pointer-events-none absolute -right-28 -top-28 h-96 w-96 rounded-full bg-brand/30 blur-3xl" />
      <div class="pointer-events-none absolute -bottom-36 -left-24 h-[28rem] w-[28rem] rounded-full bg-brand/20 blur-3xl" />

      <div class="relative">
        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-medium text-white/70">
          <span class="h-1.5 w-1.5 rounded-full bg-brand" /> Panel rekrutera
        </span>
      </div>

      <div class="relative">
        <img v-if="logoOk" :src="logoSrc" alt="Logo" class="mb-6 h-16 max-w-[280px] object-contain" @error="logoOk = false" />
        <h1 v-else class="text-5xl font-bold tracking-tight">edge recruiting</h1>
        <p class="mt-5 max-w-md text-lg leading-relaxed text-white/70">
          System rekrutacji kierowców zawodowych — dodawanie kandydatów w sekundy,
          pełna kontrola nad ogłoszeniami, skierowaniami i rozliczeniami.
        </p>
      </div>

      <p class="relative text-sm text-white/35">© {{ year }} edge recruiting</p>
    </div>

    <!-- Formularz -->
    <div class="flex flex-1 items-center justify-center px-6 py-12">
      <div class="w-full max-w-sm">
        <div class="mb-8 lg:hidden">
          <h1 class="text-2xl font-bold tracking-tight text-ink">edge recruiting</h1>
          <p class="mt-1 text-sm text-stone">System rekrutacji kierowców</p>
        </div>

        <h2 class="text-[26px] font-bold tracking-tight text-ink">Zaloguj się</h2>
        <p class="mt-1.5 text-sm text-stone">Wprowadź dane, aby przejść do panelu.</p>

        <form class="mt-7 space-y-4" @submit.prevent="submit">
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

          <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

          <button type="submit" class="btn-primary !h-12 w-full text-[15px]" :disabled="loading">
            {{ loading ? 'Logowanie…' : 'Zaloguj się' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
