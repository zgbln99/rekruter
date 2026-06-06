<script setup lang="ts">
// Logo aplikacji: jeśli wgrano własne logo — pokazuje je; inaczej kwadratowy
// znak (wgrana ikona albo domyślny) + nazwa agencji / „edge recruiting".
withDefaults(defineProps<{ dark?: boolean; size?: number }>(), { dark: false, size: 32 })

const auth = useAuthStore()
const branding = useBranding()
const name = computed(() => auth.user?.agency_name || 'edge recruiting')
const initial = computed(() => (name.value || 'e').charAt(0).toUpperCase())
</script>

<template>
  <div class="flex min-w-0 items-center gap-2.5">
    <!-- Pełne logo wgrane przez agencję -->
    <img v-if="branding.logoUrl.value" :src="branding.logoUrl.value" alt="Logo" class="max-w-[170px] object-contain" :style="{ height: size + 'px' }" />

    <!-- Albo: znak + nazwa -->
    <template v-else>
      <img
        v-if="branding.iconUrl.value"
        :src="branding.iconUrl.value"
        alt=""
        class="shrink-0 rounded-lg object-cover"
        :style="{ height: size + 'px', width: size + 'px' }"
      />
      <span
        v-else
        class="flex shrink-0 items-center justify-center rounded-lg font-bold"
        :class="dark ? 'bg-brand text-ink' : 'bg-ink text-brand'"
        :style="{ height: size + 'px', width: size + 'px', fontSize: Math.round(size * 0.42) + 'px' }"
      >{{ initial }}</span>
      <span class="truncate font-bold tracking-tight" :class="dark ? 'text-white' : 'text-ink'" :style="{ fontSize: Math.round(size * 0.5) + 'px' }">
        {{ name }}
      </span>
    </template>
  </div>
</template>
