<script setup lang="ts">
// Renderuje tekst „inteligentnie": linie zaczynające się od -, •, * lub – stają
// się punktorami (grupowane w listę), a pozostałe linie to zwykłe akapity.
// Dzięki temu nie punktujemy każdej linii, tylko faktyczne wypunktowania.
const props = defineProps<{ text?: string | null; size?: 'sm' | 'base' }>()

interface Block { type: 'ul' | 'p'; items: string[] }

const blocks = computed<Block[]>(() => {
  const out: Block[] = []
  for (const raw of (props.text || '').split(/\r?\n/)) {
    const line = raw.trim()
    if (!line) continue
    const m = line.match(/^[-•*–—·]\s+(.*)$/)
    if (m) {
      const item = m[1].trim()
      const last = out[out.length - 1]
      if (last && last.type === 'ul') last.items.push(item)
      else out.push({ type: 'ul', items: [item] })
    } else {
      out.push({ type: 'p', items: [line] })
    }
  }
  return out
})

const textClass = computed(() =>
  props.size === 'sm' ? 'text-sm leading-relaxed text-charcoal' : 'text-[15px] leading-relaxed text-charcoal',
)
</script>

<template>
  <div class="space-y-2">
    <template v-for="(b, i) in blocks" :key="i">
      <ul v-if="b.type === 'ul'" class="space-y-1.5">
        <li v-for="(it, j) in b.items" :key="j" class="flex gap-2.5" :class="textClass">
          <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand" />{{ it }}
        </li>
      </ul>
      <p v-else :class="textClass">{{ b.items[0] }}</p>
    </template>
  </div>
</template>
