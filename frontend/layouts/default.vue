<script setup lang="ts">
const auth = useAuthStore()
const online = useOnline()
</script>

<template>
  <div class="min-h-full pb-24">
    <header
      class="sticky top-0 z-20 border-b border-hairline bg-canvas/90 pt-safe-top backdrop-blur"
    >
      <div class="mx-auto flex h-14 max-w-2xl items-center justify-between px-4">
        <NuxtLink to="/" class="flex items-center gap-2">
          <span class="h-2.5 w-2.5 rounded-full bg-brand" />
          <span class="text-[17px] font-bold tracking-tight text-ink">Rekruter</span>
        </NuxtLink>
        <button
          v-if="auth.user"
          class="flex items-center gap-2 rounded-full py-1.5 pl-3 pr-2 text-sm text-steel
            transition active:bg-surface"
          @click="auth.logout()"
        >
          <span class="max-w-[7rem] truncate font-medium">{{ auth.user.name }}</span>
          <AppIcon name="logout" :size="18" />
        </button>
      </div>
    </header>

    <div
      v-if="!online"
      class="bg-amber-500 px-4 py-1.5 text-center text-xs font-medium text-white"
    >
      Tryb offline — zmiany zsynchronizują się po odzyskaniu połączenia
    </div>

    <main class="mx-auto max-w-2xl px-4 py-5">
      <slot />
    </main>

    <NewCandidateFab />
    <BottomNav />
  </div>
</template>
