<script setup lang="ts">
// Logo aplikacji: jeśli wgrano własne logo — pokazuje je; inaczej kwadratowy
// znak (wgrana ikona albo domyślny) + nazwa agencji / „edge recruiting".
withDefaults(defineProps<{ dark?: boolean; size?: number }>(), { dark: false, size: 32 })

const auth = useAuthStore()
const branding = useBranding()
const name = computed(() => auth.user?.agency_name || 'edge recruiting')
</script>

<template>
  <div class="flex min-w-0 items-center gap-2.5">
    <!-- Pełne logo wgrane przez agencję (jednokolorowe — dopasowane do tła:
         białe na ciemnym menu, czarne na jasnym nagłówku) -->
    <img
      v-if="branding.logoUrl.value"
      :src="branding.logoUrl.value"
      alt="Logo"
      class="max-w-[210px] object-contain"
      :class="dark ? 'brightness-0 invert' : 'brightness-0'"
      :style="{ height: size + 'px' }"
    />

    <!-- Albo: wgrana ikona (jeśli jest) + nazwa agencji -->
    <template v-else>
      <img
        v-if="branding.iconUrl.value"
        :src="branding.iconUrl.value"
        alt=""
        class="shrink-0 rounded-lg object-cover"
        :style="{ height: size + 'px', width: size + 'px' }"
      />
      <span class="truncate font-bold tracking-tight" :class="dark ? 'text-white' : 'text-ink'" :style="{ fontSize: Math.round(size * 0.5) + 'px' }">
        {{ name }}
      </span>
    </template>
  </div>
</template>
