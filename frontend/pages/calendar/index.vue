<script setup lang="ts">
import type { CalendarEvent } from '~/types'

// Wbudowany kalendarz: przyjazdy kierowców (weryfikacja „Dotarł / Nie dotarł")
// oraz terminy rozliczeń (raty) — te ostatnie widzi tylko administrator.
const auth = useAuthStore()

const WEEKDAYS = ['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Nd']
const MONTHS = [
  'styczeń', 'luty', 'marzec', 'kwiecień', 'maj', 'czerwiec',
  'lipiec', 'sierpień', 'wrzesień', 'październik', 'listopad', 'grudzień',
]

function toIso(d: Date) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

// Kursor = pierwszy dzień widocznego miesiąca.
const cursor = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1))
const todayIso = toIso(new Date())

const monthLabel = computed(() => `${MONTHS[cursor.value.getMonth()]} ${cursor.value.getFullYear()}`)

// Siatka 6 tygodni (42 dni) zaczynająca się od poniedziałku.
const gridDays = computed(() => {
  const first = new Date(cursor.value)
  const offset = (first.getDay() + 6) % 7 // poniedziałek = 0
  const start = new Date(first)
  start.setDate(first.getDate() - offset)
  return Array.from({ length: 42 }, (_, i) => {
    const d = new Date(start)
    d.setDate(start.getDate() + i)
    return d
  })
})

const rangeFrom = computed(() => toIso(gridDays.value[0]))
const rangeTo = computed(() => toIso(gridDays.value[41]))

const { data: events, isLoading } = useCalendarQuery(rangeFrom, rangeTo)
const updateArrival = useUpdateArrival()
const updateInstallment = useUpdateInstallment()

// Filtry typów.
const showArrivals = ref(true)
const showInstallments = ref(true)

const filtered = computed<CalendarEvent[]>(() =>
  (events.value || []).filter((e) =>
    (e.type === 'arrival' && showArrivals.value) ||
    (e.type === 'installment' && showInstallments.value),
  ),
)

// Mapa dzień → wydarzenia.
const byDay = computed(() => {
  const map: Record<string, CalendarEvent[]> = {}
  for (const e of filtered.value) {
    ;(map[e.date] ||= []).push(e)
  }
  return map
})

const selected = ref(todayIso)
const selectedEvents = computed(() => byDay.value[selected.value] || [])

function prevMonth() {
  cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() - 1, 1)
}
function nextMonth() {
  cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() + 1, 1)
}
function goToday() {
  cursor.value = new Date(new Date().getFullYear(), new Date().getMonth(), 1)
  selected.value = todayIso
}

function isCurrentMonth(d: Date) {
  return d.getMonth() === cursor.value.getMonth()
}
function dayLabel(iso: string) {
  return new Date(iso).toLocaleDateString('pl-PL', { weekday: 'long', day: 'numeric', month: 'long' })
}

async function markArrival(ev: CalendarEvent, status: 'confirmed' | 'no_show' | 'pending') {
  await updateArrival.mutateAsync({ placementId: ev.placement_id, status })
}
async function markInstallment(ev: CalendarEvent, status: 'invoiced' | 'paid' | 'pending') {
  if (!ev.installment_id) return
  await updateInstallment.mutateAsync({ installmentId: ev.installment_id, status })
}
</script>

<template>
  <section class="mx-auto max-w-7xl pb-10">
    <UiPageHeader title="Kalendarz" subtitle="Przyjazdy kierowców i terminy rozliczeń" />

    <!-- Pasek nawigacji -->
    <div class="mb-4 flex flex-wrap items-center gap-2">
      <div class="flex items-center gap-1">
        <button class="flex h-9 w-9 items-center justify-center rounded-full border border-hairline text-ink transition hover:bg-surface" @click="prevMonth">
          <AppIcon name="chevron" :size="18" class="rotate-180" />
        </button>
        <button class="flex h-9 w-9 items-center justify-center rounded-full border border-hairline text-ink transition hover:bg-surface" @click="nextMonth">
          <AppIcon name="chevron" :size="18" />
        </button>
      </div>
      <h2 class="text-lg font-bold capitalize text-ink">{{ monthLabel }}</h2>
      <button class="ml-1 rounded-full border border-hairline px-3 py-1.5 text-sm font-medium text-ink transition hover:bg-surface" @click="goToday">
        Dziś
      </button>

      <!-- Filtry -->
      <div class="ml-auto flex items-center gap-1.5">
        <button
          class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition"
          :class="showArrivals ? 'bg-brand text-white' : 'border border-hairline text-stone'"
          @click="showArrivals = !showArrivals"
        >
          <span class="h-2 w-2 rounded-full" :class="showArrivals ? 'bg-white' : 'bg-brand'" /> Przyjazdy
        </button>
        <button
          v-if="auth.isAdmin"
          class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition"
          :class="showInstallments ? 'bg-amber-500 text-white' : 'border border-hairline text-stone'"
          @click="showInstallments = !showInstallments"
        >
          <span class="h-2 w-2 rounded-full" :class="showInstallments ? 'bg-white' : 'bg-amber-500'" /> Rozliczenia
        </button>
      </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3 lg:items-start">
      <!-- Siatka miesiąca -->
      <div class="card overflow-hidden p-0 lg:col-span-2">
        <div class="grid grid-cols-7 border-b border-hairline bg-surface-soft">
          <div v-for="wd in WEEKDAYS" :key="wd" class="px-1 py-2 text-center text-[11px] font-semibold uppercase tracking-wide text-stone">
            {{ wd }}
          </div>
        </div>
        <div class="grid grid-cols-7">
          <button
            v-for="d in gridDays"
            :key="toIso(d)"
            class="relative min-h-[68px] border-b border-r border-hairline p-1.5 text-left transition hover:bg-surface-soft sm:min-h-[92px]"
            :class="[
              !isCurrentMonth(d) && 'bg-surface-soft/40',
              selected === toIso(d) && 'ring-2 ring-inset ring-brand',
            ]"
            @click="selected = toIso(d)"
          >
            <span
              class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold"
              :class="[
                toIso(d) === todayIso ? 'bg-brand text-white' : isCurrentMonth(d) ? 'text-ink' : 'text-stone/60',
              ]"
            >{{ d.getDate() }}</span>

            <div class="mt-1 space-y-0.5">
              <div
                v-for="(ev, i) in (byDay[toIso(d)] || []).slice(0, 3)"
                :key="i"
                class="truncate rounded px-1 py-0.5 text-[10px] font-medium leading-tight"
                :style="{ backgroundColor: ev.color + '1a', color: ev.color }"
              >
                <span v-if="ev.time" class="font-bold">{{ ev.time }} </span>{{ ev.title }}
              </div>
              <div v-if="(byDay[toIso(d)] || []).length > 3" class="px-1 text-[10px] font-medium text-stone">
                +{{ (byDay[toIso(d)] || []).length - 3 }} więcej
              </div>
            </div>
          </button>
        </div>
      </div>

      <!-- Agenda wybranego dnia -->
      <div class="card p-4 lg:sticky lg:top-20">
        <p class="mb-3 text-[13px] font-semibold capitalize text-ink">{{ dayLabel(selected) }}</p>

        <p v-if="isLoading" class="text-sm text-muted">Ładowanie…</p>
        <p v-else-if="!selectedEvents.length" class="text-sm text-muted">Brak wydarzeń tego dnia.</p>

        <ul v-else class="space-y-2.5">
          <li v-for="(ev, i) in selectedEvents" :key="i" class="rounded-xl border border-hairline p-3">
            <div class="flex items-start gap-2">
              <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: ev.color }" />
              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-ink">
                  <span v-if="ev.time" class="text-stone">{{ ev.time }} · </span>{{ ev.title }}
                </p>
                <p v-if="ev.subtitle" class="truncate text-xs text-stone">{{ ev.subtitle }}</p>
                <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold" :style="{ backgroundColor: ev.color + '1a', color: ev.color }">
                  {{ ev.status_label }}
                </span>
              </div>
            </div>

            <!-- Akcje: przyjazd -->
            <div v-if="ev.type === 'arrival'" class="mt-2.5 flex flex-wrap gap-2">
              <button class="inline-flex h-8 items-center gap-1 rounded-full bg-emerald-50 px-3 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100" @click="markArrival(ev, 'confirmed')">
                <AppIcon name="check" :size="14" /> Dotarł
              </button>
              <button class="inline-flex h-8 items-center gap-1 rounded-full bg-red-50 px-3 text-xs font-semibold text-red-600 transition hover:bg-red-100" @click="markArrival(ev, 'no_show')">
                <AppIcon name="x" :size="14" /> Nie dotarł
              </button>
              <NuxtLink v-if="ev.candidate_id" :to="`/candidates/${ev.candidate_id}`" class="ml-auto inline-flex h-8 items-center rounded-full border border-hairline px-3 text-xs font-medium text-ink transition hover:bg-surface">
                Karta kierowcy
              </NuxtLink>
            </div>

            <!-- Akcje: rozliczenie (admin) -->
            <div v-else-if="ev.type === 'installment' && auth.isAdmin" class="mt-2.5 flex flex-wrap gap-2">
              <button class="inline-flex h-8 items-center gap-1 rounded-full bg-indigo-50 px-3 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100" @click="markInstallment(ev, 'invoiced')">
                Wystawiona
              </button>
              <button class="inline-flex h-8 items-center gap-1 rounded-full bg-emerald-50 px-3 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100" @click="markInstallment(ev, 'paid')">
                Opłacona
              </button>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>
