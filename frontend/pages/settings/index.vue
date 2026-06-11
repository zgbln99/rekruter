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
const unsplashKey = ref('') // pusty = bez zmian
const templates = ref<{ name: string; body: string }[]>([])
const configured = ref(false)
const unsplashConfigured = ref(false)
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
    unsplashConfigured.value = !!s.unsplash_configured
    ready.value = true
  }
}, { immediate: true })

function addTemplate() {
  templates.value.push({ name: '', body: '' })
}
function removeTemplate(i: number) {
  templates.value.splice(i, 1)
}

// --- Branding (logo / ikona / favicon) ---
const branding = useBranding()
const uploadBranding = useUploadBranding()
const deleteBranding = useDeleteBranding()
const brandingError = ref('')

// --- Zdjęcie hero strony kariery ---
const randomHero = useRandomCareersHero()
const heroLoading = ref(false)
async function randomizeHero() {
  heroLoading.value = true
  try {
    await randomHero.mutateAsync()
  } finally {
    heroLoading.value = false
  }
}
async function resetHero() {
  heroLoading.value = true
  try {
    await updateSettings.mutateAsync({ ...(data.value as any), careers_hero_image: null })
  } finally {
    heroLoading.value = false
  }
}

// --- Edytowalne teksty strony kariery ---
const careersTexts = reactive<Record<string, string>>({})
watch(
  () => data.value?.careers_texts,
  (ct) => {
    if (ct) for (const k of Object.keys(ct)) careersTexts[k] = ct[k].value
  },
  { immediate: true },
)
const textsSaving = ref(false)
const textsSaved = ref(false)
async function saveCareersTexts() {
  textsSaving.value = true
  textsSaved.value = false
  try {
    await updateSettings.mutateAsync({ ...(data.value as any), careers_texts: { ...careersTexts } })
    textsSaved.value = true
    setTimeout(() => (textsSaved.value = false), 2500)
  } finally {
    textsSaving.value = false
  }
}
const BRANDING = [
  { type: 'logo' as const, label: 'Logo (nagłówek)', hint: 'PNG/SVG, najlepiej poziome, na jasnym tle.' },
  { type: 'icon' as const, label: 'Ikona (kwadrat)', hint: 'PNG/SVG kwadratowe — znak bez napisu.' },
  { type: 'favicon' as const, label: 'Favicon (karta przeglądarki)', hint: 'PNG/SVG/ICO, kwadrat 32–512 px.' },
]
async function onBrandingFile(type: 'logo' | 'icon' | 'favicon', e: Event) {
  brandingError.value = ''
  const file = (e.target as HTMLInputElement).files?.[0]
  ;(e.target as HTMLInputElement).value = ''
  if (!file) return
  const form = new FormData()
  form.append(type, file)
  try {
    await uploadBranding.mutateAsync(form)
  } catch (err: any) {
    brandingError.value = err?.response?.status === 403
      ? 'Tylko administrator może zmieniać branding.'
      : 'Nie udało się wgrać pliku (dozwolone: PNG/JPG/WEBP/SVG, do 2 MB).'
  }
}
async function removeBranding(type: 'logo' | 'icon' | 'favicon') {
  brandingError.value = ''
  try {
    await deleteBranding.mutateAsync(type)
  } catch {
    brandingError.value = 'Nie udało się usunąć.'
  }
}
function brandingUrl(type: 'logo' | 'icon' | 'favicon') {
  return type === 'logo' ? branding.logoUrl.value : type === 'icon' ? branding.iconUrl.value : branding.faviconUrl.value
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
    if (unsplashKey.value.trim()) payload.unsplash_key = unsplashKey.value.trim()
    const res = await updateSettings.mutateAsync(payload)
    configured.value = !!res.openai_configured
    unsplashConfigured.value = !!res.unsplash_configured
    openaiKey.value = ''
    unsplashKey.value = ''
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
  <section class="mx-auto max-w-6xl">
    <UiPageHeader title="Ustawienia" subtitle="Dane agencji i integracje" />

    <UiSkeletonDetail v-if="isLoading" />
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

      <!-- Branding -->
      <div class="card space-y-4 p-5">
        <p class="text-[13px] font-semibold text-ink">Logo i ikony aplikacji</p>
        <p class="text-xs text-stone">Wgraj własne logo, ikonę i favicon — pojawią się w aplikacji i na karcie przeglądarki.</p>
        <div class="space-y-3">
          <div v-for="b in BRANDING" :key="b.type" class="flex items-center gap-3 rounded-xl border border-hairline p-3">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-hairline bg-surface-soft">
              <img v-if="brandingUrl(b.type)" :src="brandingUrl(b.type)!" alt="" class="max-h-full max-w-full object-contain" />
              <AppIcon v-else name="camera" :size="18" class="text-stone" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-ink">{{ b.label }}</p>
              <p class="text-xs text-stone">{{ b.hint }}</p>
            </div>
            <label v-if="auth.isAdmin" class="btn-sm cursor-pointer shrink-0">
              Wgraj
              <input type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/x-icon" class="hidden" @change="(e) => onBrandingFile(b.type, e)" />
            </label>
            <button v-if="auth.isAdmin && brandingUrl(b.type)" type="button" class="shrink-0 text-sm text-red-600" @click="removeBranding(b.type)">Usuń</button>
          </div>
        </div>
        <p v-if="brandingError" class="text-sm text-red-600">{{ brandingError }}</p>
      </div>

      <!-- Zdjęcia z Unsplash (klucz API) -->
      <div class="card space-y-4 p-5">
        <div class="flex items-center justify-between">
          <p class="text-[13px] font-semibold text-ink">Zdjęcia ciężarówek (Unsplash)</p>
          <span class="badge" :class="unsplashConfigured ? 'badge-accent' : 'badge-neutral'">
            {{ unsplashConfigured ? 'Połączono' : 'Nie skonfigurowano' }}
          </span>
        </div>
        <p class="text-xs text-stone">
          Bez klucza okładki ofert i zdjęcie hero losowane są z małej, stałej puli (te same kilka zdjęć).
          Podaj Access Key z
          <a href="https://unsplash.com/developers" target="_blank" rel="noopener" class="font-medium text-brand-deep underline">unsplash.com/developers</a>,
          aby pobierać świeże zdjęcia europejskich ciężarówek (Scania, Volvo, DAF, MAN, Actros).
        </p>
        <div>
          <label class="field-label">Access Key (Unsplash)</label>
          <input
            v-model="unsplashKey"
            type="password"
            class="input-field"
            :placeholder="unsplashConfigured ? '•••••••••• (ustawiony — wpisz, aby zmienić)' : 'np. AbCdEf123...'"
            :disabled="!auth.isAdmin"
            autocomplete="off"
          />
          <p class="mt-1 text-xs text-stone">Użyj wyłącznie „Access Key" (nie Secret Key). Klucz jest przechowywany w ustawieniach organizacji i nie jest pokazywany ponownie.</p>
        </div>
      </div>

      <!-- Zdjęcie hero strony kariery -->
      <div class="card space-y-4 p-5">
        <p class="text-[13px] font-semibold text-ink">Strona kariery — zdjęcie hero</p>
        <p class="text-xs text-stone">Duże zdjęcie u góry publicznej strony z ofertami. Losuj europejską ciężarówkę z Unsplash.</p>
        <div class="overflow-hidden rounded-xl border border-hairline">
          <div
            class="h-40 bg-gradient-to-br from-slate-800 to-ink"
            :style="data?.careers_hero_effective ? `background-image:url('${data.careers_hero_effective}');background-size:cover;background-position:center` : ''"
          />
        </div>
        <div v-if="auth.isAdmin" class="flex flex-wrap items-center gap-2">
          <button class="btn-sm" :disabled="heroLoading" @click="randomizeHero">
            <AppIcon name="camera" :size="16" /> {{ heroLoading ? 'Pobieram…' : 'Losuj zdjęcie' }}
          </button>
          <button
            v-if="data?.careers_hero_image"
            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface disabled:opacity-50"
            :disabled="heroLoading"
            @click="resetHero"
          >
            Przywróć domyślne
          </button>
        </div>
      </div>

      <!-- Teksty strony kariery -->
      <div v-if="data?.careers_texts" class="card space-y-4 p-5">
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-[13px] font-semibold text-ink">Strona kariery — teksty</p>
            <p class="text-xs text-stone">Nagłówki i opisy na publicznej stronie głównej. Puste = tekst domyślny.</p>
          </div>
          <span v-if="textsSaved" class="badge badge-accent shrink-0">Zapisano</span>
        </div>
        <div class="space-y-3">
          <div v-for="(field, key) in data.careers_texts" :key="key">
            <label class="field-label">{{ field.label }}</label>
            <textarea
              v-if="field.type === 'textarea'"
              v-model="careersTexts[key]"
              rows="2"
              class="input-field !h-auto py-2.5"
              :disabled="!auth.isAdmin"
            />
            <input v-else v-model="careersTexts[key]" type="text" class="input-field" :disabled="!auth.isAdmin" />
          </div>
        </div>
        <button v-if="auth.isAdmin" class="btn-primary !w-auto" :disabled="textsSaving" @click="saveCareersTexts">
          {{ textsSaving ? 'Zapisywanie…' : 'Zapisz teksty' }}
        </button>
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
