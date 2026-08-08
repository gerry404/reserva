<script setup>
import { computed, onMounted, ref } from 'vue'
import { Check, Copy, ExternalLink, LayoutGrid, Settings, Share2 } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useDashboardData } from '@/composables/useDashboardData'
import StatCards from '@/components/dashboard/home/StatCards.vue'
import BookingsChart from '@/components/dashboard/home/BookingsChart.vue'
import UpcomingList from '@/components/dashboard/home/UpcomingList.vue'
import AnalyticsPanels from '@/components/dashboard/home/AnalyticsPanels.vue'

/**
 * L'accueil du tableau de bord.
 *
 * La vue ne fait plus qu'assembler : le chargement et les calculs dérivés
 * vivent dans useDashboardData, chaque bloc dans son composant. Elle portait
 * huit sections, quinze propriétés calculées et deux appels réseau dans un
 * fichier de plus de huit cents lignes.
 */

const auth = useAuthStore()

const {
  stats, upcoming, chart, analytics, loading, error, actionEnCours,
  charger, confirmer, annuler,
  quotaPourcent, quotaTendu,
  heuresPointe, maxHeurePointe, maxJourPointe,
  segmentsStatut, totalStatuts,
} = useDashboardData()

const lienCopie = ref(false)

const publicUrl = computed(() => {
  const slug = auth.business?.slug
  return slug ? `${window.location.origin}/b/${slug}` : null
})

const salutation = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Bonjour'
  if (h < 18) return 'Bon après-midi'
  return 'Bonsoir'
})

/** Le prénom seul : « Bonjour Serge » se lit mieux que le nom complet. */
const prenom = computed(() => (auth.user?.name ?? '').trim().split(' ')[0])

/**
 * Les trois pas de démarrage.
 *
 * Ils ne s'affichent que tant que le commerce n'a reçu aucune réservation :
 * une fois le produit en usage, cette liste ne fait qu'occuper le haut de
 * l'écran.
 */
const demarrage = computed(() => {
  const b = auth.business
  if (!b) return []

  return [
    {
      id: 'profil',
      label: 'Compléter le profil du commerce',
      fait: Boolean(b.description && b.address),
      to: { name: 'settings' },
      icone: Settings,
    },
    {
      id: 'services',
      label: 'Ajouter au moins une prestation',
      fait: stats.value?.has_services ?? false,
      to: { name: 'services' },
      icone: LayoutGrid,
    },
    {
      id: 'partage',
      label: 'Partager votre lien de réservation',
      fait: false,
      action: partager,
      icone: Share2,
    },
  ]
})

const demarrageVisible = computed(() =>
  demarrage.value.length > 0
  && demarrage.value.some((etape) => !etape.fait)
  && stats.value?.monthly_bookings === 0,
)

onMounted(charger)

async function copierLien() {
  if (!publicUrl.value) return
  await navigator.clipboard.writeText(publicUrl.value)
  lienCopie.value = true
  setTimeout(() => { lienCopie.value = false }, 2500)
}

async function partager() {
  if (navigator.share && publicUrl.value) {
    try {
      await navigator.share({
        title: `Réserver chez ${auth.business?.name}`,
        text: `Réservez en ligne chez ${auth.business?.name}`,
        url: publicUrl.value,
      })
      return
    } catch {
      // Partage refusé ou annulé : le presse-papiers reste une issue.
    }
  }
  await copierLien()
}
</script>

<template>
  <div class="d-flex flex-column ga-6">
    <v-alert v-if="error" type="error" closable @click:close="error = ''">
      {{ error }}
    </v-alert>

    <div class="d-flex flex-wrap align-center justify-space-between ga-4">
      <div>
        <h2 class="page__title">{{ salutation }}{{ prenom ? `, ${prenom}` : '' }}</h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Voici où en est {{ auth.business?.name ?? 'votre commerce' }}.
        </p>
      </div>

      <div v-if="publicUrl" class="d-flex align-center ga-2">
        <v-btn variant="tonal" color="primary" size="small" class="text-none" @click="copierLien">
          <component :is="lienCopie ? Check : Copy" :size="15" class="mr-2" />
          {{ lienCopie ? 'Copié' : 'Copier mon lien' }}
        </v-btn>
        <v-btn :href="publicUrl" target="_blank" variant="text" size="small" class="text-none">
          <ExternalLink :size="15" class="mr-2" />
          Voir
        </v-btn>
      </div>
    </div>

    <v-card v-if="demarrageVisible" class="pa-5">
      <h3 class="text-subtitle-2 font-weight-bold mb-1">Pour recevoir vos premières réservations</h3>
      <p class="text-body-2 text-medium-emphasis mb-4">
        Trois pas, et votre page est prête à être partagée.
      </p>

      <div class="d-flex flex-column ga-2">
        <component
          :is="etape.to ? 'RouterLink' : 'button'"
          v-for="etape in demarrage"
          :key="etape.id"
          :to="etape.to"
          type="button"
          :class="['step', { 'is-done': etape.fait }]"
          @click="etape.action?.()"
        >
          <span class="step__mark">
            <Check v-if="etape.fait" :size="14" />
            <component :is="etape.icone" v-else :size="15" />
          </span>
          <span class="text-body-2">{{ etape.label }}</span>
        </component>
      </div>
    </v-card>

    <v-alert v-if="quotaTendu && stats?.plan_limit" type="warning" class="text-body-2">
      <div class="d-flex flex-wrap align-center ga-3">
        <span>
          <span class="numeric-inline font-weight-bold">{{ stats.plan_used }}</span>
          réservations sur
          <span class="numeric-inline font-weight-bold">{{ stats.plan_limit }}</span>
          ce mois-ci. Au-delà, vos clients ne pourront plus réserver.
        </span>
        <v-btn :to="{ name: 'billing' }" size="small" color="warning" variant="flat" class="text-none">
          Passer au Pro
        </v-btn>
      </div>
      <v-progress-linear
        :model-value="quotaPourcent"
        height="6"
        rounded
        color="warning"
        bg-color="clay-200"
        class="mt-3"
      />
    </v-alert>

    <template v-if="loading">
      <v-row dense>
        <v-col v-for="i in 4" :key="i" cols="6" md="3">
          <v-skeleton-loader type="article" />
        </v-col>
      </v-row>
      <v-skeleton-loader type="image" />
    </template>

    <template v-else>
      <StatCards :stats="stats" />

      <v-row dense>
        <v-col cols="12" lg="7">
          <BookingsChart :chart="chart" />
        </v-col>
        <v-col cols="12" lg="5">
          <UpcomingList
            :bookings="upcoming"
            :busy-id="actionEnCours"
            @confirm="confirmer"
            @cancel="annuler"
          />
        </v-col>
      </v-row>

      <AnalyticsPanels
        :analytics="analytics"
        :segments="segmentsStatut"
        :total-statuts="totalStatuts"
        :heures-pointe="heuresPointe"
        :max-heure-pointe="maxHeurePointe"
        :max-jour-pointe="maxJourPointe"
      />
    </template>
  </div>
</template>

<style scoped>
.page__title {
  font-family: 'Dekatron', Roboto, sans-serif;
  font-size: 22px;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--forest-950);
  margin: 0 0 2px;
}

.step {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 13px;
  border: 1px solid var(--clay-200);
  border-radius: 12px;
  color: var(--forest-950);
  text-decoration: none;
  text-align: left;
  width: 100%;
  transition: border-color 0.15s ease, background-color 0.15s ease;
}

.step:hover { border-color: var(--forest-300); background: var(--forest-50); }

.step.is-done {
  color: var(--clay-500);
  border-style: dashed;
}

.step__mark {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  flex-shrink: 0;
  border-radius: 999px;
  background: var(--clay-100);
  color: var(--clay-500);
}

.step.is-done .step__mark {
  background: var(--forest-100);
  color: var(--forest-700);
}

@media (prefers-reduced-motion: reduce) {
  .step { transition: none; }
}
</style>
