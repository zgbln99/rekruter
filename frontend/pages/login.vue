<script setup lang="ts">
definePageMeta({ layout: 'blank' })

const auth = useAuthStore()
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

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
  <div class="flex min-h-screen flex-col justify-center px-6 py-12">
    <div class="mx-auto w-full max-w-sm">
      <div class="mb-8 text-center">
        <img src="/logo.svg" alt="edge recruiting" class="mx-auto mb-4 h-14 w-14 rounded-2xl" />
        <h1 class="text-2xl font-bold tracking-tight text-ink">edge recruiting</h1>
        <p class="mt-1 text-sm text-stone">System rekrutacji kierowców</p>
      </div>

      <div class="card p-6">
        <form class="space-y-4" @submit.prevent="submit">
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

          <button type="submit" class="btn-primary" :disabled="loading">
            {{ loading ? 'Logowanie…' : 'Zaloguj się' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
