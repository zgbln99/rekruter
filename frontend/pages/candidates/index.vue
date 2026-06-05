<script setup lang="ts">
import { CANDIDATE_STATUS_OPTIONS } from '~/utils/options'

// Lista kandydatów z wyszukiwaniem i filtrem statusu.
const search = ref('')
const status = ref('')
const { data, isLoading } = useCandidatesQuery(search, status)
const candidates = computed(() => data.value?.data ?? [])
const total = computed(() => data.value?.meta?.total ?? candidates.value.length)
</script>

<template>
  <section>
    <header class="mb-4 flex items-center justify-between">
      <h1 class="text-[26px] font-bold tracking-tight text-ink">Kandydaci</h1>
      <span class="text-sm text-stone">{{ total }}</span>
    </header>

    <div class="relative mb-3">
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

    <div class="mb-4 flex flex-wrap gap-2">
      <UiChip :active="status === ''" @click="status = ''">Wszyscy</UiChip>
      <UiChip
        v-for="opt in CANDIDATE_STATUS_OPTIONS"
        :key="opt.value"
        :active="status === opt.value"
        @click="status = status === opt.value ? '' : opt.value"
      >
        {{ opt.label }}
      </UiChip>
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
      <p class="mt-1 text-sm text-stone">Zmień filtr lub dodaj nowego kandydata.</p>
    </div>

    <ul v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
      <li v-for="c in candidates" :key="c.id">
        <NuxtLink
          :to="`/candidates/${c.id}`"
          class="card-tile flex h-full items-center gap-3 p-4 active:bg-surface-soft"
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
          <span class="badge badge-neutral shrink-0">{{ c.status_label }}</span>
        </NuxtLink>
      </li>
    </ul>
  </section>
</template>
