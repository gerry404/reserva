<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { Check, ChevronDown } from 'lucide-vue-next'

/**
 * Une liste déroulante.
 *
 * Le `<select>` natif ne se style pas : son panneau est dessiné par le système
 * et ignore la palette, la police et le rayon du produit. Sur Android il
 * s'ouvre en boîte de dialogue plein écran, sur Windows en liste grise. Sept
 * champs du tableau de bord l'utilisaient, et c'était le seul endroit où
 * l'interface changeait d'apparence selon la machine.
 *
 * Ce composant redessine le panneau mais garde ce que le natif faisait bien, et
 * qu'une liste maison rate presque toujours :
 *
 *   · le clavier complet, flèches, Origine, Fin, Entrée, Échap ;
 *   · la saisie rapide, taper « je » sélectionne « jeudi » ;
 *   · les rôles ARIA, pour qu'un lecteur d'écran annonce une liste et non un
 *     amas de div ;
 *   · le retour du focus au déclencheur à la fermeture.
 *
 * Une liste déroulante qui perd ces quatre points est une régression, même si
 * elle est plus jolie.
 */

const props = defineProps({
  modelValue: { type: [String, Number, null], default: null },

  /**
   * Les choix. Chaînes simples, ou objets dont les clés sont nommées par
   * `valueKey` et `labelKey` : les appelants portent déjà des formes
   * différentes, les convertir sur place aurait fait sept boucles de plus.
   */
  options: { type: Array, required: true },
  valueKey: { type: String, default: 'value' },
  labelKey: { type: String, default: 'label' },

  placeholder: { type: String, default: 'Choisir…' },
  disabled: { type: Boolean, default: false },
  /** `sm` pour les champs serrés des horaires, `md` partout ailleurs. */
  size: { type: String, default: 'md' },
  id: { type: String, default: null },
  ariaLabel: { type: String, default: null },
})

const emit = defineEmits(['update:modelValue'])

const items = computed(() =>
  props.options.map((o) =>
    o !== null && typeof o === 'object'
      ? { value: o[props.valueKey], label: String(o[props.labelKey] ?? o[props.valueKey]) }
      : { value: o, label: String(o) },
  ),
)

const selectedIndex = computed(() => items.value.findIndex((i) => i.value === props.modelValue))
const selectedLabel = computed(() => items.value[selectedIndex.value]?.label ?? null)

const open = ref(false)
const activeIndex = ref(-1)
const root = ref(null)
const trigger = ref(null)
const list = ref(null)
/** Ouverture vers le haut quand le bas de la fenêtre est trop proche. */
const dropUp = ref(false)

const listId = computed(() => `${props.id || 'select'}-liste`)
const optionId = (i) => `${listId.value}-option-${i}`

function ouvrir() {
  if (props.disabled || open.value) return
  open.value = true
  activeIndex.value = selectedIndex.value >= 0 ? selectedIndex.value : 0

  nextTick(() => {
    positionner()
    faireDefiler()
  })
}

function fermer({ rendreFocus = true } = {}) {
  if (!open.value) return
  open.value = false
  activeIndex.value = -1
  // Sans ce retour, la tabulation repart du début du document.
  if (rendreFocus) trigger.value?.focus()
}

function choisir(i) {
  const item = items.value[i]
  if (!item) return
  emit('update:modelValue', item.value)
  fermer()
}

/**
 * Retourne le panneau vers le haut s'il déborderait.
 *
 * Mesuré au moment de l'ouverture plutôt que deviné : le même champ est en
 * milieu de page dans les paramètres et en bas de carte dans le formulaire de
 * service.
 */
function positionner() {
  const r = trigger.value?.getBoundingClientRect()
  if (!r) return
  const hauteur = list.value?.offsetHeight ?? 240
  dropUp.value = r.bottom + hauteur + 8 > window.innerHeight && r.top > hauteur
}

function faireDefiler() {
  const el = list.value?.querySelector('[data-active="true"]')
  el?.scrollIntoView({ block: 'nearest' })
}

watch(activeIndex, () => nextTick(faireDefiler))

// ── Clavier ────────────────────────────────────────────────────────────────

function deplacer(pas) {
  const n = items.value.length
  if (!n) return
  activeIndex.value = (activeIndex.value + pas + n) % n
}

function surTouche(e) {
  if (props.disabled) return

  if (!open.value) {
    if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) {
      e.preventDefault()
      ouvrir()
    }
    return
  }

  switch (e.key) {
    case 'ArrowDown': e.preventDefault(); deplacer(1); break
    case 'ArrowUp':   e.preventDefault(); deplacer(-1); break
    case 'Home':      e.preventDefault(); activeIndex.value = 0; break
    case 'End':       e.preventDefault(); activeIndex.value = items.value.length - 1; break
    case 'Enter':
    case ' ':         e.preventDefault(); choisir(activeIndex.value); break
    case 'Escape':    e.preventDefault(); fermer(); break
    case 'Tab':       fermer({ rendreFocus: false }); break
    default:          saisieRapide(e); break
  }
}

/**
 * Taper les premières lettres amène sur l'entrée correspondante.
 *
 * Le tampon se vide après une seconde de silence, comme le fait le `<select>`
 * natif : sans cela, deux recherches successives se concatènent et la seconde
 * ne trouve plus rien.
 */
let tampon = ''
let tamponTimer = null

function saisieRapide(e) {
  if (e.key.length !== 1 || e.metaKey || e.ctrlKey || e.altKey) return

  tampon += e.key.toLowerCase()
  clearTimeout(tamponTimer)
  tamponTimer = setTimeout(() => { tampon = '' }, 1000)

  const i = items.value.findIndex((it) => it.label.toLowerCase().startsWith(tampon))
  if (i >= 0) activeIndex.value = i
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
    // `capture` : un conteneur défilant ne remonte pas l'évènement.
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
  clearTimeout(tamponTimer)
})
</script>

<template>
  <div ref="root" class="sel" :class="[`sel--${size}`, { 'is-open': open, 'is-disabled': disabled }]">
    <button
      :id="id"
      ref="trigger"
      type="button"
      class="sel__trigger"
      role="combobox"
      :aria-expanded="open"
      :aria-controls="listId"
      :aria-activedescendant="open && activeIndex >= 0 ? optionId(activeIndex) : undefined"
      :aria-label="ariaLabel || undefined"
      :disabled="disabled"
      @click="open ? fermer() : ouvrir()"
      @keydown="surTouche"
    >
      <span :class="['sel__value', { 'is-placeholder': selectedLabel === null }]">
        {{ selectedLabel ?? placeholder }}
      </span>
      <ChevronDown class="sel__chevron" :size="16" />
    </button>

    <Transition name="sel-pop">
      <ul
        v-if="open"
        :id="listId"
        ref="list"
        class="sel__list"
        :class="{ 'is-up': dropUp }"
        role="listbox"
        :aria-label="ariaLabel || placeholder"
      >
        <li
          v-for="(item, i) in items"
          :id="optionId(i)"
          :key="item.value"
          class="sel__option"
          :class="{ 'is-active': i === activeIndex, 'is-selected': i === selectedIndex }"
          :data-active="i === activeIndex"
          role="option"
          :aria-selected="i === selectedIndex"
          @click="choisir(i)"
          @mousemove="activeIndex = i"
        >
          <span class="sel__option-label">{{ item.label }}</span>
          <Check v-if="i === selectedIndex" class="sel__tick" :size="15" />
        </li>
      </ul>
    </Transition>
  </div>
</template>

<style scoped>
.sel {
  position: relative;
  width: 100%;
}

.sel__trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
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

.sel--sm .sel__trigger {
  padding: 6px 12px;
  font-size: 12px;
}

.sel__trigger:hover:not(:disabled) {
  border-color: var(--clay-300);
}

.sel__trigger:focus-visible,
.sel.is-open .sel__trigger {
  outline: none;
  border-color: var(--forest-500);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--forest-500) 18%, transparent);
}

.sel.is-disabled .sel__trigger {
  opacity: 0.55;
  cursor: not-allowed;
}

.sel__value {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sel__value.is-placeholder {
  color: var(--clay-400);
  font-weight: 400;
}

.sel__chevron {
  flex-shrink: 0;
  color: var(--clay-400);
  transition: transform 0.18s ease;
}

.sel.is-open .sel__chevron {
  transform: rotate(180deg);
}

.sel__list {
  position: absolute;
  z-index: 50;
  left: 0;
  right: 0;
  top: calc(100% + 6px);
  max-height: 264px;
  margin: 0;
  padding: 6px;
  overflow-y: auto;
  list-style: none;
  border: 1px solid var(--clay-200);
  border-radius: var(--radius-surface);
  background: #fff;
  box-shadow: 0 12px 32px -8px rgb(31 27 22 / 0.18), 0 2px 6px rgb(31 27 22 / 0.06);
}

.sel__list.is-up {
  top: auto;
  bottom: calc(100% + 6px);
}

.sel__option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 9px 12px;
  border-radius: calc(var(--radius-surface) - 8px);
  font-size: 14px;
  color: var(--clay-800);
  cursor: pointer;
}

.sel--sm .sel__option {
  padding: 7px 10px;
  font-size: 12px;
}

/*
 * Le survol ne colore rien de lui-même : c'est `activeIndex` qui porte la
 * position courante, et la souris ne fait que le déplacer. Sinon le clavier et
 * la souris désignent deux entrées différentes en même temps.
 */
.sel__option.is-active {
  background: var(--forest-50);
  color: var(--forest-900);
}

.sel__option.is-selected {
  font-weight: 600;
  color: var(--forest-800);
}

.sel__option-label {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sel__tick {
  flex-shrink: 0;
  color: var(--forest-600);
}

.sel-pop-enter-active,
.sel-pop-leave-active {
  transition: opacity 0.14s ease, transform 0.14s cubic-bezier(0.22, 1, 0.36, 1);
}

.sel-pop-enter-from,
.sel-pop-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.sel__list.is-up.sel-pop-enter-from,
.sel__list.is-up.sel-pop-leave-to {
  transform: translateY(4px);
}

@media (prefers-reduced-motion: reduce) {
  .sel__chevron,
  .sel-pop-enter-active,
  .sel-pop-leave-active {
    transition: none;
  }
}
</style>
