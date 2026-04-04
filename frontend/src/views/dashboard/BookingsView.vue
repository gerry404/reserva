<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
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
  ClockIcon,
  EnvelopeIcon,
  ChatBubbleLeftIcon,
} from '@heroicons/vue/24/outline'
import { format, parseISO } from 'date-fns'
import { fr } from 'date-fns/locale'

const store       = useBookingsStore()
const actionMenu  = ref(null)
const confirmModal = ref(null)
const viewDetail  = ref(null)
const toast        = ref(null) // { message, whatsappLink }

onMounted(() => store.fetchBookings())

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
  pending:   { label: 'En attente', class: 'badge-pending',   dot: 'bg-amber-400' },
  confirmed: { label: 'Confirmé',   class: 'badge-confirmed', dot: 'bg-emerald-400' },
  cancelled: { label: 'Annulé',     class: 'badge-cancelled', dot: 'bg-red-400' },
  completed: { label: 'Terminé',    class: 'badge-completed', dot: 'bg-blue-400' },
}

function formatDate(d) {
  try { return format(parseISO(d), 'EEE d MMM', { locale: fr }) }
  catch { return d }
}

function formatDateLong(d) {
  try { return format(parseISO(d), 'EEEE d MMMM yyyy', { locale: fr }) }
  catch { return d }
}

async function changeStatus(id, status) {
  confirmModal.value = null
  actionMenu.value   = null
  const data = await store.updateStatus(id, status)

  if (status === 'confirmed' && data.whatsapp_link) {
    const name = data.customer_name || 'le client'
    toast.value = {
      message: data.customer_email
        ? `✅ Confirmé ! Un email a été envoyé à ${name}.`
        : `✅ Confirmé ! Prévenez ${name} via WhatsApp :`,
      whatsappLink: data.whatsapp_link,
    }
    setTimeout(() => { toast.value = null }, 15000)
  }
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

// Dropdown positioning
const menuStyle = ref({})

function toggleMenu(id, event) {
  if (actionMenu.value === id) {
    actionMenu.value = null
    return
  }
  const btn = event.currentTarget
  const rect = btn.getBoundingClientRect()
  const spaceBelow = window.innerHeight - rect.bottom
  const menuHeight = 160

  if (spaceBelow > menuHeight) {
    menuStyle.value = { top: rect.bottom + 4 + 'px', left: (rect.right - 192) + 'px' }
  } else {
    menuStyle.value = { top: (rect.top - menuHeight - 4) + 'px', left: (rect.right - 192) + 'px' }
  }
  actionMenu.value = id
}

function onClickOutside(e) {
  if (actionMenu.value && !e.target.closest('[data-menu-trigger]')) {
    actionMenu.value = null
  }
}

onMounted(() => {
  document.addEventListener('click', onClickOutside, true)
})
onUnmounted(() => {
  document.removeEventListener('click', onClickOutside, true)
})

// Stats
const stats = computed(() => {
  const all = store.bookings
  return {
    total: store.pagination?.total ?? 0,
    pending: all.filter(b => b.status === 'pending').length,
    confirmed: all.filter(b => b.status === 'confirmed').length,
    today: all.filter(b => {
      try { return format(parseISO(b.date), 'yyyy-MM-dd') === format(new Date(), 'yyyy-MM-dd') } catch { return false }
    }).length,
  }
})
</script>

<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-xl font-black text-gray-900">Réservations</h2>
        <p class="text-sm text-gray-500 mt-0.5">Gérez et suivez toutes vos réservations</p>
      </div>
      <button @click="store.exportCsv()" class="btn-secondary text-sm">
        <ArrowDownTrayIcon class="w-4 h-4" />
        Exporter CSV
      </button>
    </div>

    <!-- Confirmation toast with WhatsApp link -->
    <Transition name="fade">
      <div v-if="toast" class="relative flex flex-col sm:flex-row items-start sm:items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
        <button @click="toast = null" class="absolute top-2 right-2 text-emerald-400 hover:text-emerald-600">
          <XMarkIcon class="w-4 h-4" />
        </button>
        <p class="text-sm text-emerald-800 font-medium pr-6">{{ toast.message }}</p>
        <a
          :href="toast.whatsappLink"
          target="_blank"
          class="shrink-0 flex items-center gap-2 px-4 py-2 bg-[#25D366] hover:bg-[#1fb855] text-white text-sm font-bold rounded-lg transition-colors shadow-sm"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
          Envoyer sur WhatsApp
        </a>
      </div>
    </Transition>

    <!-- Mini stat pills -->
    <div class="flex flex-wrap gap-2">
      <div class="flex items-center gap-2 px-3.5 py-2 bg-white border border-gray-100 rounded-xl text-sm">
        <span class="font-bold text-gray-900">{{ stats.total }}</span>
        <span class="text-gray-400">total</span>
      </div>
      <button @click="store.filters.status = 'pending'; store.fetchBookings()"
        class="flex items-center gap-2 px-3.5 py-2 bg-amber-50 border border-amber-100 rounded-xl text-sm hover:bg-amber-100 transition-colors">
        <span class="w-2 h-2 rounded-full bg-amber-400" />
        <span class="font-bold text-amber-700">{{ stats.pending }}</span>
        <span class="text-amber-600/70">en attente</span>
      </button>
      <button @click="store.filters.status = 'confirmed'; store.fetchBookings()"
        class="flex items-center gap-2 px-3.5 py-2 bg-emerald-50 border border-emerald-100 rounded-xl text-sm hover:bg-emerald-100 transition-colors">
        <span class="w-2 h-2 rounded-full bg-emerald-400" />
        <span class="font-bold text-emerald-700">{{ stats.confirmed }}</span>
        <span class="text-emerald-600/70">confirmés</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 items-center">
      <div class="relative flex-1 min-w-[220px]">
        <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input
          v-model="store.filters.search"
          type="search"
          class="input-field pl-10"
          placeholder="Rechercher un client, une référence…"
        />
      </div>

      <select v-model="store.filters.status" class="input-field sm:w-48">
        <option v-for="o in statusOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>

      <input v-model="store.filters.date" type="date" class="input-field sm:w-44" />

      <button v-if="store.filters.search || store.filters.status || store.filters.date" @click="store.resetFilters(); store.fetchBookings()" class="btn-ghost text-sm">
        <XMarkIcon class="w-4 h-4" /> Réinitialiser
      </button>
    </div>

    <!-- Loading skeleton -->
    <div v-if="store.loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="card p-4 flex items-center gap-4 animate-pulse">
        <div class="w-11 h-11 bg-gray-100 rounded-full shrink-0" />
        <div class="flex-1 space-y-2">
          <div class="h-4 bg-gray-100 rounded w-32" />
          <div class="h-3 bg-gray-100 rounded w-48" />
        </div>
        <div class="h-6 bg-gray-100 rounded-full w-20" />
      </div>
    </div>

    <!-- Empty state -->
    <div v-else-if="store.bookings.length === 0" class="card p-16 text-center">
      <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mx-auto mb-4">
        <CalendarDaysIcon class="w-8 h-8 text-gray-300" />
      </div>
      <h3 class="font-bold text-gray-900 mb-2">Aucune réservation trouvée</h3>
      <p class="text-gray-400 text-sm">Essayez de modifier les filtres ou attendez vos prochaines réservations</p>
    </div>

    <!-- Booking cards -->
    <div v-else class="space-y-2">
      <div
        v-for="b in store.bookings"
        :key="b.id"
        class="card hover:shadow-md transition-all duration-200 group cursor-pointer"
        @click="viewDetail = viewDetail?.id === b.id ? null : b"
      >
        <div class="flex items-center gap-4 p-4">
          <!-- Avatar -->
          <div class="w-11 h-11 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-sm"
            :style="{ backgroundColor: b.service?.color ?? '#a855f7' }">
            {{ b.customer_name?.charAt(0)?.toUpperCase() }}
          </div>

          <!-- Main info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <p class="font-bold text-gray-900 truncate">{{ b.customer_name }}</p>
              <span class="font-mono text-[10px] text-gray-300 hidden sm:inline">{{ b.reference }}</span>
            </div>
            <div class="flex items-center gap-3 mt-0.5 text-xs text-gray-400">
              <span v-if="b.service" class="flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :style="{ backgroundColor: b.service.color ?? '#a855f7' }" />
                {{ b.service.name }}
              </span>
              <span class="flex items-center gap-1">
                <CalendarDaysIcon class="w-3 h-3" />
                {{ formatDate(b.date) }} · {{ b.time_slot }}
              </span>
            </div>
          </div>

          <!-- Status badge -->
          <span :class="['badge shrink-0', statusConfig[b.status]?.class ?? 'badge-pending']">
            {{ statusConfig[b.status]?.label ?? b.status }}
          </span>

          <!-- Action button -->
          <div data-menu-trigger>
            <button @click.stop="toggleMenu(b.id, $event)" class="p-2 rounded-xl text-gray-300 hover:text-gray-600 hover:bg-gray-100 transition-colors opacity-0 group-hover:opacity-100">
              <EllipsisHorizontalIcon class="w-5 h-5" />
            </button>
          </div>

          <!-- Teleported dropdown -->
          <Teleport to="body">
            <Transition name="menu">
              <div
                v-if="actionMenu === b.id"
                class="fixed w-48 bg-white rounded-xl shadow-2xl shadow-black/15 border border-gray-100 overflow-hidden z-[9999]"
                :style="menuStyle"
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
          </Teleport>
        </div>

        <!-- Expanded detail -->
        <Transition name="expand">
          <div v-if="viewDetail?.id === b.id" class="border-t border-gray-50 px-4 pb-4 pt-3">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div>
                <p class="text-[11px] text-gray-400 font-medium mb-0.5">Téléphone</p>
                <a :href="'tel:' + b.customer_phone" class="text-sm font-semibold text-gray-900 flex items-center gap-1 hover:text-primary-600">
                  <PhoneIcon class="w-3.5 h-3.5 text-gray-400" /> {{ b.customer_phone }}
                </a>
              </div>
              <div v-if="b.customer_email">
                <p class="text-[11px] text-gray-400 font-medium mb-0.5">Email</p>
                <a :href="'mailto:' + b.customer_email" class="text-sm font-semibold text-gray-900 flex items-center gap-1 hover:text-primary-600 truncate">
                  <EnvelopeIcon class="w-3.5 h-3.5 text-gray-400 shrink-0" /> {{ b.customer_email }}
                </a>
              </div>
              <div>
                <p class="text-[11px] text-gray-400 font-medium mb-0.5">Date complète</p>
                <p class="text-sm font-semibold text-gray-900 capitalize">{{ formatDateLong(b.date) }}</p>
              </div>
              <div>
                <p class="text-[11px] text-gray-400 font-medium mb-0.5">Référence</p>
                <p class="text-sm font-mono font-bold text-primary-600">{{ b.reference }}</p>
              </div>
            </div>
            <div v-if="b.notes" class="mt-3 p-3 bg-gray-50 rounded-xl">
              <p class="text-[11px] text-gray-400 font-medium mb-1 flex items-center gap-1">
                <ChatBubbleLeftIcon class="w-3 h-3" /> Notes du client
              </p>
              <p class="text-sm text-gray-600">{{ b.notes }}</p>
            </div>

            <!-- Quick actions -->
            <div v-if="b.status === 'pending'" class="flex gap-2 mt-3">
              <button @click.stop="openConfirm(b.id, 'confirm')" class="btn-primary text-xs py-2 flex-1">
                <CheckIcon class="w-3.5 h-3.5" /> Confirmer
              </button>
              <button @click.stop="openConfirm(b.id, 'cancel')" class="btn-danger text-xs py-2 flex-1">
                <XMarkIcon class="w-3.5 h-3.5" /> Annuler
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="store.pagination && store.pagination.last_page > 1" class="flex items-center justify-between text-sm">
      <p class="text-gray-400">
        Page {{ store.pagination.current_page }} sur {{ store.pagination.last_page }}
        <span class="text-gray-300 ml-1">({{ store.pagination.total }} résultats)</span>
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

.expand-enter-active { transition: all 0.2s ease-out; }
.expand-leave-active { transition: all 0.15s ease-in; }
.expand-enter-from { opacity: 0; max-height: 0; }
.expand-leave-to { opacity: 0; max-height: 0; }
</style>
