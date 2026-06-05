<script setup lang="ts">
import { LICENSE_CATEGORIES, REQUIREMENT_OPTIONS } from '~/utils/options'
import type { JobPosting, LicenseCategory, OfferRequirementKey } from '~/types'

// Tworzenie ogłoszenia. Priorytet: kompletność danych biznesowych.
const router = useRouter()
const route = useRoute()
const createOffer = useCreateJobOffer()
const { data: companiesData } = useCompaniesQuery()
const companies = computed(() => companiesData.value?.data ?? [])

const form = reactive<Partial<JobPosting>>({
  company_id: (route.query.company_id as string) || '',
  title: '',
  driver_type: '',
  trailer_type: '',
  country: '',
  region_base: '',
  work_system: '',
  salary_amount: '',
  currency: 'EUR',
  required_language: '',
  required_experience: '',
  public_description: '',
  recruiter_notes: '',
  status: 'open',
})

const categories = ref<Set<LicenseCategory>>(new Set())
const requirements = ref<Set<OfferRequirementKey>>(new Set())
const callScript = ref<string[]>([''])
const error = ref('')

function toggleCat(c: LicenseCategory) {
  const s = new Set(categories.value)
  s.has(c) ? s.delete(c) : s.add(c)
  categories.value = s
}
function toggleReq(k: OfferRequirementKey) {
  const s = new Set(requirements.value)
  s.has(k) ? s.delete(k) : s.add(k)
  requirements.value = s
}

async function submit() {
  error.value = ''
  if (!form.company_id || !form.title) {
    error.value = 'Wybierz firmę i podaj tytuł.'
    return
  }
  const requirementsMap: Record<string, boolean> = {}
  requirements.value.forEach((k) => (requirementsMap[k] = true))

  try {
    const offer = await createOffer.mutateAsync({
      ...form,
      required_categories: [...categories.value],
      requirements: requirementsMap,
      call_script: callScript.value.map((s) => s.trim()).filter(Boolean),
    } as Partial<JobPosting>)
    await router.push(`/job-offers/${offer.id}`)
  } catch {
    error.value = 'Nie udało się zapisać ogłoszenia.'
  }
}
</script>

<template>
  <section class="pb-8">
    <div class="mb-5 flex items-center justify-between">
      <h1 class="text-[26px] font-bold tracking-tight text-ink">Nowe ogłoszenie</h1>
      <NuxtLink to="/job-offers" class="text-sm text-stone">Anuluj</NuxtLink>
    </div>

    <form class="space-y-5" @submit.prevent="submit">
      <div>
        <label class="field-label">Firma</label>
        <select v-model="form.company_id" class="input-field">
          <option value="">Wybierz firmę…</option>
          <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>

      <div>
        <label class="field-label">Tytuł stanowiska</label>
        <input v-model="form.title" placeholder="np. Kierowca C+E chłodnia DE" class="input-field" />
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="field-label">Typ kierowcy</label>
          <input v-model="form.driver_type" placeholder="solo / team" class="input-field" />
        </div>
        <div>
          <label class="field-label">Typ naczepy</label>
          <input v-model="form.trailer_type" placeholder="plandeka / chłodnia" class="input-field" />
        </div>
        <div>
          <label class="field-label">Kraj pracy</label>
          <input v-model="form.country" placeholder="Niemcy" class="input-field" />
        </div>
        <div>
          <label class="field-label">Region / baza</label>
          <input v-model="form.region_base" class="input-field" />
        </div>
        <div>
          <label class="field-label">System pracy</label>
          <input v-model="form.work_system" placeholder="3/1" class="input-field" />
        </div>
        <div>
          <label class="field-label">Wymagany język</label>
          <input v-model="form.required_language" placeholder="komunikatywny DE" class="input-field" />
        </div>
        <div>
          <label class="field-label">Wynagrodzenie</label>
          <input v-model="form.salary_amount" placeholder="2000-2300" class="input-field" />
        </div>
        <div>
          <label class="field-label">Waluta</label>
          <input v-model="form.currency" class="input-field" />
        </div>
      </div>

      <div>
        <label class="field-label">Wymagane doświadczenie</label>
        <input v-model="form.required_experience" placeholder="min. 2 lata C+E" class="input-field" />
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
          <UiChip v-for="r in REQUIREMENT_OPTIONS" :key="r.key" :active="requirements.has(r.key)" @click="toggleReq(r.key)">
            {{ r.label }}
          </UiChip>
        </div>
      </div>

      <div>
        <label class="field-label">Skrypt rozmowy (pytania do kierowcy)</label>
        <div class="space-y-2">
          <div v-for="(q, i) in callScript" :key="i" class="flex gap-2">
            <input v-model="callScript[i]" placeholder="np. Czy ma Pan/Pani kartę kierowcy?" class="input-field" />
            <button type="button" class="px-2 text-stone" @click="callScript.splice(i, 1)">
              <AppIcon name="x" :size="18" />
            </button>
          </div>
          <button type="button" class="text-sm font-medium text-brand-deep" @click="callScript.push('')">
            + Dodaj pytanie
          </button>
        </div>
      </div>

      <div>
        <label class="field-label">Gotowy opis ogłoszenia (publiczny — do kopiowania)</label>
        <textarea v-model="form.public_description" rows="4" class="input-field !h-auto py-2.5" placeholder="Treść na FB / OLX / Jooble…" />
      </div>

      <div>
        <label class="field-label">Notatka dla rekruterki (wewnętrzna)</label>
        <textarea v-model="form.recruiter_notes" rows="2" class="input-field !h-auto py-2.5" placeholder="np. klient nie chce kandydatów bez chłodni" />
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <button type="submit" class="btn-primary" :disabled="createOffer.isPending.value">
        {{ createOffer.isPending.value ? 'Zapisywanie…' : 'Zapisz ogłoszenie' }}
      </button>
    </form>
  </section>
</template>
