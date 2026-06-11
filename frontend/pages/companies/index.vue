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
    <header class="mb-4 flex items-center justify-between">
      <h1 class="text-[26px] font-bold tracking-tight text-ink">Firmy</h1>
      <button class="btn-sm" @click="showAdd = !showAdd">
        <AppIcon name="plus" :size="16" /> Dodaj
      </button>
    </header>

    <div v-if="showAdd" class="card mb-4 p-4">
      <input v-model="name" placeholder="Nazwa firmy" class="input-field mb-2.5" />
      <button class="btn-primary" :disabled="createCompany.isPending.value" @click="add">
        Zapisz firmę
      </button>
    </div>

    <div class="relative mb-4">
      <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
        <AppIcon name="search" :size="18" />
      </span>
      <input v-model="search" type="search" placeholder="Szukaj firmy…" class="input-field pl-10" />
    </div>

    <UiSkeletonList v-if="isLoading" />

    <div v-else-if="!companies.length" class="card flex flex-col items-center px-6 py-12 text-center">
      <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-surface text-stone">
        <AppIcon name="building" :size="24" />
      </div>
      <p class="font-semibold text-ink">Brak firm</p>
      <p class="mt-1 text-sm text-stone">Dodaj pierwszego klienta.</p>
    </div>

    <ul v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
      <li v-for="c in companies" :key="c.id">
        <NuxtLink :to="`/companies/${c.id}`" class="card-tile flex h-full items-center gap-3 p-4 active:bg-surface-soft">
          <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-surface text-stone">
            <AppIcon name="building" :size="20" />
          </span>
          <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-ink">{{ c.name }}</p>
            <p class="text-sm text-stone">{{ c.city || '—' }}</p>
          </div>
          <span class="badge badge-neutral shrink-0">{{ c.job_postings_count ?? 0 }} ogł.</span>
          <AppIcon name="chevron" :size="18" class="shrink-0 text-muted" />
        </NuxtLink>
      </li>
    </ul>
  </section>
</template>
