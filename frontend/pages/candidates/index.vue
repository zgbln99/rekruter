<script setup lang="ts">
// Lista kandydatów z wyszukiwaniem (imię, nazwisko, miasto, telefon).
const search = ref('')
const { data, isLoading } = useCandidatesQuery(search)
const candidates = computed(() => data.value?.data ?? [])
</script>

<template>
  <section>
    <header class="mb-4">
      <h1 class="text-[26px] font-bold tracking-tight text-ink">Kandydaci</h1>
    </header>

    <div class="relative mb-4">
      <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
        <AppIcon name="search" :size="18" />
      </span>
      <input
        v-model="search"
        type="search"
        placeholder="Szukaj: imię, miasto, telefon…"
        class="input-field pl-10"
      />
    </div>

    <p v-if="isLoading" class="py-10 text-center text-muted">Ładowanie…</p>

    <div
      v-else-if="candidates.length === 0"
      class="card flex flex-col items-center px-6 py-12 text-center"
    >
      <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-surface text-stone">
        <AppIcon name="users" :size="24" />
      </div>
      <p class="font-semibold text-ink">Brak kandydatów</p>
      <p class="mt-1 text-sm text-stone">Dodaj pierwszego przyciskiem „Nowy kandydat".</p>
    </div>

    <ul v-else class="space-y-2.5">
      <li v-for="c in candidates" :key="c.id">
        <NuxtLink
          :to="`/candidates/${c.id}`"
          class="card flex items-center gap-3 p-4 transition active:bg-surface-soft"
        >
          <div
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-ink text-sm font-semibold text-white"
          >
            {{ c.first_name.charAt(0) }}{{ (c.last_name || '').charAt(0) }}
          </div>
          <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-ink">{{ c.full_name }}</p>
            <p class="text-sm text-stone">{{ c.phone }}</p>
            <div class="mt-1.5 flex flex-wrap gap-1">
              <span
                v-for="cat in c.license_categories"
                :key="cat"
                class="badge badge-neutral"
              >{{ cat }}</span>
              <span v-if="c.has_adr" class="badge bg-amber-50 text-amber-700">ADR</span>
            </div>
          </div>
          <AppIcon name="chevron" :size="18" class="shrink-0 text-muted" />
        </NuxtLink>
      </li>
    </ul>
  </section>
</template>
