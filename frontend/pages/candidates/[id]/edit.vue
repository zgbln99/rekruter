<script setup lang="ts">
import {
  CANDIDATE_SOURCE_OPTIONS,
  CANDIDATE_STATUS_OPTIONS,
  DRIVER_ATTRIBUTE_OPTIONS,
  LICENSE_CATEGORIES,
} from '~/utils/options'
import type { Candidate, LicenseCategory, WorkHistoryItem } from '~/types'

// Pełna edycja kartoteki kandydata.
const route = useRoute()
const router = useRouter()
const id = computed(() => route.params.id as string)
const { data: candidate } = useCandidateQuery(id)
const updateCandidate = useUpdateCandidate(id)

const form = reactive<Record<string, any>>({
  first_name: '', last_name: '', phone: '', email: '',
  city: '', country: '', address: '', date_of_birth: '', nationality: '',
  availability_from: '', source: '', status: 'new', experience_notes: '', internal_notes: '',
  has_adr: false, has_code_95: false, has_hds: false,
  exp_reefer: false, exp_tilt: false, exp_international: false,
  lang_de: false, lang_en: false,
})
const categories = ref<Set<LicenseCategory>>(new Set())
const workHistory = ref<WorkHistoryItem[]>([])
const ready = ref(false)
const error = ref('')

watch(
  candidate,
  (c) => {
    if (!c || ready.value) return
    for (const k of Object.keys(form)) {
      if (c[k as keyof Candidate] != null) form[k] = c[k as keyof Candidate]
    }
    categories.value = new Set(c.license_categories || [])
    workHistory.value = (c.work_history || []).map((w) => ({ ...w }))
    ready.value = true
  },
  { immediate: true },
)

function toggleCat(cat: LicenseCategory) {
  const s = new Set(categories.value)
  s.has(cat) ? s.delete(cat) : s.add(cat)
  categories.value = s
}

async function save() {
  error.value = ''
  try {
    await updateCandidate.mutateAsync({
      ...form,
      license_categories: [...categories.value],
      work_history: workHistory.value.filter((w) => w.employer || w.position),
    } as Partial<Candidate>)
    await router.push(`/candidates/${id.value}`)
  } catch (e: any) {
    const errs = e?.response?._data?.errors
    error.value = errs
      ? Object.values(errs).flat().join(' ')
      : 'Nie udało się zapisać zmian.'
  }
}
</script>

<template>
  <section class="mx-auto max-w-7xl pb-8">
    <div class="mb-5 flex items-center justify-between">
      <h1 class="text-[26px] font-bold tracking-tight text-ink">Edycja kandydata</h1>
      <NuxtLink :to="`/candidates/${id}`" class="text-sm text-stone">Anuluj</NuxtLink>
    </div>

    <form class="space-y-5" @submit.prevent="save">
      <div class="grid gap-3 sm:grid-cols-2">
        <div><label class="field-label">Imię</label><input v-model="form.first_name" class="input-field" /></div>
        <div><label class="field-label">Nazwisko</label><input v-model="form.last_name" class="input-field" /></div>
        <div><label class="field-label">Telefon</label><input v-model="form.phone" type="tel" class="input-field" /></div>
        <div><label class="field-label">E-mail</label><input v-model="form.email" type="email" class="input-field" /></div>
        <div><label class="field-label">Data urodzenia</label><input v-model="form.date_of_birth" type="date" class="input-field" /></div>
        <div><label class="field-label">Narodowość</label><input v-model="form.nationality" class="input-field" /></div>
        <div><label class="field-label">Miasto</label><input v-model="form.city" class="input-field" /></div>
        <div><label class="field-label">Kraj</label><input v-model="form.country" class="input-field" /></div>
        <div class="sm:col-span-2"><label class="field-label">Adres</label><input v-model="form.address" class="input-field" /></div>
        <div><label class="field-label">Dostępność od</label><input v-model="form.availability_from" type="date" class="input-field" /></div>
        <div>
          <label class="field-label">Źródło</label>
          <select v-model="form.source" class="input-field">
            <option value="">—</option>
            <option v-for="s in CANDIDATE_SOURCE_OPTIONS" :key="s.value" :value="s.value">{{ s.label }}</option>
          </select>
        </div>
        <div>
          <label class="field-label">Status kandydata</label>
          <select v-model="form.status" class="input-field">
            <option v-for="s in CANDIDATE_STATUS_OPTIONS" :key="s.value" :value="s.value">{{ s.label }}</option>
          </select>
        </div>
      </div>

      <div>
        <label class="field-label">Kategorie prawa jazdy</label>
        <div class="flex flex-wrap gap-2">
          <UiChip v-for="c in LICENSE_CATEGORIES" :key="c" :active="categories.has(c)" @click="toggleCat(c)">{{ c }}</UiChip>
        </div>
      </div>

      <div>
        <label class="field-label">Uprawnienia i doświadczenie</label>
        <div class="flex flex-wrap gap-2">
          <UiChip v-for="a in DRIVER_ATTRIBUTE_OPTIONS" :key="a.key" :active="form[a.key]" @click="form[a.key] = !form[a.key]">
            {{ a.label }}
          </UiChip>
        </div>
      </div>

      <div>
        <label class="field-label">Opis doświadczenia</label>
        <textarea v-model="form.experience_notes" rows="2" class="input-field !h-auto py-2.5" />
      </div>

      <!-- Historia pracy -->
      <div>
        <label class="field-label">Historia pracy</label>
        <div class="space-y-2">
          <div v-for="(job, i) in workHistory" :key="i" class="card space-y-2 p-3">
            <div class="grid gap-2 sm:grid-cols-3">
              <input v-model="job.employer" placeholder="Pracodawca" class="input-field" />
              <input v-model="job.position" placeholder="Stanowisko" class="input-field" />
              <input v-model="job.period" placeholder="Okres (2018–2022)" class="input-field" />
            </div>
            <div class="flex gap-2">
              <input v-model="job.description" placeholder="Opis (opcjonalnie)" class="input-field" />
              <button type="button" class="px-2 text-stone" @click="workHistory.splice(i, 1)">
                <AppIcon name="x" :size="18" />
              </button>
            </div>
          </div>
          <button type="button" class="text-sm font-medium text-brand-deep" @click="workHistory.push({})">
            + Dodaj miejsce pracy
          </button>
        </div>
      </div>

      <div>
        <label class="field-label">Notatka wewnętrzna</label>
        <textarea v-model="form.internal_notes" rows="2" class="input-field !h-auto py-2.5" />
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <button type="submit" class="btn-primary" :disabled="updateCandidate.isPending.value">
        {{ updateCandidate.isPending.value ? 'Zapisywanie…' : 'Zapisz zmiany' }}
      </button>
    </form>
  </section>
</template>
