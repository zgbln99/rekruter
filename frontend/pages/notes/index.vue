<script setup lang="ts">
import type { Note } from '~/types'

// Osobisty notatnik rekrutera — prywatne notatki zapisywane na koncie.
const { data, isLoading } = useNotesQuery()
const createNote = useCreateNote()
const updateNote = useUpdateNote()
const deleteNote = useDeleteNote()

const notes = computed(() => data.value ?? [])

// Kolory akcentu notatki.
const COLORS = [
  { key: '', label: 'Domyślny', dot: 'bg-stone', tile: 'bg-canvas' },
  { key: 'amber', label: 'Żółty', dot: 'bg-amber-400', tile: 'bg-amber-50' },
  { key: 'blue', label: 'Niebieski', dot: 'bg-blue-400', tile: 'bg-blue-50' },
  { key: 'emerald', label: 'Zielony', dot: 'bg-emerald-400', tile: 'bg-emerald-50' },
  { key: 'violet', label: 'Fioletowy', dot: 'bg-violet-400', tile: 'bg-violet-50' },
  { key: 'rose', label: 'Różowy', dot: 'bg-rose-400', tile: 'bg-rose-50' },
]
const tileClass = (c: string | null) => COLORS.find((x) => x.key === (c || ''))?.tile || 'bg-canvas'

// Edytor (nowa / edycja).
const editing = ref<Note | null>(null)
const isNew = ref(false)
const form = reactive({ title: '', body: '', color: '', pinned: false })
const saving = ref(false)

function openNew() {
  isNew.value = true
  editing.value = null
  Object.assign(form, { title: '', body: '', color: '', pinned: false })
}
function openEdit(n: Note) {
  isNew.value = false
  editing.value = n
  Object.assign(form, { title: n.title || '', body: n.body || '', color: n.color || '', pinned: n.pinned })
}
function close() {
  editing.value = null
  isNew.value = false
}

async function save() {
  if (!form.title.trim() && !form.body.trim()) {
    close()
    return
  }
  saving.value = true
  try {
    if (isNew.value) {
      await createNote.mutateAsync({ ...form })
    } else if (editing.value) {
      await updateNote.mutateAsync({ id: editing.value.id, ...form })
    }
    close()
  } finally {
    saving.value = false
  }
}

async function togglePin(n: Note) {
  await updateNote.mutateAsync({ id: n.id, pinned: !n.pinned })
}
async function remove(n: Note) {
  if (confirm('Usunąć tę notatkę?')) await deleteNote.mutateAsync(n.id)
}

function fmt(d: string | null) {
  if (!d) return ''
  return new Date(d).toLocaleString('pl-PL', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}
const open = computed(() => isNew.value || editing.value !== null)
</script>

<template>
  <section>
    <UiPageHeader title="Notatki" :subtitle="`${notes.length} ${notes.length === 1 ? 'notatka' : 'notatek'}`">
      <template #actions>
        <button class="btn-sm" @click="openNew">
          <AppIcon name="plus" :size="16" /> Nowa
        </button>
      </template>
    </UiPageHeader>

    <UiSkeletonList v-if="isLoading" />

    <div v-else-if="!notes.length" class="card flex flex-col items-center px-6 py-14 text-center">
      <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-surface text-stone">
        <AppIcon name="note" :size="24" />
      </div>
      <p class="font-semibold text-ink">Brak notatek</p>
      <p class="mt-1 text-sm text-stone">Zapisuj ustalenia, kontakty i przypomnienia w jednym miejscu.</p>
      <button class="btn-primary mt-4 !w-auto" @click="openNew">
        <AppIcon name="plus" :size="18" /> Dodaj pierwszą notatkę
      </button>
    </div>

    <ul v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <li v-for="n in notes" :key="n.id">
        <div
          class="card-tile flex h-full cursor-pointer flex-col p-4"
          :class="tileClass(n.color)"
          @click="openEdit(n)"
        >
          <div class="flex items-start justify-between gap-2">
            <p class="min-w-0 flex-1 font-semibold leading-snug text-ink">
              {{ n.title || 'Bez tytułu' }}
            </p>
            <button
              class="-mr-1 -mt-1 shrink-0 rounded-lg p-1.5 transition hover:bg-black/5"
              :class="n.pinned ? 'text-brand' : 'text-muted'"
              :title="n.pinned ? 'Odepnij' : 'Przypnij'"
              @click.stop="togglePin(n)"
            >
              <svg width="17" height="17" viewBox="0 0 24 24" :fill="n.pinned ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4v6l-2 3h10l-2-3V4M12 16v5M8 4h8"/></svg>
            </button>
          </div>

          <p v-if="n.body" class="mt-1.5 line-clamp-6 whitespace-pre-wrap text-sm text-slate">{{ n.body }}</p>

          <div class="mt-3 flex items-center justify-between border-t border-black/5 pt-2.5">
            <span class="text-xs text-stone">{{ fmt(n.updated_at) }}</span>
            <button class="rounded-lg p-1.5 text-muted transition hover:bg-black/5 hover:text-red-600" title="Usuń" @click.stop="remove(n)">
              <AppIcon name="x" :size="15" />
            </button>
          </div>
        </div>
      </li>
    </ul>

    <!-- Edytor -->
    <Teleport to="body">
      <div v-if="open" class="fixed inset-0 z-50 flex items-end justify-center bg-ink/40 p-0 backdrop-blur-sm sm:items-center sm:p-6" @click.self="close">
        <div class="w-full max-w-lg overflow-hidden rounded-t-2xl bg-canvas shadow-elevated sm:rounded-2xl">
          <div class="flex items-center justify-between border-b border-hairline px-5 py-3.5">
            <h2 class="font-semibold text-ink">{{ isNew ? 'Nowa notatka' : 'Edytuj notatkę' }}</h2>
            <button class="rounded-lg p-1.5 text-stone hover:bg-surface" @click="close"><AppIcon name="x" :size="18" /></button>
          </div>

          <div class="space-y-3 p-5">
            <input v-model="form.title" type="text" placeholder="Tytuł" class="input-field font-semibold" />
            <textarea v-model="form.body" rows="8" placeholder="Treść notatki…" class="input-field !h-auto resize-y py-3 leading-relaxed" />

            <div class="flex items-center justify-between gap-3">
              <!-- Kolory -->
              <div class="flex items-center gap-1.5">
                <button
                  v-for="c in COLORS"
                  :key="c.key"
                  type="button"
                  class="flex h-6 w-6 items-center justify-center rounded-full ring-offset-2 transition"
                  :class="[c.dot, form.color === c.key ? 'ring-2 ring-ink' : '']"
                  :title="c.label"
                  @click="form.color = c.key"
                />
              </div>
              <!-- Przypnij -->
              <label class="flex cursor-pointer select-none items-center gap-2 text-sm text-slate">
                <input v-model="form.pinned" type="checkbox" class="peer sr-only" />
                <span class="flex h-[18px] w-[18px] items-center justify-center rounded-[5px] border border-hairline bg-canvas text-white transition peer-checked:border-brand peer-checked:bg-brand">
                  <AppIcon name="check" :size="12" />
                </span>
                Przypnij
              </label>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 border-t border-hairline px-5 py-3.5">
            <button class="rounded-xl border border-hairline px-4 py-2 text-sm font-medium text-ink transition hover:bg-surface" @click="close">Anuluj</button>
            <button class="btn-primary !w-auto" :disabled="saving" @click="save">
              <span v-if="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
              {{ saving ? 'Zapisywanie…' : 'Zapisz' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>
