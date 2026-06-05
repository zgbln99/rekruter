<script setup lang="ts">
// Globalna wyszukiwarka — otwierana skrótem Ctrl/Cmd+K lub przyciskiem w nagłówku.
const open = useState('global-search-open', () => false)
const q = ref('')
const inputEl = ref<HTMLInputElement | null>(null)
const router = useRouter()
const { data, isFetching } = useSearchQuery(q)

const hasResults = computed(
  () => (data.value?.candidates?.length || 0) + (data.value?.offers?.length || 0) > 0,
)

function close() {
  open.value = false
}
function go(path: string) {
  close()
  router.push(path)
}

function onKey(e: KeyboardEvent) {
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    open.value = !open.value
  } else if (e.key === 'Escape' && open.value) {
    close()
  }
}

onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

watch(open, (v) => {
  if (v) nextTick(() => inputEl.value?.focus())
  else q.value = ''
})
</script>

<template>
  <div v-if="open" class="fixed inset-0 z-[60] flex items-start justify-center bg-black/40 p-4 pt-[12vh]" @click.self="close">
    <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-canvas shadow-xl">
      <div class="flex items-center gap-2 border-b border-hairline px-4">
        <AppIcon name="search" :size="20" class="text-stone" />
        <input
          ref="inputEl"
          v-model="q"
          placeholder="Szukaj kandydata (nazwisko / telefon) lub ogłoszenia…"
          class="h-14 flex-1 bg-transparent text-[15px] text-ink outline-none placeholder:text-stone"
        />
        <kbd class="hidden rounded border border-hairline px-1.5 py-0.5 text-[11px] text-stone sm:block">ESC</kbd>
      </div>

      <div class="max-h-[60vh] overflow-y-auto p-2">
        <p v-if="q.trim().length < 2" class="px-3 py-6 text-center text-sm text-muted">
          Wpisz min. 2 znaki. Skrót: <kbd class="rounded border border-hairline px-1">Ctrl</kbd>+<kbd class="rounded border border-hairline px-1">K</kbd>
        </p>
        <p v-else-if="isFetching && !hasResults" class="px-3 py-6 text-center text-sm text-muted">Szukam…</p>
        <p v-else-if="!hasResults" class="px-3 py-6 text-center text-sm text-muted">Brak wyników.</p>

        <template v-else>
          <div v-if="data?.candidates?.length" class="mb-1">
            <p class="px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-stone">Kandydaci</p>
            <button
              v-for="c in data.candidates"
              :key="c.id"
              class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-surface"
              @click="go(`/candidates/${c.id}`)"
            >
              <span class="flex items-center gap-2.5 min-w-0">
                <AppIcon name="users" :size="16" class="shrink-0 text-stone" />
                <span class="min-w-0">
                  <span class="block truncate text-sm font-medium text-ink">{{ c.full_name }}</span>
                  <span class="block truncate text-xs text-stone">{{ c.phone }}</span>
                </span>
              </span>
              <span v-if="c.status_label" class="badge badge-neutral shrink-0">{{ c.status_label }}</span>
            </button>
          </div>

          <div v-if="data?.offers?.length">
            <p class="px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-stone">Ogłoszenia</p>
            <button
              v-for="o in data.offers"
              :key="o.id"
              class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2.5 text-left transition hover:bg-surface"
              @click="go(`/job-offers/${o.id}`)"
            >
              <AppIcon name="document" :size="16" class="shrink-0 text-stone" />
              <span class="min-w-0">
                <span class="block truncate text-sm font-medium text-ink">{{ o.title }}</span>
                <span v-if="o.company" class="block truncate text-xs text-stone">{{ o.company }}</span>
              </span>
            </button>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
