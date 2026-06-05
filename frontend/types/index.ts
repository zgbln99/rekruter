// Kontrakty API (utrzymywane zgodnie z backendem — DESIGN.md sekcja 9).

export type UserRole = 'admin' | 'recruiter'

export interface User {
  id: string
  name: string
  email: string
  role: UserRole
  role_label: string
  phone: string | null
  avatar_path: string | null
  tenant_id: string
  last_login_at: string | null
}

export interface LoginResponse {
  token: string
  user: User
}

export type CandidateStatus =
  | 'new'
  | 'active'
  | 'placed'
  | 'unavailable'
  | 'blacklisted'
  | 'archived'

export type LicenseCategory = 'B' | 'C' | 'C1' | 'C+E' | 'D' | 'D1' | 'D+E'

export type ContactChannel = 'phone' | 'whatsapp' | 'sms' | 'email'

export type ContactOutcome =
  | 'interested'
  | 'not_interested'
  | 'no_answer'
  | 'callback'
  | 'wrong_number'
  | 'hired_elsewhere'
  | 'documents_requested'

export type TaskStatus = 'open' | 'done' | 'cancelled'

export interface Candidate {
  id: string
  first_name: string
  last_name: string | null
  full_name: string
  phone: string
  phone_normalized: string
  email: string | null
  city: string | null
  country: string | null
  status: CandidateStatus
  status_label: string
  license_categories: LicenseCategory[]
  has_adr: boolean
  has_code_95: boolean
  source: string | null
  internal_notes: string | null
  created_at: string | null
  contact_logs?: ContactLog[]
  tasks?: Task[]
}

export interface ContactLog {
  id: string
  candidate_id: string
  channel: ContactChannel
  channel_label: string
  outcome: ContactOutcome
  outcome_label: string
  note: string | null
  contacted_at: string | null
  next_contact_at: string | null
  task_id: string | null
  user?: User
}

export interface Task {
  id: string
  candidate_id: string | null
  type: string
  type_label: string
  status: TaskStatus
  status_label: string
  title: string
  description: string | null
  due_at: string | null
  completed_at: string | null
  candidate?: Candidate
}

export interface LookupResponse {
  normalized: string | null
  exists: boolean
  candidate: Candidate | null
}

export interface ContactInput {
  channel: ContactChannel
  outcome: ContactOutcome
  note?: string | null
  next_contact_at?: string | null
}

export interface QuickAddInput {
  phone: string
  first_name: string
  last_name?: string | null
  license_categories?: LicenseCategory[]
  has_adr?: boolean
  has_code_95?: boolean
  city?: string | null
  internal_notes?: string | null
  contact?: ContactInput | null
}

export interface Paginated<T> {
  data: T[]
  meta?: { current_page: number; last_page: number; total: number }
}

export type DocumentType =
  | 'cv'
  | 'id_card'
  | 'passport'
  | 'driving_license'
  | 'driver_card'
  | 'adr'
  | 'code_95'
  | 'photo'
  | 'other'

export interface CandidateDocument {
  id: string
  candidate_id: string
  type: DocumentType
  type_label: string
  original_name: string | null
  mime: string | null
  size: number
  is_profile_photo: boolean
  created_at: string | null
  download_url: string
}

export interface ProfileSend {
  id: string
  candidate_id: string
  recipient_email: string
  status: 'queued' | 'sent' | 'failed' | 'viewed'
  status_label: string
  sent_at: string | null
  created_at: string | null
}

export const DOCUMENT_TYPE_OPTIONS: { value: DocumentType; label: string }[] = [
  { value: 'cv', label: 'CV' },
  { value: 'id_card', label: 'Dowód' },
  { value: 'passport', label: 'Paszport' },
  { value: 'driving_license', label: 'Prawo jazdy' },
  { value: 'driver_card', label: 'Karta kierowcy' },
  { value: 'adr', label: 'ADR' },
  { value: 'code_95', label: 'Kod 95' },
  { value: 'photo', label: 'Zdjęcie' },
  { value: 'other', label: 'Inne' },
]
