<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { dashboardApi, bookingsApi } from '@/api'
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
} from '@heroicons/vue/24/outline'
import { format, parseISO } from 'date-fns'
import { fr } from 'date-fns/locale'

const auth     = useAuthStore()
const stats    = ref(null)
const upcoming = ref([])
const chart    = ref(null)
const loading  = ref(true)
const error    = ref('')
const actionLoading = ref(null)

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

// Onboarding checklist
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

// Load dashboard data
async function loadDashboard() {
  loading.value = true
  error.value = ''
  try {
    const [statsRes, upcomingRes, chartRes] = await Promise.all([
      dashboardApi.stats(),
      dashboardApi.upcoming(),
      dashboardApi.chart(),
    ])
    stats.value    = statsRes.data
    upcoming.value = upcomingRes.data
    chart.value    = chartRes.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Impossible de charger le tableau de bord.'
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)

// Quick actions on bookings
async function confirmBooking(id) {
  actionLoading.value = id
  try {
    await bookingsApi.updateStatus(id, 'confirmed')
    const idx = upcoming.value.findIndex(b => b.id === id)
    if (idx !== -1) upcoming.value[idx].status = 'confirmed'
    if (stats.value) {
      stats.value.pending_bookings = Math.max(0, stats.value.pending_bookings - 1)
    }
  } catch {}
  actionLoading.value = null
}

async function cancelBooking(id) {
  actionLoading.value = id
  try {
    await bookingsApi.cancel(id)
    upcoming.value = upcoming.value.filter(b => b.id !== id)
    if (stats.value) {
      stats.value.pending_bookings = Math.max(0, stats.value.pending_bookings - 1)
    }
  } catch {}
  actionLoading.value = null
}

// Share link
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
  try {
    return format(parseISO(dateStr), 'EEE d MMM', { locale: fr })
  } catch {
    return dateStr
  }
}

function formatRevenue(amount) {
  if (!amount || amount === 0) return '0'
  if (amount >= 1000000) return (amount / 1000000).toFixed(1) + 'M'
  if (amount >= 1000) return (amount / 1000).toFixed(0) + 'k'
  return amount.toLocaleString('fr-FR')
}

const statusClass = {
  pending:   'badge-pending',
  confirmed: 'badge-confirmed',
  cancelled: 'badge-cancelled',
  completed: 'badge-completed',
}
const statusLabel = {
  pending:   'En attente',
  confirmed: 'Confirmé',
  cancelled: 'Annulé',
  completed: 'Terminé',
}

// Chart
const maxChartVal = computed(() => {
  if (!chart.value) return 1
  return Math.max(...chart.value.daily.values, 1)
})

const chartHasData = computed(() => {
  if (!chart.value) return false
  return chart.value.daily.values.some(v => v > 0)
})

// Time greeting
const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Bonjour'
  if (h < 18) return 'Bon après-midi'
  return 'Bonsoir'
})
</script>

<template>
  <div class="space-y-6">
    <!-- Error state -->
    <div v-if="error && !loading" class="card p-8 text-center">
      <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center mx-auto mb-4">
        <XMarkIcon class="w-7 h-7 text-red-500" />
      </div>
      <h3 class="font-bold text-gray-900 mb-2">Erreur de chargement</h3>
      <p class="text-sm text-gray-500 mb-4">{{ error }}</p>
      <button @click="loadDashboard" class="btn-primary">Réessayer</button>
    </div>

    <template v-else>
      <!-- Welcome banner -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-black text-gray-900">
            {{ greeting }}, {{ auth.user?.name?.split(' ')[0] }} 👋
          </h2>
          <p class="text-gray-500 mt-1">
            {{ new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
          </p>
        </div>

        <!-- Public link -->
        <div v-if="publicUrl" class="flex items-center gap-2">
          <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-500 max-w-[220px] overflow-hidden">
            <LinkIcon class="w-4 h-4 shrink-0 text-primary-500" />
            <span class="truncate text-xs font-mono">{{ publicUrl.replace(/^https?:\/\//, '') }}</span>
          </div>
          <button @click="copyLink" class="btn-primary px-3 py-2 text-xs">
            <template v-if="linkCopied">
              <CheckIcon class="w-3.5 h-3.5" /> Copié !
            </template>
            <template v-else>Copier</template>
          </button>
          <button @click="shareLink" class="btn-secondary px-3 py-2 text-xs" title="Partager">
            <ShareIcon class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- Onboarding checklist (new users) -->
      <div v-if="!loading && showOnboarding" class="card overflow-hidden">
        <div class="bg-gradient-to-r from-primary-600 to-violet-600 px-6 py-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
              <RocketLaunchIcon class="w-5 h-5 text-white" />
            </div>
            <div>
              <h3 class="font-bold text-white">Bienvenue sur Réserva !</h3>
              <p class="text-white/70 text-sm">Complétez ces étapes pour recevoir vos premières réservations</p>
            </div>
            <div class="ml-auto hidden sm:flex items-center gap-2">
              <span class="text-white/70 text-sm font-medium">{{ onboardingDone }}/{{ onboarding.length }}</span>
              <div class="w-20 h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-white rounded-full transition-all duration-500" :style="{ width: (onboardingDone / onboarding.length * 100) + '%' }" />
              </div>
            </div>
          </div>
        </div>
        <div class="divide-y divide-gray-50">
          <div v-for="step in onboarding" :key="step.id" class="flex items-center gap-4 px-6 py-4">
            <div :class="['w-8 h-8 rounded-full flex items-center justify-center shrink-0 transition-colors', step.done ? 'bg-emerald-100' : 'bg-gray-100']">
              <CheckIcon v-if="step.done" class="w-4 h-4 text-emerald-600" />
              <component v-else :is="step.icon" class="w-4 h-4 text-gray-400" />
            </div>
            <span :class="['flex-1 text-sm font-medium', step.done ? 'text-gray-400 line-through' : 'text-gray-700']">
              {{ step.label }}
            </span>
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
          <div class="card p-6 animate-pulse"><div class="h-40 bg-gray-50 rounded-xl" /></div>
          <div class="card p-6 animate-pulse"><div class="h-40 bg-gray-50 rounded-xl" /></div>
        </div>
      </div>

      <!-- Stats cards -->
      <div v-else-if="stats" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
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
            <p class="text-3xl font-black text-gray-900">{{ stats.monthly_bookings }}</p>
            <p class="text-sm text-gray-500 mt-0.5">Ce mois-ci</p>
          </div>
        </div>

        <!-- Today -->
        <div class="stat-card group hover:shadow-md transition-shadow">
          <div class="w-10 h-10 rounded-2xl bg-violet-50 flex items-center justify-center group-hover:bg-violet-100 transition-colors">
            <ClockIcon class="w-5 h-5 text-violet-600" />
          </div>
          <div>
            <p class="text-3xl font-black text-gray-900">{{ stats.today_bookings }}</p>
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
            <p class="text-3xl font-black text-gray-900">{{ stats.pending_bookings }}</p>
            <p class="text-sm text-gray-500 mt-0.5">En attente</p>
          </div>
        </RouterLink>

        <!-- Revenue -->
        <div class="stat-card group hover:shadow-md transition-shadow">
          <div class="w-10 h-10 rounded-2xl bg-emerald-50 flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
            <CurrencyDollarIcon class="w-5 h-5 text-emerald-600" />
          </div>
          <div>
            <p class="text-3xl font-black text-gray-900">{{ formatRevenue(stats.revenue_this_month) }}</p>
            <p class="text-sm text-gray-500 mt-0.5">Revenus (F CFA)</p>
          </div>
        </div>
      </div>

      <!-- Plan usage bar -->
      <div v-if="stats && stats.plan === 'free'" class="card p-5">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
          <div class="flex-1">
            <div class="flex items-center justify-between mb-2">
              <p class="text-sm font-semibold text-gray-700">Utilisation du plan gratuit</p>
              <span class="text-sm font-bold" :class="stats.plan_used >= 25 ? 'text-amber-600' : 'text-gray-900'">
                {{ stats.plan_used }} / {{ stats.plan_limit }}
              </span>
            </div>
            <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-700"
                :class="stats.plan_used >= 25 ? 'bg-gradient-to-r from-amber-500 to-red-500' : 'bg-gradient-to-r from-primary-500 to-violet-500'"
                :style="{ width: Math.min((stats.plan_used / stats.plan_limit) * 100, 100) + '%' }"
              />
            </div>
            <p v-if="stats.plan_used >= stats.plan_limit * 0.8" class="text-xs text-amber-600 mt-1.5 font-medium">
              ⚠ Presque à la limite — passez au Pro pour des réservations illimitées.
            </p>
          </div>
          <RouterLink to="/dashboard/settings" class="btn-primary text-xs px-4 py-2 whitespace-nowrap shrink-0">
            <SparklesIcon class="w-3.5 h-3.5" /> Passer Pro
          </RouterLink>
        </div>
      </div>

      <!-- Main grid -->
      <div v-if="!loading" class="grid lg:grid-cols-2 gap-6">
        <!-- 7-day chart -->
        <div class="card p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-gray-900">7 derniers jours</h3>
            <div v-if="chartHasData" class="flex items-center gap-1.5 text-xs text-gray-400">
              <div class="w-2.5 h-2.5 rounded-sm bg-primary-500" />
              Réservations
            </div>
          </div>

          <!-- Chart with data -->
          <div v-if="chart && chartHasData" class="flex items-end gap-2 h-44">
            <div
              v-for="(val, i) in chart.daily.values"
              :key="i"
              class="flex flex-col items-center gap-1.5 flex-1"
            >
              <span class="text-xs font-bold text-gray-600">{{ val || '' }}</span>
              <div class="w-full rounded-t-lg transition-all duration-700 relative group cursor-default"
                :class="val > 0 ? 'bg-gradient-to-t from-primary-600 to-primary-400 hover:from-primary-700 hover:to-primary-500' : 'bg-gray-100'"
                :style="{ height: val > 0 ? Math.max((val / maxChartVal) * 100, 12) + '%' : '4px', minHeight: val > 0 ? '20px' : '4px' }"
              />
              <span class="text-[11px] text-gray-400 font-medium">{{ chart.daily.labels[i] }}</span>
            </div>
          </div>

          <!-- Empty chart -->
          <div v-else class="h-44 flex flex-col items-center justify-center text-center">
            <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
              <CalendarDaysIcon class="w-7 h-7 text-gray-300" />
            </div>
            <p class="text-sm text-gray-400 font-medium mb-1">Aucune réservation cette semaine</p>
            <p class="text-xs text-gray-300">Partagez votre lien pour recevoir vos premières réservations</p>
          </div>
        </div>

        <!-- Upcoming bookings -->
        <div class="card p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-gray-900">Prochaines réservations</h3>
            <RouterLink to="/dashboard/bookings" class="text-xs text-primary-600 font-semibold hover:text-primary-700 flex items-center gap-1">
              Tout voir <ArrowRightIcon class="w-3 h-3" />
            </RouterLink>
          </div>

          <!-- Empty state -->
          <div v-if="upcoming.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
            <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
              <CalendarDaysIcon class="w-7 h-7 text-gray-300" />
            </div>
            <p class="text-sm text-gray-400 font-medium mb-1">Aucune réservation à venir</p>
            <p class="text-xs text-gray-300 mb-4">Vos prochaines réservations apparaîtront ici</p>
            <button @click="copyLink" v-if="publicUrl" class="btn-secondary text-xs">
              <LinkIcon class="w-3.5 h-3.5" /> Copier mon lien de réservation
            </button>
          </div>

          <!-- Bookings list -->
          <div v-else class="space-y-2">
            <div
              v-for="b in upcoming.slice(0, 5)"
              :key="b.id"
              class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors group"
            >
              <!-- Avatar -->
              <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-sm"
                :style="{ backgroundColor: b.service?.color ?? '#6366f1' }">
                {{ b.customer_name?.charAt(0)?.toUpperCase() }}
              </div>

              <!-- Info -->
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm text-gray-900 truncate">{{ b.customer_name }}</p>
                <p class="text-xs text-gray-400 truncate">
                  {{ b.service?.name }} · {{ formatDate(b.date) }} à {{ b.time_slot }}
                </p>
              </div>

              <!-- Status + Actions -->
              <div class="flex items-center gap-2">
                <span :class="['badge', statusClass[b.status]]">{{ statusLabel[b.status] }}</span>

                <!-- Quick actions for pending -->
                <div v-if="b.status === 'pending'" class="hidden group-hover:flex items-center gap-1">
                  <button
                    @click.stop="confirmBooking(b.id)"
                    :disabled="actionLoading === b.id"
                    class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-100 flex items-center justify-center transition-colors"
                    title="Confirmer"
                  >
                    <CheckIcon class="w-3.5 h-3.5 text-emerald-600" />
                  </button>
                  <button
                    @click.stop="cancelBooking(b.id)"
                    :disabled="actionLoading === b.id"
                    class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-100 flex items-center justify-center transition-colors"
                    title="Annuler"
                  >
                    <XMarkIcon class="w-3.5 h-3.5 text-red-500" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Show more link if > 5 -->
            <RouterLink v-if="upcoming.length > 5" to="/dashboard/bookings"
              class="block text-center py-2 text-xs font-semibold text-primary-600 hover:text-primary-700">
              Voir les {{ upcoming.length - 5 }} autres →
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- Quick actions bar -->
      <div v-if="!loading && stats" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
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
  </div>
</template>
