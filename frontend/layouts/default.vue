<script setup lang="ts">
const auth = useAuthStore()
const online = useOnline()
const searchOpen = useState('global-search-open', () => false)

// Własny favicon / ikona iOS (z wgranego brandingu, z wersją do cache-bustingu).
const branding = useBranding()
useHead({
  link: computed(() => {
    const out: { rel: string; href: string; key: string }[] = []
    if (branding.faviconUrl.value) out.push({ rel: 'icon', href: branding.faviconUrl.value, key: 'favicon' })
    if (branding.iconUrl.value) out.push({ rel: 'apple-touch-icon', href: branding.iconUrl.value, key: 'apple-touch' })
    return out
  }),
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

    <!-- Powiadomienia toast (potwierdzenia akcji) -->
    <UiToasts />

    <!-- Nawigacja mobile -->
    <NewCandidateFab />
    <BottomNav />
  </div>
</template>
