<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { CreditCard, Rocket, Sparkles, Store } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { paymentsApi } from '@/api'
import { describePayment } from '@/constants/paymentStatus'
import PlanCard from '@/components/dashboard/PlanCard.vue'

/**
 * L'abonnement et l'historique des paiements.
 */

const route = useRoute()
const auth = useAuthStore()

const chargement = ref(true)
const abonnement = ref(null)
const historique = ref([])
const cycle = ref('monthly')
const enCours = ref(null)
const verification = ref(false)
const message = ref(null)

const PLANS = [
  {
    id: null,
    name: 'Découverte',
    tagline: 'Pour commencer',
    icon: Store,
    tone: 'clay-600',
    monthly: 0,
    yearly: 0,
    features: [
      '30 réservations par mois',
      'Page de réservation publique',
      'Notifications WhatsApp',
      'Tableau de bord',
      'Export CSV',
    ],
  },
  {
    id: 'pro',
    name: 'Pro',
    tagline: 'Pour un commerce qui tourne',
    icon: Sparkles,
    tone: 'primary',
    highlighted: true,
    monthly: 2900,
    yearly: 24900,
    features: [
      'Réservations illimitées',
      'Rappels automatiques la veille',
      'Adresse de réservation personnalisée',
      'Statistiques détaillées',
      'Marque Nuvo retirée de votre page',
      'Support prioritaire',
    ],
  },
  {
    id: 'business',
    name: 'Business',
    tagline: 'Pour plusieurs points de vente',
    icon: Rocket,
    tone: 'warning',
    monthly: 7900,
    yearly: 69900,
    features: [
      'Tout le plan Pro',
      'SMS automatiques',
      'API et intégrations',
      'Analyses complètes',
      'Support dédié WhatsApp',
    ],
  },
]

const CYCLES = [
  { value: 'monthly', title: 'Mensuel' },
  { value: 'yearly', title: 'Annuel' },
]

const enteteHistorique = [
  { title: 'Date', key: 'created_at' },
  { title: 'Plan', key: 'plan' },
  { title: 'Montant', key: 'amount', align: 'end' },
  { title: 'Statut', key: 'status', align: 'center' },
]

/** Le plan réellement actif, celui que le serveur calcule, pas celui payé jadis. */
const planActif = computed(() => auth.user?.plan ?? 'free')

function estActuel(plan) {
  if (!plan.id) return planActif.value === 'free'
  return planActif.value === plan.id && abonnement.value?.is_active !== false
}

function dateLisible(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('fr-FR', {
    day: '2-digit', month: 'long', year: 'numeric',
  })
}

onMounted(async () => {
  try {
    await recharger()
  } catch (e) {
    message.value = { type: 'error', texte: e.message }
  } finally {
    chargement.value = false
  }

  // Retour de Flutterwave. La clé est `tx_ref` seule : la passerelle ajoute son
  // propre `status` à l'URL de retour, si bien que l'ancien test sur
  // `status === 'callback'` voyait un tableau et ne se déclenchait jamais. Le
  // client payait, et rien ne se passait.
  if (typeof route.query.tx_ref === 'string') {
    await verifier(route.query.tx_ref)
  }
})

async function recharger() {
  const [sub, paiements] = await Promise.all([
    paymentsApi.subscription(),
    paymentsApi.history(),
  ])
  abonnement.value = sub
  historique.value = paiements
}

async function choisir(planId) {
  enCours.value = planId
  message.value = null

  try {
    const { payment_link: lien } = await paymentsApi.initiate({
      plan: planId,
      billing_cycle: cycle.value,
    })
    window.location.href = lien
  } catch (e) {
    message.value = { type: 'error', texte: e.message || "Le paiement n'a pas pu démarrer." }
    enCours.value = null
  }
}

async function verifier(txRef) {
  verification.value = true
  message.value = null

  try {
    const resultat = await paymentsApi.verify(txRef)
    message.value = {
      type: resultat.status === 'successful' ? 'success' : 'error',
      texte: resultat.message,
    }

    if (resultat.status === 'successful') {
      // Le plan a changé : la session est rafraîchie pour que la pastille de la
      // barre latérale et chaque écran réservé au Pro disent la même chose.
      await auth.refresh()
    }

    await recharger()
  } catch (e) {
    message.value = { type: 'error', texte: e.message }
  } finally {
    verification.value = false
  }
}
</script>

<template>
  <div class="d-flex flex-column ga-6">
    <div>
      <h2 class="page__title">Abonnement</h2>
      <p class="text-body-2 text-medium-emphasis mb-0">Gérez votre plan et vos paiements</p>
    </div>

    <v-alert
      v-if="message"
      :type="message.type"
      closable
      @click:close="message = null"
    >
      {{ message.texte }}
    </v-alert>

    <v-alert v-if="verification" type="info" density="compact">
      Vérification du paiement en cours…
    </v-alert>

    <v-card v-if="abonnement?.plan_expires_at" class="pa-5">
      <div class="d-flex flex-wrap align-center ga-4">
        <CreditCard :size="20" class="text-medium-emphasis" />
        <div>
          <p class="text-body-2 font-weight-semibold mb-0">
            Plan {{ abonnement.plan }} actif
          </p>
          <p class="text-caption text-medium-emphasis mb-0">
            Jusqu'au {{ dateLisible(abonnement.plan_expires_at) }}
          </p>
        </div>
      </div>
    </v-card>

    <div class="d-flex justify-center">
      <v-btn-toggle
        v-model="cycle"
        mandatory
        divided
        density="comfortable"
        rounded="control"
        color="primary"
        variant="outlined"
      >
        <v-btn v-for="c in CYCLES" :key="c.value" :value="c.value" class="text-none px-6">
          {{ c.title }}
        </v-btn>
      </v-btn-toggle>
    </div>

    <v-row dense>
      <v-col v-for="plan in PLANS" :key="plan.name" cols="12" md="4">
        <PlanCard
          :plan="plan"
          :cycle="cycle"
          :current="estActuel(plan)"
          :loading="enCours === plan.id"
          @choose="choisir"
        />
      </v-col>
    </v-row>

    <v-card>
      <div class="pa-5 pb-3">
        <p class="section__label mb-0">Historique des paiements</p>
      </div>
      <v-data-table
        :headers="enteteHistorique"
        :items="historique"
        :loading="chargement"
        density="comfortable"
        no-data-text="Aucun paiement pour le moment."
        loading-text="Chargement…"
        items-per-page-text="Par page"
      >
        <template #[`item.created_at`]="{ item }">
          <span class="text-body-2">{{ dateLisible(item.created_at) }}</span>
        </template>
        <template #[`item.plan`]="{ item }">
          <span class="text-body-2 text-capitalize">{{ item.plan }} · {{ item.billing_cycle }}</span>
        </template>
        <template #[`item.amount`]="{ item }">
          <span class="numeric text-body-2">{{ Number(item.amount).toLocaleString('fr-FR') }} F</span>
        </template>
        <template #[`item.status`]="{ item }">
          <v-chip
            size="small"
            variant="flat"
            :style="{
              backgroundColor: describePayment(item.status).tone.bg,
              color: describePayment(item.status).tone.text,
              border: `1px solid ${describePayment(item.status).tone.border}`,
            }"
          >
            {{ describePayment(item.status).label }}
          </v-chip>
        </template>
      </v-data-table>
    </v-card>
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

.section__label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--clay-400);
}
</style>
