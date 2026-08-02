<script setup>
import { computed } from 'vue'

/**
 * L'indicateur d'attente, en un seul endroit.
 *
 * Le même bloc de onze classes utilitaires était recopié à onze endroits, avec
 * des tailles et des couleurs qui avaient fini par diverger.
 */
const props = defineProps({
  /** `xs` dans un bouton, `sm` en ligne, `md` en bloc, `lg` en plein écran. */
  size: { type: String, default: 'sm' },
  /** `current` suit la couleur du texte, `accent` prend la couleur d'action. */
  tone: { type: String, default: 'current' },
  label: { type: String, default: 'Chargement en cours' },
})

const dimensions = { xs: '14px', sm: '18px', md: '28px', lg: '44px' }
const strokes = { xs: '2px', sm: '2px', md: '3px', lg: '4px' }

const style = computed(() => ({
  '--spinner-size': dimensions[props.size] ?? dimensions.sm,
  '--spinner-stroke': strokes[props.size] ?? strokes.sm,
}))
</script>

<template>
  <span
    class="spinner"
    :class="`spinner--${tone}`"
    :style="style"
    role="status"
    :aria-label="label"
  />
</template>

<style scoped>
.spinner {
  display: inline-block;
  width: var(--spinner-size);
  height: var(--spinner-size);
  border-radius: 999px;
  border: var(--spinner-stroke) solid currentColor;
  border-top-color: transparent;
  opacity: 0.85;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}

.spinner--accent {
  color: theme('colors.primary.600');
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/*
 * Une rotation continue est exactement ce que « mouvement réduit » vise. On
 * garde un signal — la pulsation d'opacité — sans faire tourner quoi que ce
 * soit.
 */
@media (prefers-reduced-motion: reduce) {
  .spinner {
    animation: pulse 1.4s ease-in-out infinite;
    border-top-color: currentColor;
  }

  @keyframes pulse {
    0%, 100% { opacity: 0.25; }
    50%      { opacity: 0.9; }
  }
}
</style>
