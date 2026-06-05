<script setup lang="ts">
const auth = useAuthStore()
const online = useOnline()
</script>

<template>
  <div class="flex min-h-screen flex-col">
    <header
      class="sticky top-0 z-30 border-b border-hairline bg-canvas/90 pt-safe-top backdrop-blur"
    >
      <div class="flex h-14 items-center justify-between px-4 lg:px-8">
        <NuxtLink to="/" class="flex items-center gap-2">
          <span class="h-2.5 w-2.5 rounded-full bg-brand" />
          <span class="text-[17px] font-bold tracking-tight text-ink">Rekruter</span>
        </NuxtLink>
        <button
          v-if="auth.user"
          class="flex items-center gap-2 rounded-full py-1.5 pl-3 pr-2 text-sm text-steel transition hover:bg-surface active:bg-surface"
          @click="auth.logout()"
        >
          <span class="max-w-[12rem] truncate font-medium">{{ auth.user.name }}</span>
          <AppIcon name="logout" :size="18" />
        </button>
      </div>
      <div
        v-if="!online"
        class="bg-amber-500 px-4 py-1.5 text-center text-xs font-medium text-white"
      >
        Tryb offline — zmiany zsynchronizują się po odzyskaniu połączenia
      </div>
    </header>

    <div class="flex flex-1">
      <!-- Boczna nawigacja (desktop) -->
      <SideNav />

      <!-- Treść — pełna szerokość -->
      <main class="w-full min-w-0 px-4 py-5 pb-24 lg:px-10 lg:py-8 lg:pb-10">
        <div class="mx-auto w-full max-w-[1400px]">
          <slot />
        </div>
      </main>
    </div>

    <!-- Nawigacja mobile -->
    <NewCandidateFab />
    <BottomNav />
  </div>
</template>
