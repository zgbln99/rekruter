<script setup lang="ts">
// Lista ogłoszeń — wybór tablicy kanban.
const { data, isLoading } = useJobPostingsQuery()
const postings = computed(() => data.value?.data ?? [])
</script>

<template>
  <section>
    <header class="mb-4">
      <h1 class="text-[26px] font-bold tracking-tight text-ink">Pipeline</h1>
      <p class="text-sm text-stone">Wybierz ogłoszenie, aby otworzyć tablicę</p>
    </header>

    <p v-if="isLoading" class="py-10 text-center text-muted">Ładowanie…</p>

    <div v-else-if="!postings.length" class="card flex flex-col items-center px-6 py-12 text-center">
      <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-surface text-stone">
        <AppIcon name="board" :size="24" />
      </div>
      <p class="font-semibold text-ink">Brak ogłoszeń</p>
      <p class="mt-1 text-sm text-stone">Dodaj ogłoszenie w sekcji „Firmy".</p>
    </div>

    <ul v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
      <li v-for="p in postings" :key="p.id">
        <NuxtLink :to="`/pipeline/${p.id}`" class="card-tile flex h-full items-center gap-3 p-4 active:bg-surface-soft">
          <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-surface text-stone">
            <AppIcon name="board" :size="20" />
          </span>
          <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-ink">{{ p.title }}</p>
            <p class="truncate text-sm text-stone">{{ p.company?.name }} · {{ p.status_label }}</p>
          </div>
          <span class="badge badge-neutral shrink-0">{{ p.applications_count ?? 0 }} kand.</span>
          <AppIcon name="chevron" :size="18" class="shrink-0 text-muted" />
        </NuxtLink>
      </li>
    </ul>
  </section>
</template>
