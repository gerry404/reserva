import { computed, unref } from 'vue'
import { DAY_END, DAY_START, DAY_SPAN } from '@/composables/useDuration'

/**
 * La signature d'un commerce, dérivée de ses horaires réels.
 *
 * Chaque page publique porte un motif d'en-tête qui n'appartient qu'à elle :
 * une bande par jour de la semaine, positionnée et dimensionnée par les heures
 * d'ouverture. Un salon ouvert six jours de 8h à 19h ne ressemble pas à un
 * cabinet ouvert quatre jours de 9h à 17h.
 *
 * Le choix de dériver le motif des horaires plutôt que d'un hash aléatoire est
 * délibéré. Un motif tiré au sort serait de la décoration : unique, mais sans
 * rapport avec ce que le commerce est. Ici la signature *dit* quelque chose,
 * elle prolonge le langage du temps établi ailleurs dans le produit au lieu de
 * le concurrencer, et elle reste stable : les mêmes horaires donnent toujours
 * le même dessin.
 */

const DAYS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche']

/** Minutes depuis minuit pour une heure « HH:MM ». */
function toMinutes(time) {
  const [h, m] = String(time ?? '').split(':').map(Number)
  return Number.isNaN(h) ? null : h * 60 + (m || 0)
}

/**
 * Traduit les horaires en bandes verticales.
 *
 * Chaque bande porte sa position dans la semaine (x) et l'amplitude
 * d'ouverture de ce jour, ramenée à la journée de référence (top, height).
 * Un jour fermé produit une bande nulle : c'est le vide qui dessine autant que
 * le plein.
 *
 * @returns {{ day: string, x: number, top: number, height: number, open: boolean }[]}
 */
export function rhythmBands(workingHours) {
  if (!workingHours) return []

  return DAYS.map((day, index) => {
    const hours = workingHours[day]
    const x = index / DAYS.length

    if (!hours?.is_open) {
      return { day, x, top: 0, height: 0, open: false }
    }

    const opens = toMinutes(hours.open)
    const closes = toMinutes(hours.close)

    if (opens === null || closes === null || closes <= opens) {
      return { day, x, top: 0, height: 0, open: false }
    }

    // Ramené à la journée de référence (8h–20h), borné pour qu'un commerce
    // ouvert 24h ne déborde pas du cadre.
    const top = Math.max(0, Math.min(1, (opens - DAY_START) / DAY_SPAN))
    const bottom = Math.max(0, Math.min(1, (closes - DAY_START) / DAY_SPAN))

    return { day, x, top, height: Math.max(0.06, bottom - top), open: true }
  })
}

/**
 * Le motif complet, en SVG inline.
 *
 * Inline plutôt que fichier : le dessin dépend de données que seul le client
 * connaît au moment du rendu, et le SVG pèse moins d'un kilooctet, une requête
 * supplémentaire coûterait davantage que le balisage lui-même, surtout en 3G.
 *
 * Le motif se répète horizontalement : la semaine devient une trame continue,
 * assez discrète pour rester un fond et assez marquée pour être reconnue d'une
 * page à l'autre.
 */
export function useRhythm(business) {
  const bands = computed(() => rhythmBands(unref(business)?.working_hours))

  /** Nombre de jours ouverts : détermine la densité du motif. */
  const openDays = computed(() => bands.value.filter((b) => b.open).length)

  /**
   * Largeur des bandes.
   *
   * Un commerce ouvert tous les jours a des bandes fines et serrées ; un
   * commerce ouvert quatre jours a des bandes larges et espacées. La densité
   * elle-même devient une caractéristique reconnaissable.
   */
  const bandWidth = computed(() => {
    const count = openDays.value || 1
    return 0.34 + (7 - count) * 0.055
  })

  /**
   * Le SVG, encodé pour une propriété CSS background-image.
   *
   * Les couleurs sont laissées en `currentColor` translucide : le motif hérite
   * ainsi de la teinte du conteneur, et le contraste reste sous le contrôle de
   * useAccent plutôt que d'être fixé ici.
   */
  const patternUrl = computed(() => {
    const visible = bands.value.filter((b) => b.open)
    if (!visible.length) return null

    const width = 280
    const height = 160
    const step = width / DAYS.length

    const rects = visible
      .map((band) => {
        const x = (band.x * width).toFixed(1)
        const w = (step * bandWidth.value).toFixed(1)
        const y = (band.top * height).toFixed(1)
        const h = (band.height * height).toFixed(1)
        return `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${(Number(w) / 2).toFixed(1)}"/>`
      })
      .join('')

    const svg =
      `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" ` +
      `viewBox="0 0 ${width} ${height}" fill="rgba(255,255,255,.13)">${rects}</svg>`

    // encodeURIComponent plutôt que base64 : plus court pour du SVG, et
    // lisible dans l'inspecteur.
    return `url("data:image/svg+xml,${encodeURIComponent(svg)}")`
  })

  /** Description textuelle, pour les lecteurs d'écran et le title. */
  const description = computed(() => {
    const count = openDays.value
    if (!count) return ''
    return `Ouvert ${count} jour${count > 1 ? 's' : ''} par semaine`
  })

  return { bands, openDays, bandWidth, patternUrl, description }
}
