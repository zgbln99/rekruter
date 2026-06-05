<script setup lang="ts">
// Floating Action Button „Nowy Kandydat" — kluczowy element KPI 60s.
</script>

<template>
  <NuxtLink
    to="/candidates/new"
    class="fixed bottom-[5.25rem] right-4 z-40 flex h-14 items-center gap-2
      rounded-full bg-ink px-5 font-semibold text-white shadow-fab transition
      active:scale-95 mb-safe-bottom"
    aria-label="Nowy kandydat"
  >
    <AppIcon name="plus" :size="22" />
    <span class="text-[15px]">Nowy kandydat</span>
  </NuxtLink>
</template>
