<script setup lang="ts">
// Lista ogłoszeń (job offers) — centrum pracy rekruterki.
const { data, isLoading } = useJobOffersQuery()
const offers = computed(() => data.value?.data ?? [])
</script>

<template>
  <section>
    <header class="mb-4 flex items-center justify-between">
      <h1 class="text-[26px] font-bold tracking-tight text-ink">Ogłoszenia</h1>
      <NuxtLink to="/job-offers/new" class="btn-sm">
        <AppIcon name="plus" :size="16" /> Nowe
      </NuxtLink>
    </header>

    <p v-if="isLoading" class="py-10 text-center text-muted">Ładowanie…</p>

    <div v-else-if="!offers.length" class="card flex flex-col items-center px-6 py-12 text-center">
      <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-surface text-stone">
        <AppIcon name="document" :size="24" />
      </div>
      <p class="font-semibold text-ink">Brak ogłoszeń</p>
      <p class="mt-1 text-sm text-stone">Dodaj pierwsze ogłoszenie.</p>
    </div>

    <ul v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
      <li v-for="o in offers" :key="o.id">
        <NuxtLink :to="`/job-offers/${o.id}`" class="card-tile flex h-full items-center gap-3 p-4 active:bg-surface-soft">
          <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-ink">{{ o.title }}</p>
            <p class="truncate text-sm text-stone">
              {{ o.company?.name }}<span v-if="o.country"> · {{ o.country }}</span> · {{ o.status_label }}
            </p>
            <div class="mt-1.5 flex flex-wrap gap-1">
              <span v-for="cat in o.required_categories" :key="cat" class="badge badge-neutral">{{ cat }}</span>
            </div>
          </div>
          <span class="badge badge-neutral shrink-0">{{ o.applications_count ?? 0 }} kand.</span>
          <AppIcon name="chevron" :size="18" class="shrink-0 text-muted" />
        </NuxtLink>
      </li>
    </ul>
  </section>
</template>
