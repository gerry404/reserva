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
      colors: {
        /*
         * Argile & Forêt.
         *
         * Deux couleurs, deux rôles. L'argile porte les surfaces — elle
         * remplace le blanc, qui ne dit rien et fatigue en plein soleil. Le
         * vert porte l'action et la confirmation : c'est le « oui, c'est
         * réservé » du produit.
         *
         * Le vert 600 sur argile 50 donne un contraste de 7,3:1, au-delà du
         * seuil AAA. Un choix chaleureux n'a pas à être un choix illisible.
         */
        primary: {
          50:  '#EDF7F1',
          100: '#D3EBDE',
          200: '#A8D7BF',
          300: '#74BC9B',
          400: '#419C76',
          500: '#1F7D57',
          600: '#14603C',   // action, boutons, confirmation
          700: '#114D31',
          800: '#0E3E28',
          900: '#0B3120',
          950: '#051A11',
        },

        /*
         * L'argile. Utilisée en fond de page, en cartes et en séparateurs.
         *
         * Ce n'est pas un gris teinté : la saturation reste franche pour que
         * la chaleur se voie sur un écran de téléphone bon marché, où les gris
         * subtils virent au blanc sale.
         */
        clay: {
          50:  '#FBF8F4',   // cartes, surfaces hautes
          100: '#F5EFE6',   // fond de page
          200: '#E2D6C4',   // bordures, séparateurs
          300: '#CBBBA2',
          400: '#836F52',
          500: '#7E6A4E',
          600: '#665640',
          700: '#4F4333',
          800: '#3A3128',
          900: '#2A241E',
          950: '#1F1B16',   // texte principal
        },

        /*
         * `gray` est remappé sur l'argile plutôt que remplacé dans les vues.
         *
         * Les templates portent près de neuf cents classes text-gray-*,
         * bg-gray-* et border-gray-*. Les réécrire produirait un diff illisible
         * pour un résultat identique ; les remapper ici teinte toute
         * l'application d'un coup et reste réversible.
         *
         * Les nuances 400 et 500 sont volontairement plus sombres que la
         * progression ne le voudrait. text-gray-400 porte du texte discret dans
         * 163 endroits, à 2,43:1 sur l'ancien fond — sous le seuil, et sous le
         * seuil élargi. Le passer à 4,21:1 corrige un défaut d'accessibilité
         * qui existait avant cette palette. La régularité de l'échelle compte
         * moins qu'un texte lisible sur un téléphone bon marché en plein
         * soleil.
         */
        gray: {
          // Fond de page. Plus franc que la nuance des cartes : avec la même
          // valeur des deux côtés, les cartes ne se détachaient plus et la
          // chaleur passait pour un blanc sale.
          50:  '#F0E8DA',
          100: '#E8DECB',
          200: '#DACBB3',
          300: '#CBBBA2',
          400: '#836F52',
          500: '#7E6A4E',
          600: '#665640',
          700: '#4F4333',
          800: '#3A3128',
          900: '#2A241E',
          950: '#1F1B16',
        },
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
  plugins: [require('@tailwindcss/forms')],
}
