<script setup lang="ts">
// Spójny nagłówek strony: tytuł + opcjonalny podtytuł + slot akcji po prawej.
defineProps<{ title: string; subtitle?: string }>()
</script>

<template>
  <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
      <h1 class="page-title">{{ title }}</h1>
      <p v-if="subtitle" class="mt-0.5 text-sm text-stone">{{ subtitle }}</p>
    </div>
    <div class="flex items-center gap-2">
      <slot name="actions" />
    </div>
  </header>
</template>
