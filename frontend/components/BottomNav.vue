<script setup lang="ts">
import { NAV_ITEMS } from '~/utils/nav'
// Dolna nawigacja — mobile/tablet (ukryta na desktopie).
const auth = useAuthStore()
const items = computed(() =>
  NAV_ITEMS.filter((i) => !i.adminOnly || auth.isAdmin),
)
</script>

<template>
  <nav
    class="fixed inset-x-0 bottom-0 z-30 border-t border-hairline bg-canvas/95
      backdrop-blur pb-safe-bottom lg:hidden"
  >
    <ul
      class="mx-auto grid max-w-2xl"
      :style="{ gridTemplateColumns: `repeat(${items.length}, minmax(0, 1fr))` }"
    >
      <li v-for="item in items" :key="item.to">
        <NuxtLink
          :to="item.to"
          class="group flex h-16 flex-col items-center justify-center gap-1 text-stone"
          :active-class="item.exact ? '' : '!text-ink'"
          :exact-active-class="item.exact ? '!text-ink' : ''"
        >
          <AppIcon :name="item.icon" :size="22" />
          <span class="text-[10px] font-medium">{{ item.label }}</span>
        </NuxtLink>
      </li>
    </ul>
  </nav>
</template>
