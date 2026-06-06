<script setup lang="ts">
import type { JobPosting } from '~/types'

// Lista ogłoszeń — tabela (desktop) + karty (mobile), filtr statusu.
const router = useRouter()
const { data, isLoading } = useJobOffersQuery()
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
    <div class="mb-4 flex flex-wrap gap-2">
      <UiChip :active="status === ''" @click="status = ''">Wszystkie</UiChip>
      <UiChip
        v-for="opt in STATUS_OPTIONS"
        :key="opt.value"
        :active="status === opt.value"
        @click="status = status === opt.value ? '' : opt.value"
      >
        {{ opt.label }}
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
              v-for="o in offers"
              :key="o.id"
              class="cursor-pointer border-b border-hairline-soft transition last:border-0 hover:bg-surface-soft"
              @click="open(o)"
            >
              <td class="px-4 py-3 font-medium text-ink">{{ o.title }}</td>
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
                <span class="badge" :class="statusTone[o.status] || 'badge-neutral'">{{ o.status_label }}</span>
              </td>
              <td class="px-4 py-3 text-right font-semibold text-ink">{{ o.applications_count ?? 0 }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Karty (mobile) -->
      <ul class="grid gap-3 sm:grid-cols-2 lg:hidden">
        <li v-for="o in offers" :key="o.id">
          <NuxtLink :to="`/job-offers/${o.id}`" class="card-tile flex h-full items-start gap-3 p-4">
            <div class="min-w-0 flex-1">
              <p class="truncate font-semibold text-ink">{{ o.title }}</p>
              <p class="truncate text-sm text-stone">{{ o.company?.name }}<span v-if="o.country"> · {{ o.country }}</span></p>
              <div class="mt-1.5 flex flex-wrap gap-1">
                <span v-for="cat in o.required_categories" :key="cat" class="badge badge-neutral">{{ cat }}</span>
                <span class="badge" :class="statusTone[o.status] || 'badge-neutral'">{{ o.status_label }}</span>
              </div>
            </div>
            <span class="badge badge-neutral shrink-0">{{ o.applications_count ?? 0 }} kand.</span>
          </NuxtLink>
        </li>
      </ul>
    </template>
  </section>
</template>
