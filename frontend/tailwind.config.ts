import type { Config } from 'tailwindcss'

// System wizualny — DESIGN.md sekcja 11. Mobile-first, wysoki kontrast.
export default <Partial<Config>>{
  theme: {
    extend: {
      colors: {
        // Kolor akcentu (do potwierdzenia z księgą znaku agencji — D4).
        brand: {
          DEFAULT: '#0f766e',
          dark: '#115e59',
          light: '#5eead4',
        },
      },
      borderRadius: {
        xl: '12px',
        '2xl': '16px',
      },
      // Bezpieczne marginesy pod notch/gesture bar (iOS).
      spacing: {
        'safe-bottom': 'env(safe-area-inset-bottom)',
        'safe-top': 'env(safe-area-inset-top)',
      },
    },
  },
}
