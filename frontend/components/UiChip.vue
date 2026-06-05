<script setup lang="ts">
// Chip toggle — jeden tap zamiast dropdownu (UX mobilny, KPI 60s).
defineProps<{ active?: boolean }>()
</script>

<template>
  <button
    type="button"
    class="rounded-full border px-4 py-2 text-sm font-medium transition active:scale-95"
    :class="
      active
        ? 'border-brand bg-brand text-white'
        : 'border-gray-300 bg-white text-gray-700'
    "
  >
    <slot />
  </button>
</template>
