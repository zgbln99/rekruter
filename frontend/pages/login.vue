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
  } catch (e: unknown) {
    error.value = 'Nieprawidłowy e-mail lub hasło.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen flex-col justify-center px-6 py-12">
    <div class="mx-auto w-full max-w-sm">
      <h1 class="mb-1 text-center text-3xl font-bold text-brand">Rekruter</h1>
      <p class="mb-8 text-center text-sm text-gray-500">
        Recruitment Operating System
      </p>

      <form class="space-y-4" @submit.prevent="submit">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">E-mail</label>
          <input
            v-model="email"
            type="email"
            autocomplete="username"
            inputmode="email"
            class="input-field"
            required
          />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Hasło</label>
          <input
            v-model="password"
            type="password"
            autocomplete="current-password"
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
</template>
