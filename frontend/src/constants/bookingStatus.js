import { semantic } from '@/design/tokens'

/**
 * Les statuts de réservation : la source unique.
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Ce vocabulaire était défini quatre fois : DashboardView (en trois objets
 * séparés), BookingsView, TrackBookingView et BillingView. Ajouter le statut
 * `no_show` a donc demandé d'éditer plusieurs fichiers, et il en manquait
 * encore dans les filtres, exactement le genre d'oubli que la duplication
 * garantit.
 *
 * Toute vue qui affiche un statut lit désormais ici. Ajouter un statut
 * consiste à ajouter une entrée dans STATUSES, et rien d'autre.
 *
 * Les valeurs correspondent aux constantes de App\Models\Booking côté serveur.
 */

export const BookingStatus = {
  PENDING: 'pending',
  CONFIRMED: 'confirmed',
  CANCELLED: 'cancelled',
  COMPLETED: 'completed',
  NO_SHOW: 'no_show',
}

/**
 * @typedef {object} StatusDescriptor
 * @property {string} label     libellé côté commerçant
 * @property {string} customer  libellé côté client, au féminin, « votre
 *                              réservation », et plus explicite : le client a
 *                              besoin de savoir ce qu'il attend
 * @property {string} short     variante compacte, pour les filtres et puces
 * @property {string} icon      pictogramme, pour les listes denses
 * @property {object} tone      couleurs issues des jetons
 * @property {string} chart     couleur pleine, pour les graphiques
 * @property {boolean} isFinal  l'état ne changera plus de lui-même
 */

/** @type {Record<string, StatusDescriptor>} */
export const STATUSES = {
  [BookingStatus.PENDING]: {
    customer: 'En attente de confirmation',
    label: 'En attente',
    short: 'En attente',
    icon: '⏳',
    tone: semantic.pending,
    chart: semantic.pending.solid,
    isFinal: false,
  },
  [BookingStatus.CONFIRMED]: {
    customer: 'Confirmée',
    label: 'Confirmé',
    short: 'Confirmé',
    icon: '✅',
    tone: semantic.confirmed,
    chart: semantic.confirmed.solid,
    isFinal: false,
  },
  [BookingStatus.COMPLETED]: {
    customer: 'Terminée',
    label: 'Terminé',
    short: 'Terminé',
    icon: '🏁',
    tone: semantic.completed,
    chart: semantic.completed.solid,
    isFinal: true,
  },
  [BookingStatus.CANCELLED]: {
    customer: 'Annulée',
    label: 'Annulé',
    short: 'Annulé',
    icon: '❌',
    tone: semantic.cancelled,
    chart: semantic.cancelled.solid,
    isFinal: true,
  },
  [BookingStatus.NO_SHOW]: {
    customer: 'Non honorée',
    label: 'Non présenté',
    short: 'Absent',
    icon: '🚫',
    tone: semantic.noShow,
    chart: semantic.noShow.solid,
    isFinal: true,
  },
}

/**
 * Descripteur d'un statut, avec repli sûr.
 *
 * Un statut inconnu (ajouté côté serveur avant de l'être ici) s'affiche
 * lisiblement plutôt que de laisser passer un `snake_case` brut dans
 * l'interface, ce qui est exactement ce qui s'était produit avec `no_show`.
 */
export function describeStatus(status) {
  return (
    STATUSES[status] ?? {
      customer: String(status ?? 'Inconnu'),
      label: String(status ?? 'Inconnu'),
      short: String(status ?? '?'),
      icon: '•',
      tone: semantic.noShow,
      chart: semantic.noShow.solid,
      isFinal: true,
    }
  )
}

/** Options d'un menu de filtre, entrée « tous » comprise. */
export const STATUS_FILTER_OPTIONS = [
  { value: '', label: 'Tous les statuts' },
  ...Object.entries(STATUSES).map(([value, { label }]) => ({ value, label })),
]

/** Statuts vers lesquels un commerçant peut basculer une réservation. */
export const ACTIONABLE_STATUSES = [
  BookingStatus.CONFIRMED,
  BookingStatus.COMPLETED,
  BookingStatus.NO_SHOW,
  BookingStatus.CANCELLED,
]
