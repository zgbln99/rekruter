// Pomocniki do wiadomości WhatsApp: wypełnianie szablonów i link wa.me.

/** Wypełnia placeholdery {imie} {nazwisko} {telefon} {agencja} w treści szablonu. */
export function fillTemplate(body: string, vars: Record<string, string | null | undefined>): string {
  return body.replace(/\{(\w+)\}/g, (_, key: string) => (vars[key] ?? '').toString())
}

/** Buduje link wa.me z numeru (same cyfry z kodem kraju) i tekstu. */
export function waLink(phone: string | null | undefined, text: string): string {
  const digits = (phone || '').replace(/\D+/g, '')
  const q = text ? `?text=${encodeURIComponent(text)}` : ''
  return `https://wa.me/${digits}${q}`
}
