<script setup>
import { computed, ref } from 'vue'
import { ChartColumn } from 'lucide-vue-next'

/**
 * Le volume de réservations, au jour ou au mois.
 *
 * Dessiné en barres CSS et non par une bibliothèque de graphiques : il n'y a
 * qu'une série et pas d'interaction, et le moindre paquet de courbes pèse plus
 * lourd que tout le reste de cet écran.
 */
const props = defineProps({
  chart: { type: Object, default: null },
})

const echelle = ref('daily')

const ECHELLES = [
  { value: 'daily', title: '30 jours' },
  { value: 'monthly', title: '12 mois' },
]

const serie = computed(() => {
  if (!props.chart) return { labels: [], values: [] }
  return props.chart[echelle.value] ?? { labels: [], values: [] }
})

const maximum = computed(() => Math.max(...(serie.value.values ?? [0]), 1))

const garni = computed(() => (serie.value.values ?? []).some((v) => v > 0))

/**
 * La hauteur d'une barre, en pourcentage.
 *
 * Le plancher à 2 % garde une trace visible pour un jour à zéro : sans lui, la
 * barre disparaît et l'axe se lit comme un trou dans les données plutôt que
 * comme une journée creuse.
 */
function hauteur(valeur) {
  return valeur === 0 ? 2 : Math.max(6, Math.round((valeur / maximum.value) * 100))
}

/** Un libellé sur cinq : les trente afficheraient une bouillie illisible. */
function visible(index) {
  const pas = echelle.value === 'daily' ? 5 : 1
  return index % pas === 0
}
</script>

<template>
  <v-card class="pa-5 d-flex flex-column">
    <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-5">
      <h3 class="text-subtitle-2 font-weight-bold mb-0">Volume de réservations</h3>
      <v-btn-toggle
        v-model="echelle"
        mandatory
        density="compact"
        rounded="control"
        color="primary"
        variant="outlined"
      >
        <v-btn v-for="e in ECHELLES" :key="e.value" :value="e.value" size="small" class="text-none px-4">
          {{ e.title }}
        </v-btn>
      </v-btn-toggle>
    </div>

    <div v-if="garni" class="chart">
      <div
        v-for="(valeur, i) in serie.values"
        :key="i"
        class="chart__col"
        :title="`${serie.labels[i]} : ${valeur}`"
      >
        <div class="chart__track">
          <div
            class="chart__bar"
            :class="{ 'is-empty': valeur === 0 }"
            :style="{ height: `${hauteur(valeur)}%` }"
          />
        </div>
        <span class="chart__tick">{{ visible(i) ? serie.labels[i] : '' }}</span>
      </div>
    </div>

    <div v-else class="chart__empty">
      <ChartColumn :size="26" />
      <p class="text-body-2 mt-3 mb-0">Aucune réservation sur cette période</p>
    </div>
  </v-card>
</template>

<style scoped>
.chart {
  display: flex;
  align-items: flex-end;
  gap: 3px;
  min-height: 180px;
}

/* Une largeur plafonnée : sur une série de sept points, des colonnes en
   `flex: 1` devenaient des dalles larges de cent pixels. */
.chart__col {
  flex: 1 1 0;
  max-width: 46px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  min-width: 0;
}

.chart__track {
  display: flex;
  align-items: flex-end;
  width: 100%;
  height: 160px;
}

.chart__bar {
  width: 100%;
  border-radius: 4px 4px 2px 2px;
  background: var(--forest-500);
  transition: background-color 0.15s ease;
}

.chart__col:hover .chart__bar { background: var(--forest-700); }

.chart__bar.is-empty { background: var(--clay-200); }

.chart__tick {
  font-family: 'Yuzo', Roboto, monospace;
  font-size: 9px;
  color: var(--clay-400);
  white-space: nowrap;
}

.chart__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 180px;
  color: var(--clay-400);
}

@media (prefers-reduced-motion: reduce) {
  .chart__bar { transition: none; }
}
</style>
