import type {
  ApplicationStatus,
  ContactOutcome,
  LicenseCategory,
  OfferRequirementKey,
} from '~/types'

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

/** Wymagania ogłoszenia (checkboxy) — klucz ↔ etykieta. */
export const REQUIREMENT_OPTIONS: { key: OfferRequirementKey; label: string }[] = [
  { key: 'c', label: 'C' },
  { key: 'ce', label: 'C+E' },
  { key: 'code_95', label: 'Kod 95' },
  { key: 'driver_card', label: 'Karta kierowcy' },
  { key: 'adr', label: 'ADR' },
  { key: 'hds', label: 'HDS' },
  { key: 'exp_reefer', label: 'Doświadczenie: chłodnia' },
  { key: 'exp_tilt', label: 'Doświadczenie: plandeka' },
  { key: 'exp_international', label: 'Doświadczenie: międzynarodowe' },
  { key: 'lang_de', label: 'Język niemiecki' },
  { key: 'lang_en', label: 'Język angielski' },
]

/** Atrybuty kierowcy do edycji profilu (mapują się na wymagania). */
export const DRIVER_ATTRIBUTE_OPTIONS: { key: string; label: string }[] = [
  { key: 'has_adr', label: 'ADR' },
  { key: 'has_code_95', label: 'Kod 95' },
  { key: 'has_hds', label: 'HDS' },
  { key: 'exp_reefer', label: 'Chłodnia' },
  { key: 'exp_tilt', label: 'Plandeka' },
  { key: 'exp_international', label: 'Międzynarodowe' },
  { key: 'lang_de', label: 'Niemiecki' },
  { key: 'lang_en', label: 'Angielski' },
]

export const APPLICATION_STATUS_OPTIONS: { value: ApplicationStatus; label: string }[] = [
  { value: 'new', label: 'Nowy' },
  { value: 'interested', label: 'Zainteresowany' },
  { value: 'missing_data', label: 'Brakuje danych' },
  { value: 'ready_for_pdf', label: 'Gotowy do PDF' },
  { value: 'sent_to_company', label: 'Wysłany do firmy' },
  { value: 'accepted_by_company', label: 'Zaakceptowany' },
  { value: 'rejected_by_company', label: 'Odrzucony' },
  { value: 'hired', label: 'Zatrudniony' },
  { value: 'failed', label: 'Nieudany' },
]

export const CANDIDATE_STATUS_OPTIONS: { value: string; label: string }[] = [
  { value: 'new', label: 'Nowy' },
  { value: 'active', label: 'Aktywny' },
  { value: 'placed', label: 'Zatrudniony' },
  { value: 'unavailable', label: 'Niedostępny' },
  { value: 'blacklisted', label: 'Czarna lista' },
  { value: 'archived', label: 'Zarchiwizowany' },
]

export const CANDIDATE_SOURCE_OPTIONS: { value: string; label: string }[] = [
  { value: 'facebook', label: 'Facebook' },
  { value: 'olx', label: 'OLX' },
  { value: 'jooble', label: 'Jooble' },
  { value: 'facebook_group', label: 'Grupa Facebook' },
  { value: 'whatsapp', label: 'WhatsApp' },
  { value: 'phone', label: 'Telefon' },
  { value: 'referral', label: 'Polecenie' },
  { value: 'other', label: 'Inne' },
]
