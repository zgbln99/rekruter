<script setup lang="ts">
// Dolna nawigacja — główny sposób poruszania się po aplikacji (mobile-first).
const items = [
  { to: '/', label: 'Dziś', icon: 'home' },
  { to: '/candidates', label: 'Kandydaci', icon: 'users' },
  { to: '/pipeline', label: 'Pipeline', icon: 'board' },
  { to: '/companies', label: 'Firmy', icon: 'building' },
]
</script>

<template>
  <nav
    class="fixed inset-x-0 bottom-0 z-30 border-t border-hairline bg-canvas/95
      backdrop-blur pb-safe-bottom"
  >
    <ul class="mx-auto grid max-w-2xl grid-cols-4">
      <li v-for="item in items" :key="item.to">
        <NuxtLink
          :to="item.to"
          class="group flex h-16 flex-col items-center justify-center gap-1 text-stone"
          :active-class="item.to === '/' ? '' : '!text-ink'"
          :exact-active-class="item.to === '/' ? '!text-ink' : ''"
        >
          <AppIcon :name="item.icon" :size="23" />
          <span class="text-[11px] font-medium">{{ item.label }}</span>
        </NuxtLink>
      </li>
    </ul>
  </nav>
</template>
