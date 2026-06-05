<script setup lang="ts">
// Ustawienia organizacji — nazwa agencji (w całej aplikacji i PDF) + integracja AI.
const auth = useAuthStore()
const { data, isLoading, isError } = useSettingsQuery()
const updateSettings = useUpdateSettings()

const form = reactive({
  agency_name: '', agency_phone: '', agency_email: '', agency_website: '',
  openai_model: 'gpt-4o-mini',
  placement_fee: '' as string | number,
  placement_currency: 'EUR',
})
const openaiKey = ref('') // pusty = bez zmian
const templates = ref<{ name: string; body: string }[]>([])
const configured = ref(false)
const saved = ref(false)
const error = ref('')
const ready = ref(false)

watch(data, (s) => {
  if (s && !ready.value) {
    form.agency_name = s.agency_name || ''
    form.agency_phone = s.agency_phone || ''
    form.agency_email = s.agency_email || ''
    form.agency_website = s.agency_website || ''
    form.openai_model = s.openai_model || 'gpt-4o-mini'
    form.placement_fee = s.placement_fee ?? ''
    form.placement_currency = s.placement_currency || 'EUR'
    templates.value = (s.message_templates ?? []).map((t) => ({ name: t.name, body: t.body }))
    configured.value = !!s.openai_configured
    ready.value = true
  }
}, { immediate: true })

function addTemplate() {
  templates.value.push({ name: '', body: '' })
}
function removeTemplate(i: number) {
  templates.value.splice(i, 1)
}

async function save() {
  error.value = ''
  saved.value = false
  if (!form.agency_name.trim()) {
    error.value = 'Podaj nazwę agencji.'
    return
  }
  try {
    const payload: any = { ...form }
    payload.message_templates = templates.value.filter((t) => t.body.trim())
    if (openaiKey.value.trim()) payload.openai_api_key = openaiKey.value.trim()
    const res = await updateSettings.mutateAsync(payload)
    configured.value = !!res.openai_configured
    openaiKey.value = ''
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
    <UiPageHeader title="Ustawienia" subtitle="Dane agencji i integracje" />

    <p v-if="isLoading" class="py-10 text-center text-muted">Ładowanie…</p>
    <div v-else-if="isError" class="card p-6 text-center text-stone">Brak dostępu do ustawień.</div>

    <form v-else class="space-y-5" @submit.prevent="save">
      <!-- Dane agencji -->
      <div class="card space-y-4 p-5">
        <p class="text-[13px] font-semibold text-ink">Dane agencji</p>
        <div>
          <label class="field-label">Nazwa agencji</label>
          <input v-model="form.agency_name" class="input-field" :disabled="!auth.isAdmin" />
          <p class="mt-1 text-xs text-stone">Pojawia się w nagłówku aplikacji oraz na wszystkich dokumentach PDF i grafikach.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
          <div><label class="field-label">Telefon</label><input v-model="form.agency_phone" class="input-field" :disabled="!auth.isAdmin" /></div>
          <div><label class="field-label">E-mail</label><input v-model="form.agency_email" type="email" class="input-field" :disabled="!auth.isAdmin" /></div>
          <div class="sm:col-span-2"><label class="field-label">Strona WWW</label><input v-model="form.agency_website" class="input-field" :disabled="!auth.isAdmin" /></div>
        </div>
      </div>

      <!-- Integracja AI -->
      <div class="card space-y-4 p-5">
        <div class="flex items-center justify-between">
          <p class="text-[13px] font-semibold text-ink">Integracja ChatGPT (OpenAI)</p>
          <span class="badge" :class="configured ? 'badge-accent' : 'badge-neutral'">
            {{ configured ? 'Połączono' : 'Nie skonfigurowano' }}
          </span>
        </div>
        <div>
          <label class="field-label">Klucz API OpenAI</label>
          <input
            v-model="openaiKey"
            type="password"
            class="input-field"
            :placeholder="configured ? '•••••••••• (ustawiony — wpisz, aby zmienić)' : 'sk-...'"
            :disabled="!auth.isAdmin"
            autocomplete="off"
          />
          <p class="mt-1 text-xs text-stone">Używany do generowania treści ogłoszeń. Klucz jest przechowywany w ustawieniach organizacji i nie jest pokazywany ponownie.</p>
        </div>
        <div>
          <label class="field-label">Model</label>
          <select v-model="form.openai_model" class="input-field" :disabled="!auth.isAdmin">
            <option value="gpt-4o-mini">gpt-4o-mini (szybki, tani)</option>
            <option value="gpt-4o">gpt-4o (najlepsza jakość)</option>
          </select>
        </div>
      </div>

      <!-- Rozliczenia (stała kwota — tylko administrator) -->
      <div class="card space-y-4 p-5">
        <p class="text-[13px] font-semibold text-ink">Rozliczenia</p>
        <div class="grid gap-3 sm:grid-cols-2">
          <div>
            <label class="field-label">Stała kwota rozliczenia za kierowcę</label>
            <input v-model="form.placement_fee" type="number" min="0" step="0.01" placeholder="np. 1000" class="input-field" :disabled="!auth.isAdmin" />
          </div>
          <div>
            <label class="field-label">Waluta</label>
            <input v-model="form.placement_currency" class="input-field" :disabled="!auth.isAdmin" />
          </div>
        </div>
        <p class="text-xs text-stone">
          Kwota jest ustalona z góry i używana automatycznie przy każdym skierowaniu.
          Płatność dzielona na 2 raty (faktury +14 i +28 dni od przyjazdu). Widoczna tylko dla administratora.
        </p>
      </div>

      <!-- Szablony wiadomości (WhatsApp/SMS) -->
      <div class="card space-y-4 p-5">
        <div class="flex items-center justify-between">
          <p class="text-[13px] font-semibold text-ink">Szablony wiadomości (WhatsApp/SMS)</p>
          <button v-if="auth.isAdmin" type="button" class="text-sm font-medium text-brand-deep" @click="addTemplate">+ Dodaj</button>
        </div>
        <p class="text-xs text-stone">
          Dostępne pola: <code>{imie}</code> <code>{nazwisko}</code> <code>{telefon}</code> <code>{agencja}</code>.
          Używane przy przycisku WhatsApp na karcie kierowcy.
        </p>
        <div v-for="(t, i) in templates" :key="i" class="rounded-xl border border-hairline p-3">
          <div class="flex items-center gap-2">
            <input v-model="t.name" placeholder="Nazwa szablonu" class="input-field" :disabled="!auth.isAdmin" />
            <button v-if="auth.isAdmin" type="button" class="px-2 text-stone" @click="removeTemplate(i)"><AppIcon name="x" :size="18" /></button>
          </div>
          <textarea v-model="t.body" rows="2" placeholder="Treść wiadomości…" class="input-field mt-2 !h-auto py-2.5" :disabled="!auth.isAdmin" />
        </div>
        <p v-if="!templates.length" class="text-sm text-muted">Brak szablonów. Dodaj pierwszy.</p>
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
