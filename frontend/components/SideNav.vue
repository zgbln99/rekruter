<script setup lang="ts">
import { NAV_ITEMS } from '~/utils/nav'
// Boczna nawigacja — desktop (≥ lg). Ciemny panel, jak w dojrzałych SaaS-ach.
const auth = useAuthStore()
const searchOpen = useState('global-search-open', () => false)
const items = computed(() =>
  NAV_ITEMS.filter((i) => !i.adminOnly || auth.isAdmin),
)
</script>

<template>
  <aside class="hidden w-64 shrink-0 lg:block">
    <div class="sticky top-0 flex h-screen flex-col bg-ink text-white">
      <!-- Logo -->
      <NuxtLink to="/" class="flex h-[76px] items-center justify-center border-b border-white/5 px-4">
        <AppLogo dark :size="38" />
      </NuxtLink>

      <!-- Główna akcja -->
      <div class="px-4 pb-2 pt-4">
        <NuxtLink
          to="/candidates/new"
          class="flex h-11 items-center justify-center gap-2 rounded-xl bg-brand text-[15px] font-semibold text-white shadow-[0_8px_20px_-8px_rgba(220,38,38,0.7)] transition hover:bg-brand-deep active:scale-[0.98]"
        >
          <AppIcon name="plus" :size="18" /> Nowy kandydat
        </NuxtLink>
      </div>

      <!-- Szukaj -->
      <div class="px-4 pb-1">
        <button
          class="flex w-full items-center gap-2.5 rounded-xl bg-white/5 px-3 py-2.5 text-sm font-medium text-white/55 transition hover:bg-white/10 hover:text-white"
          @click="searchOpen = true"
        >
          <AppIcon name="search" :size="18" /> Szukaj…
          <kbd class="ml-auto rounded border border-white/15 px-1.5 py-0.5 text-[10px] text-white/40">⌘K</kbd>
        </button>
      </div>

      <!-- Nawigacja -->
      <nav class="mt-3 flex-1 space-y-0.5 px-3">
        <NuxtLink
          v-for="item in items"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-white/55 transition hover:bg-white/5 hover:text-white"
          :active-class="item.exact ? '' : 'bg-white/10 !text-white shadow-[inset_3px_0_0_0_#dc2626]'"
          :exact-active-class="item.exact ? 'bg-white/10 !text-white shadow-[inset_3px_0_0_0_#dc2626]' : ''"
        >
          <AppIcon :name="item.icon" :size="19" />
          {{ item.label }}
        </NuxtLink>
      </nav>

      <!-- Użytkownik -->
      <div class="border-t border-white/10 p-3">
        <div class="flex items-center gap-3 rounded-xl bg-white/[0.03] px-3 py-2.5">
          <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand/90 text-sm font-semibold text-white">
            {{ auth.user?.name?.charAt(0) }}
          </span>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-white">{{ auth.user?.name }}</p>
            <p class="truncate text-xs text-white/50">{{ auth.user?.role_label }}</p>
          </div>
          <NotificationBell dark up />
          <button class="rounded-lg p-1.5 text-white/50 transition hover:bg-white/10 hover:text-white" title="Wyloguj" @click="auth.logout()">
            <AppIcon name="logout" :size="18" />
          </button>
        </div>
      </div>
    </div>
  </aside>
</template>
