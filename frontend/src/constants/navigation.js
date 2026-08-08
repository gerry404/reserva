import { CalendarDays, CreditCard, House, LayoutGrid, Settings } from 'lucide-vue-next'

/**
 * La navigation du tableau de bord : la source unique.
 *
 * Elle était écrite deux fois dans AppSidebar, une fois pour le bureau et une
 * fois pour le tiroir mobile, et les titres de page une troisième fois dans
 * AppHeader sous une autre forme. Ajouter un écran demandait trois éditions, et
 * la version mobile a effectivement fini par diverger.
 *
 * `name` correspond au nom de route : le titre affiché en tête de page s'en
 * déduit, plus besoin d'une table parallèle.
 */
export const NAV_ITEMS = [
  { name: 'dashboard', label: 'Tableau de bord', to: '/dashboard',          icon: House },
  { name: 'bookings',  label: 'Réservations',    to: '/dashboard/bookings', icon: CalendarDays },
  { name: 'services',  label: 'Services',        to: '/dashboard/services', icon: LayoutGrid },
  { name: 'settings',  label: 'Paramètres',      to: '/dashboard/settings', icon: Settings },
  { name: 'billing',   label: 'Abonnement',      to: '/dashboard/billing',  icon: CreditCard },
]

/** Le titre de la page courante, d'après le nom de route. */
export function titleForRoute(routeName) {
  return NAV_ITEMS.find((item) => item.name === routeName)?.label ?? 'Nuvo'
}
