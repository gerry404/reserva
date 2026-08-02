<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { dashboardApi, bookingsApi } from '@/api'
import DurationBar from '@/components/time/DurationBar.vue'
import { RouterLink } from 'vue-router'
import {
  CalendarDaysIcon,
  ClockIcon,
  CurrencyDollarIcon,
  CheckCircleIcon,
  LinkIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  UserGroupIcon,
  PlusIcon,
  Squares2X2Icon,
  Cog6ToothIcon,
  ShareIcon,
  CheckIcon,
  XMarkIcon,
  SparklesIcon,
  RocketLaunchIcon,
  ArrowRightIcon,
  ArrowPathIcon,
  BoltIcon,
  ChartBarIcon,
  FireIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline'
import { format, parseISO } from 'date-fns'
import { fr } from 'date-fns/locale'

const auth      = useAuthStore()
const stats     = ref(null)
const upcoming  = ref([])
const chart     = ref(null)
const analytics = ref(null)
const loading   = ref(true)
const error     = ref('')
const actionLoading = ref(null)

// Tabs for chart
const chartTab = ref('daily') // daily | monthly

// Public URL
const publicUrl = computed(() => {
  const slug = auth.business?.slug
  return slug ? `${window.location.origin}/b/${slug}` : null
})

const linkCopied = ref(false)
async function copyLink() {
  if (!publicUrl.value) return
  await navigator.clipboard.writeText(publicUrl.value)
  linkCopied.value = true
  setTimeout(() => linkCopied.value = false, 2500)
}

// Onboarding
const onboarding = computed(() => {
  if (!auth.business) return []
  const b = auth.business
  return [
    { id: 'profile',  label: 'Compléter le profil commerce', done: !!(b.description && b.address), to: '/dashboard/settings', icon: Cog6ToothIcon },
    { id: 'services', label: 'Ajouter au moins un service',  done: stats.value?.has_services ?? false, to: '/dashboard/services', icon: Squares2X2Icon },
    { id: 'share',    label: 'Partager votre lien de réservation', done: false, action: 'share', icon: ShareIcon },
  ]
})
const onboardingDone = computed(() => onboarding.value.filter(s => s.done).length)
const showOnboarding = computed(() => onboardingDone.value < onboarding.value.length && stats.value?.monthly_bookings === 0)

/**
 * Quota gauge. Derived from the limit the API reports rather than from a
 * hardcoded 30, so changing a plan's allowance never leaves the warning
 * threshold pointing at the old number.
 */
const quotaPercent = computed(() => {
  if (!stats.value?.plan_limit) return 0
  return Math.min(100, Math.round((stats.value.plan_used / stats.value.plan_limit) * 100))
})

const quotaIsTight = computed(() => quotaPercent.value >= 80)

// Load all dashboard data
async function loadDashboard() {
  loading.value = true
  error.value = ''
  try {
    // allSettled, not all: analytics is Pro-only and answers 402 on a free
    // plan. With Promise.all that single rejection blanked the whole dashboard
    // — the free tier saw an error page instead of their bookings.
    const [statsResult, upcomingResult, chartResult, analyticsResult] = await Promise.allSettled([
      dashboardApi.stats(),
      dashboardApi.upcoming(),
      dashboardApi.chart(),
      dashboardApi.analytics(),
    ])

    if (statsResult.status === 'rejected') throw statsResult.reason

    stats.value    = statsResult.value
    upcoming.value = upcomingResult.status === 'fulfilled' ? upcomingResult.value : []
    chart.value    = chartResult.status === 'fulfilled' ? chartResult.value : null

    // Absent rather than broken: the UI shows the upgrade prompt instead.
    analytics.value = analyticsResult.status === 'fulfilled' ? analyticsResult.value : null
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)

// Quick actions
async function confirmBooking(id) {
  actionLoading.value = id
  try {
    await bookingsApi.updateStatus(id, 'confirmed')
    const idx = upcoming.value.findIndex(b => b.id === id)
    if (idx !== -1) upcoming.value[idx].status = 'confirmed'
    if (stats.value) stats.value.pending_bookings = Math.max(0, stats.value.pending_bookings - 1)
  } catch {}
  actionLoading.value = null
}

async function cancelBooking(id) {
  actionLoading.value = id
  try {
    await bookingsApi.cancel(id)
    upcoming.value = upcoming.value.filter(b => b.id !== id)
    if (stats.value) stats.value.pending_bookings = Math.max(0, stats.value.pending_bookings - 1)
  } catch {}
  actionLoading.value = null
}

async function shareLink() {
  if (navigator.share && publicUrl.value) {
    try {
      await navigator.share({
        title: `Réserver chez ${auth.business?.name}`,
        text: `Réservez en ligne chez ${auth.business?.name}`,
        url: publicUrl.value,
      })
    } catch {}
  } else {
    copyLink()
  }
}

// Format helpers
function formatDate(dateStr) {
  try { return format(parseISO(dateStr), 'EEE d MMM', { locale: fr }) }
  catch { return dateStr }
}

function formatRevenue(amount) {
  if (!amount || amount === 0) return '0'
  if (amount >= 1000000) return (amount / 1000000).toFixed(1) + 'M'
  if (amount >= 1000) return (amount / 1000).toFixed(0) + 'k'
  return amount.toLocaleString('fr-FR')
}

function formatPrice(p) {
  if (!p || p == 0) return 'Gratuit'
  return Number(p).toLocaleString('fr-FR') + ' F'
}

const statusClass = {
  pending: 'badge-pending', confirmed: 'badge-confirmed',
  cancelled: 'badge-cancelled', completed: 'badge-completed',
  no_show: 'badge-cancelled',
}
const statusLabel = {
  pending: 'En attente', confirmed: 'Confirmé',
  cancelled: 'Annulé', completed: 'Terminé',
  no_show: 'Non présenté',
}
const statusIcon = {
  pending: '⏳', confirmed: '✅', cancelled: '❌', completed: '🏁', no_show: '🚫',
}

// Chart helpers
const activeChartData = computed(() => {
  if (!chart.value) return { labels: [], values: [] }
  return chartTab.value === 'daily' ? chart.value.daily : chart.value.monthly
})

const maxChartVal = computed(() => Math.max(...(activeChartData.value.values || [0]), 1))
const chartHasData = computed(() => activeChartData.value.values?.some(v => v > 0))

// Peak hours
const maxPeakHour = computed(() => {
  if (!analytics.value?.peak_hours) return 1
  return Math.max(...Object.values(analytics.value.peak_hours), 1)
})

const peakHoursFormatted = computed(() => {
  if (!analytics.value?.peak_hours) return []
  const hours = []
  for (let h = 7; h <= 21; h++) {
    const key = String(h).padStart(2, '0')
    hours.push({ label: `${h}h`, count: analytics.value.peak_hours[key] ?? 0 })
  }
  return hours
})

// Peak days
const maxPeakDay = computed(() => {
  if (!analytics.value?.peak_days) return 1
  return Math.max(...analytics.value.peak_days.map(d => d.count), 1)
})

// Status donut
const statusTotal = computed(() => {
  if (!analytics.value?.status_distribution) return 0
  return Object.values(analytics.value.status_distribution).reduce((a, b) => a + b, 0)
})

const statusSegments = computed(() => {
  if (!analytics.value?.status_distribution || statusTotal.value === 0) return []
  const colors = {
    confirmed: '#10b981', completed: '#3b82f6', pending: '#f59e0b',
    cancelled: '#ef4444', no_show: '#6b7280',
  }
  const labels = {
    confirmed: 'Confirmé', completed: 'Terminé', pending: 'En attente',
    cancelled: 'Annulé', no_show: 'Non présenté',
  }
  let offset = 0
  return Object.entries(analytics.value.status_distribution).map(([status, count]) => {
    const pct = (count / statusTotal.value) * 100
    const seg = { status, count, pct, color: colors[status] || '#9ca3af', label: labels[status] || status, offset }
    offset += pct
    return seg
  })
})

// Trial info
const trialDaysLeft = computed(() => {
  const expires = auth.user?.plan_expires_at
  if (!expires || auth.user?.plan === 'free') return -1
  const diff = Math.ceil((new Date(expires) - new Date()) / (1000 * 60 * 60 * 24))
  return diff
})

const isOnTrial = computed(() => trialDaysLeft.value > 0 && trialDaysLeft.value <= 14)

// Greeting
const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Bonjour'
  if (h < 18) return 'Bon après-midi'
  return 'Bonsoir'
})
</script>

<template>
  <div class="space-y-6">
    <!-- Error -->
    <div v-if="error && !loading" class="card p-8 text-center">
      <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center mx-auto mb-4">
        <XMarkIcon class="w-7 h-7 text-red-500" />
      </div>
      <h3 class="font-bold text-gray-900 mb-2">Erreur de chargement</h3>
      <p class="text-sm text-gray-500 mb-4">{{ error }}</p>
      <button @click="loadDashboard" class="btn-primary">Réessayer</button>
    </div>

    <template v-else>
      <!-- Welcome + Public link -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-black text-gray-900">
            {{ greeting }}, {{ auth.user?.name?.split(' ')[0] }} 👋
          </h2>
          <p class="text-gray-500 mt-1">
            {{ new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
          </p>
        </div>
        <div v-if="publicUrl" class="flex items-center gap-2">
          <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-500 max-w-[220px] overflow-hidden">
            <LinkIcon class="w-4 h-4 shrink-0 text-primary-500" />
            <span class="truncate text-xs font-mono">{{ publicUrl.replace(/^https?:\/\//, '') }}</span>
          </div>
          <button @click="copyLink" class="btn-primary px-3 py-2 text-xs">
            <template v-if="linkCopied"><CheckIcon class="w-3.5 h-3.5" /> Copié !</template>
            <template v-else>Copier</template>
          </button>
          <button @click="shareLink" class="btn-secondary px-3 py-2 text-xs" title="Partager">
            <ShareIcon class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- Onboarding (new users) -->
      <div v-if="!loading && showOnboarding" class="card overflow-hidden">
        <div class="bg-gradient-to-r from-primary-600 to-violet-600 px-6 py-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
              <RocketLaunchIcon class="w-5 h-5 text-white" />
            </div>
            <div>
              <h3 class="font-bold text-white">Bienvenue sur Nuvo !</h3>
              <p class="text-white/70 text-sm">Complétez ces étapes pour recevoir vos premières réservations</p>
            </div>
            <div class="ml-auto hidden sm:flex items-center gap-2">
              <span class="text-white/70 text-sm font-medium">{{ onboardingDone }}/{{ onboarding.length }}</span>
              <div class="w-20 h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-clay-50 rounded-full transition-all duration-500" :style="{ width: (onboardingDone / onboarding.length * 100) + '%' }" />
              </div>
            </div>
          </div>
        </div>
        <div class="divide-y divide-gray-50">
          <div v-for="step in onboarding" :key="step.id" class="flex items-center gap-4 px-6 py-4">
            <div :class="['w-8 h-8 rounded-full flex items-center justify-center shrink-0', step.done ? 'bg-emerald-100' : 'bg-gray-100']">
              <CheckIcon v-if="step.done" class="w-4 h-4 text-emerald-600" />
              <component v-else :is="step.icon" class="w-4 h-4 text-gray-400" />
            </div>
            <span :class="['flex-1 text-sm font-medium', step.done ? 'text-gray-400 line-through' : 'text-gray-700']">{{ step.label }}</span>
            <RouterLink v-if="step.to && !step.done" :to="step.to" class="text-xs font-semibold text-primary-600 hover:text-primary-700 flex items-center gap-1">
              Compléter <ArrowRightIcon class="w-3 h-3" />
            </RouterLink>
            <button v-else-if="step.action === 'share' && !step.done" @click="shareLink" class="text-xs font-semibold text-primary-600 hover:text-primary-700 flex items-center gap-1">
              Partager <ArrowRightIcon class="w-3 h-3" />
            </button>
            <span v-else-if="step.done" class="text-xs text-emerald-600 font-semibold">Fait</span>
          </div>
        </div>
      </div>

      <!-- Loading skeleton -->
      <div v-if="loading" class="space-y-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div v-for="i in 4" :key="i" class="card p-6 animate-pulse">
            <div class="h-10 w-10 bg-gray-100 rounded-2xl mb-3" />
            <div class="h-7 bg-gray-100 rounded w-16 mb-2" />
            <div class="h-4 bg-gray-100 rounded w-24" />
          </div>
        </div>
        <div class="grid lg:grid-cols-2 gap-6">
          <div class="card p-6 animate-pulse"><div class="h-52 bg-gray-50 rounded-xl" /></div>
          <div class="card p-6 animate-pulse"><div class="h-52 bg-gray-50 rounded-xl" /></div>
        </div>
      </div>

      <template v-if="!loading && stats">
        <!-- ═══════ STAT CARDS ═══════ -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Monthly bookings -->
          <div class="stat-card group hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div class="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                <CalendarDaysIcon class="w-5 h-5 text-blue-600" />
              </div>
              <div v-if="stats.monthly_trend !== 0" :class="['flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full', stats.monthly_trend >= 0 ? 'text-emerald-700 bg-emerald-50' : 'text-red-600 bg-red-50']">
                <component :is="stats.monthly_trend >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" class="w-3 h-3" />
                {{ Math.abs(stats.monthly_trend) }}%
              </div>
            </div>
            <div>
              <p class="text-3xl font-black text-gray-900 numeric">{{ stats.monthly_bookings }}</p>
              <p class="text-sm text-gray-500 mt-0.5">Réservations ce mois</p>
            </div>
          </div>

          <!-- Today -->
          <div class="stat-card group hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div class="w-10 h-10 rounded-2xl bg-violet-50 flex items-center justify-center group-hover:bg-violet-100 transition-colors">
                <ClockIcon class="w-5 h-5 text-violet-600" />
              </div>
              <span v-if="stats.today_bookings > 0" class="text-xs font-semibold text-violet-600 bg-violet-50 px-2 py-0.5 rounded-full">
                Aujourd'hui
              </span>
            </div>
            <div>
              <p class="text-3xl font-black text-gray-900 numeric">{{ stats.today_bookings }}</p>
              <p class="text-sm text-gray-500 mt-0.5">Aujourd'hui</p>
            </div>
          </div>

          <!-- Pending -->
          <RouterLink to="/dashboard/bookings" class="stat-card group hover:shadow-md transition-shadow cursor-pointer">
            <div class="flex items-center justify-between">
              <div class="w-10 h-10 rounded-2xl bg-amber-50 flex items-center justify-center group-hover:bg-amber-100 transition-colors">
                <UserGroupIcon class="w-5 h-5 text-amber-600" />
              </div>
              <span v-if="stats.pending_bookings > 0" class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75" />
                <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500" />
              </span>
            </div>
            <div>
              <p class="text-3xl font-black text-gray-900 numeric">{{ stats.pending_bookings }}</p>
              <p class="text-sm text-gray-500 mt-0.5">En attente</p>
            </div>
          </RouterLink>

          <!-- Revenue -->
          <div class="stat-card group hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div class="w-10 h-10 rounded-2xl bg-emerald-50 flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
                <CurrencyDollarIcon class="w-5 h-5 text-emerald-600" />
              </div>
              <div v-if="stats.revenue_trend !== 0" :class="['flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full', stats.revenue_trend >= 0 ? 'text-emerald-700 bg-emerald-50' : 'text-red-600 bg-red-50']">
                <component :is="stats.revenue_trend >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" class="w-3 h-3" />
                {{ Math.abs(stats.revenue_trend) }}%
              </div>
            </div>
            <div>
              <p class="text-3xl font-black text-gray-900 numeric">{{ formatRevenue(stats.revenue_this_month) }}</p>
              <p class="text-sm text-gray-500 mt-0.5">Revenus (F CFA)</p>
            </div>
          </div>
        </div>

        <!-- ═══════ SECONDARY STATS ═══════ -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div class="card px-4 py-3 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center">
              <UsersIcon class="w-4.5 h-4.5 text-indigo-600" />
            </div>
            <div>
              <p class="text-lg font-black text-gray-900 numeric">{{ stats.total_clients }}</p>
              <p class="text-[11px] text-gray-400">Clients total</p>
            </div>
          </div>
          <div class="card px-4 py-3 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-pink-50 flex items-center justify-center">
              <ArrowPathIcon class="w-4.5 h-4.5 text-pink-600" />
            </div>
            <div>
              <p class="text-lg font-black text-gray-900">{{ stats.returning_clients }}</p>
              <p class="text-[11px] text-gray-400">Clients fidèles</p>
            </div>
          </div>
          <div class="card px-4 py-3 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
              <CheckCircleIcon class="w-4.5 h-4.5 text-emerald-600" />
            </div>
            <div>
              <p class="text-lg font-black text-gray-900">{{ stats.completion_rate }}%</p>
              <p class="text-[11px] text-gray-400">Taux complétion</p>
            </div>
          </div>
          <div class="card px-4 py-3 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center">
              <XMarkIcon class="w-4.5 h-4.5 text-red-500" />
            </div>
            <div>
              <p class="text-lg font-black text-gray-900">{{ stats.cancellation_rate }}%</p>
              <p class="text-[11px] text-gray-400">Taux annulation</p>
            </div>
          </div>
        </div>

        <!-- Trial banner -->
        <div v-if="isOnTrial" class="card overflow-hidden">
          <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex items-center gap-3 flex-1">
              <span class="text-2xl">🎁</span>
              <div>
                <h3 class="font-bold text-white text-sm">Essai Pro gratuit</h3>
                <p class="text-white/80 text-xs">
                  <template v-if="trialDaysLeft <= 3">Plus que <strong>{{ trialDaysLeft }} jour{{ trialDaysLeft > 1 ? 's' : '' }}</strong> ! Passez au Pro pour ne rien perdre.</template>
                  <template v-else>Il vous reste <strong>{{ trialDaysLeft }} jours</strong> d'essai. Profitez de toutes les fonctionnalités Pro !</template>
                </p>
              </div>
            </div>
            <RouterLink to="/dashboard/billing" class="bg-clay-50 text-amber-700 font-bold text-xs px-4 py-2 rounded-xl hover:bg-amber-50 transition-colors shrink-0">
              Voir les plans
            </RouterLink>
          </div>
        </div>

        <!-- Plan usage. plan_limit is null on paid plans (unmetered), so this
             whole block only makes sense while a limit exists. -->
        <div v-if="stats.plan_limit" class="card p-5">
          <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex-1">
              <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-semibold text-gray-700">Réservations ce mois-ci</p>
                <span class="text-sm font-bold" :class="quotaIsTight ? 'text-amber-600' : 'text-gray-900'">
                  <span class="numeric">{{ stats.plan_used }} / {{ stats.plan_limit }}</span>
                </span>
              </div>
              <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-700"
                  :class="quotaIsTight ? 'bg-gradient-to-r from-amber-500 to-red-500' : 'bg-gradient-to-r from-primary-500 to-violet-500'"
                  :style="{ width: quotaPercent + '%' }"
                />
              </div>
              <p v-if="quotaIsTight" class="text-xs text-amber-600 mt-1.5 font-medium">
                ⚠ Bientôt à la limite — au-delà, vos clients ne pourront plus réserver ce mois-ci.
              </p>
            </div>
            <RouterLink to="/dashboard/billing" class="btn-primary text-xs px-4 py-2 whitespace-nowrap shrink-0">
              <SparklesIcon class="w-3.5 h-3.5" /> Passer Pro
            </RouterLink>
          </div>
        </div>

        <!-- ═══════ CHARTS ROW ═══════ -->
        <div class="grid lg:grid-cols-3 gap-6">
          <!-- Booking chart (daily/monthly toggle) -->
          <div class="card p-6 lg:col-span-2">
            <div class="flex items-center justify-between mb-6">
              <h3 class="font-bold text-gray-900">Réservations</h3>
              <div class="flex items-center bg-gray-100 rounded-lg p-0.5">
                <button @click="chartTab = 'daily'" :class="['text-xs font-semibold px-3 py-1.5 rounded-md transition-all', chartTab === 'daily' ? 'bg-clay-50 text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                  7 jours
                </button>
                <button @click="chartTab = 'monthly'" :class="['text-xs font-semibold px-3 py-1.5 rounded-md transition-all', chartTab === 'monthly' ? 'bg-clay-50 text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                  6 mois
                </button>
              </div>
            </div>

            <div v-if="chartHasData" class="flex items-end gap-2 h-48">
              <div v-for="(val, i) in activeChartData.values" :key="chartTab + '-' + i" class="flex flex-col items-center gap-1.5 flex-1">
                <span class="text-xs font-bold text-gray-600">{{ val || '' }}</span>
                <div
                  class="w-full rounded-t-lg transition-all duration-700 relative group cursor-default"
                  :class="val > 0 ? 'bg-gradient-to-t from-primary-600 to-primary-400 hover:from-primary-700 hover:to-primary-500' : 'bg-gray-100'"
                  :style="{ height: val > 0 ? Math.max((val / maxChartVal) * 100, 8) + '%' : '4px', minHeight: val > 0 ? '20px' : '4px' }"
                />
                <span class="text-[10px] text-gray-400 font-medium truncate max-w-full">{{ activeChartData.labels[i] }}</span>
              </div>
            </div>

            <div v-else class="h-48 flex flex-col items-center justify-center text-center">
              <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                <ChartBarIcon class="w-7 h-7 text-gray-300" />
              </div>
              <p class="text-sm text-gray-400 font-medium mb-1">Aucune donnée pour cette période</p>
              <p class="text-xs text-gray-300">Partagez votre lien pour recevoir vos premières réservations</p>
            </div>
          </div>

          <!-- Status distribution -->
          <div class="card p-6">
            <h3 class="font-bold text-gray-900 mb-6">Répartition</h3>

            <div v-if="statusTotal > 0" class="flex flex-col items-center gap-5">
              <!-- SVG donut -->
              <div class="relative w-36 h-36">
                <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                  <circle v-for="seg in statusSegments" :key="seg.status"
                    cx="18" cy="18" r="14" fill="none" stroke-width="5"
                    :stroke="seg.color"
                    :stroke-dasharray="`${seg.pct * 0.88} ${100 - seg.pct * 0.88}`"
                    :stroke-dashoffset="`${-seg.offset * 0.88}`"
                    stroke-linecap="round"
                    class="transition-all duration-700"
                  />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                  <span class="text-2xl font-black text-gray-900 numeric">{{ statusTotal }}</span>
                  <span class="text-[10px] text-gray-400">total</span>
                </div>
              </div>

              <!-- Legend -->
              <div class="w-full space-y-2">
                <div v-for="seg in statusSegments" :key="seg.status" class="flex items-center justify-between text-sm">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: seg.color }" />
                    <span class="text-gray-600">{{ seg.label }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-gray-900">{{ seg.count }}</span>
                    <span class="text-xs text-gray-400">({{ Math.round(seg.pct) }}%)</span>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="h-48 flex flex-col items-center justify-center text-center">
              <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                <ChartBarIcon class="w-6 h-6 text-gray-300" />
              </div>
              <p class="text-sm text-gray-400">Pas encore de données</p>
            </div>
          </div>
        </div>

        <!-- ═══════ UPCOMING + TOP SERVICES ═══════ -->
        <div class="grid lg:grid-cols-2 gap-6">
          <!-- Upcoming bookings -->
          <div class="card p-6">
            <div class="flex items-center justify-between mb-5">
              <h3 class="font-bold text-gray-900">Prochaines réservations</h3>
              <RouterLink to="/dashboard/bookings" class="text-xs text-primary-600 font-semibold hover:text-primary-700 flex items-center gap-1">
                Tout voir <ArrowRightIcon class="w-3 h-3" />
              </RouterLink>
            </div>

            <div v-if="upcoming.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
              <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                <CalendarDaysIcon class="w-7 h-7 text-gray-300" />
              </div>
              <p class="text-sm text-gray-400 font-medium mb-1">Aucune réservation à venir</p>
              <button @click="copyLink" v-if="publicUrl" class="btn-secondary text-xs mt-3">
                <LinkIcon class="w-3.5 h-3.5" /> Copier mon lien
              </button>
            </div>

            <div v-else class="space-y-1.5">
              <div v-for="b in upcoming.slice(0, 6)" :key="b.id"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors group">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-sm"
                  :style="{ backgroundColor: b.service?.color ?? '#a855f7' }">
                  {{ b.customer_name?.charAt(0)?.toUpperCase() }}
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-semibold text-sm text-gray-900 truncate">{{ b.customer_name }}</p>
                  <p class="text-xs text-gray-400 truncate">
                    {{ b.service?.name }} ·
                    <span class="numeric">{{ formatDate(b.date) }} {{ b.time_slot }}–{{ b.ends_at_time }}</span>
                  </p>
                  <!--
                    Le commerçant voit d'un coup ce que sa journée engage :
                    trois barres courtes se lisent autrement que deux longues,
                    même à nombre de rendez-vous égal.
                  -->
                  <DurationBar
                    :minutes="b.duration"
                    :color="b.service?.color"
                    size="sm"
                    class="mt-1.5 max-w-[150px]"
                  />
                </div>
                <div class="flex items-center gap-2">
                  <span :class="['badge text-[10px]', statusClass[b.status]]">{{ statusLabel[b.status] }}</span>
                  <div v-if="b.status === 'pending'" class="hidden group-hover:flex items-center gap-1">
                    <button @click.stop="confirmBooking(b.id)" :disabled="actionLoading === b.id"
                      class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-100 flex items-center justify-center transition-colors" title="Confirmer">
                      <CheckIcon class="w-3.5 h-3.5 text-emerald-600" />
                    </button>
                    <button @click.stop="cancelBooking(b.id)" :disabled="actionLoading === b.id"
                      class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-100 flex items-center justify-center transition-colors" title="Annuler">
                      <XMarkIcon class="w-3.5 h-3.5 text-red-500" />
                    </button>
                  </div>
                </div>
              </div>
              <RouterLink v-if="upcoming.length > 6" to="/dashboard/bookings"
                class="block text-center py-2 text-xs font-semibold text-primary-600 hover:text-primary-700">
                Voir les {{ upcoming.length - 6 }} autres →
              </RouterLink>
            </div>
          </div>

          <!-- Top services -->
          <div class="card p-6">
            <div class="flex items-center justify-between mb-5">
              <h3 class="font-bold text-gray-900">Services populaires</h3>
              <RouterLink to="/dashboard/services" class="text-xs text-primary-600 font-semibold hover:text-primary-700 flex items-center gap-1">
                Gérer <ArrowRightIcon class="w-3 h-3" />
              </RouterLink>
            </div>

            <div v-if="analytics?.top_services?.length" class="space-y-3">
              <div v-for="(svc, i) in analytics.top_services" :key="i" class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-sm font-bold shrink-0"
                  :style="{ backgroundColor: svc.color }">
                  {{ i + 1 }}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ svc.name }}</p>
                    <span class="text-sm font-bold text-gray-900 ml-2">{{ svc.count }}</span>
                  </div>
                  <div class="mt-1.5 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700"
                      :style="{ width: (svc.count / analytics.top_services[0].count * 100) + '%', backgroundColor: svc.color }" />
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="flex flex-col items-center justify-center py-8 text-center">
              <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                <Squares2X2Icon class="w-7 h-7 text-gray-300" />
              </div>
              <p class="text-sm text-gray-400 font-medium">Aucune donnée encore</p>
            </div>
          </div>
        </div>

        <!-- Advanced analytics are a Pro feature. When the API declines, say so
             plainly instead of leaving three empty cards on the page. -->
        <div v-if="!analytics" class="card p-8 text-center border-2 border-dashed border-primary-100 bg-primary-50/30">
          <div class="w-14 h-14 rounded-2xl bg-clay-50 flex items-center justify-center mx-auto mb-4 shadow-sm">
            <ChartBarIcon class="w-7 h-7 text-primary-500" />
          </div>
          <h3 class="font-bold text-gray-900 mb-1.5">Statistiques avancées</h3>
          <p class="text-sm text-gray-500 max-w-md mx-auto leading-relaxed mb-5">
            Heures de pointe, jours les plus chargés, revenus par service et suivi
            de l'activité : disponibles avec le plan Pro.
          </p>
          <RouterLink :to="{ name: 'billing' }" class="btn-primary px-6 py-2.5 text-sm inline-flex">
            Découvrir le plan Pro
          </RouterLink>
        </div>

        <!-- ═══════ PEAK HOURS + PEAK DAYS ═══════ -->
        <div v-if="analytics" class="grid lg:grid-cols-2 gap-6">
          <!-- Peak hours heatmap -->
          <div class="card p-6">
            <div class="flex items-center gap-2 mb-5">
              <BoltIcon class="w-5 h-5 text-amber-500" />
              <h3 class="font-bold text-gray-900">Heures de pointe</h3>
            </div>

            <div v-if="peakHoursFormatted.some(h => h.count > 0)" class="space-y-4">
              <div class="flex items-end gap-1 h-32">
                <div v-for="h in peakHoursFormatted" :key="h.label" class="flex flex-col items-center gap-1 flex-1">
                  <span v-if="h.count > 0" class="text-[9px] font-bold text-gray-500">{{ h.count }}</span>
                  <div
                    class="w-full rounded-t-md transition-all duration-500"
                    :class="h.count > 0 ? '' : 'bg-gray-100'"
                    :style="{
                      height: h.count > 0 ? Math.max((h.count / maxPeakHour) * 100, 8) + '%' : '3px',
                      minHeight: h.count > 0 ? '12px' : '3px',
                      backgroundColor: h.count > 0 ? `rgba(245, 158, 11, ${0.3 + (h.count / maxPeakHour) * 0.7})` : undefined,
                    }"
                  />
                  <span class="text-[9px] text-gray-400">{{ h.label }}</span>
                </div>
              </div>
              <p class="text-[11px] text-gray-400 text-center">Basé sur l'ensemble de vos réservations</p>
            </div>

            <div v-else class="h-32 flex items-center justify-center">
              <p class="text-sm text-gray-400">Pas assez de données</p>
            </div>
          </div>

          <!-- Peak days -->
          <div class="card p-6">
            <div class="flex items-center gap-2 mb-5">
              <FireIcon class="w-5 h-5 text-orange-500" />
              <h3 class="font-bold text-gray-900">Jours les plus actifs</h3>
            </div>

            <div v-if="analytics.peak_days?.some(d => d.count > 0)" class="space-y-2.5">
              <div v-for="d in analytics.peak_days" :key="d.day" class="flex items-center gap-3">
                <span class="text-xs font-semibold text-gray-500 w-8">{{ d.day }}</span>
                <div class="flex-1 h-7 bg-gray-50 rounded-lg overflow-hidden relative">
                  <div
                    class="h-full rounded-lg transition-all duration-700 flex items-center px-2"
                    :style="{
                      width: d.count > 0 ? Math.max((d.count / maxPeakDay) * 100, 8) + '%' : '0%',
                      backgroundColor: `rgba(249, 115, 22, ${0.2 + (d.count / maxPeakDay) * 0.6})`,
                    }"
                  >
                    <span v-if="d.count > 0" class="text-[11px] font-bold text-orange-900/70">{{ d.count }}</span>
                  </div>
                </div>
              </div>
              <p class="text-[11px] text-gray-400 text-center pt-1">Basé sur l'ensemble de vos réservations</p>
            </div>

            <div v-else class="h-40 flex items-center justify-center">
              <p class="text-sm text-gray-400">Pas assez de données</p>
            </div>
          </div>
        </div>

        <!-- ═══════ RECENT ACTIVITY + REVENUE BY SERVICE ═══════ -->
        <div v-if="analytics" class="grid lg:grid-cols-2 gap-6">
          <!-- Recent activity feed -->
          <div class="card p-6">
            <h3 class="font-bold text-gray-900 mb-5">Activité récente</h3>

            <div v-if="analytics.recent_activity?.length" class="space-y-1">
              <div v-for="a in analytics.recent_activity.slice(0, 8)" :key="a.id"
                class="flex items-center gap-3 py-2.5 border-b border-gray-50 last:border-0">
                <span class="text-base shrink-0">{{ statusIcon[a.status] }}</span>
                <div class="flex-1 min-w-0">
                  <p class="text-sm text-gray-700">
                    <span class="font-semibold">{{ a.customer_name }}</span>
                    <span class="text-gray-400"> · </span>
                    <span class="text-gray-500">{{ a.service_name }}</span>
                  </p>
                  <p class="text-[11px] text-gray-400 mt-0.5">
                    {{ a.reference }} · {{ a.updated_at }}
                  </p>
                </div>
                <span :class="['badge text-[10px]', statusClass[a.status]]">{{ statusLabel[a.status] }}</span>
              </div>
            </div>

            <div v-else class="py-8 text-center">
              <p class="text-sm text-gray-400">Aucune activité récente</p>
            </div>
          </div>

          <!-- Revenue by service -->
          <div class="card p-6">
            <h3 class="font-bold text-gray-900 mb-5">Revenus par service</h3>

            <div v-if="analytics.top_revenue?.length" class="space-y-3">
              <div v-for="(svc, i) in analytics.top_revenue" :key="i"
                class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0">
                <div class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: svc.color }" />
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-gray-900 truncate">{{ svc.name }}</p>
                  <p class="text-[11px] text-gray-400">{{ svc.bookings_count }} réservation(s)</p>
                </div>
                <span class="text-sm font-black text-gray-900 numeric">{{ formatRevenue(svc.revenue) }} F</span>
              </div>
            </div>

            <div v-else class="py-8 text-center">
              <p class="text-sm text-gray-400">Aucun revenu enregistré</p>
            </div>
          </div>
        </div>

        <!-- ═══════ QUICK ACTIONS ═══════ -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <RouterLink to="/dashboard/services" class="card p-4 flex items-center gap-3 hover:shadow-md transition-all group cursor-pointer">
            <div class="w-10 h-10 rounded-xl bg-primary-50 flex items-center justify-center group-hover:bg-primary-100 transition-colors">
              <PlusIcon class="w-5 h-5 text-primary-600" />
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-900">Nouveau service</p>
              <p class="text-xs text-gray-400">Ajouter un service</p>
            </div>
          </RouterLink>

          <RouterLink to="/dashboard/bookings" class="card p-4 flex items-center gap-3 hover:shadow-md transition-all group cursor-pointer">
            <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center group-hover:bg-violet-100 transition-colors">
              <CalendarDaysIcon class="w-5 h-5 text-violet-600" />
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-900">Réservations</p>
              <p class="text-xs text-gray-400">Gérer et filtrer</p>
            </div>
          </RouterLink>

          <RouterLink to="/dashboard/settings" class="card p-4 flex items-center gap-3 hover:shadow-md transition-all group cursor-pointer">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center group-hover:bg-amber-100 transition-colors">
              <Cog6ToothIcon class="w-5 h-5 text-amber-600" />
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-900">Paramètres</p>
              <p class="text-xs text-gray-400">Horaires, profil</p>
            </div>
          </RouterLink>

          <button @click="shareLink" class="card p-4 flex items-center gap-3 hover:shadow-md transition-all group cursor-pointer text-left">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
              <ShareIcon class="w-5 h-5 text-emerald-600" />
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-900">Partager</p>
              <p class="text-xs text-gray-400">Votre page publique</p>
            </div>
          </button>
        </div>
      </template>
    </template>
  </div>
</template>
