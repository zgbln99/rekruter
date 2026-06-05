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
