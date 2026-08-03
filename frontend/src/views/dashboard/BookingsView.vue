<script setup>
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseDateField from '@/components/ui/BaseDateField.vue'
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
import { useBookingsStore } from '@/stores/bookings'
import {
  Search,
  Funnel,
  Download,
  Check,
  X,
  Ellipsis,
  CalendarDays,
  Phone,
  Clock,
  Mail,
  MessageCircle,
} from 'lucide-vue-next'
import { format, parseISO } from 'date-fns'
import DurationBar from '@/components/time/DurationBar.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import { defaultAccent } from '@/design/tokens'
import UserAvatar from '@/components/ui/UserAvatar.vue'
import BaseSpinner from '@/components/ui/BaseSpinner.vue'
import { STATUS_FILTER_OPTIONS, describeStatus } from '@/constants/bookingStatus'

/** Les libellés et couleurs viennent de constants/bookingStatus. */
const statusOptions = STATUS_FILTER_OPTIONS
import { fr } from 'date-fns/locale'
import BrandIcon from '@/components/ui/BrandIcon.vue'


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
  const response = await store.updateStatus(id, status)
  const booking  = response.booking.data ?? response.booking

  if (status === 'confirmed' && response.whatsapp_link) {
    const name = booking.customer_name || 'le client'
    toast.value = {
      message: booking.customer_email
        ? `✅ Confirmé ! Un email a été envoyé à ${name}.`
        : `✅ Confirmé ! Prévenez ${name} via WhatsApp :`,
      whatsappLink: response.whatsapp_link,
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

/**
 * Les compteurs viennent du serveur, jamais de `store.bookings`.
 *
 * Cette liste est paginée et déjà filtrée : y compter les statuts affichait
 * zéro « en attente » dès qu'on filtrait sur « confirmé », et ne voyait de
 * toute façon jamais au-delà de la première page.
 */
const stats = computed(() => store.counts)

/** Un second clic sur la même pastille relâche le filtre. */
function basculerStatut(statut) {
  store.filters.status = store.filters.status === statut ? '' : statut
}
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
        <Download class="w-4 h-4" />
        Exporter CSV
      </button>
    </div>

    <!-- Confirmation toast with WhatsApp link -->
    <Transition name="fade">
      <div v-if="toast" class="relative flex flex-col sm:flex-row items-start sm:items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
        <button @click="toast = null" class="absolute top-2 right-2 text-emerald-400 hover:text-emerald-600">
          <X class="w-4 h-4" />
        </button>
        <p class="text-sm text-emerald-800 font-medium pr-6">{{ toast.message }}</p>
        <a
          :href="toast.whatsappLink"
          target="_blank"
          class="btn-whatsapp shrink-0 px-4 py-2 text-sm shadow-sm"
        >
          <BrandIcon name="whatsapp" class="w-4 h-4" />
          Envoyer sur WhatsApp
        </a>
      </div>
    </Transition>

    <!-- Mini stat pills -->
    <div class="flex flex-wrap gap-2">
      <button
        @click="store.filters.status = ''"
        :class="['flex items-center gap-2 px-3.5 py-2 border rounded-control text-sm transition-colors',
                 store.filters.status === '' ? 'bg-gray-900 border-gray-900 text-white' : 'bg-clay-50 border-gray-100 hover:bg-gray-100']">
        <span class="font-bold">{{ stats.all }}</span>
        <span :class="store.filters.status === '' ? 'text-white/70' : 'text-gray-400'">au total</span>
      </button>
      <button
        @click="basculerStatut('pending')"
        :class="['flex items-center gap-2 px-3.5 py-2 border rounded-control text-sm transition-colors',
                 store.filters.status === 'pending' ? 'bg-amber-500 border-amber-500 text-white' : 'bg-amber-50 border-amber-100 hover:bg-amber-100']">
        <span :class="['w-2 h-2 rounded-full', store.filters.status === 'pending' ? 'bg-white' : 'bg-amber-400']" />
        <span :class="['font-bold', store.filters.status === 'pending' ? 'text-white' : 'text-amber-700']">{{ stats.pending }}</span>
        <span :class="store.filters.status === 'pending' ? 'text-white/75' : 'text-amber-600/70'">en attente</span>
      </button>
      <button
        @click="basculerStatut('confirmed')"
        :class="['flex items-center gap-2 px-3.5 py-2 border rounded-control text-sm transition-colors',
                 store.filters.status === 'confirmed' ? 'bg-emerald-600 border-emerald-600 text-white' : 'bg-emerald-50 border-emerald-100 hover:bg-emerald-100']">
        <span :class="['w-2 h-2 rounded-full', store.filters.status === 'confirmed' ? 'bg-white' : 'bg-emerald-400']" />
        <span :class="['font-bold', store.filters.status === 'confirmed' ? 'text-white' : 'text-emerald-700']">{{ stats.confirmed }}</span>
        <span :class="store.filters.status === 'confirmed' ? 'text-white/75' : 'text-emerald-600/70'">confirmés</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 items-center">
      <div class="relative flex-1 min-w-[220px]">
        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input
          v-model="store.filters.search"
          type="search"
          class="input-field pl-10"
          placeholder="Rechercher un client, une référence…"
        />
      </div>

      <div class="sm:w-48">
        <BaseSelect
          v-model="store.filters.status"
          :options="statusOptions"
          placeholder="Tous les statuts"
          aria-label="Filtrer par statut"
        />
      </div>

      <div class="sm:w-52">
        <BaseDateField v-model="store.filters.date" aria-label="Filtrer par date" />
      </div>

      <button v-if="store.filters.search || store.filters.status || store.filters.date" @click="store.resetFilters(); store.fetchBookings()" class="btn-ghost text-sm">
        <X class="w-4 h-4" /> Réinitialiser
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
        <CalendarDays class="w-8 h-8 text-gray-300" />
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
          <UserAvatar :name="b.customer_name" :color="b.service?.color" size="lg" />

          <!-- Main info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <p class="font-bold text-gray-900 truncate">{{ b.customer_name }}</p>
              <span class="font-mono text-[10px] text-gray-300 hidden sm:inline">{{ b.reference }}</span>
            </div>
            <div class="flex items-center gap-3 mt-0.5 text-xs text-gray-400">
              <span v-if="b.service" class="flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :style="{ backgroundColor: b.service.color ?? defaultAccent }" />
                {{ b.service.name }}
              </span>
              <span class="flex items-center gap-1">
                <CalendarDays class="w-3 h-3" />
                {{ formatDate(b.date) }} ·
                <span class="numeric">{{ b.time_slot }}–{{ b.ends_at_time }}</span>
              </span>
            </div>

            <!-- Même barre que sur la page publique et le tableau de bord :
                 une seule échelle de temps dans tout le produit. -->
            <DurationBar
              :minutes="b.duration"
              :color="b.service?.color"
              size="sm"
              class="mt-2 max-w-[160px]"
            />
          </div>

          <!-- Status badge -->
          <StatusBadge :status="b.status" class="shrink-0" />

          <!-- Action button -->
          <div data-menu-trigger>
            <button @click.stop="toggleMenu(b.id, $event)" class="p-2 rounded-control text-gray-300 hover:text-gray-600 hover:bg-gray-100 transition-colors opacity-0 group-hover:opacity-100">
              <Ellipsis class="w-5 h-5" />
            </button>
          </div>

          <!-- Teleported dropdown -->
          <Teleport to="body">
            <Transition name="menu">
              <div
                v-if="actionMenu === b.id"
                class="fixed w-48 bg-clay-50 rounded-xl shadow-2xl shadow-black/15 border border-gray-100 overflow-hidden z-[9999]"
                :style="menuStyle"
              >
                <template v-if="b.status === 'pending'">
                  <button @click="openConfirm(b.id, 'confirm')" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-emerald-700 hover:bg-emerald-50 transition-colors">
                    <Check class="w-4 h-4" /> Confirmer
                  </button>
                </template>
                <template v-if="['pending', 'confirmed'].includes(b.status)">
                  <button @click="openConfirm(b.id, 'complete')" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-blue-700 hover:bg-blue-50 transition-colors">
                    <Check class="w-4 h-4" /> Marquer terminé
                  </button>
                  <button @click="openConfirm(b.id, 'cancel')" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                    <X class="w-4 h-4" /> Annuler
                  </button>
                </template>
                <button @click="actionMenu = null" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-gray-500 hover:bg-gray-50 transition-colors border-t border-gray-50">
                  <X class="w-4 h-4" /> Fermer
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
                  <Phone class="w-3.5 h-3.5 text-gray-400" /> {{ b.customer_phone }}
                </a>
              </div>
              <div v-if="b.customer_email">
                <p class="text-[11px] text-gray-400 font-medium mb-0.5">Email</p>
                <a :href="'mailto:' + b.customer_email" class="text-sm font-semibold text-gray-900 flex items-center gap-1 hover:text-primary-600 truncate">
                  <Mail class="w-3.5 h-3.5 text-gray-400 shrink-0" /> {{ b.customer_email }}
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
                <MessageCircle class="w-3 h-3" /> Notes du client
              </p>
              <p class="text-sm text-gray-600">{{ b.notes }}</p>
            </div>

            <!-- Quick actions -->
            <div v-if="b.status === 'pending'" class="flex gap-2 mt-3">
              <button @click.stop="openConfirm(b.id, 'confirm')" class="btn-primary text-xs py-2 flex-1">
                <Check class="w-3.5 h-3.5" /> Confirmer
              </button>
              <button @click.stop="openConfirm(b.id, 'cancel')" class="btn-danger text-xs py-2 flex-1">
                <X class="w-3.5 h-3.5" /> Annuler
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="store.pagination && store.pagination.lastPage > 1" class="flex items-center justify-between text-sm">
      <p class="text-gray-400">
        Page {{ store.pagination.currentPage }} sur {{ store.pagination.lastPage }}
        <span class="text-gray-300 ml-1">({{ store.pagination.total }} résultats)</span>
      </p>
      <div class="flex gap-2">
        <button
          :disabled="store.pagination.currentPage <= 1"
          @click="goToPage(store.pagination.currentPage - 1)"
          class="btn-secondary text-xs px-3 py-1.5 disabled:opacity-40"
        >← Précédent</button>
        <button
          :disabled="store.pagination.currentPage >= store.pagination.lastPage"
          @click="goToPage(store.pagination.currentPage + 1)"
          class="btn-secondary text-xs px-3 py-1.5 disabled:opacity-40"
        >Suivant →</button>
      </div>
    </div>
  </div>

  <!-- Confirmation modal -->
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="confirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="bg-clay-50 rounded-2xl shadow-xl p-6 w-full max-w-sm animate-slide-up">
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
