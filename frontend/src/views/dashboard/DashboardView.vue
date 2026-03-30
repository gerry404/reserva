<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { dashboardApi } from '@/api'
import {
  CalendarDaysIcon,
  ClockIcon,
  CurrencyDollarIcon,
  CheckCircleIcon,
  LinkIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline'
import { format, parseISO } from 'date-fns'
import { fr } from 'date-fns/locale'

const auth     = useAuthStore()
const stats    = ref(null)
const upcoming = ref([])
const chart    = ref(null)
const loading  = ref(true)

const publicUrl = computed(() => {
  const slug = auth.business?.slug
  return slug ? `${window.location.origin}/b/${slug}` : null
})

const linkCopied = ref(false)
async function copyLink() {
  if (!publicUrl.value) return
  await navigator.clipboard.writeText(publicUrl.value)
  linkCopied.value = true
  setTimeout(() => linkCopied.value = false, 2000)
}

onMounted(async () => {
  try {
    const [statsRes, upcomingRes, chartRes] = await Promise.all([
      dashboardApi.stats(),
      dashboardApi.upcoming(),
      dashboardApi.chart(),
    ])
    stats.value    = statsRes.data
    upcoming.value = upcomingRes.data
    chart.value    = chartRes.data
  } finally {
    loading.value = false
  }
})

function formatDate(dateStr) {
  try {
    return format(parseISO(dateStr), 'EEE d MMM', { locale: fr })
  } catch {
    return dateStr
  }
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

// Simple bar chart heights
const maxChartVal = computed(() => {
  if (!chart.value) return 1
  return Math.max(...chart.value.daily.values, 1)
})
</script>

<template>
  <div class="space-y-8 animate-fade-in">
    <!-- Welcome banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-black text-gray-900">
          Bonjour, {{ auth.user?.name?.split(' ')[0] }} 👋
        </h2>
        <p class="text-gray-500 mt-1">
          {{ new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }) }}
        </p>
      </div>

      <!-- Public link -->
      <div v-if="publicUrl" class="flex items-center gap-2">
        <div class="flex items-center gap-2 px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-600 max-w-xs overflow-hidden">
          <LinkIcon class="w-4 h-4 shrink-0 text-primary-500" />
          <span class="truncate text-xs">{{ publicUrl }}</span>
        </div>
        <button @click="copyLink" class="btn-primary px-3 py-2 text-xs">
          {{ linkCopied ? '✓ Copié !' : 'Copier' }}
        </button>
      </div>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="i in 4" :key="i" class="card p-6 animate-pulse">
        <div class="h-4 bg-gray-100 rounded mb-3 w-3/4" />
        <div class="h-8 bg-gray-100 rounded w-1/2" />
      </div>
    </div>

    <!-- Stats cards -->
    <div v-else-if="stats" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="stat-card">
        <div class="flex items-center justify-between">
          <div class="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center">
            <CalendarDaysIcon class="w-5 h-5 text-blue-600" />
          </div>
          <div :class="['flex items-center gap-1 text-xs font-semibold', stats.monthly_trend >= 0 ? 'text-emerald-600' : 'text-red-500']">
            <component :is="stats.monthly_trend >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" class="w-3.5 h-3.5" />
            {{ Math.abs(stats.monthly_trend) }}%
          </div>
        </div>
        <div>
          <p class="text-3xl font-black text-gray-900">{{ stats.monthly_bookings }}</p>
          <p class="text-sm text-gray-500 mt-0.5">Ce mois-ci</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="w-10 h-10 rounded-2xl bg-amber-50 flex items-center justify-center">
          <ClockIcon class="w-5 h-5 text-amber-600" />
        </div>
        <div>
          <p class="text-3xl font-black text-gray-900">{{ stats.today_bookings }}</p>
          <p class="text-sm text-gray-500 mt-0.5">Aujourd'hui</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="flex items-center justify-between">
          <div class="w-10 h-10 rounded-2xl bg-amber-50 flex items-center justify-center">
            <UserGroupIcon class="w-5 h-5 text-amber-600" />
          </div>
          <span v-if="stats.pending_bookings > 0" class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Action requise</span>
        </div>
        <div>
          <p class="text-3xl font-black text-gray-900">{{ stats.pending_bookings }}</p>
          <p class="text-sm text-gray-500 mt-0.5">En attente</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="w-10 h-10 rounded-2xl bg-emerald-50 flex items-center justify-center">
          <CurrencyDollarIcon class="w-5 h-5 text-emerald-600" />
        </div>
        <div>
          <p class="text-3xl font-black text-gray-900">
            {{ stats.revenue_this_month ? (stats.revenue_this_month / 1000).toFixed(0) + 'k' : '—' }}
          </p>
          <p class="text-sm text-gray-500 mt-0.5">Revenus (F CFA)</p>
        </div>
      </div>
    </div>

    <!-- Plan usage (free plan) -->
    <div v-if="stats && stats.plan === 'free'" class="card p-5 flex items-center gap-4">
      <div class="flex-1">
        <div class="flex items-center justify-between mb-2">
          <p class="text-sm font-semibold text-gray-700">Réservations ce mois</p>
          <span class="text-sm font-bold text-gray-900">{{ stats.plan_used }} / 30</span>
        </div>
        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
          <div
            class="h-full bg-gradient-to-r from-primary-500 to-violet-500 rounded-full transition-all duration-500"
            :style="{ width: Math.min((stats.plan_used / 30) * 100, 100) + '%' }"
          />
        </div>
        <p v-if="stats.plan_used >= 25" class="text-xs text-amber-600 mt-1">
          Bientôt à la limite — passez au plan Pro pour des réservations illimitées.
        </p>
      </div>
      <a href="#" class="btn-primary text-xs px-4 py-2 whitespace-nowrap">Passer Pro</a>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
      <!-- 7-day chart -->
      <div v-if="chart" class="card p-6">
        <h3 class="font-bold text-gray-900 mb-6">Réservations — 7 derniers jours</h3>
        <div class="flex items-end gap-2 h-40">
          <div
            v-for="(val, i) in chart.daily.values"
            :key="i"
            class="flex flex-col items-center gap-1 flex-1"
          >
            <span class="text-xs font-semibold text-gray-600">{{ val || '' }}</span>
            <div
              class="w-full rounded-t-lg bg-gradient-to-t from-primary-600 to-primary-400 transition-all duration-500"
              :style="{ height: maxChartVal > 0 ? Math.max((val / maxChartVal) * 100, val > 0 ? 8 : 4) + '%' : '4px', minHeight: '4px' }"
            />
            <span class="text-xs text-gray-400">{{ chart.daily.labels[i] }}</span>
          </div>
        </div>
      </div>

      <!-- Upcoming bookings -->
      <div class="card p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="font-bold text-gray-900">Prochaines réservations</h3>
          <RouterLink to="/dashboard/bookings" class="text-xs text-primary-600 font-semibold hover:underline">
            Tout voir →
          </RouterLink>
        </div>

        <div v-if="upcoming.length === 0" class="text-center py-8 text-gray-400">
          <CalendarDaysIcon class="w-12 h-12 mx-auto mb-2 opacity-40" />
          <p class="text-sm">Aucune réservation à venir</p>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="b in upcoming"
            :key="b.id"
            class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors"
          >
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0"
              :style="{ backgroundColor: b.service?.color ?? '#6366f1' }">
              {{ b.customer_name?.charAt(0) }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-sm text-gray-900 truncate">{{ b.customer_name }}</p>
              <p class="text-xs text-gray-400 truncate">{{ b.service?.name }} · {{ formatDate(b.date) }} à {{ b.time_slot }}</p>
            </div>
            <span :class="['badge', statusClass[b.status]]">{{ statusLabel[b.status] }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
