/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Paleta KAIROS
        kairos: {
          navy: '#0F172A',    // Deep Navy (Autoridad)
          gold: '#C5A059',    // Kairos Gold (Premium)
          blue: '#3B82F6',    // Digital Blue (Acción)
          surface: '#F8FAFC', // Base Grey (Fondos)
          light: '#FFFFFF'
        }
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'], // Fuente tipo Stripe/SaaS
      }
    },
  },
  plugins: [],
}
