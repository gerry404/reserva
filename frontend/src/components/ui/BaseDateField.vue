<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import {
  addDays, addMonths, endOfMonth, format, isAfter, isBefore, isSameDay,
  isSameMonth, parseISO, startOfMonth, startOfWeek,
} from 'date-fns'
import { fr } from 'date-fns/locale'
import { CalendarDays, ChevronLeft, ChevronRight, X } from 'lucide-vue-next'

/**
 * Un champ de date, avec son calendrier.
 *
 * `<input type="date">` est dessiné par le navigateur : sur Chrome un champ
 * gris avec une icône bleue, sur Firefox un autre, sur Safari iOS une roulette
 * plein écran. Aucun de ces trois ne suit la palette, et le format affiché
 * dépend de la langue du système — un commerçant camerounais pouvait voir
 * mm/dd/yyyy dans une interface entièrement en français.
 *
 * Le calendrier est écrit ici pour trois raisons qu'aucune feuille de style ne
 * réglait : la semaine commence lundi, les mois et les jours sont en français,
 * et la valeur reste au format ISO côté modèle pendant que l'affichage reste
 * lisible.
 *
 * Le clavier fait ce qu'un calendrier doit faire : les flèches déplacent d'un
 * jour, Page précédente et suivante d'un mois, Entrée choisit, Échap ferme.
 */

const props = defineProps({
  /** Toujours ISO `yyyy-MM-dd`, ou chaîne vide. Le format d'affichage ne fuit pas dans le modèle. */
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Toutes les dates' },
  /** Bornes ISO, incluses. */
  min: { type: String, default: null },
  max: { type: String, default: null },
  clearable: { type: Boolean, default: true },
  disabled: { type: Boolean, default: false },
  id: { type: String, default: null },
  ariaLabel: { type: String, default: null },
})

const emit = defineEmits(['update:modelValue'])

const JOURS = ['lun', 'mar', 'mer', 'jeu', 'ven', 'sam', 'dim']

function versDate(iso) {
  if (!iso) return null
  try {
    const d = parseISO(iso)
    return Number.isNaN(d.getTime()) ? null : d
  } catch {
    return null
  }
}

const selected = computed(() => versDate(props.modelValue))
const borneMin = computed(() => versDate(props.min))
const borneMax = computed(() => versDate(props.max))

const open = ref(false)
const root = ref(null)
const trigger = ref(null)
const panneau = ref(null)
const dropUp = ref(false)

/** Le mois affiché, et le jour sous le curseur clavier. */
const moisVu = ref(startOfMonth(selected.value ?? new Date()))
const curseur = ref(selected.value ?? new Date())

const libelle = computed(() =>
  selected.value ? format(selected.value, 'd MMMM yyyy', { locale: fr }) : null,
)

const titreMois = computed(() => format(moisVu.value, 'MMMM yyyy', { locale: fr }))

/**
 * Les six semaines du mois affiché.
 *
 * Toujours six, jamais cinq : une grille dont la hauteur change d'un mois à
 * l'autre fait sauter le panneau et déplace les boutons sous le curseur.
 */
const semaines = computed(() => {
  const debut = startOfWeek(startOfMonth(moisVu.value), { weekStartsOn: 1 })
  return Array.from({ length: 6 }, (_, s) =>
    Array.from({ length: 7 }, (_, j) => addDays(debut, s * 7 + j)),
  )
})

function horsBornes(d) {
  if (borneMin.value && isBefore(d, borneMin.value) && !isSameDay(d, borneMin.value)) return true
  if (borneMax.value && isAfter(d, borneMax.value) && !isSameDay(d, borneMax.value)) return true
  return false
}

function ouvrir() {
  if (props.disabled || open.value) return
  open.value = true
  curseur.value = selected.value ?? new Date()
  moisVu.value = startOfMonth(curseur.value)
  nextTick(positionner)
}

function fermer({ rendreFocus = true } = {}) {
  if (!open.value) return
  open.value = false
  if (rendreFocus) trigger.value?.focus()
}

function choisir(d) {
  if (horsBornes(d)) return
  emit('update:modelValue', format(d, 'yyyy-MM-dd'))
  fermer()
}

function effacer() {
  emit('update:modelValue', '')
  fermer({ rendreFocus: false })
}

function aujourdhui() {
  const d = new Date()
  if (horsBornes(d)) return
  choisir(d)
}

function changerMois(pas) {
  moisVu.value = addMonths(moisVu.value, pas)
}

function positionner() {
  const r = trigger.value?.getBoundingClientRect()
  if (!r) return
  const hauteur = panneau.value?.offsetHeight ?? 340
  dropUp.value = r.bottom + hauteur + 8 > window.innerHeight && r.top > hauteur
}

// ── Clavier ────────────────────────────────────────────────────────────────

function deplacer(jours) {
  const suivant = addDays(curseur.value, jours)
  if (horsBornes(suivant)) return
  curseur.value = suivant
  // Le mois suit le curseur, sinon la flèche sort de la grille visible.
  if (!isSameMonth(suivant, moisVu.value)) moisVu.value = startOfMonth(suivant)
}

function surTouche(e) {
  if (props.disabled) return

  if (!open.value) {
    if (['ArrowDown', 'Enter', ' '].includes(e.key)) {
      e.preventDefault()
      ouvrir()
    }
    return
  }

  switch (e.key) {
    case 'ArrowLeft':  e.preventDefault(); deplacer(-1); break
    case 'ArrowRight': e.preventDefault(); deplacer(1); break
    case 'ArrowUp':    e.preventDefault(); deplacer(-7); break
    case 'ArrowDown':  e.preventDefault(); deplacer(7); break
    case 'PageUp':     e.preventDefault(); changerMois(-1); break
    case 'PageDown':   e.preventDefault(); changerMois(1); break
    case 'Home':       e.preventDefault(); curseur.value = startOfMonth(moisVu.value); break
    case 'End':        e.preventDefault(); curseur.value = endOfMonth(moisVu.value); break
    case 'Enter':
    case ' ':          e.preventDefault(); choisir(curseur.value); break
    case 'Escape':     e.preventDefault(); fermer(); break
    case 'Tab':        fermer({ rendreFocus: false }); break
  }
}

// ── Fermeture au clic extérieur ────────────────────────────────────────────

function surClicDocument(e) {
  if (open.value && root.value && !root.value.contains(e.target)) {
    fermer({ rendreFocus: false })
  }
}

watch(open, (ouvert) => {
  if (ouvert) {
    document.addEventListener('mousedown', surClicDocument)
    window.addEventListener('resize', positionner)
    window.addEventListener('scroll', positionner, true)
  } else {
    document.removeEventListener('mousedown', surClicDocument)
    window.removeEventListener('resize', positionner)
    window.removeEventListener('scroll', positionner, true)
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', surClicDocument)
  window.removeEventListener('resize', positionner)
  window.removeEventListener('scroll', positionner, true)
})
</script>

<template>
  <div ref="root" class="dp" :class="{ 'is-open': open, 'is-disabled': disabled }">
    <button
      :id="id"
      ref="trigger"
      type="button"
      class="dp__trigger"
      :aria-expanded="open"
      aria-haspopup="dialog"
      :aria-label="ariaLabel || undefined"
      :disabled="disabled"
      @click="open ? fermer() : ouvrir()"
      @keydown="surTouche"
    >
      <CalendarDays class="dp__icon" :size="16" />
      <span :class="['dp__value', { 'is-placeholder': !libelle }]">{{ libelle ?? placeholder }}</span>

      <span
        v-if="clearable && modelValue"
        class="dp__clear"
        role="button"
        tabindex="-1"
        aria-label="Effacer la date"
        @click.stop="effacer"
      >
        <X :size="14" />
      </span>
    </button>

    <Transition name="dp-pop">
      <div
        v-if="open"
        ref="panneau"
        class="dp__panel"
        :class="{ 'is-up': dropUp }"
        role="dialog"
        :aria-label="`Calendrier, ${titreMois}`"
      >
        <div class="dp__head">
          <button type="button" class="dp__nav" aria-label="Mois précédent" @click="changerMois(-1)">
            <ChevronLeft :size="16" />
          </button>
          <span class="dp__month">{{ titreMois }}</span>
          <button type="button" class="dp__nav" aria-label="Mois suivant" @click="changerMois(1)">
            <ChevronRight :size="16" />
          </button>
        </div>

        <div class="dp__weekdays" aria-hidden="true">
          <span v-for="j in JOURS" :key="j">{{ j }}</span>
        </div>

        <div class="dp__grid" role="grid">
          <div v-for="(semaine, s) in semaines" :key="s" class="dp__week" role="row">
            <button
              v-for="d in semaine"
              :key="d.toISOString()"
              type="button"
              role="gridcell"
              class="dp__day"
              :class="{
                'is-outside': !isSameMonth(d, moisVu),
                'is-today': isSameDay(d, new Date()),
                'is-selected': selected && isSameDay(d, selected),
                'is-cursor': isSameDay(d, curseur),
              }"
              :disabled="horsBornes(d)"
              :aria-selected="selected ? isSameDay(d, selected) : false"
              :tabindex="-1"
              @click="choisir(d)"
            >
              {{ d.getDate() }}
            </button>
          </div>
        </div>

        <div class="dp__foot">
          <button type="button" class="dp__action" @click="aujourdhui">Aujourd'hui</button>
          <button v-if="clearable && modelValue" type="button" class="dp__action" @click="effacer">
            Effacer
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.dp {
  position: relative;
  width: 100%;
}

.dp__trigger {
  display: flex;
  align-items: center;
  gap: 9px;
  width: 100%;
  padding: 10px 14px;
  border: 1px solid var(--clay-200);
  border-radius: var(--radius-control);
  background: var(--clay-50);
  font-size: 14px;
  font-weight: 500;
  color: var(--clay-950);
  text-align: left;
  transition: border-color 0.16s ease, box-shadow 0.16s ease;
}

.dp__trigger:hover:not(:disabled) {
  border-color: var(--clay-300);
}

.dp__trigger:focus-visible,
.dp.is-open .dp__trigger {
  outline: none;
  border-color: var(--forest-500);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--forest-500) 18%, transparent);
}

.dp.is-disabled .dp__trigger {
  opacity: 0.55;
  cursor: not-allowed;
}

.dp__icon {
  flex-shrink: 0;
  color: var(--clay-400);
}

.dp__value {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dp__value.is-placeholder {
  color: var(--clay-400);
  font-weight: 400;
}

.dp__clear {
  display: inline-flex;
  flex-shrink: 0;
  padding: 3px;
  border-radius: var(--radius-control);
  color: var(--clay-400);
  cursor: pointer;
}

.dp__clear:hover {
  background: var(--clay-200);
  color: var(--clay-700);
}

.dp__panel {
  position: absolute;
  z-index: 50;
  left: 0;
  top: calc(100% + 6px);
  width: 290px;
  padding: 14px;
  border: 1px solid var(--clay-200);
  border-radius: var(--radius-surface);
  background: #fff;
  box-shadow: 0 12px 32px -8px rgb(31 27 22 / 0.18), 0 2px 6px rgb(31 27 22 / 0.06);
}

.dp__panel.is-up {
  top: auto;
  bottom: calc(100% + 6px);
}

.dp__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.dp__month {
  font-size: 14px;
  font-weight: 700;
  color: var(--forest-950);
  text-transform: capitalize;
}

.dp__nav {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: var(--radius-control);
  color: var(--clay-500);
  transition: background-color 0.15s ease, color 0.15s ease;
}

.dp__nav:hover {
  background: var(--clay-100);
  color: var(--forest-800);
}

.dp__weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  margin-bottom: 4px;
}

.dp__weekdays span {
  padding: 4px 0;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-align: center;
  text-transform: uppercase;
  color: var(--clay-400);
}

.dp__week {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
}

.dp__day {
  aspect-ratio: 1;
  border-radius: var(--radius-control);
  font-size: 13px;
  font-variant-numeric: tabular-nums;
  color: var(--clay-800);
  transition: background-color 0.13s ease, color 0.13s ease;
}

.dp__day:hover:not(:disabled) {
  background: var(--clay-100);
}

/* Les jours du mois voisin restent lisibles mais s'effacent : les masquer
   ferait des trous dans la grille et casserait le repère des colonnes. */
.dp__day.is-outside {
  color: var(--clay-300);
}

.dp__day:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.dp__day.is-today {
  font-weight: 700;
  color: var(--forest-700);
}

.dp__day.is-cursor {
  box-shadow: inset 0 0 0 1px var(--forest-300);
}

.dp__day.is-selected {
  background: var(--forest-600);
  color: #fff;
  font-weight: 700;
}

.dp__day.is-selected:hover {
  background: var(--forest-700);
}

.dp__foot {
  display: flex;
  gap: 8px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid var(--clay-100);
}

.dp__action {
  padding: 6px 12px;
  border-radius: var(--radius-control);
  font-size: 12px;
  font-weight: 600;
  color: var(--forest-700);
  transition: background-color 0.15s ease;
}

.dp__action:hover {
  background: var(--forest-50);
}

.dp-pop-enter-active,
.dp-pop-leave-active {
  transition: opacity 0.14s ease, transform 0.14s cubic-bezier(0.22, 1, 0.36, 1);
}

.dp-pop-enter-from,
.dp-pop-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

@media (prefers-reduced-motion: reduce) {
  .dp-pop-enter-active,
  .dp-pop-leave-active,
  .dp__day,
  .dp__nav {
    transition: none;
  }
}
</style>
