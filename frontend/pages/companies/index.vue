<script setup lang="ts">
// Lista firm-klientów z możliwością szybkiego dodania.
const search = ref('')
const { data, isLoading } = useCompaniesQuery(search)
const createCompany = useCreateCompany()
const companies = computed(() => data.value?.data ?? [])

const showAdd = ref(false)
const name = ref('')
async function add() {
  if (!name.value.trim()) return
  await createCompany.mutateAsync({ name: name.value })
  name.value = ''
  showAdd.value = false
}
</script>

<template>
  <section>
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-2xl font-bold">Firmy</h1>
      <button class="rounded-lg bg-brand px-3 py-1 text-sm text-white" @click="showAdd = !showAdd">
        + Dodaj
      </button>
    </div>

    <div v-if="showAdd" class="mb-4 rounded-xl border border-gray-200 bg-white p-4">
      <input v-model="name" placeholder="Nazwa firmy" class="input-field mb-2" />
      <button class="btn-primary" :disabled="createCompany.isPending.value" @click="add">
        Zapisz firmę
      </button>
    </div>

    <input v-model="search" type="search" placeholder="Szukaj firmy…" class="input-field mb-4" />

    <p v-if="isLoading" class="py-8 text-center text-gray-400">Ładowanie…</p>
    <p v-else-if="!companies.length" class="py-8 text-center text-gray-400">Brak firm.</p>

    <ul v-else class="space-y-2">
      <li v-for="c in companies" :key="c.id">
        <NuxtLink :to="`/companies/${c.id}`" class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4">
          <div>
            <p class="font-semibold">{{ c.name }}</p>
            <p class="text-sm text-gray-500">{{ c.city || '—' }}</p>
          </div>
          <span class="text-xs text-gray-400">{{ c.job_postings_count ?? 0 }} ogł.</span>
        </NuxtLink>
      </li>
    </ul>
  </section>
</template>
