<script setup lang="ts">
import { nextContactPresets } from '~/utils/options'
import type { Task } from '~/types'

// Pulpit — metryki, zadania na dziś, pipeline, ostatnia aktywność.
const auth = useAuthStore()
const { data: stats } = useDashboardQuery()
// „open" = wszystkie moje otwarte zadania (dziś, zaległe i nadchodzące),
// żeby przypisane follow-upy z przyszłą datą też były widoczne.
const { data: tasks, isLoading: tasksLoading } = useTasksQuery('open')
const updateTask = useUpdateTask()

function complete(task: Task) {
  updateTask.mutate({ id: task.id, status: 'done' })
}
function snooze(task: Task) {
  updateTask.mutate({ id: task.id, due_at: nextContactPresets()[1].value })
}
function timeLabel(due: string | null) {
  if (!due) return ''
  return new Date(due).toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' })
}

const today = new Date().toLocaleDateString('pl-PL', {
  weekday: 'long', day: 'numeric', month: 'long',
})

const pipelineActive = computed(() =>
  (stats.value?.pipeline ?? []).filter((s) => s.count > 0),
)
const pipelineMax = computed(() =>
  Math.max(1, ...pipelineActive.value.map((s) => s.count)),
)

const toneClass: Record<string, string> = {
  brand: 'bg-brand-soft text-brand-deep',
  blue: 'bg-blue-50 text-blue-600',
  violet: 'bg-violet-50 text-violet-600',
  amber: 'bg-amber-50 text-amber-600',
  indigo: 'bg-indigo-50 text-indigo-600',
  emerald: 'bg-emerald-50 text-emerald-600',
}

const metrics = computed(() => {
  const s = stats.value
  if (!s) return []
  return [
    { label: 'Kandydaci', value: s.candidates.total, sub: `+${s.candidates.new_this_week} w tym tyg.`, icon: 'users', to: '/candidates', tone: 'brand' },
    { label: 'Aktywne ogłoszenia', value: s.offers.active, sub: `${s.offers.total} łącznie`, icon: 'document', to: '/job-offers', tone: 'blue' },
    { label: 'Firmy', value: s.companies, sub: 'klienci', icon: 'building', to: '/companies', tone: 'violet' },
    { label: 'Zadania dziś', value: s.tasks.today, sub: s.tasks.overdue ? `${s.tasks.overdue} zaległych` : 'na bieżąco', icon: 'clock', to: '', tone: 'amber' },
    { label: 'Wysłane profile', value: s.profiles.sent_total, sub: `${s.profiles.sent_this_week} w tym tyg.`, icon: 'mail', to: '', tone: 'indigo' },
    { label: 'Oczekujące decyzje', value: s.profiles.pending_decisions, sub: 'od firm', icon: 'check', to: '', tone: 'emerald' },
  ]
})
</script>

<template>
  <section class="space-y-6">
    <!-- Powitalny hero (styl ekranu logowania) -->
    <header class="relative overflow-hidden rounded-2xl bg-ink px-6 py-7 text-white sm:px-8">
      <!-- Cienki akcent marki u góry (jak na karcie logowania) -->
      <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand to-brand-deep" />
      <!-- Subtelna siatka (jak tło logowania) -->
      <div
        class="pointer-events-none absolute inset-0 opacity-[0.07]"
        style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 22px 22px; mask-image: radial-gradient(ellipse 80% 120% at 100% 0%, #000 30%, transparent 70%);"
      />

      <div class="relative flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-[13px] capitalize text-white/55">{{ today }}</p>
          <h1 class="mt-1 text-[28px] font-bold tracking-tight">
            Cześć, {{ auth.user?.name?.split(' ')[0] }}
          </h1>
        </div>
        <div class="flex gap-2">
          <NuxtLink to="/candidates/new" class="inline-flex h-10 items-center gap-1.5 rounded-xl bg-white px-4 text-sm font-semibold text-ink transition hover:bg-white/90 active:scale-[0.98]">
            <AppIcon name="plus" :size="16" /> Kandydat
          </NuxtLink>
          <NuxtLink to="/job-offers/new" class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-white/20 px-4 text-sm font-medium text-white transition hover:bg-white/10 active:scale-[0.98]">
            <AppIcon name="plus" :size="16" /> Ogłoszenie
          </NuxtLink>
        </div>
      </div>
    </header>

    <!-- Metryki -->
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <component
        :is="m.to ? 'NuxtLink' : 'div'"
        v-for="m in metrics"
        :key="m.label"
        :to="m.to || undefined"
        class="card flex items-center gap-4 p-4"
        :class="m.to ? 'card-tile' : ''"
      >
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" :class="toneClass[m.tone]">
          <AppIcon :name="m.icon" :size="22" />
        </span>
        <div class="min-w-0">
          <p class="text-2xl font-bold leading-none text-ink">{{ m.value }}</p>
          <p class="mt-1 truncate text-sm font-medium text-ink">{{ m.label }}</p>
          <p class="truncate text-xs text-stone">{{ m.sub }}</p>
        </div>
      </component>
    </div>

    <!-- Przypomnienia: przyjazdy dziś + terminy rozliczeń -->
    <div
      v-if="(stats?.reminders?.arrivals_today?.length || stats?.reminders?.installments_due?.length)"
      class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4"
    >
      <p class="mb-2.5 inline-flex items-center gap-1.5 text-[13px] font-semibold text-amber-800">
        <AppIcon name="calendar" :size="16" /> Przypomnienia
      </p>
      <div class="grid gap-2 sm:grid-cols-2">
        <NuxtLink
          v-for="a in stats?.reminders?.arrivals_today ?? []"
          :key="a.placement_id"
          :to="`/candidates/${a.candidate_id}`"
          class="flex items-center gap-3 rounded-xl border border-amber-200 bg-canvas px-3.5 py-2.5 transition hover:bg-surface"
        >
          <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
            <AppIcon name="truck" :size="18" />
          </span>
          <span class="min-w-0">
            <span class="block truncate text-sm font-semibold text-ink">Dziś przyjazd: {{ a.candidate_name }}</span>
            <span class="block truncate text-xs text-stone">{{ a.time }}<span v-if="a.offer_title"> · {{ a.offer_title }}</span> — zweryfikuj, czy dotarł</span>
          </span>
        </NuxtLink>

        <NuxtLink
          v-for="i in stats?.reminders?.installments_due ?? []"
          :key="i.installment_id"
          to="/calendar"
          class="flex items-center gap-3 rounded-xl border border-indigo-200 bg-canvas px-3.5 py-2.5 transition hover:bg-surface"
        >
          <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
            <AppIcon name="cash" :size="18" />
          </span>
          <span class="min-w-0">
            <span class="block truncate text-sm font-semibold text-ink">Rozliczenie {{ i.sequence }}/2: {{ i.candidate_name }}</span>
            <span class="block truncate text-xs text-stone">termin {{ i.due_date }}<span v-if="i.amount"> · {{ i.amount }} {{ i.currency }}</span> — wystaw fakturę</span>
          </span>
        </NuxtLink>
      </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
      <!-- Zadania na dziś -->
      <div class="space-y-3 lg:col-span-2">
        <h2 class="text-lg font-semibold text-ink">Twoje zadania</h2>

        <UiSkeletonList v-if="tasksLoading" variant="rows" :count="3" />
        <div v-else-if="!tasks?.length" class="card flex items-center gap-3 p-5">
          <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-soft text-brand-deep">
            <AppIcon name="check" :size="20" />
          </span>
          <div>
            <p class="font-semibold text-ink">Wszystko ogarnięte</p>
            <p class="text-sm text-stone">Brak otwartych zadań.</p>
          </div>
        </div>

        <ul v-else class="space-y-3">
          <li v-for="task in tasks" :key="task.id" class="card p-4">
            <div class="flex items-start justify-between gap-3">
              <NuxtLink :to="task.candidate_id ? `/candidates/${task.candidate_id}` : '#'" class="min-w-0 flex-1">
                <p class="truncate font-semibold text-ink">{{ task.candidate?.full_name || task.title }}</p>
                <a v-if="task.candidate?.phone" :href="`tel:${task.candidate.phone}`" class="mt-0.5 inline-flex items-center gap-1.5 text-sm font-medium text-brand-deep" @click.stop>
                  <AppIcon name="phone" :size="15" /> {{ task.candidate.phone }}
                </a>
                <p v-if="task.description" class="mt-1 line-clamp-2 text-sm text-steel">{{ task.description }}</p>
              </NuxtLink>
              <span v-if="task.due_at" class="badge badge-neutral shrink-0">{{ timeLabel(task.due_at) }}</span>
            </div>
            <div class="mt-3 flex gap-2">
              <button class="inline-flex h-10 flex-1 items-center justify-center gap-1.5 rounded-xl bg-ink text-sm font-medium text-white transition active:scale-[0.98]" @click="complete(task)">
                <AppIcon name="check" :size="17" /> Gotowe
              </button>
              <button class="inline-flex h-10 flex-1 items-center justify-center gap-1.5 rounded-xl border border-hairline text-sm font-medium text-steel transition hover:bg-surface" @click="snooze(task)">
                <AppIcon name="clock" :size="17" /> Jutro
              </button>
            </div>
          </li>
        </ul>
      </div>

      <!-- Pipeline + aktywność -->
      <div class="space-y-6">
        <div v-if="pipelineActive.length" class="card p-4">
          <h2 class="mb-3 text-[13px] font-medium text-steel">Pipeline wg statusu</h2>
          <ul class="space-y-2.5">
            <li v-for="s in pipelineActive" :key="s.value">
              <div class="mb-1 flex items-center justify-between text-sm">
                <span class="flex items-center gap-2 text-ink">
                  <span class="h-2.5 w-2.5 rounded-full" :style="{ background: s.color }" />
                  {{ s.label }}
                </span>
                <span class="font-semibold text-ink">{{ s.count }}</span>
              </div>
              <div class="h-1.5 overflow-hidden rounded-full bg-surface">
                <div class="h-full rounded-full transition-all duration-500" :style="{ width: (s.count / pipelineMax * 100) + '%', background: s.color }" />
              </div>
            </li>
          </ul>
        </div>

        <div v-if="stats?.recent_activity?.length" class="card p-4">
          <h2 class="mb-3 text-[13px] font-medium text-steel">Ostatnia aktywność</h2>
          <ul class="space-y-3">
            <li v-for="(ev, i) in stats.recent_activity" :key="i" class="flex gap-2.5">
              <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand" />
              <div class="min-w-0">
                <component :is="ev.candidate_id ? 'NuxtLink' : 'span'" :to="ev.candidate_id ? `/candidates/${ev.candidate_id}` : undefined" class="text-sm font-medium text-ink">
                  {{ ev.label }}
                </component>
                <p class="text-xs text-stone" :title="fullDateTime(ev.at)">
                  {{ timeAgo(ev.at) }}<span v-if="ev.by"> · {{ ev.by }}</span>
                </p>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </section>
</template>
