// Wspólna definicja pozycji nawigacji (dolna na mobile, boczna na desktop).
export interface NavItem {
  to: string
  label: string
  icon: string
  exact?: boolean
  adminOnly?: boolean
}

export const NAV_ITEMS: NavItem[] = [
  { to: '/', label: 'Dziś', icon: 'home', exact: true },
  { to: '/job-offers', label: 'Ogłoszenia', icon: 'document' },
  { to: '/candidates', label: 'Kandydaci', icon: 'users' },
  { to: '/pipeline', label: 'Pipeline', icon: 'board' },
  { to: '/companies', label: 'Firmy', icon: 'building' },
  { to: '/users', label: 'Użytkownicy', icon: 'shield', adminOnly: true },
  { to: '/settings', label: 'Ustawienia', icon: 'settings', adminOnly: true },
]
