// Formatowanie dat: czas względny po polsku („5 min temu", „wczoraj")
// do list aktywności i timeline'ów. Starsze daty → krótka data.

export function timeAgo(iso?: string | null): string {
  if (!iso) return ''
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) return ''

  const minutes = Math.round((Date.now() - date.getTime()) / 60_000)
  if (minutes < 1) return 'przed chwilą'
  if (minutes < 60) return `${minutes} min temu`

  const hours = Math.round(minutes / 60)
  if (hours < 24) return `${hours} godz. temu`

  const days = Math.round(hours / 24)
  if (days === 1) return 'wczoraj'
  if (days < 7) return `${days} dni temu`

  return date.toLocaleDateString('pl-PL', { day: 'numeric', month: 'short', year: 'numeric' })
}

/** Pełna data i godzina (do atrybutu title obok czasu względnego). */
export function fullDateTime(iso?: string | null): string {
  if (!iso) return ''
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleString('pl-PL', { dateStyle: 'medium', timeStyle: 'short' })
}
