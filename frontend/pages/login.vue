<script setup lang="ts">
definePageMeta({ layout: 'blank' })

const auth = useAuthStore()
const config = useRuntimeConfig()
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const showPassword = ref(false)
const remember = ref(true)

// Logo agencji z publicznego endpointu (działa przed zalogowaniem).
const logoSrc = `${config.public.apiBase}/branding/logo`
const logoOk = ref(true)
const year = new Date().getFullYear()

// Karuzela prezentacyjna (lewy panel). Zdjęcia ładują się w przeglądarce;
// gdyby się nie wczytały, zostaje elegancki gradient + nagłówek.
const slides = [
  {
    img: 'https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?auto=format&fit=crop&w=1400&q=80',
    title: 'Rekrutuj kierowców szybciej',
    subtitle: 'Dodawaj kandydatów w kilka sekund',
    from: '#0b2545', to: '#dc2626',
  },
  {
    img: 'https://images.unsplash.com/photo-1586191582151-f73872dfd183?auto=format&fit=crop&w=1400&q=80',
    title: 'Pełna kontrola nad ogłoszeniami',
    subtitle: 'Skierowania i rozliczenia w jednym miejscu',
    from: '#1a1a2e', to: '#b91c1c',
  },
  {
    img: 'https://images.unsplash.com/photo-1519003722824-194d4455a60c?auto=format&fit=crop&w=1400&q=80',
    title: 'Cały zespół w jednym panelu',
    subtitle: 'Wszystko, czego potrzebuje rekruter',
    from: '#071a33', to: '#7f1d1d',
  },
]
const active = ref(0)
let timer: ReturnType<typeof setInterval> | undefined

onMounted(() => {
  timer = setInterval(() => {
    active.value = (active.value + 1) % slides.length
  }, 6000)
})
onBeforeUnmount(() => timer && clearInterval(timer))

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value, remember.value)
    await navigateTo('/')
  } catch {
    error.value = 'Nieprawidłowy e-mail lub hasło.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-ink p-0 sm:p-5 lg:p-6">
    <div class="flex h-screen w-full max-w-[1400px] overflow-hidden bg-canvas sm:h-auto sm:min-h-[640px] sm:rounded-[28px] sm:shadow-elevated lg:min-h-[720px]">
      <!-- LEWY PANEL — prezentacja / karuzela -->
      <div class="relative hidden w-1/2 overflow-hidden lg:block">
        <!-- Slajdy -->
        <div
          v-for="(s, i) in slides"
          :key="i"
          class="absolute inset-0 transition-opacity duration-1000 ease-out"
          :class="i === active ? 'opacity-100' : 'opacity-0'"
        >
          <div class="absolute inset-0" :style="{ background: `linear-gradient(150deg, ${s.from}, ${s.to})` }" />
          <img
            :src="s.img"
            alt=""
            referrerpolicy="no-referrer"
            class="absolute inset-0 h-full w-full object-cover"
            @error="($event.target as HTMLElement).style.display = 'none'"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/15 to-black/25" />
        </div>

        <!-- Logo (góra) -->
        <div class="absolute left-9 top-8 z-10">
          <img
            v-if="logoOk"
            :src="logoSrc"
            alt="Logo"
            class="h-9 max-w-[200px] object-contain brightness-0 invert"
            @error="logoOk = false"
          />
          <span v-else class="text-xl font-bold tracking-tight text-white">edge recruiting</span>
        </div>

        <!-- Nagłówek (dół) -->
        <div class="absolute inset-x-0 bottom-0 z-10 p-9">
          <transition name="fade" mode="out-in">
            <div :key="active">
              <h2 class="text-4xl font-bold leading-tight tracking-tight text-white">
                {{ slides[active].title }}
              </h2>
              <p class="mt-3 text-[15px] text-white/75">{{ slides[active].subtitle }}</p>
            </div>
          </transition>

          <!-- Wskaźniki -->
          <div class="mt-7 flex items-center gap-2">
            <button
              v-for="(s, i) in slides"
              :key="i"
              class="h-1.5 rounded-full transition-all duration-300"
              :class="i === active ? 'w-8 bg-white' : 'w-2.5 bg-white/40 hover:bg-white/60'"
              :aria-label="`Slajd ${i + 1}`"
              @click="active = i"
            />
          </div>
        </div>
      </div>

      <!-- PRAWY PANEL — formularz -->
      <div class="flex w-full flex-col px-7 py-10 sm:px-12 lg:w-1/2 lg:px-16 lg:py-14">
        <!-- Logo (mobile) -->
        <div class="mb-10 lg:hidden">
          <img
            v-if="logoOk"
            :src="logoSrc"
            alt="Logo"
            class="h-9 max-w-[200px] object-contain"
            @error="logoOk = false"
          />
          <span v-else class="text-xl font-bold tracking-tight text-ink">edge recruiting</span>
        </div>

        <div class="flex flex-1 flex-col justify-center">
          <div class="mx-auto w-full max-w-[400px]">
            <h1 class="text-[30px] font-bold leading-tight tracking-tight text-ink">Witaj ponownie!</h1>
            <p class="mt-2 text-[14px] text-stone">Zaloguj się do swojego panelu</p>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
              <div>
                <label class="field-label">Adres e-mail</label>
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
                <div class="relative">
                  <input
                    v-model="password"
                    :type="showPassword ? 'text' : 'password'"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="input-field !pr-11"
                    required
                  />
                  <button
                    type="button"
                    class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-lg p-2 text-muted transition hover:bg-surface hover:text-slate"
                    :aria-label="showPassword ? 'Ukryj hasło' : 'Pokaż hasło'"
                    @click="showPassword = !showPassword"
                  >
                    <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 7 10 7a13.2 13.2 0 0 1-1.67 2.44M6.6 6.6A13.3 13.3 0 0 0 2 11s3.5 7 10 7a9.1 9.1 0 0 0 4.4-1.1"/><path d="m2 2 20 20"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                  </button>
                </div>
              </div>

              <label class="flex cursor-pointer select-none items-center gap-2.5 text-[13px] text-slate">
                <input v-model="remember" type="checkbox" class="peer sr-only" />
                <span class="flex h-[18px] w-[18px] items-center justify-center rounded-[5px] border border-hairline bg-canvas text-white transition peer-checked:border-brand peer-checked:bg-brand">
                  <AppIcon name="check" :size="12" />
                </span>
                Zapamiętaj mnie na tym urządzeniu
              </label>

              <p v-if="error" class="flex items-center gap-2 rounded-lg bg-brand-soft px-3 py-2.5 text-[13px] text-brand-deep">
                <AppIcon name="x" :size="15" class="shrink-0" />
                {{ error }}
              </p>

              <button type="submit" class="btn-primary !h-[52px] w-full !rounded-xl !text-[15px]" :disabled="loading">
                <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                {{ loading ? 'Logowanie…' : 'Zaloguj się' }}
              </button>
            </form>
          </div>
        </div>

        <p class="mt-8 text-center text-[12px] text-muted">© {{ year }} edge recruiting</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.4s ease, transform 0.4s ease;
}
.fade-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
