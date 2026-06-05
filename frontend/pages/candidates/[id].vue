<script setup lang="ts">
// Szczegóły kandydata: dane, historia kontaktów, zadania.
const route = useRoute()
const id = computed(() => route.params.id as string)
const { data: candidate, isLoading } = useCandidateQuery(id)
</script>

<template>
  <section v-if="isLoading" class="py-8 text-center text-gray-400">
    Ładowanie…
  </section>

  <section v-else-if="candidate" class="space-y-5 pb-8">
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-2xl font-bold">{{ candidate.full_name }}</h1>
        <a :href="`tel:${candidate.phone}`" class="text-brand">{{ candidate.phone }}</a>
      </div>
      <span class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600">
        {{ candidate.status_label }}
      </span>
    </div>

    <!-- Uprawnienia -->
    <div class="rounded-xl border border-gray-200 bg-white p-4">
      <p class="mb-2 text-sm font-medium text-gray-700">Uprawnienia</p>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="cat in candidate.license_categories"
          :key="cat"
          class="rounded-full bg-gray-100 px-3 py-1 text-sm"
          >{{ cat }}</span
        >
        <span
          v-if="candidate.has_adr"
          class="rounded-full bg-amber-100 px-3 py-1 text-sm text-amber-700"
          >ADR</span
        >
        <span
          v-if="candidate.has_code_95"
          class="rounded-full bg-emerald-100 px-3 py-1 text-sm text-emerald-700"
          >Kod 95</span
        >
        <span
          v-if="!candidate.license_categories.length && !candidate.has_adr"
          class="text-sm text-gray-400"
          >Brak danych</span
        >
      </div>
    </div>

    <!-- Notatka wewnętrzna -->
    <div
      v-if="candidate.internal_notes"
      class="rounded-xl border border-gray-200 bg-white p-4"
    >
      <p class="mb-1 text-sm font-medium text-gray-700">Notatka wewnętrzna</p>
      <p class="text-sm text-gray-600">{{ candidate.internal_notes }}</p>
    </div>

    <!-- Historia kontaktów -->
    <div>
      <h2 class="mb-2 text-lg font-semibold">Historia kontaktów</h2>
      <ul v-if="candidate.contact_logs?.length" class="space-y-2">
        <li
          v-for="log in candidate.contact_logs"
          :key="log.id"
          class="rounded-xl border border-gray-200 bg-white p-3 text-sm"
        >
          <div class="flex justify-between">
            <span class="font-medium">{{ log.outcome_label }}</span>
            <span class="text-gray-400">{{ log.channel_label }}</span>
          </div>
          <p v-if="log.note" class="mt-1 text-gray-600">{{ log.note }}</p>
          <p v-if="log.next_contact_at" class="mt-1 text-xs text-brand">
            ⏰ Kolejny kontakt zaplanowany
          </p>
        </li>
      </ul>
      <p v-else class="text-sm text-gray-400">Brak zapisanych kontaktów.</p>
    </div>

    <!-- Zadania -->
    <div v-if="candidate.tasks?.length">
      <h2 class="mb-2 text-lg font-semibold">Zadania</h2>
      <ul class="space-y-2">
        <li
          v-for="task in candidate.tasks"
          :key="task.id"
          class="rounded-xl border border-gray-200 bg-white p-3 text-sm"
        >
          <span class="font-medium">{{ task.title }}</span>
          <span class="ml-2 text-xs text-gray-400">{{ task.status_label }}</span>
        </li>
      </ul>
    </div>
  </section>
</template>
