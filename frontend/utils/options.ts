import type { ContactOutcome, LicenseCategory } from '~/types'

export const LICENSE_CATEGORIES: LicenseCategory[] = [
  'B',
  'C',
  'C1',
  'C+E',
  'D',
  'D1',
  'D+E',
]

export const OUTCOME_OPTIONS: { value: ContactOutcome; label: string }[] = [
  { value: 'interested', label: 'Zainteresowany' },
  { value: 'callback', label: 'Oddzwonić' },
  { value: 'no_answer', label: 'Nie odebrał' },
  { value: 'not_interested', label: 'Niezainteresowany' },
  { value: 'wrong_number', label: 'Zły numer' },
  { value: 'documents_requested', label: 'Poproś o dok.' },
]

/** Szybkie presety terminu kolejnego kontaktu (UX < 60s). */
export function nextContactPresets(): { label: string; value: string }[] {
  const now = new Date()

  const at = (days: number, hour: number) => {
    const d = new Date(now)
    d.setDate(d.getDate() + days)
    d.setHours(hour, 0, 0, 0)
    return d.toISOString()
  }

  return [
    { label: 'Za 1h', value: new Date(now.getTime() + 3600_000).toISOString() },
    { label: 'Jutro 10:00', value: at(1, 10) },
    { label: 'Pojutrze 10:00', value: at(2, 10) },
    { label: 'Za tydzień', value: at(7, 10) },
  ]
}
