<script setup lang="ts">
const auth = useAuthStore()
</script>

<template>
  <div class="min-h-full pb-20">
    <header
      class="sticky top-0 z-20 flex h-14 items-center justify-between border-b
        border-gray-200 bg-white px-4 pt-safe-top"
    >
      <span class="text-lg font-bold text-brand">Rekruter</span>
      <span v-if="auth.user" class="text-sm text-gray-500">
        {{ auth.user.name }}
      </span>
    </header>

    <main class="mx-auto max-w-2xl px-4 py-4">
      <slot />
    </main>

    <NewCandidateFab />
    <BottomNav />
  </div>
</template>
