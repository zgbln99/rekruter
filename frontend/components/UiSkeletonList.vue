<script setup lang="ts">
// Skeleton listy/kart na czas ładowania (zamiast napisu „Ładowanie…").
// variant „grid" — siatka kafelków; „rows" — pionowa lista (kolumny, agendy).
withDefaults(defineProps<{ count?: number; variant?: 'grid' | 'rows' }>(), {
  count: 6,
  variant: 'grid',
})
</script>

<template>
  <div :class="variant === 'grid' ? 'grid gap-3 sm:grid-cols-2 xl:grid-cols-3' : 'space-y-3'">
    <div v-for="i in count" :key="i" class="card flex items-center gap-3 p-4">
      <div class="skeleton h-11 w-11 rounded-full" />
      <div class="flex-1 space-y-2">
        <div class="skeleton h-3.5 w-2/3" />
        <div class="skeleton h-3 w-1/2" />
      </div>
    </div>
  </div>
</template>
