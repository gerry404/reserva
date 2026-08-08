<script setup>
import { computed } from 'vue'
import { Banknote, CalendarDays, Clock, TrendingUp } from 'lucide-vue-next'

/**
 * Les quatre chiffres de tête.
 *
 * Ils étaient quatre blocs recopiés, chacun avec son dégradé, son icône et sa
 * mise en page écrits à la main. Une seule définition, quatre données.
 */
const props = defineProps({
  stats: { type: Object, default: null },
})

/** Les grands montants se lisent abrégés : 1,2 M plutôt que 1 200 000. */
function recette(montant) {
  if (!montant) return '0'
  if (montant >= 1e6) return `${(montant / 1e6).toFixed(1)} M`
  if (montant >= 1e3) return `${Math.round(montant / 1e3)} k`
  return montant.toLocaleString('fr-FR')
}

const cartes = computed(() => [
  {
    cle: 'mois',
    label: 'Ce mois-ci',
    valeur: props.stats?.monthly_bookings ?? 0,
    detail: 'réservations',
    icone: CalendarDays,
    tone: 'primary',
  },
  {
    cle: 'jour',
    label: "Aujourd'hui",
    valeur: props.stats?.today_bookings ?? 0,
    detail: 'rendez-vous',
    icone: Clock,
    tone: 'info',
  },
  {
    cle: 'attente',
    label: 'En attente',
    valeur: props.stats?.pending_bookings ?? 0,
    detail: 'à confirmer',
    icone: TrendingUp,
    tone: 'warning',
  },
  {
    cle: 'recette',
    label: 'Recette du mois',
    valeur: recette(props.stats?.revenue_this_month),
    detail: 'F CFA',
    icone: Banknote,
    tone: 'success',
  },
])
</script>

<template>
  <v-row dense>
    <v-col v-for="carte in cartes" :key="carte.cle" cols="6" md="3">
      <v-card class="pa-5 h-100">
        <div class="d-flex align-center justify-space-between ga-2">
          <span class="stat__label">{{ carte.label }}</span>
          <span class="stat__icon" :class="`text-${carte.tone}`">
            <component :is="carte.icone" :size="17" />
          </span>
        </div>
        <p class="stat__value numeric mt-3 mb-0">{{ carte.valeur }}</p>
        <p class="text-caption text-medium-emphasis mb-0">{{ carte.detail }}</p>
      </v-card>
    </v-col>
  </v-row>
</template>

<style scoped>
.stat__label {
  font-size: 12px;
  font-weight: 600;
  color: var(--clay-500);
}

.stat__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: var(--clay-100);
}

.stat__value {
  font-size: clamp(26px, 4vw, 32px);
  font-weight: 800;
  line-height: 1;
  color: var(--forest-950);
}
</style>
