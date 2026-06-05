<script setup lang="ts">
import { nextContactPresets } from '~/utils/options'
import type { Task } from '~/types'

// Ekran „Dziś" — punkt startowy: zadania follow-up na dziś (z Call Log).
const { data: tasks, isLoading } = useTasksQuery('today')
const updateTask = useUpdateTask()

function complete(task: Task) {
  updateTask.mutate({ id: task.id, status: 'done' })
}
function snooze(task: Task) {
  const tomorrow = nextContactPresets()[1].value
  updateTask.mutate({ id: task.id, due_at: tomorrow })
}
function timeLabel(due: string | null) {
  if (!due) return ''
  return new Date(due).toLocaleTimeString('pl-PL', {
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <section>
    <header class="mb-5">
      <h1 class="text-[26px] font-bold tracking-tight text-ink">Dziś</h1>
      <p class="text-sm text-stone">Zaplanowane kontakty i przypomnienia</p>
    </header>

    <p v-if="isLoading" class="py-10 text-center text-muted">Ładowanie…</p>

    <div
      v-else-if="!tasks?.length"
      class="card flex flex-col items-center px-6 py-12 text-center"
    >
      <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-brand-soft text-brand-deep">
        <AppIcon name="check" :size="24" />
      </div>
      <p class="font-semibold text-ink">Wszystko ogarnięte</p>
      <p class="mt-1 text-sm text-stone">Brak zadań na dziś.</p>
    </div>

    <ul v-else class="space-y-3">
      <li v-for="task in tasks" :key="task.id" class="card p-4">
        <div class="flex items-start justify-between gap-3">
          <NuxtLink
            :to="task.candidate_id ? `/candidates/${task.candidate_id}` : '#'"
            class="min-w-0 flex-1"
          >
            <p class="truncate font-semibold text-ink">
              {{ task.candidate?.full_name || task.title }}
            </p>
            <a
              v-if="task.candidate?.phone"
              :href="`tel:${task.candidate.phone}`"
              class="mt-0.5 inline-flex items-center gap-1.5 text-sm font-medium text-brand-deep"
              @click.stop
            >
              <AppIcon name="phone" :size="15" /> {{ task.candidate.phone }}
            </a>
            <p v-if="task.description" class="mt-1 line-clamp-2 text-sm text-steel">
              {{ task.description }}
            </p>
          </NuxtLink>
          <span
            v-if="task.due_at"
            class="badge badge-neutral shrink-0"
          >
            {{ timeLabel(task.due_at) }}
          </span>
        </div>

        <div class="mt-3 flex gap-2">
          <button
            class="inline-flex h-10 flex-1 items-center justify-center gap-1.5 rounded-full bg-ink text-sm font-medium text-white transition active:scale-[0.98]"
            @click="complete(task)"
          >
            <AppIcon name="check" :size="17" /> Gotowe
          </button>
          <button
            class="inline-flex h-10 flex-1 items-center justify-center gap-1.5 rounded-full border border-hairline text-sm font-medium text-steel transition active:bg-surface"
            @click="snooze(task)"
          >
            <AppIcon name="clock" :size="17" /> Jutro
          </button>
        </div>
      </li>
    </ul>
  </section>
</template>
