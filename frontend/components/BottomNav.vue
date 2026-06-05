<script setup lang="ts">
// Dolna nawigacja — główny sposób poruszania się po aplikacji (mobile-first).
const items = [
  { to: '/', label: 'Dziś', icon: '📅' },
  { to: '/candidates', label: 'Kandydaci', icon: '👤' },
  { to: '/pipeline', label: 'Pipeline', icon: '📊' },
  { to: '/companies', label: 'Firmy', icon: '🏢' },
]
</script>

<template>
  <nav
    class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white pb-safe-bottom"
  >
    <ul class="grid grid-cols-4">
      <li v-for="item in items" :key="item.to">
        <NuxtLink
          :to="item.to"
          class="flex h-16 flex-col items-center justify-center gap-1 text-xs text-gray-500"
          active-class="text-brand"
        >
          <span class="text-xl">{{ item.icon }}</span>
          <span>{{ item.label }}</span>
        </NuxtLink>
      </li>
    </ul>
  </nav>
</template>
