<script setup lang="ts">
import { FAQ_PRESETS, LICENSE_CATEGORIES, REQUIREMENT_OPTIONS } from '~/utils/options'
import type { JobPosting, LicenseCategory, OfferRequirementKey } from '~/types'

// Edycja ogłoszenia.
const route = useRoute()
const router = useRouter()
const id = computed(() => route.params.id as string)
const { data: offer } = useJobOfferQuery(id)
const { data: companiesData } = useCompaniesQuery()
const companies = computed(() => companiesData.value?.data ?? [])
const updateOffer = useUpdateJobOffer(id)

const form = reactive<Record<string, any>>({
  company_id: '', title: '', driver_type: '', trailer_type: '', country: '',
  region_base: '', work_system: '', salary_amount: '', currency: 'EUR',
  required_language: '', required_experience: '', description: '',
  arrival_info: '', vehicle_type: '', cargo: '', routes_info: '',
  accommodation: '', onsite_contact: '', contract_type: '', points_per_day: '',
  loading_info: '', daily_km: '', pdf_url: '',
  public_description: '', recruiter_notes: '', status: 'open',
})
const categories = ref<Set<LicenseCategory>>(new Set())
const requirements = ref<Set<OfferRequirementKey>>(new Set())
const callScript = ref<string[]>([''])
const faqItems = ref<{ q: string; a: string }[]>([])
const ready = ref(false)
const error = ref('')

function addFaq(q = '') {
  faqItems.value.push({ q, a: '' })
}

const aiLoading = ref(false)
const aiError = ref('')
async function aiDescription() {
  aiError.value = ''
  aiLoading.value = true
  try {
    form.public_description = await generateOfferDescription({ ...form, required_categories: [...categories.value] })
  } catch (e: any) {
    aiError.value = e?.response?._data?.errors?.openai?.[0] || 'Nie udało się wygenerować opisu (sprawdź klucz API w Ustawieniach).'
  } finally {
    aiLoading.value = false
  }
}

watch(offer, (o) => {
  if (!o || ready.value) return
  for (const k of Object.keys(form)) {
    if (o[k as keyof JobPosting] != null) form[k] = o[k as keyof JobPosting]
  }
  categories.value = new Set(o.required_categories || [])
  requirements.value = new Set(
    Object.entries(o.requirements || {}).filter(([, v]) => v).map(([k]) => k as OfferRequirementKey),
  )
  callScript.value = o.call_script?.length ? [...o.call_script] : ['']
  faqItems.value = (o.faq || []).map((f) => ({ q: f.q || '', a: f.a || '' }))
  ready.value = true
}, { immediate: true })

function toggleCat(c: LicenseCategory) {
  const s = new Set(categories.value); s.has(c) ? s.delete(c) : s.add(c); categories.value = s
}
function toggleReq(k: OfferRequirementKey) {
  const s = new Set(requirements.value); s.has(k) ? s.delete(k) : s.add(k); requirements.value = s
}

async function save() {
  error.value = ''
  const requirementsMap: Record<string, boolean> = {}
  requirements.value.forEach((k) => (requirementsMap[k] = true))
  try {
    await updateOffer.mutateAsync({
      ...form,
      required_categories: [...categories.value],
      requirements: requirementsMap,
      call_script: callScript.value.map((s) => s.trim()).filter(Boolean),
      faq: faqItems.value.filter((f) => f.q.trim()),
    } as Partial<JobPosting>)
    await router.push(`/job-offers/${id.value}`)
  } catch {
    error.value = 'Nie udało się zapisać zmian.'
  }
}
</script>

<template>
  <section class="mx-auto max-w-3xl pb-8">
    <div class="mb-5 flex items-center justify-between">
      <h1 class="page-title">Edycja ogłoszenia</h1>
      <NuxtLink :to="`/job-offers/${id}`" class="text-sm text-stone">Anuluj</NuxtLink>
    </div>

    <form class="space-y-5" @submit.prevent="save">
      <div class="grid gap-3 sm:grid-cols-2">
        <div class="sm:col-span-2">
          <label class="field-label">Firma</label>
          <select v-model="form.company_id" class="input-field">
            <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="field-label">Tytuł stanowiska</label>
          <input v-model="form.title" class="input-field" />
        </div>
        <div><label class="field-label">Typ kierowcy</label><input v-model="form.driver_type" class="input-field" /></div>
        <div><label class="field-label">Typ naczepy</label><input v-model="form.trailer_type" class="input-field" /></div>
        <div><label class="field-label">Kraj pracy</label><input v-model="form.country" class="input-field" /></div>
        <div><label class="field-label">Region / baza</label><input v-model="form.region_base" class="input-field" /></div>
        <div><label class="field-label">System pracy</label><input v-model="form.work_system" class="input-field" /></div>
        <div><label class="field-label">Wymagany język</label><input v-model="form.required_language" class="input-field" /></div>
        <div><label class="field-label">Wynagrodzenie</label><input v-model="form.salary_amount" class="input-field" /></div>
        <div><label class="field-label">Waluta</label><input v-model="form.currency" class="input-field" /></div>
        <div>
          <label class="field-label">Status</label>
          <select v-model="form.status" class="input-field">
            <option value="open">Aktywne</option>
            <option value="paused">Wstrzymane</option>
            <option value="closed">Zamknięte</option>
          </select>
        </div>
        <div><label class="field-label">Wymagane doświadczenie</label><input v-model="form.required_experience" class="input-field" /></div>
      </div>

      <div>
        <label class="field-label">Kategorie prawa jazdy</label>
        <div class="flex flex-wrap gap-2">
          <UiChip v-for="c in LICENSE_CATEGORIES" :key="c" :active="categories.has(c)" @click="toggleCat(c)">{{ c }}</UiChip>
        </div>
      </div>

      <div>
        <label class="field-label">Wymagania</label>
        <div class="flex flex-wrap gap-2">
          <UiChip v-for="r in REQUIREMENT_OPTIONS" :key="r.key" :active="requirements.has(r.key)" @click="toggleReq(r.key)">{{ r.label }}</UiChip>
        </div>
      </div>

      <div class="rounded-lg border border-hairline bg-surface-soft p-4">
        <p class="mb-3 text-[13px] font-semibold text-ink">Skierowanie do pracy (PDF dla kierowcy)</p>
        <div class="grid gap-3 sm:grid-cols-2">
          <div><label class="field-label">Data/godzina przyjazdu</label><input v-model="form.arrival_info" class="input-field" /></div>
          <div><label class="field-label">Typ auta (opis)</label><input v-model="form.vehicle_type" class="input-field" /></div>
          <div><label class="field-label">Rodzaj umowy</label><input v-model="form.contract_type" class="input-field" /></div>
          <div><label class="field-label">Liczba punktów dziennie</label><input v-model="form.points_per_day" class="input-field" /></div>
          <div><label class="field-label">Załadunek / rozładunek</label><input v-model="form.loading_info" class="input-field" /></div>
          <div><label class="field-label">Średni przebieg dzienny</label><input v-model="form.daily_km" class="input-field" /></div>
          <div><label class="field-label">Przewożony towar</label><input v-model="form.cargo" class="input-field" /></div>
          <div><label class="field-label">Link do PDF ogłoszenia</label><input v-model="form.pdf_url" class="input-field" /></div>
        </div>
        <div class="mt-3"><label class="field-label">Trasy</label><textarea v-model="form.routes_info" rows="2" class="input-field !h-auto py-2.5" /></div>
        <div class="mt-3"><label class="field-label">Zakwaterowanie</label><textarea v-model="form.accommodation" rows="2" class="input-field !h-auto py-2.5" /></div>
        <div class="mt-3"><label class="field-label">Osoba kontaktowa na miejscu</label><textarea v-model="form.onsite_contact" rows="2" class="input-field !h-auto py-2.5" /></div>
      </div>

      <!-- FAQ dla rekruterki -->
      <div class="rounded-lg border border-hairline bg-surface-soft p-4">
        <p class="mb-1 text-[13px] font-semibold text-ink">FAQ dla rekruterki</p>
        <div class="mb-3 flex flex-wrap gap-1.5">
          <button v-for="p in FAQ_PRESETS" :key="p" type="button" class="chip" @click="addFaq(p)">+ {{ p }}</button>
        </div>
        <div class="space-y-2">
          <div v-for="(item, i) in faqItems" :key="i" class="rounded-lg border border-hairline bg-canvas p-2.5">
            <div class="flex gap-2">
              <input v-model="item.q" placeholder="Pytanie" class="input-field" />
              <button type="button" class="px-2 text-stone" @click="faqItems.splice(i, 1)"><AppIcon name="x" :size="18" /></button>
            </div>
            <input v-model="item.a" placeholder="Odpowiedź" class="input-field mt-2" />
          </div>
          <button type="button" class="text-sm font-medium text-brand-deep" @click="addFaq()">+ Dodaj własne pytanie</button>
        </div>
      </div>

      <div>
        <label class="field-label">Opis stanowiska</label>
        <textarea v-model="form.description" rows="3" class="input-field !h-auto py-2.5" />
      </div>

      <div>
        <label class="field-label">Skrypt rozmowy</label>
        <div class="space-y-2">
          <div v-for="(q, i) in callScript" :key="i" class="flex gap-2">
            <input v-model="callScript[i]" placeholder="Pytanie do kierowcy" class="input-field" />
            <button type="button" class="px-2 text-stone" @click="callScript.splice(i, 1)"><AppIcon name="x" :size="18" /></button>
          </div>
          <button type="button" class="text-sm font-medium text-brand-deep" @click="callScript.push('')">+ Dodaj pytanie</button>
        </div>
      </div>

      <div>
        <div class="mb-1.5 flex items-center justify-between">
          <label class="field-label !mb-0">Gotowy opis (publiczny)</label>
          <button type="button" class="btn-sm" :disabled="aiLoading" @click="aiDescription">
            <AppIcon name="check" :size="15" /> {{ aiLoading ? 'Generuję…' : 'Generuj opis (AI)' }}
          </button>
        </div>
        <textarea v-model="form.public_description" rows="6" class="input-field !h-auto py-2.5" />
        <p v-if="aiError" class="mt-1 text-sm text-red-600">{{ aiError }}</p>
      </div>
      <div>
        <label class="field-label">Notatka dla rekruterki (wewnętrzna)</label>
        <textarea v-model="form.recruiter_notes" rows="2" class="input-field !h-auto py-2.5" />
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <button type="submit" class="btn-primary" :disabled="updateOffer.isPending.value">
        {{ updateOffer.isPending.value ? 'Zapisywanie…' : 'Zapisz zmiany' }}
      </button>
    </form>
  </section>
</template>
