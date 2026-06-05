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
  // Najprostsze przełożenie: jutro 10:00 (pierwszy preset „jutro").
  const tomorrow = nextContactPresets()[1].value
  updateTask.mutate({ id: task.id, due_at: tomorrow })
}
</script>

<template>
  <section>
    <h1 class="mb-4 text-2xl font-bold">Dziś</h1>

    <p v-if="isLoading" class="py-8 text-center text-gray-400">Ładowanie…</p>

    <div
      v-else-if="!tasks?.length"
      class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center"
    >
      <p class="text-gray-500">🎉 Brak zadań na dziś.</p>
      <p class="mt-1 text-xs text-gray-400">Wszystko ogarnięte.</p>
    </div>

    <ul v-else class="space-y-3">
      <li
        v-for="task in tasks"
        :key="task.id"
        class="rounded-xl border border-gray-200 bg-white p-4"
      >
        <div class="flex items-start justify-between">
          <NuxtLink
            :to="task.candidate_id ? `/candidates/${task.candidate_id}` : '#'"
            class="flex-1"
          >
            <p class="font-semibold">{{ task.candidate?.full_name || task.title }}</p>
            <a
              v-if="task.candidate?.phone"
              :href="`tel:${task.candidate.phone}`"
              class="text-sm text-brand"
              @click.stop
              >{{ task.candidate.phone }}</a
            >
            <p v-if="task.description" class="mt-1 text-sm text-gray-500">
              {{ task.description }}
            </p>
          </NuxtLink>
        </div>

        <div class="mt-3 flex gap-2">
          <button
            class="flex-1 rounded-xl bg-brand py-2 text-sm font-medium text-white active:bg-brand-dark"
            @click="complete(task)"
          >
            ✓ Gotowe
          </button>
          <button
            class="flex-1 rounded-xl border border-gray-300 py-2 text-sm font-medium text-gray-600"
            @click="snooze(task)"
          >
            ⏰ Jutro
          </button>
        </div>
      </li>
    </ul>
  </section>
</template>
