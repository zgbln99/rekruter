<script setup lang="ts">
// Kontener toastów — nad dolną nawigacją na mobile, prawy dół na desktopie.
const toast = useToast()

const tone: Record<string, string> = {
  success: 'bg-ink text-white',
  error: 'bg-red-600 text-white',
  info: 'bg-canvas text-ink border border-hairline',
}
</script>

<template>
  <Teleport to="body">
    <div class="pointer-events-none fixed inset-x-0 bottom-20 z-[70] flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:bottom-6 sm:right-6 sm:items-end">
      <TransitionGroup
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        leave-active-class="transition duration-150 ease-in"
        leave-to-class="translate-y-1 opacity-0"
      >
        <button
          v-for="t in toast.items.value"
          :key="t.id"
          class="pointer-events-auto flex max-w-sm items-center gap-2.5 rounded-xl px-4 py-3 text-sm font-medium shadow-elevated"
          :class="tone[t.type]"
          @click="toast.dismiss(t.id)"
        >
          <AppIcon :name="t.type === 'error' ? 'x' : 'check'" :size="16" class="shrink-0" />
          <span class="text-left">{{ t.message }}</span>
        </button>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
