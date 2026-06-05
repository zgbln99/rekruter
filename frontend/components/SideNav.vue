<script setup lang="ts">
import { NAV_ITEMS } from '~/utils/nav'
// Boczna nawigacja — desktop (≥ lg). Ciemny panel, jak w dojrzałych SaaS-ach.
const auth = useAuthStore()
const items = computed(() =>
  NAV_ITEMS.filter((i) => !i.adminOnly || auth.isAdmin),
)
</script>

<template>
  <aside class="hidden w-64 shrink-0 lg:block">
    <div class="sticky top-0 flex h-screen flex-col bg-ink text-white">
      <!-- Logo -->
      <div class="flex h-16 items-center gap-2.5 px-6">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand text-sm font-bold text-ink">
          {{ (auth.user?.agency_name || 'R').charAt(0) }}
        </span>
        <span class="truncate text-[16px] font-bold tracking-tight">{{ auth.user?.agency_name || 'Rekruter' }}</span>
      </div>

      <!-- Główna akcja -->
      <div class="px-4 pb-2 pt-2">
        <NuxtLink
          to="/candidates/new"
          class="flex h-11 items-center justify-center gap-2 rounded-xl bg-brand text-[15px] font-semibold text-white shadow-sm transition hover:bg-brand-deep active:scale-[0.98]"
        >
          <AppIcon name="plus" :size="18" /> Nowy kandydat
        </NuxtLink>
      </div>

      <!-- Nawigacja -->
      <nav class="mt-2 flex-1 space-y-1 px-3">
        <NuxtLink
          v-for="item in items"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-white/60 transition hover:bg-white/5 hover:text-white"
          :active-class="item.exact ? '' : 'bg-white/10 !text-white'"
          :exact-active-class="item.exact ? 'bg-white/10 !text-white' : ''"
        >
          <AppIcon :name="item.icon" :size="20" />
          {{ item.label }}
        </NuxtLink>
      </nav>

      <!-- Użytkownik -->
      <div class="border-t border-white/10 p-3">
        <div class="flex items-center gap-3 rounded-lg px-3 py-2">
          <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm font-semibold">
            {{ auth.user?.name?.charAt(0) }}
          </span>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-white">{{ auth.user?.name }}</p>
            <p class="truncate text-xs text-white/50">{{ auth.user?.role_label }}</p>
          </div>
          <button class="rounded-lg p-1.5 text-white/50 transition hover:bg-white/10 hover:text-white" title="Wyloguj" @click="auth.logout()">
            <AppIcon name="logout" :size="18" />
          </button>
        </div>
      </div>
    </div>
  </aside>
</template>
