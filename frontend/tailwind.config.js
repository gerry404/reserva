import plugin from 'tailwindcss/plugin.js'
import forms from '@tailwindcss/forms'
import tokens, { cssDeclarations } from './src/design/tokens.js'

/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      fontFamily: {
        // Texte courant, sous-titres, formulaires : tout ce qui se lit par
        // phrases. Roboto est neutre et couvre le français en entier.
        sans: ['Roboto', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],

        // Titres et marque — là où le caractère de Dekatron se voit.
        display: ['"Dekatron"', 'Roboto', 'ui-sans-serif', 'system-ui', 'sans-serif'],

        // Chiffres en vedette — heures, prix, compteurs. Roboto en repli :
        // Yuzo ne dessine pas les accents, mais aucun chiffre n'en porte.
        numeric: ['"Yuzo"', 'Roboto', 'ui-monospace', 'monospace'],
      },
      /*
       * Les couleurs viennent de src/design/tokens.js — l'unique source.
       *
       * Rien n'est défini ici : ce fichier ne fait que brancher les jetons sur
       * les classes Tailwind. Pour changer la palette, éditer les jetons.
       */
      colors: tokens.colors,

      /*
       * `rounded-control` sur tout ce qui se clique, `rounded-surface` sur les
       * cartes. Les deux viennent des mêmes jetons, pour qu'un changement de
       * rayon ne demande pas de repasser sur trente fichiers.
       */
      borderRadius: {
        control: tokens.radii.control,
        surface: tokens.radii.surface,
      },

      animation: {
        'fade-in':   'fadeIn 0.3s ease-in-out',
        'slide-up':  'slideUp 0.3s ease-out',
        'slide-in':  'slideIn 0.25s ease-out',
        'pulse-dot': 'pulseDot 1.5s infinite',
        'gradient-x': 'gradientX 3s ease infinite',
      },
      keyframes: {
        fadeIn:  { from: { opacity: '0' }, to: { opacity: '1' } },
        slideUp: { from: { opacity: '0', transform: 'translateY(12px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
        slideIn: { from: { opacity: '0', transform: 'translateX(-12px)' }, to: { opacity: '1', transform: 'translateX(0)' } },
        pulseDot: {
          '0%, 100%': { opacity: '1' },
          '50%':      { opacity: '0.4' },
        },
        gradientX: {
          '0%, 100%': { 'background-position': '0% 50%' },
          '50%':      { 'background-position': '100% 50%' },
        },
      },
    },
  },
  plugins: [
    forms,

    /*
     * Expose les jetons en variables CSS sur :root.
     *
     * Les composants qui stylent hors Tailwind — les blocs du ruban, la barre
     * de durée, le bouton WhatsApp — lisent var(--forest-600) plutôt que de
     * recopier une valeur. Un seul fichier reste la source, quelle que soit la
     * manière dont la couleur est consommée.
     */
    plugin(({ addBase }) => {
      addBase({ ':root': cssDeclarations() })
    }),
  ],
}
