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
  <section v-if="isLoading" class="py-10 text-center text-muted">Ładowanie…</section>

  <section v-else-if="board">
    <header class="mb-4">
      <h1 class="text-xl font-bold tracking-tight text-ink">{{ board.job_posting.title }}</h1>
      <p class="text-sm text-stone">{{ board.job_posting.company?.name }}</p>
    </header>

    <!-- Kolumny kanban (przewijanie poziome) -->
    <div class="-mx-4 flex gap-3 overflow-x-auto px-4 pb-4">
      <div
        v-for="stage in board.stages"
        :key="stage.id"
        class="w-[17rem] shrink-0 rounded-xl bg-surface p-2.5"
      >
        <div class="mb-2.5 flex items-center gap-2 px-1">
          <span class="h-2.5 w-2.5 rounded-full" :style="{ background: stage.color }" />
          <span class="text-sm font-semibold text-ink">{{ stage.name }}</span>
          <span class="ml-auto rounded-full bg-canvas px-2 py-0.5 text-xs font-medium text-stone">
            {{ stage.applications.length }}
          </span>
        </div>

        <div class="space-y-2">
          <button
            v-for="app in stage.applications"
            :key="app.id"
            class="block w-full rounded-lg border border-hairline bg-canvas p-3 text-left shadow-subtle transition active:scale-[0.99]"
            @click="selected = app"
          >
            <p class="font-semibold text-ink">{{ app.candidate?.full_name }}</p>
            <p class="text-sm text-stone">{{ app.candidate?.phone }}</p>
            <div class="mt-1.5 flex flex-wrap gap-1">
              <span v-for="cat in app.candidate?.license_categories || []" :key="cat" class="badge badge-neutral">{{ cat }}</span>
            </div>
          </button>
          <p v-if="!stage.applications.length" class="px-1 py-3 text-xs text-muted">
            Brak kandydatów
          </p>
        </div>
      </div>
    </div>

    <!-- Bottom-sheet: zmiana etapu -->
    <div v-if="selected" class="fixed inset-0 z-50 flex flex-col justify-end bg-black/40" @click.self="selected = null">
      <div class="rounded-t-2xl bg-canvas p-5 pb-safe-bottom">
        <div class="mx-auto mb-4 h-1.5 w-10 rounded-full bg-hairline" />
        <p class="text-[13px] text-stone">Przenieś kandydata</p>
        <p class="mb-4 text-lg font-semibold text-ink">{{ selected.candidate?.full_name }}</p>
        <div class="space-y-2">
          <button
            v-for="stage in board.stages"
            :key="stage.id"
            class="flex w-full items-center gap-3 rounded-xl border border-hairline p-3.5 text-left transition active:bg-surface"
            :class="{ 'opacity-40': stage.id === selected.stage_id }"
            :disabled="stage.id === selected.stage_id || moveApplication.isPending.value"
            @click="moveTo(stage.id)"
          >
            <span class="h-2.5 w-2.5 rounded-full" :style="{ background: stage.color }" />
            <span class="font-medium text-ink">{{ stage.name }}</span>
            <span v-if="stage.id === selected.stage_id" class="ml-auto text-xs text-muted">obecny</span>
          </button>
        </div>
        <button class="mt-4 w-full py-2 text-sm font-medium text-stone" @click="selected = null">Zamknij</button>
      </div>
    </div>
  </section>
</template>
