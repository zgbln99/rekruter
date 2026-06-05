<script setup lang="ts">
import { NAV_ITEMS } from '~/utils/nav'
// Boczna nawigacja — desktop (≥ lg). Mobile używa BottomNav.
</script>

<template>
  <aside
    class="hidden w-60 shrink-0 flex-col border-r border-hairline bg-canvas lg:flex"
  >
    <NuxtLink
      to="/candidates/new"
      class="m-4 inline-flex h-11 items-center justify-center gap-2 rounded-full bg-ink text-[15px] font-semibold text-white transition active:scale-[0.98]"
    >
      <AppIcon name="plus" :size="18" /> Nowy kandydat
    </NuxtLink>

    <nav class="flex flex-col gap-1 px-3">
      <NuxtLink
        v-for="item in NAV_ITEMS"
        :key="item.to"
        :to="item.to"
        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-steel transition hover:bg-surface"
        :active-class="item.exact ? '' : 'bg-surface !text-ink'"
        :exact-active-class="item.exact ? 'bg-surface !text-ink' : ''"
      >
        <AppIcon :name="item.icon" :size="20" />
        {{ item.label }}
      </NuxtLink>
    </nav>
  </aside>
</template>
