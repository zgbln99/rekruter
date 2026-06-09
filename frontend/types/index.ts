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
  agency_name?: string | null
  last_login_at: string | null
}

export interface Settings {
  agency_name: string
  agency_phone: string | null
  agency_email: string | null
  agency_website: string | null
  careers_hero_image?: string | null
  careers_hero_effective?: string
  careers_texts?: Record<string, { label: string; type: string; value: string }>
  openai_model?: string
  openai_configured?: boolean
  // Tylko dla administratora (dane finansowe):
  placement_fee?: number | null
  placement_currency?: string | null
  // Szablony wiadomości (WhatsApp/SMS):
  message_templates?: MessageTemplate[]
  // Branding (co jest ustawione + wersja do cache-bustingu):
  branding?: { logo: boolean; icon: boolean; favicon: boolean; v: number }
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
  has_hds?: boolean
  exp_reefer?: boolean
  exp_tilt?: boolean
  exp_international?: boolean
  lang_de?: boolean
  lang_en?: boolean
  nationality?: string | null
  availability_from?: string | null
  experience_notes?: string | null
  address?: string | null
  date_of_birth?: string | null
  work_history?: WorkHistoryItem[]
  source: string | null
  profile_photo_id?: string | null
  internal_notes: string | null
  created_at: string | null
  contact_logs?: ContactLog[]
  tasks?: Task[]
  applications?: Application[]
}

export interface WorkHistoryItem {
  employer?: string
  position?: string
  period?: string
  description?: string
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

export interface Note {
  id: string
  title: string | null
  body: string | null
  pinned: boolean
  color: string | null
  updated_at: string | null
  created_at: string | null
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

export interface Company {
  id: string
  name: string
  description?: string | null
  website?: string | null
  nip: string | null
  city: string | null
  country: string | null
  contact_person: string | null
  contact_email: string | null
  contact_phone: string | null
  notes: string | null
  status: 'active' | 'inactive'
  status_label: string
  job_postings_count?: number
  job_postings?: JobPosting[]
  created_at: string | null
}

export type OfferRequirementKey =
  | 'c' | 'ce' | 'code_95' | 'driver_card' | 'adr' | 'hds'
  | 'exp_reefer' | 'exp_tilt' | 'exp_international' | 'lang_de' | 'lang_en'

export interface JobPosting {
  id: string
  company_id: string
  company?: Company
  title: string
  driver_type: string | null
  trailer_type: string | null
  vehicle_type: string | null
  cargo: string | null
  routes_info: string | null
  accommodation: string | null
  onsite_contact: string | null
  arrival_info: string | null
  contract_type: string | null
  points_per_day: string | null
  loading_info: string | null
  daily_km: string | null
  pdf_url: string | null
  faq: { q: string; a: string }[]
  country: string | null
  region_base: string | null
  work_system: string | null
  salary_amount: string | null
  salary_by_system?: { system: string; amount: string }[]
  currency: string | null
  start_date: string | null
  required_language: string | null
  required_experience: string | null
  description: string | null
  public_description: string | null
  recruiter_notes: string | null
  call_script: string[]
  required_categories: LicenseCategory[]
  requirements: Partial<Record<OfferRequirementKey, boolean>>
  location: string | null
  salary_range: string | null
  status: 'open' | 'paused' | 'closed'
  status_label: string
  is_public?: boolean
  is_featured?: boolean
  internal_ref?: string | null
  public_url?: string | null
  cover_image_url?: string | null
  applications_count?: number
  created_at: string | null
}

export type ApplicationStatus =
  | 'new' | 'interested' | 'missing_data' | 'ready_for_pdf' | 'sent_to_company'
  | 'accepted_by_company' | 'rejected_by_company' | 'hired' | 'failed'

export interface Application {
  id: string
  candidate_id: string
  job_posting_id: string
  status: ApplicationStatus
  status_label: string
  position: number
  notes: string | null
  candidate?: Candidate
  job_posting?: JobPosting
}

export interface PipelineColumn {
  id: string
  name: string
  color: string
  applications: Application[]
}

export type ArrivalStatus = 'pending' | 'confirmed' | 'no_show'
export type InstallmentStatus = 'pending' | 'invoiced' | 'paid'

export interface PlacementInstallment {
  id: string
  placement_id: string
  sequence: number
  due_date: string | null
  amount: string | null
  status: InstallmentStatus
  status_label: string
  status_color: string
  invoiced_at: string | null
  paid_at: string | null
}

export interface Placement {
  id: string
  candidate_id: string
  job_posting_id: string
  company_id: string | null
  arrival_at: string | null
  arrival_status: ArrivalStatus
  arrival_status_label: string
  arrival_status_color: string
  arrival_confirmed_at: string | null
  total_amount: string | null
  currency: string
  notes: string | null
  candidate?: Candidate
  job_posting?: JobPosting
  installments?: PlacementInstallment[]
  created_at?: string
}

export interface CalendarEvent {
  type: 'arrival' | 'installment'
  date: string
  datetime: string
  time: string | null
  title: string
  subtitle: string
  status: string
  status_label: string
  color: string
  placement_id: string
  installment_id?: string
  sequence?: number
  amount?: string | null
  currency?: string
  candidate_id: string | null
}

export interface MatchResult {
  result: 'match' | 'partial' | 'no_match'
  required: number
  met: number
  missing: string[]
}

export interface CompletenessItem {
  key: string
  label: string
  done: boolean
}

export interface Completeness {
  items: CompletenessItem[]
  missing: string[]
  complete: boolean
  percent: number
}

export interface TimelineItem {
  at: string | null
  type: string
  label: string
  by?: string | null
}

export interface DashboardStats {
  candidates: {
    total: number
    new_this_week: number
    by_status: { value: string; label: string; count: number }[]
  }
  offers: { total: number; active: number }
  companies: number
  tasks: { today: number; overdue: number }
  profiles: { sent_total: number; sent_this_week: number; pending_decisions: number }
  pipeline: { value: string; label: string; color: string; count: number }[]
  reminders: {
    arrivals_today: {
      placement_id: string
      candidate_id: string
      candidate_name: string
      time: string | null
      offer_title: string | null
    }[]
    installments_due: {
      installment_id: string
      placement_id: string
      candidate_name: string
      sequence: number
      due_date: string | null
      amount: string | null
      currency: string | null
    }[]
  }
  recent_activity: {
    label: string
    subject: string
    candidate_id: string | null
    at: string | null
    by: string | null
  }[]
}

export interface MessageTemplate {
  name: string
  body: string
}

export interface NotificationItem {
  id: string
  type: 'task' | 'arrival' | 'installment' | 'expiry'
  title: string
  subtitle: string
  to: string
  when: string | null
  color: string
}

export interface NotificationsResponse {
  count: number
  items: NotificationItem[]
}

export interface SearchResults {
  candidates: { id: string; full_name: string; phone: string; status_label: string | null }[]
  offers: { id: string; title: string; company: string | null }[]
}

export interface PipelineBoard {
  job_posting: JobPosting
  stages: PipelineColumn[]
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
