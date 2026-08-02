<script setup>
import { computed } from 'vue'
import { durationWidth, useDuration } from '@/composables/useDuration'

/**
 * La durée d'une prestation, rendue comme une longueur.
 *
 * C'est la primitive de l'identité : partout où une durée est affichée, elle
 * occupe un espace proportionnel. Le client comprend qu'une pose de tresses
 * mobilise son après-midi avant même d'avoir lu « 3h ».
 */
const props = defineProps({
  minutes: { type: Number, required: true },
  color: { type: String, default: null },
  /** `sm` dans une liste, `md` en résumé, `lg` en vedette. */
  size: { type: String, default: 'md' },
  showLabel: { type: Boolean, default: false },
})

const { label } = useDuration(() => props.minutes)

const width = computed(() => durationWidth(props.minutes))

const heights = { sm: '3px', md: '5px', lg: '8px' }
const height = computed(() => heights[props.size] ?? heights.md)
</script>

<template>
  <div class="duration" :style="{ '--bar-height': height, '--bar-color': color || 'var(--accent, var(--forest-600))' }">
    <div class="duration__track">
      <div class="duration__fill" :style="{ width: `${width}%` }" />
    </div>
    <span v-if="showLabel" class="duration__label">{{ label }}</span>
  </div>
</template>

<style scoped>
.duration {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  /* La piste se centre sur la ligne du texte voisin plutôt que de flotter
     au-dessus. */
  min-height: 1em;
}

.duration__track {
  flex: 1;
  height: var(--bar-height);
  border-radius: 999px;
  background: rgb(0 0 0 / 0.06);
  overflow: hidden;
}

.duration__fill {
  height: 100%;
  border-radius: 999px;
  background: var(--bar-color);
  /* La barre se déploie plutôt que d'apparaître : le mouvement dit « ceci est
     une longueur », pas « ceci est une jauge de progression ». */
  transform-origin: left;
  animation: duration-grow 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.duration__label {
  font-family: 'Yuzo', Roboto, monospace;
  font-size: 11px;
  font-variant-numeric: tabular-nums;
  color: rgb(0 0 0 / 0.45);
  white-space: nowrap;
}

@keyframes duration-grow {
  from { transform: scaleX(0); }
  to   { transform: scaleX(1); }
}

@media (prefers-reduced-motion: reduce) {
  .duration__fill { animation: none; }
}
</style>
