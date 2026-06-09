<script setup lang="ts">
import type { JobPosting } from '~/types'

// Lista ogłoszeń — tabela (desktop) + karty (mobile), filtr statusu.
const router = useRouter()
const showArchived = ref(false)
const { data, isLoading } = useJobOffersQuery('', showArchived)
const allOffers = computed(() => data.value?.data ?? [])

const status = ref('')
const STATUS_OPTIONS = [
  { value: 'open', label: 'Aktywne' },
  { value: 'paused', label: 'Wstrzymane' },
  { value: 'closed', label: 'Zamknięte' },
]
const offers = computed(() =>
  status.value ? allOffers.value.filter((o) => o.status === status.value) : allOffers.value,
)

// Paginacja (po stronie klienta).
const PER_PAGE = 20
const page = ref(1)
const totalPages = computed(() => Math.max(1, Math.ceil(offers.value.length / PER_PAGE)))
const pagedOffers = computed(() => offers.value.slice((page.value - 1) * PER_PAGE, page.value * PER_PAGE))
watch([status, () => offers.value.length], () => {
  if (page.value > totalPages.value) page.value = totalPages.value
})
watch(status, () => (page.value = 1))

const statusTone: Record<string, string> = {
  open: 'bg-emerald-50 text-emerald-700',
  paused: 'bg-amber-50 text-amber-700',
  closed: 'bg-surface text-muted',
}

function location(o: JobPosting) {
  return [o.country, o.region_base].filter(Boolean).join(' · ') || '—'
}
function salary(o: JobPosting) {
  return o.salary_amount ? `${o.salary_amount} ${o.currency || ''}`.trim() : '—'
}
function open(o: JobPosting) {
  router.push(`/job-offers/${o.id}`)
}
</script>

<template>
  <section>
    <UiPageHeader title="Ogłoszenia" :subtitle="`${allOffers.length} w bazie`">
      <template #actions>
        <NuxtLink to="/job-offers/new" class="btn-sm">
          <AppIcon name="plus" :size="16" /> Nowe
        </NuxtLink>
      </template>
    </UiPageHeader>

    <!-- Filtr statusu -->
    <div class="mb-4 flex flex-wrap items-center gap-2">
      <UiChip :active="status === ''" @click="status = ''">Wszystkie</UiChip>
      <UiChip
        v-for="opt in STATUS_OPTIONS"
        :key="opt.value"
        :active="status === opt.value"
        @click="status = status === opt.value ? '' : opt.value"
      >
        {{ opt.label }}
      </UiChip>
      <span class="mx-1 h-5 w-px bg-hairline" />
      <UiChip :active="showArchived" @click="showArchived = !showArchived; page = 1">
        <AppIcon name="document" :size="14" /> Archiwum
      </UiChip>
    </div>

    <p v-if="isLoading" class="py-10 text-center text-muted">Ładowanie…</p>

    <div v-else-if="!offers.length" class="card flex flex-col items-center px-6 py-12 text-center">
      <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-surface text-stone">
        <AppIcon name="document" :size="24" />
      </div>
      <p class="font-semibold text-ink">Brak ogłoszeń</p>
      <p class="mt-1 text-sm text-stone">Dodaj pierwsze ogłoszenie.</p>
    </div>

    <template v-else>
      <!-- Tabela (desktop) -->
      <div class="card hidden overflow-hidden lg:block">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-hairline bg-surface-soft text-left text-xs uppercase tracking-wide text-stone">
              <th class="px-4 py-3 font-semibold">Stanowisko</th>
              <th class="px-4 py-3 font-semibold">Firma</th>
              <th class="px-4 py-3 font-semibold">Lokalizacja</th>
              <th class="px-4 py-3 font-semibold">Kategorie</th>
              <th class="px-4 py-3 font-semibold">Wynagrodzenie</th>
              <th class="px-4 py-3 font-semibold">Status</th>
              <th class="px-4 py-3 text-right font-semibold">Kandydaci</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="o in pagedOffers"
              :key="o.id"
              class="cursor-pointer border-b border-hairline-soft transition last:border-0 hover:bg-surface-soft"
              @click="open(o)"
            >
              <td class="px-4 py-3 font-medium text-ink">
                <span class="inline-flex items-center gap-1.5">
                  <svg v-if="o.is_featured" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" class="shrink-0 text-amber-500" title="Promowana"><path d="M12 2l2.9 6 6.6.6-5 4.3 1.5 6.5L12 16.9 5.9 19.4 7.4 12.9 2.4 8.6 9 8z"/></svg>
                  {{ o.title }}
                </span>
                <span v-if="o.internal_ref" class="mt-0.5 block font-mono text-xs text-muted">{{ o.internal_ref }}</span>
              </td>
              <td class="px-4 py-3 text-steel">{{ o.company?.name || '—' }}</td>
              <td class="px-4 py-3 text-steel">{{ location(o) }}</td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1">
                  <span v-for="cat in o.required_categories" :key="cat" class="badge badge-neutral">{{ cat }}</span>
                  <span v-if="!o.required_categories?.length" class="text-muted">—</span>
                </div>
              </td>
              <td class="px-4 py-3 text-steel">{{ salary(o) }}</td>
              <td class="px-4 py-3">
                <div class="flex flex-col items-start gap-1">
                  <span class="badge" :class="statusTone[o.status] || 'badge-neutral'">{{ o.status_label }}</span>
                  <span class="badge inline-flex items-center gap-1" :class="o.is_public ? 'bg-blue-50 text-blue-700' : 'bg-surface text-muted'">
                    <span class="h-1.5 w-1.5 rounded-full" :class="o.is_public ? 'bg-blue-500' : 'bg-muted'" />
                    {{ o.is_public ? 'Publiczna' : 'Ukryta' }}
                  </span>
                </div>
              </td>
              <td class="px-4 py-3 text-right font-semibold text-ink">{{ o.applications_count ?? 0 }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Karty (mobile) -->
      <ul class="grid gap-3 sm:grid-cols-2 lg:hidden">
        <li v-for="o in pagedOffers" :key="o.id">
          <NuxtLink :to="`/job-offers/${o.id}`" class="card-tile flex h-full flex-col p-4">
            <!-- Tytuł + status + widoczność -->
            <div class="flex items-start justify-between gap-3">
              <p class="min-w-0 flex-1 font-semibold leading-snug text-ink">
                <svg v-if="o.is_featured" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" class="mr-1 inline shrink-0 text-amber-500"><path d="M12 2l2.9 6 6.6.6-5 4.3 1.5 6.5L12 16.9 5.9 19.4 7.4 12.9 2.4 8.6 9 8z"/></svg>
                {{ o.title }}
                <span v-if="o.internal_ref" class="mt-0.5 block font-mono text-xs font-normal text-muted">{{ o.internal_ref }}</span>
              </p>
              <div class="flex shrink-0 flex-col items-end gap-1">
                <span class="badge" :class="statusTone[o.status] || 'badge-neutral'">{{ o.status_label }}</span>
                <span class="badge inline-flex items-center gap-1" :class="o.is_public ? 'bg-blue-50 text-blue-700' : 'bg-surface text-muted'">
                  <span class="h-1.5 w-1.5 rounded-full" :class="o.is_public ? 'bg-blue-500' : 'bg-muted'" />
                  {{ o.is_public ? 'Publiczna' : 'Ukryta' }}
                </span>
              </div>
            </div>

            <!-- Firma -->
            <p v-if="o.company?.name" class="mt-1 flex items-center gap-1.5 truncate text-sm text-stone">
              <AppIcon name="building" :size="14" class="shrink-0 text-muted" />
              {{ o.company.name }}
            </p>

            <!-- Lokalizacja + wynagrodzenie -->
            <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-steel">
              <span class="flex items-center gap-1.5">
                <AppIcon name="truck" :size="15" class="shrink-0 text-muted" />
                {{ location(o) }}
              </span>
              <span v-if="o.salary_amount" class="flex items-center gap-1.5 font-medium text-ink">
                <AppIcon name="cash" :size="15" class="shrink-0 text-muted" />
                {{ salary(o) }}
              </span>
            </div>

            <!-- Kategorie -->
            <div v-if="o.required_categories?.length" class="mt-2.5 flex flex-wrap gap-1">
              <span v-for="cat in o.required_categories" :key="cat" class="badge badge-neutral">{{ cat }}</span>
            </div>

            <!-- Stopka: liczba kandydatów -->
            <div class="mt-3 flex items-center gap-1.5 border-t border-hairline-soft pt-3 text-sm text-stone">
              <AppIcon name="users" :size="15" class="shrink-0 text-muted" />
              <span class="font-semibold text-ink">{{ o.applications_count ?? 0 }}</span> kandydatów
            </div>
          </NuxtLink>
        </li>
      </ul>

      <!-- Paginacja -->
      <div v-if="totalPages > 1" class="mt-4 flex items-center justify-center gap-2">
        <button
          class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface disabled:opacity-40"
          :disabled="page <= 1"
          @click="page--"
        >
          <AppIcon name="chevron" :size="15" class="rotate-180" /> Poprzednia
        </button>
        <span class="px-2 text-sm text-stone">{{ page }} / {{ totalPages }}</span>
        <button
          class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-hairline px-3.5 text-sm font-medium text-ink transition hover:bg-surface disabled:opacity-40"
          :disabled="page >= totalPages"
          @click="page++"
        >
          Następna <AppIcon name="chevron" :size="15" />
        </button>
      </div>
    </template>
  </section>
</template>
