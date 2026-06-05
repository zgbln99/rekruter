<script setup lang="ts">
// Floating Action Button „Nowy Kandydat" — kluczowy element KPI 60s.
// Docelowo otwiera ekran szybkiego dodawania (Faza 1).
</script>

<template>
  <NuxtLink
    to="/candidates/new"
    class="fixed bottom-20 right-4 z-40 flex h-14 items-center gap-2 rounded-full bg-brand
      px-5 text-white shadow-lg active:bg-brand-dark mb-safe-bottom"
    aria-label="Nowy kandydat"
  >
    <span class="text-2xl leading-none">+</span>
    <span class="text-sm font-semibold">Nowy kandydat</span>
  </NuxtLink>
</template>
