<script setup lang="ts">
import { NAV_ITEMS, type NavItem } from '~/utils/nav'

// Boczna nawigacja — desktop (≥ lg). Jasny, „cichy" panel z hairline,
// pogrupowane sekcje i subtelny stan aktywny w kolorze marki.
const auth = useAuthStore()
const searchOpen = useState('global-search-open', () => false)

const can = (i: NavItem) => !i.adminOnly || auth.isAdmin
const pick = (...routes: string[]) =>
  NAV_ITEMS.filter((i) => routes.includes(i.to) && can(i))

// Sekcje — porządkują nawigację bez zmiany płaskiej listy współdzielonej z mobile.
const groups = computed(() => [
  { label: '', items: pick('/') },
  { label: 'Rekrutacja', items: pick('/job-offers', '/candidates', '/pipeline', '/calendar') },
  { label: 'Organizacja', items: pick('/companies', '/users', '/settings') },
].filter((g) => g.items.length))

const initials = computed(() => {
  const n = auth.user?.name?.trim() || ''
  return n.split(/\s+/).slice(0, 2).map((p) => p[0]).join('').toUpperCase() || '?'
})
</script>

<template>
  <aside class="hidden w-[264px] shrink-0 lg:block">
    <div class="sticky top-0 flex h-screen flex-col border-r border-hairline bg-canvas">
      <!-- Logo -->
      <NuxtLink to="/" class="flex h-16 items-center px-5">
        <AppLogo :size="30" />
      </NuxtLink>

      <!-- Akcje -->
      <div class="space-y-2 px-3 pb-1 pt-1">
        <NuxtLink
          to="/candidates/new"
          class="flex h-10 items-center justify-center gap-2 rounded-xl bg-ink text-[14px] font-semibold text-white transition hover:bg-charcoal active:scale-[0.98]"
        >
          <AppIcon name="plus" :size="17" /> Nowy kandydat
        </NuxtLink>
        <button
          class="flex h-9 w-full items-center gap-2.5 rounded-lg border border-hairline bg-surface-soft px-3 text-[13px] font-medium text-stone transition hover:border-muted/50 hover:text-slate"
          @click="searchOpen = true"
        >
          <AppIcon name="search" :size="16" /> Szukaj…
          <kbd class="ml-auto rounded border border-hairline bg-canvas px-1.5 py-0.5 text-[10px] font-medium text-muted">⌘K</kbd>
        </button>
      </div>

      <!-- Nawigacja -->
      <nav class="flex-1 overflow-y-auto px-3 pt-3">
        <div v-for="(group, gi) in groups" :key="gi" :class="gi ? 'mt-5' : ''">
          <p v-if="group.label" class="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-muted">
            {{ group.label }}
          </p>
          <div class="space-y-0.5">
            <NuxtLink
              v-for="item in group.items"
              :key="item.to"
              :to="item.to"
              class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-[14px] font-medium text-steel transition hover:bg-surface hover:text-ink"
              :active-class="item.exact ? '' : 'nav-active'"
              :exact-active-class="item.exact ? 'nav-active' : ''"
            >
              <AppIcon :name="item.icon" :size="18" class="shrink-0" />
              {{ item.label }}
            </NuxtLink>
          </div>
        </div>
      </nav>

      <!-- Użytkownik -->
      <div class="border-t border-hairline p-3">
        <div class="flex items-center gap-3 rounded-xl px-2 py-1.5">
          <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-ink text-[13px] font-semibold text-white">
            {{ initials }}
          </span>
          <div class="min-w-0 flex-1">
            <p class="truncate text-[13px] font-semibold text-ink">{{ auth.user?.name }}</p>
            <p class="truncate text-[12px] text-stone">{{ auth.user?.role_label }}</p>
          </div>
          <NotificationBell up />
          <button
            class="rounded-lg p-1.5 text-stone transition hover:bg-surface hover:text-brand"
            title="Wyloguj"
            @click="auth.logout()"
          >
            <AppIcon name="logout" :size="18" />
          </button>
        </div>
      </div>
    </div>
  </aside>
</template>

<style scoped>
/* Stan aktywny — delikatne tło marki, tekst marki i pionowy akcent. */
.nav-active {
  background-color: theme('colors.brand.soft');
  color: theme('colors.brand.deep') !important;
}
.nav-active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  height: 18px;
  width: 3px;
  transform: translateY(-50%);
  border-radius: 0 3px 3px 0;
  background-color: theme('colors.brand.DEFAULT');
}
</style>
