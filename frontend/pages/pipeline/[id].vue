<script setup lang="ts">
import type { Application } from '~/types'

// Tablica kanban dla ogłoszenia. Na telefonie przenoszenie kandydata między
// etapami realizowane przez bottom-sheet (DESIGN.md, mitygacja R10).
const route = useRoute()
const id = computed(() => route.params.id as string)
const { data: board, isLoading } = usePipelineBoardQuery(id)
const moveApplication = useMoveApplication(id)

const selected = ref<Application | null>(null)

async function moveTo(stageId: string) {
  if (!selected.value) return
  await moveApplication.mutateAsync({ id: selected.value.id, stage_id: stageId })
  selected.value = null
}
</script>

<template>
  <section v-if="isLoading" class="py-8 text-center text-gray-400">Ładowanie…</section>

  <section v-else-if="board">
    <div class="mb-4">
      <h1 class="text-xl font-bold">{{ board.job_posting.title }}</h1>
      <p class="text-sm text-gray-500">{{ board.job_posting.company?.name }}</p>
    </div>

    <!-- Kolumny kanban (przewijanie poziome) -->
    <div class="-mx-4 flex gap-3 overflow-x-auto px-4 pb-4">
      <div
        v-for="stage in board.stages"
        :key="stage.id"
        class="w-64 shrink-0 rounded-xl bg-gray-100 p-2"
      >
        <div class="mb-2 flex items-center gap-2 px-1">
          <span class="h-3 w-3 rounded-full" :style="{ background: stage.color }" />
          <span class="text-sm font-semibold">{{ stage.name }}</span>
          <span class="text-xs text-gray-400">{{ stage.applications.length }}</span>
        </div>

        <div class="space-y-2">
          <button
            v-for="app in stage.applications"
            :key="app.id"
            class="block w-full rounded-lg border border-gray-200 bg-white p-3 text-left"
            @click="selected = app"
          >
            <p class="font-medium">{{ app.candidate?.full_name }}</p>
            <p class="text-sm text-gray-500">{{ app.candidate?.phone }}</p>
            <div class="mt-1 flex flex-wrap gap-1">
              <span
                v-for="cat in app.candidate?.license_categories || []"
                :key="cat"
                class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600"
              >{{ cat }}</span>
            </div>
          </button>
          <p v-if="!stage.applications.length" class="px-1 py-2 text-xs text-gray-400">
            Brak kandydatów
          </p>
        </div>
      </div>
    </div>

    <!-- Bottom-sheet: zmiana etapu -->
    <div v-if="selected" class="fixed inset-0 z-50 flex flex-col justify-end bg-black/40" @click.self="selected = null">
      <div class="rounded-t-2xl bg-white p-4 pb-safe-bottom">
        <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-gray-300" />
        <p class="mb-1 text-sm text-gray-500">Przenieś kandydata</p>
        <p class="mb-4 text-lg font-semibold">{{ selected.candidate?.full_name }}</p>
        <div class="space-y-2">
          <button
            v-for="stage in board.stages"
            :key="stage.id"
            class="flex w-full items-center gap-3 rounded-xl border border-gray-200 p-3 text-left"
            :class="{ 'opacity-50': stage.id === selected.stage_id }"
            :disabled="stage.id === selected.stage_id || moveApplication.isPending.value"
            @click="moveTo(stage.id)"
          >
            <span class="h-3 w-3 rounded-full" :style="{ background: stage.color }" />
            <span class="font-medium">{{ stage.name }}</span>
            <span v-if="stage.id === selected.stage_id" class="ml-auto text-xs text-gray-400">obecny</span>
          </button>
        </div>
        <button class="mt-4 w-full py-2 text-sm text-gray-400" @click="selected = null">Zamknij</button>
      </div>
    </div>
  </section>
</template>
