<script setup lang="ts">
import { LICENSE_CATEGORIES } from '~/utils/options'
import type { LicenseCategory } from '~/types'

// Szczegóły firmy + jej ogłoszenia + dodawanie ogłoszenia.
const route = useRoute()
const id = computed(() => route.params.id as string)
const { data: company, isLoading } = useCompanyQuery(id)
const createPosting = useCreateJobPosting()

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
  <section v-if="isLoading" class="py-8 text-center text-gray-400">Ładowanie…</section>

  <section v-else-if="company" class="space-y-5 pb-8">
    <div>
      <h1 class="text-2xl font-bold">{{ company.name }}</h1>
      <p class="text-sm text-gray-500">{{ company.city || '—' }}</p>
    </div>

    <div v-if="company.contact_person || company.contact_phone" class="rounded-xl border border-gray-200 bg-white p-4 text-sm">
      <p class="font-medium text-gray-700">Kontakt</p>
      <p>{{ company.contact_person }}</p>
      <a v-if="company.contact_phone" :href="`tel:${company.contact_phone}`" class="text-brand">{{ company.contact_phone }}</a>
      <p v-if="company.contact_email" class="text-gray-500">{{ company.contact_email }}</p>
    </div>

    <div>
      <div class="mb-2 flex items-center justify-between">
        <h2 class="text-lg font-semibold">Ogłoszenia</h2>
        <button class="rounded-lg bg-brand px-3 py-1 text-sm text-white" @click="showAdd = !showAdd">+ Dodaj</button>
      </div>

      <div v-if="showAdd" class="mb-3 rounded-xl border border-gray-200 bg-white p-4">
        <input v-model="title" placeholder="Tytuł, np. Kierowca C+E" class="input-field mb-2" />
        <input v-model="location" placeholder="Lokalizacja" class="input-field mb-2" />
        <div class="mb-3 flex flex-wrap gap-2">
          <UiChip v-for="cat in LICENSE_CATEGORIES" :key="cat" :active="categories.has(cat)" @click="toggle(cat)">
            {{ cat }}
          </UiChip>
        </div>
        <button class="btn-primary" :disabled="createPosting.isPending.value" @click="addPosting">Zapisz ogłoszenie</button>
      </div>

      <ul v-if="company.job_postings?.length" class="space-y-2">
        <li v-for="p in company.job_postings" :key="p.id">
          <NuxtLink :to="`/pipeline/${p.id}`" class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4">
            <div>
              <p class="font-semibold">{{ p.title }}</p>
              <p class="text-sm text-gray-500">{{ p.status_label }} · {{ p.location || '—' }}</p>
            </div>
            <span class="text-xs text-brand">Pipeline →</span>
          </NuxtLink>
        </li>
      </ul>
      <p v-else class="text-sm text-gray-400">Brak ogłoszeń.</p>
    </div>
  </section>
</template>
