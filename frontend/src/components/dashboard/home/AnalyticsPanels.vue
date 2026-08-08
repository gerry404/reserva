<script setup>
import { computed } from 'vue'
import { Lock } from 'lucide-vue-next'
import { iconForService } from '@/constants/serviceIcons'

/**
 * Les analyses réservées au plan Pro.
 *
 * Répartition par statut, heures et jours d'affluence, recette par prestation.
 * L'API répond 402 sur un plan gratuit : le bloc dit alors ce qu'il montrerait,
 * au lieu de disparaître sans explication.
 */
const props = defineProps({
  analytics: { type: Object, default: null },
  segments: { type: Array, default: () => [] },
  totalStatuts: { type: Number, default: 0 },
  heuresPointe: { type: Array, default: () => [] },
  maxHeurePointe: { type: Number, default: 1 },
  maxJourPointe: { type: Number, default: 1 },
})

const disponible = computed(() => Boolean(props.analytics))

/** La circonférence de l'anneau, pour convertir un pourcentage en pointillé. */
const CIRCONFERENCE = 2 * Math.PI * 42

function tiret(part) {
  return `${(part / 100) * CIRCONFERENCE} ${CIRCONFERENCE}`
}

function decalage(part) {
  return -((part / 100) * CIRCONFERENCE)
}

function montant(valeur) {
  return Number(valeur ?? 0).toLocaleString('fr-FR')
}
</script>

<template>
  <v-card v-if="!disponible" class="pa-8 text-center">
    <span class="locked__icon"><Lock :size="22" /></span>
    <h3 class="text-subtitle-1 font-weight-bold mt-4 mb-2">Analyses détaillées</h3>
    <p class="text-body-2 text-medium-emphasis mb-5">
      Vos heures d'affluence, vos jours les plus chargés et la recette par
      prestation. Réservé au plan Pro.
    </p>
    <v-btn :to="{ name: 'billing' }" color="primary" variant="flat" class="text-none px-5">
      Découvrir le plan Pro
    </v-btn>
  </v-card>

  <template v-else>
    <v-row dense>
      <v-col cols="12" md="5">
        <v-card class="pa-5 h-100">
          <h3 class="text-subtitle-2 font-weight-bold mb-4">Répartition par statut</h3>

          <div v-if="segments.length" class="d-flex flex-wrap align-center ga-6">
            <svg viewBox="0 0 100 100" class="donut" aria-hidden="true">
              <circle cx="50" cy="50" r="42" class="donut__track" />
              <circle
                v-for="s in segments"
                :key="s.statut"
                cx="50"
                cy="50"
                r="42"
                class="donut__seg"
                :stroke="s.couleur"
                :stroke-dasharray="tiret(s.part)"
                :stroke-dashoffset="decalage(s.decalage)"
              />
              <text x="50" y="50" class="donut__total">{{ totalStatuts }}</text>
              <text x="50" y="60" class="donut__legend">au total</text>
            </svg>

            <ul class="legend">
              <li v-for="s in segments" :key="s.statut">
                <span class="legend__dot" :style="{ backgroundColor: s.couleur }" />
                <span class="text-body-2">{{ s.label }}</span>
                <span class="text-body-2 font-weight-bold numeric-inline ml-auto">{{ s.nombre }}</span>
              </li>
            </ul>
          </div>

          <p v-else class="text-body-2 text-medium-emphasis mb-0">
            Pas encore assez de réservations pour dégager une répartition.
          </p>
        </v-card>
      </v-col>

      <v-col cols="12" md="7">
        <v-card class="pa-5 h-100">
          <h3 class="text-subtitle-2 font-weight-bold mb-4">Heures d'affluence</h3>

          <div class="hours">
            <div v-for="h in heuresPointe" :key="h.label" class="hours__col" :title="`${h.label} : ${h.count}`">
              <div class="hours__track">
                <div
                  class="hours__bar"
                  :style="{ height: `${h.count ? Math.max(8, (h.count / maxHeurePointe) * 100) : 3}%` }"
                  :class="{ 'is-empty': !h.count }"
                />
              </div>
              <span class="hours__tick">{{ h.label }}</span>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <v-row dense>
      <v-col cols="12" md="6">
        <v-card class="pa-5 h-100">
          <h3 class="text-subtitle-2 font-weight-bold mb-4">Jours les plus chargés</h3>

          <div class="d-flex flex-column ga-3">
            <div v-for="jour in analytics.peak_days ?? []" :key="jour.day" class="d-flex align-center ga-3">
              <span class="days__name">{{ jour.day }}</span>
              <v-progress-linear
                :model-value="(jour.count / maxJourPointe) * 100"
                height="8"
                rounded
                color="primary"
                bg-color="clay-200"
                class="flex-grow-1"
              />
              <span class="text-body-2 numeric-inline days__count">{{ jour.count }}</span>
            </div>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card class="pa-5 h-100">
          <h3 class="text-subtitle-2 font-weight-bold mb-4">Recette par prestation</h3>

          <div v-if="analytics.top_revenue?.length" class="d-flex flex-column ga-3">
            <div
              v-for="ligne in analytics.top_revenue"
              :key="ligne.name"
              class="d-flex align-center ga-3"
            >
              <component
                :is="iconForService({ name: ligne.name, color: ligne.color })"
                :size="15"
                :style="{ color: ligne.color }"
              />
              <span class="text-body-2 text-truncate flex-grow-1">{{ ligne.name }}</span>
              <span class="text-body-2 font-weight-semibold numeric-inline">
                {{ montant(ligne.revenue) }} F
              </span>
            </div>
          </div>

          <p v-else class="text-body-2 text-medium-emphasis mb-0">
            Aucune recette enregistrée sur la période.
          </p>
        </v-card>
      </v-col>
    </v-row>
  </template>
</template>

<style scoped>
.locked__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: var(--clay-100);
  color: var(--clay-400);
}

.donut {
  width: 148px;
  height: 148px;
  /* Le tracé part de midi et non de trois heures : un anneau qui démarre à
     droite se lit mal, l'œil cherche le haut. */
  transform: rotate(-90deg);
}

.donut__track {
  fill: none;
  stroke: var(--clay-200);
  stroke-width: 11;
}

.donut__seg {
  fill: none;
  stroke-width: 11;
  stroke-linecap: butt;
}

.donut__total,
.donut__legend {
  transform: rotate(90deg);
  transform-origin: 50px 50px;
  text-anchor: middle;
  fill: var(--forest-950);
}

.donut__total {
  font-family: 'Yuzo', Roboto, monospace;
  font-size: 17px;
  font-weight: 800;
}

.donut__legend {
  font-size: 7px;
  fill: var(--clay-500);
}

.legend {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 9px;
  min-width: 170px;
  flex: 1;
}

.legend li { display: flex; align-items: center; gap: 9px; }

.legend__dot {
  width: 9px;
  height: 9px;
  border-radius: 999px;
  flex-shrink: 0;
}

.hours {
  display: flex;
  align-items: flex-end;
  gap: 5px;
  min-height: 150px;
}

.hours__col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  min-width: 0;
}

.hours__track {
  display: flex;
  align-items: flex-end;
  width: 100%;
  height: 128px;
}

.hours__bar {
  width: 100%;
  border-radius: 4px 4px 2px 2px;
  background: var(--forest-500);
}

.hours__bar.is-empty { background: var(--clay-200); }

.hours__tick {
  font-family: 'Yuzo', Roboto, monospace;
  font-size: 9px;
  color: var(--clay-400);
  white-space: nowrap;
}

.days__name {
  width: 76px;
  flex-shrink: 0;
  font-size: 13px;
  text-transform: capitalize;
}

.days__count { width: 32px; text-align: right; }
</style>
