<script setup>
import { ref, onMounted, watch } from 'vue'
import { useBookingsStore } from '@/stores/bookings'
import {
  MagnifyingGlassIcon,
  FunnelIcon,
  ArrowDownTrayIcon,
  CheckIcon,
  XMarkIcon,
  EllipsisHorizontalIcon,
  CalendarDaysIcon,
  PhoneIcon,
} from '@heroicons/vue/24/outline'
import { format, parseISO } from 'date-fns'
import { fr } from 'date-fns/locale'

const store       = useBookingsStore()
const actionMenu  = ref(null) // booking id with open menu
const confirmModal = ref(null) // { bookingId, action }

onMounted(() => store.fetchBookings())

// Re-fetch when filters change (debounced for search)
let searchTimeout = null
watch(() => store.filters.search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => store.fetchBookings(), 400)
})
watch(() => [store.filters.status, store.filters.date], () => store.fetchBookings())

const statusOptions = [
  { value: '',           label: 'Tous les statuts' },
  { value: 'pending',    label: 'En attente' },
  { value: 'confirmed',  label: 'Confirmé' },
  { value: 'completed',  label: 'Terminé' },
  { value: 'cancelled',  label: 'Annulé' },
]

const statusConfig = {
  pending:   { label: 'En attente', class: 'badge-pending' },
  confirmed: { label: 'Confirmé',   class: 'badge-confirmed' },
  cancelled: { label: 'Annulé',     class: 'badge-cancelled' },
  completed: { label: 'Terminé',    class: 'badge-completed' },
}

function formatDate(d) {
  try { return format(parseISO(d), 'EEE d MMM', { locale: fr }) }
  catch { return d }
}

async function changeStatus(id, status) {
  confirmModal.value = null
  actionMenu.value   = null
  await store.updateStatus(id, status)
}

async function cancelBooking(id) {
  await store.cancelBooking(id)
  confirmModal.value = null
  actionMenu.value   = null
}

function openConfirm(bookingId, action) {
  confirmModal.value = { bookingId, action }
  actionMenu.value   = null
}

function goToPage(page) {
  store.fetchBookings({ page })
}
</script>

<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-xl font-black text-gray-900">Réservations</h2>
        <p class="text-sm text-gray-500 mt-0.5">{{ store.pagination?.total ?? 0 }} réservation(s) au total</p>
      </div>
      <button @click="store.exportCsv()" class="btn-secondary text-sm">
        <ArrowDownTrayIcon class="w-4 h-4" />
        Exporter CSV
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4 flex flex-col sm:flex-row gap-3">
      <!-- Search -->
      <div class="relative flex-1">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input
          v-model="store.filters.search"
          type="search"
          class="input-field pl-9"
          placeholder="Rechercher un client, une référence…"
        />
      </div>

      <!-- Status filter -->
      <select v-model="store.filters.status" class="input-field sm:w-48">
        <option v-for="o in statusOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>

      <!-- Date filter -->
      <input v-model="store.filters.date" type="date" class="input-field sm:w-44" />

      <!-- Reset -->
      <button v-if="store.filters.search || store.filters.status || store.filters.date" @click="store.resetFilters(); store.fetchBookings()" class="btn-ghost text-sm">
        <XMarkIcon class="w-4 h-4" /> Réinitialiser
      </button>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 bg-gray-50/70">
              <th class="text-left font-semibold text-gray-500 px-5 py-3.5 whitespace-nowrap">Client</th>
              <th class="text-left font-semibold text-gray-500 px-4 py-3.5">Service</th>
              <th class="text-left font-semibold text-gray-500 px-4 py-3.5 whitespace-nowrap">Date & Heure</th>
              <th class="text-left font-semibold text-gray-500 px-4 py-3.5">Statut</th>
              <th class="text-left font-semibold text-gray-500 px-4 py-3.5">Réf.</th>
              <th class="px-4 py-3.5" />
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <!-- Loading -->
            <template v-if="store.loading">
              <tr v-for="i in 5" :key="i" class="animate-pulse">
                <td class="px-5 py-4"><div class="h-4 bg-gray-100 rounded w-32" /></td>
                <td class="px-4 py-4"><div class="h-4 bg-gray-100 rounded w-24" /></td>
                <td class="px-4 py-4"><div class="h-4 bg-gray-100 rounded w-28" /></td>
                <td class="px-4 py-4"><div class="h-5 bg-gray-100 rounded-full w-20" /></td>
                <td class="px-4 py-4"><div class="h-4 bg-gray-100 rounded w-24" /></td>
                <td class="px-4 py-4" />
              </tr>
            </template>

            <!-- Empty -->
            <tr v-else-if="store.bookings.length === 0">
              <td colspan="6" class="py-20 text-center">
                <CalendarDaysIcon class="w-12 h-12 text-gray-200 mx-auto mb-3" />
                <p class="text-gray-400 font-medium">Aucune réservation trouvée</p>
                <p class="text-gray-300 text-xs mt-1">Essayez de modifier les filtres</p>
              </td>
            </tr>

            <!-- Rows -->
            <tr v-else v-for="b in store.bookings" :key="b.id" class="hover:bg-gray-50/50 transition-colors">
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary-500 to-violet-500 flex items-center justify-center text-white font-bold text-xs shrink-0">
                    {{ b.customer_name.charAt(0) }}
                  </div>
                  <div>
                    <p class="font-semibold text-gray-900">{{ b.customer_name }}</p>
                    <a :href="'tel:' + b.customer_phone" class="flex items-center gap-1 text-xs text-gray-400 hover:text-primary-600">
                      <PhoneIcon class="w-3 h-3" />{{ b.customer_phone }}
                    </a>
                  </div>
                </div>
              </td>
              <td class="px-4 py-4">
                <div class="flex items-center gap-2">
                  <div v-if="b.service" class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: b.service.color ?? '#6366f1' }" />
                  <span class="text-gray-700">{{ b.service?.name ?? '—' }}</span>
                </div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <p class="text-gray-700 font-medium">{{ formatDate(b.date) }}</p>
                <p class="text-xs text-gray-400">{{ b.time_slot }}</p>
              </td>
              <td class="px-4 py-4">
                <span :class="['badge', statusConfig[b.status]?.class ?? 'badge-pending']">
                  {{ statusConfig[b.status]?.label ?? b.status }}
                </span>
              </td>
              <td class="px-4 py-4">
                <span class="font-mono text-xs text-gray-400">{{ b.reference }}</span>
              </td>
              <td class="px-4 py-4 relative">
                <button @click="actionMenu = actionMenu === b.id ? null : b.id" class="btn-ghost p-2">
                  <EllipsisHorizontalIcon class="w-5 h-5" />
                </button>

                <!-- Action dropdown -->
                <Transition name="menu">
                  <div
                    v-if="actionMenu === b.id"
                    class="absolute right-4 top-full mt-1 w-48 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden z-10"
                  >
                    <template v-if="b.status === 'pending'">
                      <button @click="openConfirm(b.id, 'confirm')" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-emerald-700 hover:bg-emerald-50 transition-colors">
                        <CheckIcon class="w-4 h-4" /> Confirmer
                      </button>
                    </template>
                    <template v-if="['pending', 'confirmed'].includes(b.status)">
                      <button @click="openConfirm(b.id, 'complete')" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-blue-700 hover:bg-blue-50 transition-colors">
                        <CheckIcon class="w-4 h-4" /> Marquer terminé
                      </button>
                      <button @click="openConfirm(b.id, 'cancel')" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                        <XMarkIcon class="w-4 h-4" /> Annuler
                      </button>
                    </template>
                    <button @click="actionMenu = null" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-gray-500 hover:bg-gray-50 transition-colors border-t border-gray-50">
                      <XMarkIcon class="w-4 h-4" /> Fermer
                    </button>
                  </div>
                </Transition>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="store.pagination && store.pagination.last_page > 1" class="px-5 py-4 border-t border-gray-100 flex items-center justify-between text-sm">
        <p class="text-gray-400">
          Page {{ store.pagination.current_page }} sur {{ store.pagination.last_page }}
        </p>
        <div class="flex gap-2">
          <button
            :disabled="store.pagination.current_page <= 1"
            @click="goToPage(store.pagination.current_page - 1)"
            class="btn-secondary text-xs px-3 py-1.5 disabled:opacity-40"
          >← Précédent</button>
          <button
            :disabled="store.pagination.current_page >= store.pagination.last_page"
            @click="goToPage(store.pagination.current_page + 1)"
            class="btn-secondary text-xs px-3 py-1.5 disabled:opacity-40"
          >Suivant →</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Confirmation modal -->
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="confirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm animate-slide-up">
          <h3 class="font-bold text-gray-900 text-lg mb-2">
            {{ confirmModal.action === 'cancel' ? 'Annuler la réservation ?' : confirmModal.action === 'confirm' ? 'Confirmer la réservation ?' : 'Marquer comme terminée ?' }}
          </h3>
          <p class="text-sm text-gray-500 mb-6">Cette action sera notifiée au client.</p>
          <div class="flex gap-3">
            <button @click="confirmModal = null" class="btn-secondary flex-1">Annuler</button>
            <button
              @click="confirmModal.action === 'cancel' ? cancelBooking(confirmModal.bookingId) : changeStatus(confirmModal.bookingId, confirmModal.action === 'confirm' ? 'confirmed' : 'completed')"
              :class="['flex-1', confirmModal.action === 'cancel' ? 'btn-danger' : 'btn-primary']"
            >
              Confirmer
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }

.menu-enter-active { transition: all 0.12s ease-out; }
.menu-leave-active { transition: all 0.1s ease-in; }
.menu-enter-from, .menu-leave-to { opacity: 0; transform: scale(0.95) translateY(-4px); }
</style>
