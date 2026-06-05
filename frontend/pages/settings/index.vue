<script setup lang="ts">
import type { Settings } from '~/types'

// Ustawienia organizacji — nazwa agencji (używana w całej aplikacji i w PDF).
const auth = useAuthStore()
const { data, isLoading, isError } = useSettingsQuery()
const updateSettings = useUpdateSettings()

const form = reactive<Settings>({
  agency_name: '', agency_phone: '', agency_email: '', agency_website: '',
})
const saved = ref(false)
const error = ref('')
const ready = ref(false)

watch(data, (s) => {
  if (s && !ready.value) {
    Object.assign(form, s)
    ready.value = true
  }
}, { immediate: true })

async function save() {
  error.value = ''
  saved.value = false
  if (!form.agency_name.trim()) {
    error.value = 'Podaj nazwę agencji.'
    return
  }
  try {
    await updateSettings.mutateAsync({ ...form })
    saved.value = true
    setTimeout(() => (saved.value = false), 2500)
  } catch (e: any) {
    error.value = e?.response?.status === 403
      ? 'Tylko administrator może zmieniać ustawienia.'
      : 'Nie udało się zapisać ustawień.'
  }
}
</script>

<template>
  <section class="mx-auto max-w-2xl">
    <UiPageHeader title="Ustawienia" subtitle="Dane agencji używane w aplikacji i dokumentach" />

    <p v-if="isLoading" class="py-10 text-center text-muted">Ładowanie…</p>
    <div v-else-if="isError" class="card p-6 text-center text-stone">Brak dostępu do ustawień.</div>

    <form v-else class="card space-y-4 p-5" @submit.prevent="save">
      <div>
        <label class="field-label">Nazwa agencji</label>
        <input v-model="form.agency_name" class="input-field" placeholder="np. LTS Recruitment" :disabled="!auth.isAdmin" />
        <p class="mt-1 text-xs text-stone">Pojawia się w nagłówku aplikacji oraz na wszystkich dokumentach PDF.</p>
      </div>
      <div class="grid gap-3 sm:grid-cols-2">
        <div><label class="field-label">Telefon</label><input v-model="form.agency_phone" class="input-field" :disabled="!auth.isAdmin" /></div>
        <div><label class="field-label">E-mail</label><input v-model="form.agency_email" type="email" class="input-field" :disabled="!auth.isAdmin" /></div>
        <div class="sm:col-span-2"><label class="field-label">Strona WWW</label><input v-model="form.agency_website" class="input-field" :disabled="!auth.isAdmin" /></div>
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <p v-if="saved" class="text-sm text-brand-deep">Zapisano ustawienia.</p>

      <button v-if="auth.isAdmin" type="submit" class="btn-primary" :disabled="updateSettings.isPending.value">
        {{ updateSettings.isPending.value ? 'Zapisywanie…' : 'Zapisz ustawienia' }}
      </button>
      <p v-else class="text-sm text-stone">Tylko administrator może edytować te dane.</p>
    </form>
  </section>
</template>
