import { h } from 'vue'
import { createVuetify } from 'vuetify'
import {
  Check, ChevronDown, ChevronLeft, ChevronRight, ChevronUp, CircleAlert,
  CircleCheck, CircleX, Info, Minus, Paperclip, Square, SquareCheck, SquareMinus,
  Star, TriangleAlert, X,
} from 'lucide-vue-next'

import tokens, { clay, forest, radii, semantic } from '@/design/tokens'

/**
 * Vuetify, alimenté par les jetons de design.
 *
 * Le thème n'écrit aucune couleur : il lit src/design/tokens.js, qui reste la
 * source unique. Recopier la palette ici en ferait une seconde source, et le
 * jour où le vert change, la moitié du tableau de bord garderait l'ancien.
 *
 * Vuetify n'est monté que pour le tableau de bord. La landing et la page
 * publique de réservation gardent leur identité propre : ce sont elles qui
 * portent le langage visuel du produit, et Material Design l'écraserait.
 * Les styles de Vuetify sont chargés globalement — c'est inévitable — mais
 * aucun composant `v-*` n'est employé hors du tableau de bord.
 */

/**
 * Les icônes de Vuetify viennent de Lucide, comme partout ailleurs.
 *
 * Vuetify installe Material Design Icons par défaut. Le laisser faire aurait
 * mis deux jeux d'icônes dans la même page : une flèche Lucide dans un bouton,
 * une flèche Material dans le sélecteur juste à côté. C'est exactement le
 * défaut de cohérence que cette migration doit corriger.
 */
const aliases = {
  collapse: ChevronUp,
  complete: Check,
  cancel: CircleX,
  close: X,
  delete: CircleX,
  clear: CircleX,
  success: CircleCheck,
  info: Info,
  warning: CircleAlert,
  error: TriangleAlert,
  prev: ChevronLeft,
  next: ChevronRight,
  checkboxOn: SquareCheck,
  checkboxOff: Square,
  checkboxIndeterminate: SquareMinus,
  delimiter: Minus,
  sortAsc: ChevronUp,
  sortDesc: ChevronDown,
  expand: ChevronDown,
  menu: ChevronDown,
  subgroup: ChevronDown,
  dropdown: ChevronDown,
  radioOn: CircleCheck,
  radioOff: Square,
  edit: Paperclip,
  ratingEmpty: Star,
  ratingFull: Star,
  ratingHalf: Star,
  loading: CircleAlert,
  first: ChevronLeft,
  last: ChevronRight,
  unfold: ChevronDown,
  file: Paperclip,
  plus: Check,
  minus: Minus,
  calendar: ChevronDown,
  treeviewCollapse: ChevronUp,
  treeviewExpand: ChevronDown,
  eyeDropper: Paperclip,
  upload: Paperclip,
  color: Paperclip,
  command: Paperclip,
  ctrl: Paperclip,
  space: Paperclip,
  shift: Paperclip,
  alt: Paperclip,
  enter: Paperclip,
  backspace: Paperclip,
  play: ChevronRight,
  pause: Minus,
  stop: Square,
}

/**
 * Le rendu d'une icône Lucide dans le gabarit attendu par Vuetify.
 *
 * `size: '100%'` laisse la taille au conteneur `.v-icon`, sinon chaque icône
 * imposerait ses 24 px et les boutons denses seraient déséquilibrés.
 */
const lucide = {
  component: (props) => {
    const { icon, tag, ...reste } = props
    return h(tag, reste, [h(icon, { size: '100%', 'stroke-width': 2 })])
  },
}

/**
 * Une nuance Vuetify par palier de nos échelles.
 *
 * Vuetify attend des noms plats (`primary`, `primary-darken-1`). Les exposer
 * sous `forest-600` en plus permet d'écrire `color="forest-700"` sur un
 * composant sans sortir du jeu de jetons.
 */
function echelle(prefixe, valeurs) {
  return Object.fromEntries(
    Object.entries(valeurs).map(([palier, valeur]) => [`${prefixe}-${palier}`, valeur]),
  )
}

const theme = {
  dark: false,
  colors: {
    background: clay[100],
    surface: clay[50],
    'surface-bright': '#ffffff',
    'surface-light': clay[50],
    'surface-variant': clay[200],
    'on-surface-variant': clay[700],

    primary: forest[600],
    'primary-darken-1': forest[700],
    secondary: clay[600],
    'secondary-darken-1': clay[700],

    // Les couleurs d'état viennent des jetons sémantiques, pas du nuancier
    // Material : « confirmé » doit rester le vert du produit.
    success: semantic.confirmed.solid,
    warning: semantic.pending.solid,
    error: semantic.cancelled.solid,
    info: semantic.completed.solid,

    'on-background': clay[950],
    'on-surface': clay[950],
    'on-primary': '#ffffff',
    'on-secondary': '#ffffff',
    'on-success': '#ffffff',
    'on-warning': '#ffffff',
    'on-error': '#ffffff',
    'on-info': '#ffffff',

    ...echelle('forest', forest),
    ...echelle('clay', clay),
  },
  variables: {
    'border-color': clay[200],
    'border-opacity': 1,
    'high-emphasis-opacity': 1,
    'medium-emphasis-opacity': 0.72,
    'disabled-opacity': 0.42,
    'theme-overlay-multiplier': 1,
  },
}

/**
 * Les réglages par défaut, pour que la cohérence soit portée par la
 * configuration plutôt que répétée sur chaque balise.
 *
 * C'est le point qui manquait le plus au tableau de bord : chaque écran
 * choisissait ses propres arrondis, ses propres élévations et ses propres
 * densités. Ici, un champ ressemble à un champ partout, et changer d'avis se
 * fait à un seul endroit.
 */
const defaults = {
  global: {
    // Les ombres portées sont remplacées par des bordures : la palette est
    // chaude et peu contrastée, une ombre grise Material y paraît sale.
    elevation: 0,
  },

  VCard: {
    rounded: 'surface',
    border: true,
    color: 'surface',
  },
  VSheet: { color: 'surface' },

  VBtn: {
    rounded: 'control',
    variant: 'flat',
    height: 42,
  },

  VTextField: {
    variant: 'outlined',
    density: 'comfortable',
    rounded: 'control',
    color: 'primary',
    hideDetails: 'auto',
  },
  VTextarea: {
    variant: 'outlined',
    density: 'comfortable',
    rounded: 'surface',
    color: 'primary',
    hideDetails: 'auto',
  },
  VSelect: {
    variant: 'outlined',
    density: 'comfortable',
    rounded: 'control',
    color: 'primary',
    hideDetails: 'auto',
    menuProps: { rounded: 'surface' },
  },
  VAutocomplete: {
    variant: 'outlined',
    density: 'comfortable',
    rounded: 'control',
    color: 'primary',
    hideDetails: 'auto',
    menuProps: { rounded: 'surface' },
  },
  VSwitch: {
    color: 'primary',
    density: 'compact',
    hideDetails: 'auto',
    inset: true,
  },
  VCheckbox: { color: 'primary', density: 'comfortable', hideDetails: 'auto' },

  VChip: { rounded: 'control', size: 'small', variant: 'tonal' },
  VDialog: { maxWidth: 560 },
  VMenu: { rounded: 'surface' },
  VList: { rounded: 'surface' },
  VDataTable: { density: 'comfortable', hover: true },
  VDatePicker: { color: 'primary', showAdjacentMonths: true, firstDayOfWeek: 1 },
  VPagination: { rounded: 'control', activeColor: 'primary', density: 'comfortable' },
  VAlert: { rounded: 'surface', variant: 'tonal', border: 'start' },
  VProgressLinear: { color: 'primary', rounded: true },
  VTooltip: { location: 'top' },
}

export default createVuetify({
  theme: {
    defaultTheme: 'nuvo',
    themes: { nuvo: theme },
  },

  // Les rayons portent les mêmes noms que les classes Tailwind du produit
  // (`rounded-control`, `rounded-surface`) et la même valeur, tirée des jetons.
  defaults,

  icons: {
    defaultSet: 'lucide',
    aliases,
    sets: { lucide },
  },
})

/** Les rayons Vuetify, à poser en CSS : l'API JS ne les expose pas. */
export const radiusRules = `
.rounded-control { border-radius: ${radii.control} !important; }
.rounded-surface { border-radius: ${radii.surface} !important; }
`

export { tokens }
