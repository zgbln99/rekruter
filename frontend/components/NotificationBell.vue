<script setup lang="ts">
// Dzwonek powiadomień — zaległe zadania, dzisiejsze przyjazdy, terminy rozliczeń
// (admin) i wygasające uprawnienia kierowców. Odświeżane co 60 s.
withDefaults(defineProps<{ dark?: boolean; up?: boolean }>(), { dark: false, up: false })

const { data } = useNotificationsQuery()
const router = useRouter()
const push = usePush()
const pushMsg = ref('')
const open = ref(false)
const root = ref<HTMLElement | null>(null)

async function togglePush() {
  pushMsg.value = ''
  if (push.enabled.value) {
    await push.disable()
  } else {
    const err = await push.enable()
    if (err) pushMsg.value = err
  }
}

const count = computed(() => data.value?.count ?? 0)
const items = computed(() => data.value?.items ?? [])

function go(to: string) {
  open.value = false
  router.push(to)
}
function whenLabel(iso: string | null) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('pl-PL', { day: 'numeric', month: 'short' })
}
function onDocClick(e: MouseEvent) {
  if (root.value && !root.value.contains(e.target as Node)) open.value = false
}
onMounted(() => document.addEventListener('click', onDocClick))
onBeforeUnmount(() => document.removeEventListener('click', onDocClick))
</script>

<template>
  <div ref="root" class="relative">
    <button
      class="relative flex h-9 w-9 items-center justify-center rounded-full transition"
      :class="dark ? 'text-white/60 hover:bg-white/10 hover:text-white' : 'text-steel hover:bg-surface'"
      aria-label="Powiadomienia"
      @click="open = !open"
    >
      <AppIcon name="bell" :size="20" />
      <span
        v-if="count"
        class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand px-1 text-[10px] font-bold text-white ring-2"
        :class="dark ? 'ring-ink' : 'ring-canvas'"
      >{{ count > 99 ? '99+' : count }}</span>
    </button>

    <div
      v-if="open"
      class="absolute right-0 z-50 max-h-[70vh] w-80 overflow-hidden rounded-2xl border border-hairline bg-canvas shadow-xl"
      :class="up ? 'bottom-full mb-2' : 'mt-2'"
    >
      <div class="flex items-center justify-between border-b border-hairline px-4 py-3">
        <p class="text-sm font-semibold text-ink">Powiadomienia</p>
        <span v-if="count" class="badge badge-neutral">{{ count }}</span>
      </div>
      <div class="max-h-[60vh] overflow-y-auto">
        <p v-if="!items.length" class="px-4 py-8 text-center text-sm text-muted">Brak nowych powiadomień.</p>
        <button
          v-for="(n, i) in items"
          :key="i"
          class="flex w-full items-start gap-3 px-4 py-2.5 text-left transition hover:bg-surface"
          @click="go(n.to)"
        >
          <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: n.color }" />
          <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-medium text-ink">{{ n.title }}</span>
            <span class="block truncate text-xs text-stone">{{ n.subtitle }}</span>
          </span>
          <span v-if="n.when" class="shrink-0 text-[11px] text-stone">{{ whenLabel(n.when) }}</span>
        </button>
      </div>

      <!-- Powiadomienia push (na telefon/komputer) -->
      <div v-if="push.supported.value" class="border-t border-hairline px-4 py-2.5">
        <button
          class="flex w-full items-center justify-between text-left"
          :disabled="push.busy.value"
          @click="togglePush"
        >
          <span class="flex items-center gap-2 text-sm text-ink">
            <AppIcon name="bell" :size="15" :class="push.enabled.value ? 'text-brand-deep' : 'text-stone'" />
            Powiadomienia push
          </span>
          <span class="text-xs font-semibold" :class="push.enabled.value ? 'text-brand-deep' : 'text-stone'">
            {{ push.busy.value ? '…' : push.enabled.value ? 'Włączone' : 'Włącz' }}
          </span>
        </button>
        <p v-if="pushMsg" class="mt-1 text-xs text-red-600">{{ pushMsg }}</p>
      </div>
    </div>
  </div>
</template>
