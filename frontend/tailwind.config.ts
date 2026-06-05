import type { Config } from 'tailwindcss'

// System wizualny — docs/design-system.md (Mintlify) przełożony na konkretne tokeny.
// Mięta jako akcent, czarne (ink) pill-buttony jako akcja dominująca, czyste karty
// z hairline, dyscyplina promieni. Mobile-first, wysoki kontrast.
export default <Partial<Config>>{
  theme: {
    extend: {
      colors: {
        // Akcent marki (mięta) — zarezerwowany dla akcentów, stanów aktywnych, potwierdzeń.
        brand: {
          DEFAULT: '#10b981',
          deep: '#059669',
          soft: '#ecfdf5',
          light: '#6ee7b7',
        },
        // Akcja dominująca (czarny pill).
        ink: '#18181b',
        charcoal: '#27272a',
        slate: '#3f3f46',
        steel: '#52525b',
        stone: '#71717a',
        muted: '#a1a1aa',
        // Powierzchnie.
        canvas: '#ffffff',
        surface: '#f4f4f5',
        'surface-soft': '#fafafa',
        hairline: '#e4e4e7',
        'hairline-soft': '#f1f1f3',
      },
      fontFamily: {
        sans: [
          'Inter',
          '-apple-system',
          'BlinkMacSystemFont',
          'Segoe UI',
          'Roboto',
          'sans-serif',
        ],
      },
      borderRadius: {
        md: '8px',
        lg: '12px',
        xl: '16px',
        '2xl': '20px',
      },
      boxShadow: {
        subtle: '0 1px 2px 0 rgba(0,0,0,0.04)',
        card: '0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.04)',
        elevated: '0 8px 24px -6px rgba(0,0,0,0.12)',
        fab: '0 6px 16px -4px rgba(0,0,0,0.30)',
      },
      // Bezpieczne marginesy pod notch/gesture bar (iOS).
      spacing: {
        'safe-bottom': 'env(safe-area-inset-bottom)',
        'safe-top': 'env(safe-area-inset-top)',
      },
    },
  },
}
