<script setup lang="ts">
const auth = useAuthStore()
const online = useOnline()
const searchOpen = useState('global-search-open', () => false)

// Własny favicon (jeśli wgrany w panelu) nadpisuje domyślny.
const branding = useBranding()
useHead({
  link: computed(() =>
    branding.faviconUrl.value
      ? [{ rel: 'icon', href: branding.faviconUrl.value, key: 'favicon' }]
      : [],
  ),
})
</script>

<template>
  <div class="flex min-h-screen bg-surface-soft">
    <!-- Boczna nawigacja (desktop) -->
    <SideNav />

    <div class="flex min-w-0 flex-1 flex-col">
      <!-- Górny pasek (mobile) -->
      <header
        class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-hairline bg-canvas/90 px-4 pt-safe-top backdrop-blur lg:hidden"
      >
        <NuxtLink to="/" class="min-w-0">
          <AppLogo :size="30" />
        </NuxtLink>
        <div class="flex items-center gap-1">
          <button class="rounded-full p-2 text-steel active:bg-surface" aria-label="Szukaj" @click="searchOpen = true">
            <AppIcon name="search" :size="20" />
          </button>
          <NotificationBell />
          <button v-if="auth.user" class="rounded-full p-2 text-steel active:bg-surface" @click="auth.logout()">
            <AppIcon name="logout" :size="20" />
          </button>
        </div>
      </header>

      <div v-if="!online" class="bg-amber-500 px-4 py-1.5 text-center text-xs font-medium text-white">
        Tryb offline — zmiany zsynchronizują się po odzyskaniu połączenia
      </div>

      <!-- Treść -->
      <main class="flex-1 px-4 py-5 pb-24 lg:px-10 lg:py-8 lg:pb-10">
        <div class="mx-auto w-full max-w-[1850px]">
          <slot />
        </div>
      </main>
    </div>

    <!-- Globalna wyszukiwarka (Ctrl/Cmd+K) -->
    <GlobalSearch />

    <!-- Nawigacja mobile -->
    <NewCandidateFab />
    <BottomNav />
  </div>
</template>
