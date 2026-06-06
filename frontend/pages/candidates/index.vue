<script setup lang="ts">
import { CANDIDATE_STATUS_OPTIONS } from '~/utils/options'
import type { Candidate } from '~/types'

// Lista kandydatów: tabela (desktop) + karty (mobile), wyszukiwanie i filtr statusu.
const router = useRouter()
const search = ref('')
const status = ref('')
const { data, isLoading } = useCandidatesQuery(search, status)
const candidates = computed(() => data.value?.data ?? [])
const total = computed(() => data.value?.meta?.total ?? candidates.value.length)

const statusTone: Record<string, string> = {
  new: 'bg-surface text-steel',
  active: 'bg-blue-50 text-blue-700',
  placed: 'bg-emerald-50 text-emerald-700',
  unavailable: 'bg-amber-50 text-amber-700',
  blacklisted: 'bg-red-50 text-red-700',
  archived: 'bg-surface text-muted',
}

function initials(c: Candidate) {
  return (c.first_name?.charAt(0) || '') + (c.last_name?.charAt(0) || '')
}
function fmtDate(d: string | null) {
  return d ? new Date(d).toLocaleDateString('pl-PL') : '—'
}
function open(c: Candidate) {
  router.push(`/candidates/${c.id}`)
}
</script>

<template>
  <section>
    <UiPageHeader title="Kandydaci" :subtitle="`${total} w bazie`">
      <template #actions>
        <NuxtLink to="/candidates/new" class="btn-sm">
          <AppIcon name="plus" :size="16" /> Nowy
        </NuxtLink>
      </template>
    </UiPageHeader>

    <!-- Wyszukiwanie + filtry -->
    <div class="mb-4 space-y-3">
      <div class="relative max-w-md">
        <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
          <AppIcon name="search" :size="18" />
        </span>
        <input v-model="search" type="search" placeholder="Szukaj: imię, miasto, telefon…" class="input-field pl-10" />
      </div>
      <div class="flex flex-wrap gap-2">
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
    </div>

    <UiSkeletonList v-if="isLoading" />

    <div v-else-if="!candidates.length" class="card flex flex-col items-center px-6 py-14 text-center">
      <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-surface text-stone">
        <AppIcon name="users" :size="24" />
      </div>
      <p class="font-semibold text-ink">Brak kandydatów</p>
      <p class="mt-1 text-sm text-stone">Zmień filtr lub dodaj nowego kandydata.</p>
    </div>

    <template v-else>
      <!-- Tabela (desktop) -->
      <div class="card hidden overflow-hidden lg:block">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-hairline bg-surface-soft text-left text-xs uppercase tracking-wide text-stone">
              <th class="px-4 py-3 font-semibold">Kandydat</th>
              <th class="px-4 py-3 font-semibold">Telefon</th>
              <th class="px-4 py-3 font-semibold">Miasto</th>
              <th class="px-4 py-3 font-semibold">Uprawnienia</th>
              <th class="px-4 py-3 font-semibold">Status</th>
              <th class="px-4 py-3 font-semibold">Dodano</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="c in candidates"
              :key="c.id"
              class="cursor-pointer border-b border-hairline-soft transition last:border-0 hover:bg-surface-soft"
              @click="open(c)"
            >
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-ink text-xs font-semibold text-white">
                    {{ initials(c) }}
                  </span>
                  <span class="font-medium text-ink">{{ c.full_name }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-steel">{{ c.phone }}</td>
              <td class="px-4 py-3 text-steel">{{ c.city || '—' }}</td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1">
                  <span v-for="cat in c.license_categories" :key="cat" class="badge badge-neutral">{{ cat }}</span>
                  <span v-if="c.has_adr" class="badge bg-amber-50 text-amber-700">ADR</span>
                  <span v-if="!c.license_categories.length && !c.has_adr" class="text-muted">—</span>
                </div>
              </td>
              <td class="px-4 py-3">
                <span class="badge" :class="statusTone[c.status] || 'badge-neutral'">{{ c.status_label }}</span>
              </td>
              <td class="px-4 py-3 text-stone">{{ fmtDate(c.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Karty (mobile) -->
      <ul class="grid gap-3 sm:grid-cols-2 lg:hidden">
        <li v-for="c in candidates" :key="c.id">
          <NuxtLink :to="`/candidates/${c.id}`" class="card-tile flex h-full items-center gap-3 p-4">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-ink text-sm font-semibold text-white">
              {{ initials(c) }}
            </span>
            <div class="min-w-0 flex-1">
              <p class="truncate font-semibold text-ink">{{ c.full_name }}</p>
              <p class="text-sm text-stone">{{ c.phone }}</p>
              <div class="mt-1.5 flex flex-wrap gap-1">
                <span v-for="cat in c.license_categories" :key="cat" class="badge badge-neutral">{{ cat }}</span>
                <span v-if="c.has_adr" class="badge bg-amber-50 text-amber-700">ADR</span>
              </div>
            </div>
            <span class="badge shrink-0" :class="statusTone[c.status] || 'badge-neutral'">{{ c.status_label }}</span>
          </NuxtLink>
        </li>
      </ul>
    </template>
  </section>
</template>
