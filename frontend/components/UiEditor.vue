<script setup lang="ts">
// Prosty edytor tekstu „jak w Word": pogrubienie, kursywa, podkreślenie, listy.
// Trzyma treść jako HTML (v-model).
const props = defineProps<{ modelValue?: string | null; placeholder?: string }>()
const emit = defineEmits<{ 'update:modelValue': [string] }>()

const el = ref<HTMLElement | null>(null)

function setContent(v?: string | null) {
  const html = looksLikeHtml(v) ? (v as string) : textToHtml(v)
  if (el.value && el.value.innerHTML !== html) el.value.innerHTML = html
}

onMounted(() => {
  setContent(props.modelValue)
  // jeśli wejściowy tekst był zwykłym tekstem, znormalizuj model do HTML
  if (!looksLikeHtml(props.modelValue) && props.modelValue) emit('update:modelValue', el.value?.innerHTML || '')
})

watch(
  () => props.modelValue,
  (v) => {
    // aktualizuj tylko gdy zmiana przyszła z zewnątrz (np. „Generuj opis AI")
    if (el.value && document.activeElement !== el.value) setContent(v)
  },
)

function onInput() {
  emit('update:modelValue', el.value?.innerHTML || '')
}
function cmd(command: string, value?: string) {
  el.value?.focus()
  document.execCommand(command, false, value)
  onInput()
}

const isEmpty = computed(() => !htmlToText(props.modelValue).trim())
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-hairline bg-canvas focus-within:border-brand">
    <div class="flex flex-wrap items-center gap-0.5 border-b border-hairline bg-surface-soft p-1.5">
      <button type="button" class="ed-btn font-bold" title="Pogrubienie" @mousedown.prevent="cmd('bold')">B</button>
      <button type="button" class="ed-btn italic" title="Kursywa" @mousedown.prevent="cmd('italic')">I</button>
      <button type="button" class="ed-btn underline" title="Podkreślenie" @mousedown.prevent="cmd('underline')">U</button>
      <span class="mx-1 h-5 w-px bg-hairline" />
      <button type="button" class="ed-btn" title="Lista punktowana" @mousedown.prevent="cmd('insertUnorderedList')">• Lista</button>
      <button type="button" class="ed-btn" title="Lista numerowana" @mousedown.prevent="cmd('insertOrderedList')">1. Lista</button>
      <span class="mx-1 h-5 w-px bg-hairline" />
      <button type="button" class="ed-btn text-stone" title="Wyczyść formatowanie" @mousedown.prevent="cmd('removeFormat')">Wyczyść</button>
    </div>
    <div class="relative">
      <div
        ref="el"
        contenteditable="true"
        class="ed-content min-h-[130px] px-3.5 py-2.5 text-[15px] leading-relaxed text-ink outline-none"
        @input="onInput"
      />
      <p v-if="isEmpty && placeholder" class="pointer-events-none absolute left-3.5 top-2.5 text-[15px] text-muted">
        {{ placeholder }}
      </p>
    </div>
  </div>
</template>

<style scoped>
.ed-btn {
  border-radius: 6px;
  padding: 3px 9px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  transition: background-color .15s;
}
.ed-btn:hover { background: #fff; }
</style>
