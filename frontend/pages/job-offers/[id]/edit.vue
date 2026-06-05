<script setup lang="ts">
import { LICENSE_CATEGORIES, REQUIREMENT_OPTIONS } from '~/utils/options'
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
  public_description: '', recruiter_notes: '', status: 'open',
})
const categories = ref<Set<LicenseCategory>>(new Set())
const requirements = ref<Set<OfferRequirementKey>>(new Set())
const callScript = ref<string[]>([''])
const ready = ref(false)
const error = ref('')

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
        <label class="field-label">Gotowy opis (publiczny)</label>
        <textarea v-model="form.public_description" rows="4" class="input-field !h-auto py-2.5" />
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
