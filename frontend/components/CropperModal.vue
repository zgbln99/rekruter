<script setup lang="ts">
import Cropper from 'cropperjs'
import 'cropperjs/dist/cropper.css'

// Modal wycinania zdjęcia profilowego z dokumentu (DESIGN.md reguła 5.4).
const props = defineProps<{ src: string }>()
const emit = defineEmits<{ cropped: [blob: Blob]; close: [] }>()

const imgEl = ref<HTMLImageElement | null>(null)
const saving = ref(false)
let cropper: Cropper | null = null

onMounted(() => {
  if (imgEl.value) {
    cropper = new Cropper(imgEl.value, {
      aspectRatio: 1,
      viewMode: 1,
      autoCropArea: 0.8,
      background: false,
    })
  }
})

onBeforeUnmount(() => cropper?.destroy())

function confirm() {
  if (!cropper) return
  saving.value = true
  cropper
    .getCroppedCanvas({ width: 600, height: 600 })
    .toBlob(
      (blob) => {
        saving.value = false
        if (blob) emit('cropped', blob)
      },
      'image/jpeg',
      0.9,
    )
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex flex-col bg-black/90">
    <div class="flex items-center justify-between p-4 text-white">
      <button @click="emit('close')">Anuluj</button>
      <span class="font-semibold">Wytnij zdjęcie</span>
      <button class="font-semibold text-brand-light" :disabled="saving" @click="confirm">
        {{ saving ? '…' : 'Zapisz' }}
      </button>
    </div>
    <div class="flex flex-1 items-center justify-center overflow-hidden p-2">
      <img ref="imgEl" :src="props.src" class="max-h-full max-w-full" alt="" />
    </div>
  </div>
</template>
