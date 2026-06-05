<script setup lang="ts">
// Chip toggle — jeden tap zamiast dropdownu (UX mobilny, KPI 60s).
defineProps<{ active?: boolean }>()
</script>

<template>
  <button type="button" class="chip" :class="{ 'chip-active': active }">
    <slot />
  </button>
</template>
