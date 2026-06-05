<script setup lang="ts">
const auth = useAuthStore()
const online = useOnline()
</script>

<template>
  <div class="min-h-full pb-20">
    <header
      class="sticky top-0 z-20 flex h-14 items-center justify-between border-b
        border-gray-200 bg-white px-4 pt-safe-top"
    >
      <span class="text-lg font-bold text-brand">Rekruter</span>
      <button
        v-if="auth.user"
        class="text-sm text-gray-500"
        @click="auth.logout()"
      >
        {{ auth.user.name }} · Wyloguj
      </button>
    </header>

    <div v-if="!online" class="bg-amber-500 px-4 py-1 text-center text-xs font-medium text-white">
      Tryb offline — zmiany zostaną zsynchronizowane po odzyskaniu połączenia
    </div>

    <main class="mx-auto max-w-2xl px-4 py-4">
      <slot />
    </main>

    <NewCandidateFab />
    <BottomNav />
  </div>
</template>
