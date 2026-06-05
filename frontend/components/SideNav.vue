<script setup lang="ts">
import { NAV_ITEMS } from '~/utils/nav'
// Boczna nawigacja — desktop (≥ lg). Mobile używa BottomNav.
const auth = useAuthStore()
const items = computed(() =>
  NAV_ITEMS.filter((i) => !i.adminOnly || auth.isAdmin),
)
</script>

<template>
  <aside class="hidden w-64 shrink-0 border-r border-hairline bg-canvas lg:block">
    <div class="sticky top-14 flex max-h-[calc(100vh-3.5rem)] flex-col px-3 py-5">
      <NuxtLink
        to="/candidates/new"
        class="mx-1 mb-5 inline-flex h-11 items-center justify-center gap-2 rounded-full bg-ink text-[15px] font-semibold text-white shadow-subtle transition hover:opacity-90 active:scale-[0.98]"
      >
        <AppIcon name="plus" :size="18" /> Nowy kandydat
      </NuxtLink>

      <nav class="flex flex-col gap-1">
        <NuxtLink
          v-for="item in items"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-steel transition hover:bg-surface hover:text-ink"
          :active-class="item.exact ? '' : 'bg-surface !text-ink'"
          :exact-active-class="item.exact ? 'bg-surface !text-ink' : ''"
        >
          <AppIcon :name="item.icon" :size="20" />
          {{ item.label }}
        </NuxtLink>
      </nav>
    </div>
  </aside>
</template>
