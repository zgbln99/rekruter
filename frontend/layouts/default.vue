<script setup lang="ts">
const auth = useAuthStore()
const online = useOnline()
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
        <NuxtLink to="/" class="flex items-center gap-2">
          <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-ink text-xs font-bold text-brand">
            {{ (auth.user?.agency_name || 'R').charAt(0) }}
          </span>
          <span class="truncate text-[17px] font-bold tracking-tight text-ink">{{ auth.user?.agency_name || 'Rekruter' }}</span>
        </NuxtLink>
        <button v-if="auth.user" class="rounded-full p-2 text-steel active:bg-surface" @click="auth.logout()">
          <AppIcon name="logout" :size="20" />
        </button>
      </header>

      <div v-if="!online" class="bg-amber-500 px-4 py-1.5 text-center text-xs font-medium text-white">
        Tryb offline — zmiany zsynchronizują się po odzyskaniu połączenia
      </div>

      <!-- Treść -->
      <main class="flex-1 px-4 py-5 pb-24 lg:px-10 lg:py-8 lg:pb-10">
        <div class="mx-auto w-full max-w-[1600px]">
          <slot />
        </div>
      </main>
    </div>

    <!-- Nawigacja mobile -->
    <NewCandidateFab />
    <BottomNav />
  </div>
</template>
