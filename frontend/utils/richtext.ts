// Sanityzacja i konwersja prostego HTML z edytora tekstu.

const ALLOWED = new Set(['B', 'STRONG', 'I', 'EM', 'U', 'UL', 'OL', 'LI', 'P', 'BR', 'A', 'DIV', 'SPAN'])

/** Czy tekst zawiera znaczniki HTML. */
export function looksLikeHtml(s?: string | null): boolean {
  return !!s && /<\/?[a-z][\s\S]*>/i.test(s)
}

/** Zostawia tylko bezpieczne znaczniki, usuwa atrybuty (poza href w <a>). */
export function sanitizeHtml(html: string): string {
  if (typeof window === 'undefined' || !html) return html
  const doc = new DOMParser().parseFromString(html, 'text/html')
  const walk = (node: Element) => {
    for (const child of Array.from(node.children)) {
      if (!ALLOWED.has(child.tagName)) {
        child.replaceWith(document.createTextNode(child.textContent || ''))
        continue
      }
      for (const attr of Array.from(child.attributes)) {
        if (!(child.tagName === 'A' && attr.name === 'href')) child.removeAttribute(attr.name)
      }
      walk(child)
    }
  }
  walk(doc.body)
  return doc.body.innerHTML
}

/** Zamienia HTML na czysty tekst (z zachowaniem akapitów/list) — do kopiowania. */
export function htmlToText(html?: string | null): string {
  if (!html) return ''
  if (!looksLikeHtml(html)) return html
  const tmp = html
    .replace(/<\/(p|div|li)>/gi, '\n')
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<li[^>]*>/gi, '• ')
  if (typeof window === 'undefined') return tmp.replace(/<[^>]*>/g, '').trim()
  const el = document.createElement('div')
  el.innerHTML = tmp
  return (el.textContent || '').replace(/\n{3,}/g, '\n\n').trim()
}

/** Czysty tekst → HTML (akapity/<br>) — aby edytor pokazał istniejący tekst z liniami. */
export function textToHtml(text?: string | null): string {
  if (!text) return ''
  if (looksLikeHtml(text)) return text
  const esc = (s: string) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  return text
    .split(/\n{2,}/)
    .map((p) => '<p>' + esc(p).replace(/\n/g, '<br>') + '</p>')
    .join('')
}
