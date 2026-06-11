<script setup lang="ts">
// Skeleton strony szczegółów (nagłówek + karty) na czas ładowania.
</script>

<template>
  <section class="mx-auto max-w-7xl space-y-5 pb-8">
    <div class="flex items-center gap-3">
      <div class="skeleton h-12 w-12 rounded-xl" />
      <div class="flex-1 space-y-2">
        <div class="skeleton h-5 w-1/3 max-w-[260px]" />
        <div class="skeleton h-3.5 w-1/4 max-w-[180px]" />
      </div>
    </div>
    <div v-for="i in 3" :key="i" class="card space-y-2.5 p-5">
      <div class="skeleton h-3.5 w-28" />
      <div class="skeleton h-3 w-2/3" />
      <div class="skeleton h-3 w-1/2" />
    </div>
  </section>
</template>
