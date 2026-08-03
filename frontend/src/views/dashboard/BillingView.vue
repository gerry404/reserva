<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { paymentsApi } from '@/api'
import { describePayment } from '@/constants/paymentStatus'
import {
  Check,
  Sparkles,
  Rocket,
  CreditCard,
  RefreshCw,
  CircleCheck,
  CircleX,
  Clock,
} from 'lucide-vue-next'

const route = useRoute()
const auth  = useAuthStore()

const loading     = ref(true)
const subscription = ref(null)
const history     = ref([])
const cycle       = ref('monthly')
const initiating  = ref(null) // 'pro' or 'business'
const verifying   = ref(false)
const toast       = ref(null) // { type: 'success'|'error', message }

const plans = [
  {
    id: 'pro',
    name: 'Pro',
    icon: Sparkles,
    color: 'from-primary-500 to-violet-600',
    monthly: 2900,
    yearly: 24900,
    features: [
      'Réservations illimitées',
      'Notifications email auto',
      'Lien de réservation personnalisé',
      'Statistiques avancées',
      'Support prioritaire',
      'Export CSV illimité',
    ],
  },
  {
    id: 'business',
    name: 'Business',
    icon: Rocket,
    color: 'from-amber-500 to-orange-600',
    monthly: 7900,
    yearly: 69900,
    features: [
      'Tout le plan Pro',
      'Multi-employés (bientôt)',
      'SMS automatiques',
      'API & intégrations',
      'Dashboard analytics complet',
      'Support dédié WhatsApp',
    ],
  },
]

const freePlan = {
  features: [
    '30 réservations / mois',
    'Page de réservation',
    'Notifications WhatsApp (wa.me)',
    'Tableau de bord basique',
    'Export CSV',
  ],
}

function formatPrice(amount) {
  return Number(amount).toLocaleString('fr-FR')
}

/** ISO timestamps from the API, rendered the way a French merchant reads them. */
function formatDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
}

function yearlySaving(plan) {
  return Math.round((1 - plan.yearly / (plan.monthly * 12)) * 100)
}

async function loadSubscription() {
  const [sub, payments] = await Promise.all([
    paymentsApi.subscription(),
    paymentsApi.history(),
  ])
  subscription.value = sub
  history.value      = payments
}

onMounted(async () => {
  try {
    await loadSubscription()
  } catch (e) {
    toast.value = { type: 'error', message: e.message }
  } finally {
    loading.value = false
  }

  // Returning from Flutterwave. Keyed on tx_ref alone: the gateway appends its
  // own `status` to the redirect URL, so the previous check against
  // `status === 'callback'` saw an array and never fired — the customer paid
  // and nothing happened.
  if (typeof route.query.tx_ref === 'string') {
    verifyPayment(route.query.tx_ref)
  }
})

async function initPayment(planId) {
  initiating.value = planId
  toast.value = null
  try {
    const { payment_link: paymentLink } = await paymentsApi.initiate({
      plan: planId,
      billing_cycle: cycle.value,
    })
    window.location.href = paymentLink
  } catch (e) {
    toast.value = {
      type: 'error',
      message: e.message || 'Erreur lors de l\'initiation du paiement.',
    }
  } finally {
    initiating.value = null
  }
}

async function verifyPayment(txRef) {
  verifying.value = true
  toast.value = null
  try {
    const result = await paymentsApi.verify(txRef)

    toast.value = {
      type: result.status === 'successful' ? 'success' : 'error',
      message: result.message,
    }

    if (result.status === 'successful') {
      // The plan changed: refresh the session so the sidebar badge and every
      // Pro-gated screen agree with what was just paid for.
      await auth.refresh()
    }

    await loadSubscription()
  } catch (e) {
    toast.value = { type: 'error', message: e.message }
  } finally {
    verifying.value = false
  }
}

</script>

<template>
  <div class="max-w-4xl mx-auto space-y-8 animate-fade-in">
    <!-- Header -->
    <div>
      <h2 class="text-xl font-black text-gray-900">Abonnement & facturation</h2>
      <p class="text-sm text-gray-500 mt-0.5">Gérez votre plan et vos paiements</p>
    </div>

    <!-- Toast -->
    <Transition name="fade">
      <div v-if="toast" :class="['p-4 rounded-xl border flex items-center gap-3',
        toast.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800']">
        <CircleCheck v-if="toast.type === 'success'" class="w-5 h-5 shrink-0" />
        <CircleX v-else class="w-5 h-5 shrink-0" />
        <p class="text-sm font-medium">{{ toast.message }}</p>
      </div>
    </Transition>

    <!-- Verifying payment -->
    <div v-if="verifying" class="card p-10 text-center">
      <div class="w-12 h-12 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-4" />
      <h3 class="font-bold text-gray-900 mb-1">Vérification du paiement…</h3>
      <p class="text-sm text-gray-500">Veuillez patienter quelques secondes</p>
    </div>

    <!-- Loading -->
    <div v-else-if="loading" class="space-y-6">
      <div class="card p-6 animate-pulse"><div class="h-24 bg-gray-100 rounded-xl" /></div>
      <div class="grid md:grid-cols-3 gap-4">
        <div v-for="i in 3" :key="i" class="card p-6 animate-pulse"><div class="h-60 bg-gray-100 rounded-xl" /></div>
      </div>
    </div>

    <template v-else>
      <!-- Current plan banner -->
      <div class="card overflow-hidden">
        <div :class="['px-6 py-5', subscription?.plan === 'free' ? 'bg-gray-50' : 'bg-gradient-to-r from-primary-600 to-violet-600']">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div :class="['w-12 h-12 rounded-2xl flex items-center justify-center', subscription?.plan === 'free' ? 'bg-gray-200' : 'bg-white/20']">
                <Sparkles :class="['w-6 h-6', subscription?.plan === 'free' ? 'text-gray-500' : 'text-white']" />
              </div>
              <div>
                <h3 :class="['text-lg font-black', subscription?.plan === 'free' ? 'text-gray-900' : 'text-white']">
                  Plan {{ subscription?.plan_label }}
                </h3>
                <p :class="['text-sm', subscription?.plan === 'free' ? 'text-gray-500' : 'text-white/70']">
                  <template v-if="subscription?.plan === 'free'">30 réservations / mois — Passez à Pro pour déverrouiller tout</template>
                  <template v-else-if="subscription?.plan_expires_at">Actif jusqu'au {{ formatDate(subscription.plan_expires_at) }}</template>
                  <template v-else>Abonnement actif</template>
                </p>
              </div>
            </div>
            <span v-if="subscription?.is_active && subscription?.plan !== 'free'"
              class="hidden sm:flex items-center gap-1.5 bg-white/20 text-white text-xs font-bold px-3 py-1.5 rounded-full">
              <CircleCheck class="w-4 h-4" /> Actif
            </span>
          </div>
        </div>
      </div>

      <!-- Billing cycle toggle -->
      <div class="flex items-center justify-center gap-3">
        <button @click="cycle = 'monthly'" :class="['text-sm font-semibold px-4 py-2 rounded-control transition-all', cycle === 'monthly' ? 'bg-primary-600 text-white' : 'text-gray-500 hover:text-gray-700']">
          Mensuel
        </button>
        <button @click="cycle = 'yearly'" :class="['text-sm font-semibold px-4 py-2 rounded-control transition-all', cycle === 'yearly' ? 'bg-primary-600 text-white' : 'text-gray-500 hover:text-gray-700']">
          Annuel
          <span class="ml-1 text-[10px] font-bold bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-full">Économisez</span>
        </button>
      </div>

      <!-- Plans grid -->
      <div class="grid md:grid-cols-3 gap-4">
        <!-- Free plan -->
        <div class="card p-6 flex flex-col">
          <div class="mb-6">
            <p class="text-sm font-bold text-gray-400 mb-1">GRATUIT</p>
            <div class="flex items-baseline gap-1">
              <span class="text-4xl font-black text-gray-900 numeric">0</span>
              <span class="text-sm text-gray-400">F CFA</span>
            </div>
            <p class="text-xs text-gray-400 mt-1">Pour toujours</p>
          </div>

          <ul class="space-y-3 flex-1 mb-6">
            <li v-for="f in freePlan.features" :key="f" class="flex items-start gap-2 text-sm text-gray-600">
              <Check class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" />
              {{ f }}
            </li>
          </ul>

          <button disabled class="w-full py-3 rounded-control border-2 border-gray-200 text-sm font-bold text-gray-400 cursor-not-allowed">
            {{ subscription?.plan === 'free' ? 'Plan actuel' : 'Plan de base' }}
          </button>
        </div>

        <!-- Paid plans -->
        <div v-for="plan in plans" :key="plan.id"
          :class="['card p-6 flex flex-col relative overflow-hidden', plan.id === 'pro' ? 'ring-2 ring-primary-500' : '']">
          <!-- Popular badge -->
          <div v-if="plan.id === 'pro'" class="absolute top-0 right-0 bg-primary-500 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl">
            POPULAIRE
          </div>

          <div class="mb-6">
            <p class="text-sm font-bold text-primary-600 mb-1">{{ plan.name.toUpperCase() }}</p>
            <div class="flex items-baseline gap-1">
              <span class="text-4xl font-black text-gray-900 numeric">{{ formatPrice(cycle === 'monthly' ? plan.monthly : plan.yearly) }}</span>
              <span class="text-sm text-gray-400">F CFA</span>
            </div>
            <p class="text-xs text-gray-400 mt-1">
              {{ cycle === 'monthly' ? '/ mois' : '/ an' }}
              <span v-if="cycle === 'yearly'" class="text-emerald-600 font-semibold ml-1">
                -{{ yearlySaving(plan) }}%
              </span>
            </p>
          </div>

          <ul class="space-y-3 flex-1 mb-6">
            <li v-for="f in plan.features" :key="f" class="flex items-start gap-2 text-sm text-gray-600">
              <Check class="w-4 h-4 text-primary-500 shrink-0 mt-0.5" />
              {{ f }}
            </li>
          </ul>

          <button
            v-if="subscription?.plan === plan.id && subscription?.is_active"
            disabled
            class="w-full py-3 rounded-control border-2 border-primary-200 text-sm font-bold text-primary-400 cursor-not-allowed"
          >
            <CircleCheck class="w-4 h-4 inline -mt-0.5" /> Plan actuel
          </button>
          <button
            v-else
            @click="initPayment(plan.id)"
            :disabled="initiating === plan.id"
            :class="['w-full py-3 rounded-control text-sm font-bold text-white transition-all hover:opacity-90 disabled:opacity-60 bg-gradient-to-r', plan.color]"
          >
            <span v-if="initiating === plan.id" class="flex items-center justify-center gap-2">
              <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              Redirection…
            </span>
            <span v-else class="flex items-center justify-center gap-2">
              <CreditCard class="w-4 h-4" />
              Passer au {{ plan.name }}
            </span>
          </button>
        </div>
      </div>

      <!-- Payment methods info -->
      <div class="card p-5">
        <div class="flex items-center gap-3 mb-3">
          <CreditCard class="w-5 h-5 text-gray-400" />
          <h3 class="font-bold text-gray-900 text-sm">Moyens de paiement acceptés</h3>
        </div>
        <div class="flex flex-wrap gap-3">
          <div class="flex items-center gap-2 px-3 py-2 bg-amber-50 border border-amber-100 rounded-xl text-sm">
            <span class="text-lg">📱</span>
            <div>
              <p class="font-semibold text-gray-900 text-xs">MTN Mobile Money</p>
              <p class="text-[10px] text-gray-400">Cameroun, RDC, Ghana…</p>
            </div>
          </div>
          <div class="flex items-center gap-2 px-3 py-2 bg-orange-50 border border-orange-100 rounded-xl text-sm">
            <span class="text-lg">📱</span>
            <div>
              <p class="font-semibold text-gray-900 text-xs">Orange Money</p>
              <p class="text-[10px] text-gray-400">Cameroun, Côte d'Ivoire…</p>
            </div>
          </div>
          <div class="flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-100 rounded-xl text-sm">
            <span class="text-lg">💳</span>
            <div>
              <p class="font-semibold text-gray-900 text-xs">Carte bancaire</p>
              <p class="text-[10px] text-gray-400">Visa, Mastercard</p>
            </div>
          </div>
        </div>
        <p class="text-[11px] text-gray-400 mt-3">Paiements sécurisés via Flutterwave. Vos données bancaires ne sont jamais stockées.</p>
      </div>

      <!-- Payment history -->
      <div v-if="history.length > 0" class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h3 class="font-bold text-gray-900">Historique des paiements</h3>
        </div>
        <div class="divide-y divide-gray-50">
          <div v-for="p in history" :key="p.id" class="flex items-center gap-4 px-6 py-4">
            <span
              class="w-2.5 h-2.5 rounded-full shrink-0"
              :style="{ backgroundColor: describePayment(p.status).tone.solid }"
              aria-hidden="true"
            />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-gray-900">{{ p.plan }} — {{ p.billing_cycle }}</p>
              <p class="text-xs text-gray-400">{{ formatDate(p.created_at) }}{{ p.payment_method ? ' · ' + p.payment_method : '' }}</p>
            </div>
            <span class="text-sm font-bold text-gray-900 numeric">{{ p.formatted_amount }}</span>
            <span
              class="text-[11px] font-semibold px-2 py-0.5 rounded-full"
              :style="{
                backgroundColor: describePayment(p.status).tone.bg,
                color: describePayment(p.status).tone.text,
              }"
            >
              {{ describePayment(p.status).label }}
            </span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
