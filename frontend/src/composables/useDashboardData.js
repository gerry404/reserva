import { computed, ref } from 'vue'
import { bookingsApi, dashboardApi } from '@/api'
import { describeStatus } from '@/constants/bookingStatus'

/**
 * Les données du tableau de bord, et tout ce qui s'en déduit.
 *
 * Le chargement, les actions rapides et une quinzaine de calculs dérivés
 * vivaient dans la vue, entre le balisage de huit sections. Les sortir permet
 * à chaque bloc d'affichage de ne recevoir que ce qu'il montre.
 */
export function useDashboardData() {
  const stats = ref(null)
  const upcoming = ref([])
  const chart = ref(null)
  const analytics = ref(null)

  const loading = ref(true)
  const error = ref('')
  const actionEnCours = ref(null)

  async function charger() {
    loading.value = true
    error.value = ''

    try {
      // `allSettled`, pas `all` : les analyses sont réservées au plan Pro et
      // répondent 402 sur un plan gratuit. Avec `all`, ce seul rejet vidait
      // tout le tableau de bord, et le plan gratuit voyait une page d'erreur
      // à la place de ses réservations.
      const [s, u, c, a] = await Promise.allSettled([
        dashboardApi.stats(),
        dashboardApi.upcoming(),
        dashboardApi.chart(),
        dashboardApi.analytics(),
      ])

      if (s.status === 'rejected') throw s.reason

      stats.value = s.value
      upcoming.value = u.status === 'fulfilled' ? u.value : []
      chart.value = c.status === 'fulfilled' ? c.value : null

      // Absentes plutôt que cassées : l'écran propose alors de passer au Pro.
      analytics.value = a.status === 'fulfilled' ? a.value : null
    } catch (e) {
      error.value = e.message
    } finally {
      loading.value = false
    }
  }

  async function confirmer(id) {
    actionEnCours.value = id
    try {
      await bookingsApi.updateStatus(id, 'confirmed')
      const i = upcoming.value.findIndex((b) => b.id === id)
      if (i !== -1) upcoming.value[i].status = 'confirmed'
      if (stats.value) {
        stats.value.pending_bookings = Math.max(0, stats.value.pending_bookings - 1)
      }
    } catch (e) {
      error.value = e.message ?? "La confirmation n'a pas abouti."
    } finally {
      actionEnCours.value = null
    }
  }

  async function annuler(id) {
    actionEnCours.value = id
    try {
      await bookingsApi.cancel(id)
      upcoming.value = upcoming.value.filter((b) => b.id !== id)
      if (stats.value) {
        stats.value.pending_bookings = Math.max(0, stats.value.pending_bookings - 1)
      }
    } catch (e) {
      error.value = e.message ?? "L'annulation n'a pas abouti."
    } finally {
      actionEnCours.value = null
    }
  }

  /**
   * La jauge de quota.
   *
   * Dérivée de la limite que l'API annonce plutôt que d'un 30 écrit en dur :
   * changer l'allocation d'un plan ne laisse pas le seuil d'alerte pointer sur
   * l'ancien chiffre.
   */
  const quotaPourcent = computed(() => {
    if (!stats.value?.plan_limit) return 0
    return Math.min(100, Math.round((stats.value.plan_used / stats.value.plan_limit) * 100))
  })

  const quotaTendu = computed(() => quotaPourcent.value >= 80)

  /** Les heures d'affluence, de 7 h à 21 h, trous compris. */
  const heuresPointe = computed(() => {
    const source = analytics.value?.peak_hours
    if (!source) return []

    const liste = []
    for (let h = 7; h <= 21; h++) {
      liste.push({ label: `${h} h`, count: source[String(h).padStart(2, '0')] ?? 0 })
    }
    return liste
  })

  const maxHeurePointe = computed(() =>
    Math.max(...heuresPointe.value.map((h) => h.count), 1),
  )

  const maxJourPointe = computed(() =>
    Math.max(...(analytics.value?.peak_days ?? []).map((d) => d.count), 1),
  )

  /**
   * La répartition par statut, en segments cumulés.
   *
   * `offset` porte le décalage du segment précédent : c'est ce qui permet de
   * dessiner l'anneau en un seul cercle, sans calculer d'angles.
   */
  const segmentsStatut = computed(() => {
    const source = analytics.value?.status_distribution
    if (!source) return []

    const total = Object.values(source).reduce((a, b) => a + b, 0)
    if (total === 0) return []

    let decalage = 0
    return Object.entries(source).map(([statut, nombre]) => {
      const { chart: couleur, label } = describeStatus(statut)
      const part = (nombre / total) * 100
      const segment = { statut, nombre, part, couleur, label, decalage }
      decalage += part
      return segment
    })
  })

  const totalStatuts = computed(() =>
    segmentsStatut.value.reduce((somme, s) => somme + s.nombre, 0),
  )

  return {
    stats, upcoming, chart, analytics, loading, error, actionEnCours,
    charger, confirmer, annuler,
    quotaPourcent, quotaTendu,
    heuresPointe, maxHeurePointe, maxJourPointe,
    segmentsStatut, totalStatuts,
  }
}
