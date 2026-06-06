// Branding aplikacji (logo/ikona/favicon) wgrany w panelu. URL-e publiczne.
export function useBranding() {
  const config = useRuntimeConfig()
  const { data } = useSettingsQuery()

  const base = config.public.apiBase as string
  const v = computed(() => data.value?.branding?.v ?? 0)

  const url = (type: 'logo' | 'icon' | 'favicon') => `${base}/branding/${type}?v=${v.value}`

  const hasLogo = computed(() => !!data.value?.branding?.logo)
  const hasIcon = computed(() => !!data.value?.branding?.icon)
  const hasFavicon = computed(() => !!data.value?.branding?.favicon)

  return {
    hasLogo,
    hasIcon,
    hasFavicon,
    logoUrl: computed(() => (hasLogo.value ? url('logo') : null)),
    iconUrl: computed(() => (hasIcon.value ? url('icon') : null)),
    faviconUrl: computed(() => (hasFavicon.value ? url('favicon') : null)),
  }
}
