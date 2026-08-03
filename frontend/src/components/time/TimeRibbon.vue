<script setup>
import { computed } from 'vue'
import { rulerTicks, spanWidth, timeOffset } from '@/composables/useDuration'

/**
 * Une journée vue comme un ruban continu.
 *
 * Remplace la grille de créneaux, qui présentait toutes les heures comme
 * équivalentes et masquait ce qui compte : la journée est déjà occupée à tel
 * endroit, et il reste tel bloc libre. Un client voit d'un coup d'œil si le
 * salon est plein, et où se logerait sa prestation.
 *
 * Le ruban est aussi la réponse visuelle au bug fondateur du produit : avant,
 * une réservation de 3 h n'occupait qu'un point. Ici, elle occupe trois heures.
 */
const props = defineProps({
  /** @type {{ start: string, minutes: number, label?: string }[]} */
  busy: { type: Array, default: () => [] },
  /** Créneaux libres, mêmes clés. */
  free: { type: Array, default: () => [] },
  /** Prestation en cours de sélection, mise en avant. */
  selected: { type: Object, default: null },
  compact: { type: Boolean, default: false },
})

const ticks = rulerTicks()

const blocks = computed(() => [
  ...props.busy.map((b) => ({ ...b, kind: 'busy' })),
  ...props.free.map((b) => ({ ...b, kind: 'free' })),
])

function position(block) {
  return {
    left: `${timeOffset(block.start)}%`,
    width: `${spanWidth(block.minutes)}%`,
  }
}
</script>

<template>
  <div class="ribbon" :class="{ 'ribbon--compact': compact }">
    <!-- Graduation : le repère qui rend les longueurs lisibles. -->
    <div class="ribbon__ruler" aria-hidden="true">
      <span
        v-for="tick in ticks"
        :key="tick.hour"
        class="ribbon__tick"
        :class="{ 'ribbon__tick--major': tick.major }"
        :style="{ left: `${tick.offset}%` }"
      >
        <span v-if="tick.major && !compact" class="ribbon__tick-label">{{ tick.label }}</span>
      </span>
    </div>

    <div class="ribbon__track">
      <div
        v-for="(block, i) in blocks"
        :key="`${block.kind}-${block.start}-${i}`"
        class="ribbon__block"
        :class="`ribbon__block--${block.kind}`"
        :style="position(block)"
        :title="block.label"
      />

      <!-- La prestation choisie, posée par-dessus : on voit exactement la
           place qu'elle prendra dans la journée. -->
      <div
        v-if="selected"
        class="ribbon__block ribbon__block--selected"
        :style="position(selected)"
      />
    </div>
  </div>
</template>

<style scoped>
.ribbon {
  --ribbon-height: 30px;
  width: 100%;
  user-select: none;
}

.ribbon--compact {
  --ribbon-height: 12px;
}

.ribbon__ruler {
  position: relative;
  height: 14px;
  margin-bottom: 4px;
}

.ribbon__tick {
  position: absolute;
  top: 6px;
  width: 1px;
  height: 5px;
  background: rgb(0 0 0 / 0.12);
}

.ribbon__tick--major {
  top: 3px;
  height: 8px;
  background: rgb(0 0 0 / 0.22);
}

.ribbon__tick-label {
  position: absolute;
  top: -11px;
  left: 50%;
  transform: translateX(-50%);
  font-family: 'Yuzo', Roboto, monospace;
  font-size: 9px;
  font-variant-numeric: tabular-nums;
  color: rgb(0 0 0 / 0.35);
}

.ribbon__track {
  position: relative;
  height: var(--ribbon-height);
  border-radius: 6px;
  background: rgb(0 0 0 / 0.04);
  overflow: hidden;
}

.ribbon__block {
  position: absolute;
  top: 0;
  height: 100%;
  border-radius: 4px;
}

/* Occupé : une matière dense, hachurée. La texture dit « plein » sans
   dépendre de la couleur seule — lisible aussi en daltonisme. */
.ribbon__block--busy {
  background: repeating-linear-gradient(
    -45deg,
    rgb(0 0 0 / 0.28) 0 3px,
    rgb(0 0 0 / 0.14) 3px 6px
  );
}

.ribbon__block--free {
  background: color-mix(in srgb, var(--accent, var(--forest-600)) 16%, transparent);
}

.ribbon__block--selected {
  background: var(--accent, var(--forest-600));
  box-shadow: 0 0 0 2px #fff, 0 2px 8px rgb(0 0 0 / 0.18);
  z-index: 1;
  animation: ribbon-settle 0.35s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes ribbon-settle {
  from { transform: scaleX(0.4); opacity: 0; }
  to   { transform: scaleX(1); opacity: 1; }
}

@media (prefers-reduced-motion: reduce) {
  .ribbon__block--selected { animation: none; }
}
</style>
