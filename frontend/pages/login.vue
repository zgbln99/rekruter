<script setup lang="ts">
definePageMeta({ layout: 'blank' })

const auth = useAuthStore()
const config = useRuntimeConfig()
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const showPassword = ref(false)

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
  <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-surface-soft px-6 py-12">
    <!-- Subtelna siatka tła (cicha, nie krzykliwa) -->
    <div
      class="pointer-events-none absolute inset-0 opacity-[0.5]"
      style="background-image: radial-gradient(theme('colors.hairline') 1px, transparent 1px); background-size: 22px 22px; mask-image: radial-gradient(ellipse 70% 55% at 50% 40%, #000 30%, transparent 75%);"
    />

    <div class="relative w-full max-w-[400px]">
      <!-- Logo / marka -->
      <div class="mb-8 flex flex-col items-center text-center">
        <img
          v-if="logoOk"
          :src="logoSrc"
          alt="Logo"
          class="h-14 max-w-[260px] object-contain"
          @error="logoOk = false"
        />
        <h1 v-else class="text-[32px] font-bold tracking-tight text-ink">edge recruiting</h1>
        <p class="mt-3 text-[13px] text-stone">System rekrutacji kierowców zawodowych</p>
      </div>

      <!-- Karta logowania -->
      <div class="overflow-hidden rounded-2xl border border-hairline bg-canvas shadow-elevated">
        <div class="h-1 bg-gradient-to-r from-brand to-brand-deep" />
        <div class="p-7 sm:p-8">
          <h2 class="text-[19px] font-semibold tracking-tight text-ink">Witaj ponownie</h2>
          <p class="mt-1 text-[13px] text-stone">Zaloguj się, aby przejść do panelu.</p>

          <form class="mt-6 space-y-4" @submit.prevent="submit">
            <div>
              <label class="field-label">E-mail</label>
              <div class="relative">
                <AppIcon name="mail" :size="18" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted" />
                <input
                  v-model="email"
                  type="email"
                  autocomplete="username"
                  inputmode="email"
                  placeholder="ty@agencja.pl"
                  class="input-field !pl-11"
                  required
                />
              </div>
            </div>

            <div>
              <label class="field-label">Hasło</label>
              <div class="relative">
                <AppIcon name="shield" :size="18" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted" />
                <input
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="current-password"
                  placeholder="••••••••"
                  class="input-field !px-11"
                  required
                />
                <button
                  type="button"
                  class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-1 text-[12px] font-medium text-stone transition hover:bg-surface hover:text-slate"
                  @click="showPassword = !showPassword"
                >
                  {{ showPassword ? 'Ukryj' : 'Pokaż' }}
                </button>
              </div>
            </div>

            <p v-if="error" class="flex items-center gap-2 rounded-lg bg-brand-soft px-3 py-2.5 text-[13px] text-brand-deep">
              <AppIcon name="x" :size="15" class="shrink-0" />
              {{ error }}
            </p>

            <button type="submit" class="btn-accent !h-12 w-full !text-[14px]" :disabled="loading">
              <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
              {{ loading ? 'Logowanie…' : 'Zaloguj się' }}
            </button>
          </form>
        </div>
      </div>

      <p class="mt-8 text-center text-[12px] text-muted">© {{ year }} edge recruiting</p>
    </div>
  </div>
</template>
