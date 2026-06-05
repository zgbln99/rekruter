<script setup lang="ts">
// Lista kandydatów z wyszukiwaniem (imię, nazwisko, miasto, telefon).
const search = ref('')
const { data, isLoading } = useCandidatesQuery(search)
const candidates = computed(() => data.value?.data ?? [])
</script>

<template>
  <section>
    <h1 class="mb-4 text-2xl font-bold">Kandydaci</h1>

    <input
      v-model="search"
      type="search"
      placeholder="Szukaj: imię, miasto, telefon…"
      class="input-field mb-4"
    />

    <p v-if="isLoading" class="py-8 text-center text-gray-400">Ładowanie…</p>

    <p
      v-else-if="candidates.length === 0"
      class="py-8 text-center text-gray-400"
    >
      Brak kandydatów. Dodaj pierwszego przyciskiem „Nowy kandydat".
    </p>

    <ul v-else class="space-y-2">
      <li v-for="c in candidates" :key="c.id">
        <NuxtLink
          :to="`/candidates/${c.id}`"
          class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4"
        >
          <div>
            <p class="font-semibold">{{ c.full_name }}</p>
            <p class="text-sm text-gray-500">{{ c.phone }}</p>
            <div class="mt-1 flex flex-wrap gap-1">
              <span
                v-for="cat in c.license_categories"
                :key="cat"
                class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600"
              >
                {{ cat }}
              </span>
              <span
                v-if="c.has_adr"
                class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700"
                >ADR</span
              >
            </div>
          </div>
          <span class="text-xs text-gray-400">{{ c.status_label }}</span>
        </NuxtLink>
      </li>
    </ul>
  </section>
</template>
