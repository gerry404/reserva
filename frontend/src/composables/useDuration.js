import { computed, unref } from 'vue'

/**
 * Le temps comme matière.
 *
 * Toute l'identité visuelle de Nuvo repose sur une règle unique : ce qui dure
 * longtemps occupe plus d'espace. Une prestation de trois heures n'est pas une
 * étiquette « 3h » à côté d'une étiquette « 30 min » — elle est six fois plus
 * longue à l'écran.
 *
 * Ce n'est pas décoratif. Le défaut central que ce produit a corrigé était
 * précisément que la durée n'existait nulle part : le moteur de disponibilité
 * l'ignorait, et l'interface ne la montrait pas. La rendre visible partout est
 * la traduction visuelle de l'invariant métier.
 *
 * Toutes les mesures passent par ce module, pour qu'une barre de durée ait la
 * même échelle sur la page publique, dans le tableau de bord et dans la liste
 * des services.
 */

/** Journée de référence, en minutes : 8 h → 20 h. */
export const DAY_START = 8 * 60
export const DAY_END = 20 * 60
export const DAY_SPAN = DAY_END - DAY_START

/**
 * Durée au-delà de laquelle une barre atteint sa pleine largeur.
 *
 * Quatre heures : au-delà, l'écart cesse d'être lisible et une prestation
 * exceptionnellement longue écraserait toutes les autres.
 */
export const FULL_WIDTH_MINUTES = 240

/**
 * Largeur relative d'une durée, en pourcentage.
 *
 * Compromis entre deux échecs. En linéaire, une manucure de 30 min face à un
 * plafond de 4 h donne 12 % — un trait qu'on ne voit plus. En racine carrée,
 * l'inverse : tout se tasse entre 40 % et 100 % et une prestation de 30 min
 * paraît presque aussi longue qu'une de 4 h, ce qui détruit l'idée même.
 *
 * L'exposant 0,72 garde les courtes durées lisibles tout en préservant un
 * écart franc entre les extrêmes — 30 min tombe autour de 30 %, 4 h à 100 %.
 */
const CURVE = 0.72

export function durationWidth(minutes, { min = 14, max = 100 } = {}) {
  const value = Math.max(0, Number(minutes) || 0)
  const ratio = Math.min(1, value / FULL_WIDTH_MINUTES)

  return Math.round(min + (max - min) * ratio ** CURVE)
}

/** Position d'un horaire « HH:MM » sur la journée de référence, en %. */
export function timeOffset(time) {
  const [h, m] = String(time ?? '').split(':').map(Number)
  if (Number.isNaN(h)) return 0

  const minutes = h * 60 + (m || 0)
  return Math.max(0, Math.min(100, ((minutes - DAY_START) / DAY_SPAN) * 100))
}

/** Largeur d'une durée rapportée à la journée de référence, en %. */
export function spanWidth(minutes) {
  return Math.max(1.5, Math.min(100, ((Number(minutes) || 0) / DAY_SPAN) * 100))
}

/**
 * Les repères horaires d'un ruban : une graduation par heure pleine, étiquetée
 * toutes les deux heures pour ne pas saturer un écran de téléphone.
 */
export function rulerTicks() {
  const ticks = []

  for (let minutes = DAY_START; minutes <= DAY_END; minutes += 60) {
    const hour = minutes / 60
    ticks.push({
      hour,
      label: `${String(hour).padStart(2, '0')}h`,
      offset: ((minutes - DAY_START) / DAY_SPAN) * 100,
      major: hour % 2 === 0,
    })
  }

  return ticks
}

/**
 * Composable pour une durée réactive.
 *
 * Accepte un nombre, une ref ou un getter. `unref` seul ne déballe pas une
 * fonction : passer `() => props.minutes` — la forme idiomatique pour rester
 * réactif sur une prop — donnait silencieusement 0.
 *
 * @param {import('vue').Ref<number>|(() => number)|number} minutes
 */
export function useDuration(minutes) {
  const resolve = () => (typeof minutes === 'function' ? minutes() : unref(minutes))

  const width = computed(() => durationWidth(resolve()))
  const span = computed(() => spanWidth(resolve()))

  const label = computed(() => {
    const value = Number(resolve()) || 0
    if (value < 60) return `${value} min`

    const hours = Math.floor(value / 60)
    const rest = value % 60
    return rest ? `${hours}h${String(rest).padStart(2, '0')}` : `${hours}h`
  })

  return { width, span, label }
}
