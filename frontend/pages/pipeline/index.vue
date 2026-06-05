<script setup lang="ts">
// Lista ogłoszeń — wybór tablicy kanban.
const { data, isLoading } = useJobPostingsQuery()
const postings = computed(() => data.value?.data ?? [])
</script>

<template>
  <section>
    <h1 class="mb-4 text-2xl font-bold">Pipeline</h1>

    <p v-if="isLoading" class="py-8 text-center text-gray-400">Ładowanie…</p>
    <p v-else-if="!postings.length" class="py-8 text-center text-gray-400">
      Brak ogłoszeń. Dodaj ogłoszenie w sekcji „Firmy".
    </p>

    <ul v-else class="space-y-2">
      <li v-for="p in postings" :key="p.id">
        <NuxtLink :to="`/pipeline/${p.id}`" class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4">
          <div>
            <p class="font-semibold">{{ p.title }}</p>
            <p class="text-sm text-gray-500">{{ p.company?.name }} · {{ p.status_label }}</p>
          </div>
          <span class="text-xs text-gray-400">{{ p.applications_count ?? 0 }} kand.</span>
        </NuxtLink>
      </li>
    </ul>
  </section>
</template>
