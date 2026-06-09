<script setup lang="ts">
import { LICENSE_CATEGORIES } from '~/utils/options'
import type { LicenseCategory } from '~/types'

// Szczegóły firmy + jej ogłoszenia + dodawanie ogłoszenia.
const route = useRoute()
const id = computed(() => route.params.id as string)
const { data: company, isLoading } = useCompanyQuery(id)
const createPosting = useCreateJobPosting()

// Edycja danych firmy.
const updateCompany = useUpdateCompany(id)
const editing = ref(false)
const saving = ref(false)
const form = reactive({
  name: '', city: '', country: '', nip: '', website: '',
  contact_person: '', contact_phone: '', contact_email: '',
  description: '', notes: '', status: 'active' as 'active' | 'inactive',
})
function startEdit() {
  if (!company.value) return
  const c = company.value
  Object.assign(form, {
    name: c.name || '', city: c.city || '', country: c.country || '', nip: c.nip || '',
    website: c.website || '', contact_person: c.contact_person || '', contact_phone: c.contact_phone || '',
    contact_email: c.contact_email || '', description: c.description || '', notes: c.notes || '',
    status: c.status || 'active',
  })
  editing.value = true
}
async function saveCompany() {
  if (!form.name.trim()) return
  saving.value = true
  try {
    await updateCompany.mutateAsync({ ...form })
    editing.value = false
  } finally {
    saving.value = false
  }
}

const showAdd = ref(false)
const title = ref('')
const categories = ref<Set<LicenseCategory>>(new Set())
const location = ref('')

function toggle(cat: LicenseCategory) {
  const s = new Set(categories.value)
  s.has(cat) ? s.delete(cat) : s.add(cat)
  categories.value = s
}

async function addPosting() {
  if (!title.value.trim()) return
  await createPosting.mutateAsync({
    company_id: id.value,
    title: title.value,
    required_categories: [...categories.value],
    location: location.value || null,
  })
  title.value = ''
  location.value = ''
  categories.value = new Set()
  showAdd.value = false
}
</script>

<template>
  <section v-if="isLoading" class="py-10 text-center text-muted">Ładowanie…</section>

  <section v-else-if="company" class="mx-auto max-w-7xl space-y-5 pb-8">
    <div class="flex items-center gap-3">
      <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-surface text-stone">
        <AppIcon name="building" :size="22" />
      </span>
      <div class="min-w-0 flex-1">
        <h1 class="truncate text-2xl font-bold tracking-tight text-ink">{{ company.name }}</h1>
        <p class="text-sm text-stone">{{ [company.city, company.country].filter(Boolean).join(', ') || '—' }}</p>
      </div>
      <button v-if="!editing" class="btn-sm shrink-0" @click="startEdit">
        <AppIcon name="settings" :size="16" /> Edytuj
      </button>
    </div>

    <!-- Formularz edycji firmy -->
    <div v-if="editing" class="card space-y-3 p-5">
      <div class="grid gap-3 sm:grid-cols-2">
        <div class="sm:col-span-2"><label class="field-label">Nazwa firmy</label><input v-model="form.name" class="input-field" /></div>
        <div><label class="field-label">Miasto</label><input v-model="form.city" class="input-field" /></div>
        <div><label class="field-label">Kraj</label><input v-model="form.country" class="input-field" /></div>
        <div><label class="field-label">NIP</label><input v-model="form.nip" class="input-field" /></div>
        <div><label class="field-label">Strona www</label><input v-model="form.website" class="input-field" /></div>
        <div><label class="field-label">Osoba kontaktowa</label><input v-model="form.contact_person" class="input-field" /></div>
        <div><label class="field-label">Telefon</label><input v-model="form.contact_phone" class="input-field" /></div>
        <div><label class="field-label">E-mail</label><input v-model="form.contact_email" type="email" class="input-field" /></div>
        <div>
          <label class="field-label">Status</label>
          <select v-model="form.status" class="input-field">
            <option value="active">Aktywna</option>
            <option value="inactive">Nieaktywna</option>
          </select>
        </div>
        <div class="sm:col-span-2"><label class="field-label">Opis</label><textarea v-model="form.description" rows="2" class="input-field !h-auto py-2.5" /></div>
        <div class="sm:col-span-2"><label class="field-label">Notatki (wewnętrzne)</label><textarea v-model="form.notes" rows="2" class="input-field !h-auto py-2.5" /></div>
      </div>
      <div class="flex gap-2">
        <button class="btn-primary !w-auto" :disabled="saving || !form.name.trim()" @click="saveCompany">
          {{ saving ? 'Zapisywanie…' : 'Zapisz zmiany' }}
        </button>
        <button class="rounded-xl border border-hairline px-4 py-2 text-sm font-medium text-ink transition hover:bg-surface" @click="editing = false">Anuluj</button>
      </div>
    </div>

    <div v-else-if="company.contact_person || company.contact_phone || company.contact_email" class="card p-4">
      <p class="mb-1.5 text-[13px] font-medium text-steel">Kontakt</p>
      <p v-if="company.contact_person" class="font-medium text-ink">{{ company.contact_person }}</p>
      <a v-if="company.contact_phone" :href="`tel:${company.contact_phone}`" class="inline-flex items-center gap-1.5 text-brand-deep">
        <AppIcon name="phone" :size="15" /> {{ company.contact_phone }}
      </a>
      <p v-if="company.contact_email" class="text-sm text-stone">{{ company.contact_email }}</p>
    </div>

    <div>
      <div class="mb-3 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-ink">Ogłoszenia</h2>
        <button class="btn-sm" @click="showAdd = !showAdd">
          <AppIcon name="plus" :size="16" /> Dodaj
        </button>
      </div>

      <div v-if="showAdd" class="card mb-3 p-4">
        <input v-model="title" placeholder="Tytuł, np. Kierowca C+E" class="input-field mb-2.5" />
        <input v-model="location" placeholder="Lokalizacja" class="input-field mb-3" />
        <div class="mb-3 flex flex-wrap gap-2">
          <UiChip v-for="cat in LICENSE_CATEGORIES" :key="cat" :active="categories.has(cat)" @click="toggle(cat)">
            {{ cat }}
          </UiChip>
        </div>
        <button class="btn-primary" :disabled="createPosting.isPending.value" @click="addPosting">Zapisz ogłoszenie</button>
      </div>

      <ul v-if="company.job_postings?.length" class="space-y-2.5">
        <li v-for="p in company.job_postings" :key="p.id">
          <NuxtLink :to="`/pipeline/${p.id}`" class="card flex items-center gap-3 p-4 transition active:bg-surface-soft">
            <div class="min-w-0 flex-1">
              <p class="truncate font-semibold text-ink">{{ p.title }}</p>
              <p class="text-sm text-stone">{{ p.status_label }} · {{ p.location || '—' }}</p>
            </div>
            <span class="inline-flex items-center gap-1 text-sm font-medium text-brand-deep">
              Pipeline <AppIcon name="chevron" :size="15" />
            </span>
          </NuxtLink>
        </li>
      </ul>
      <p v-else class="text-sm text-muted">Brak ogłoszeń.</p>
    </div>
  </section>
</template>
