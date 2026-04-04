<script setup>
import { ref, reactive } from 'vue'
import api from '@/api'
import {
  MagnifyingGlassIcon,
  CheckCircleIcon,
  ClockIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

const loading  = ref(false)
const error    = ref('')
const booking  = ref(null)
const cancelling = ref(false)
const cancelled  = ref(false)

const form = reactive({
  reference: '',
  phone: '',
})

const statusConfig = {
  pending:   { label: 'En attente de confirmation', icon: ClockIcon, color: 'text-amber-600', bg: 'bg-amber-50 border-amber-200' },
  confirmed: { label: 'Confirmée',                  icon: CheckCircleIcon, color: 'text-emerald-600', bg: 'bg-emerald-50 border-emerald-200' },
  completed: { label: 'Terminée',                   icon: CheckCircleIcon, color: 'text-blue-600', bg: 'bg-blue-50 border-blue-200' },
  cancelled: { label: 'Annulée',                    icon: XCircleIcon, color: 'text-red-600', bg: 'bg-red-50 border-red-200' },
}

async function search() {
  error.value = ''
  booking.value = null
  cancelled.value = false
  loading.value = true
  try {
    const { data } = await api.post('/track-booking', {
      reference: form.reference.trim().toUpperCase(),
      phone: form.phone.trim(),
    })
    booking.value = data
  } catch (e) {
    error.value = e.response?.data?.message || 'Aucune réservation trouvée.'
  } finally {
    loading.value = false
  }
}

async function cancelBooking() {
  if (!confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) return
  cancelling.value = true
  try {
    await api.post('/cancel-booking', {
      reference: booking.value.reference,
      phone: form.phone.trim(),
    })
    booking.value.status = 'cancelled'
    booking.value.status_label = 'Annulée'
    booking.value.can_cancel = false
    cancelled.value = true
  } catch (e) {
    error.value = e.response?.data?.message || 'Impossible d\'annuler.'
  } finally {
    cancelling.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-gradient-to-r from-primary-600 to-violet-600 py-10 px-4 text-center">
      <RouterLink to="/" class="inline-flex items-center gap-2 mb-6">
        <img src="/logo.png" alt="Réserva" class="w-8 h-8" />
        <span class="font-display font-extrabold text-white text-lg">Réserva</span>
      </RouterLink>
      <h1 class="text-2xl font-black text-white">Suivre ma réservation</h1>
      <p class="text-white/70 text-sm mt-1">Entrez votre référence et votre numéro de téléphone</p>
    </div>

    <div class="max-w-md mx-auto px-4 py-8">
      <!-- Search form -->
      <form @submit.prevent="search" class="card p-6 space-y-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Référence</label>
          <input
            v-model="form.reference"
            type="text"
            class="input-field uppercase"
            placeholder="RES-XXXXXXXX"
            required
          />
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Téléphone</label>
          <input
            v-model="form.phone"
            type="tel"
            class="input-field"
            placeholder="+237 6XX XXX XXX"
            required
          />
          <p class="text-[11px] text-gray-400 mt-1">Le numéro utilisé lors de la réservation</p>
        </div>
        <button type="submit" class="btn-primary w-full py-3" :disabled="loading">
          <MagnifyingGlassIcon v-if="!loading" class="w-4 h-4" />
          <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
          {{ loading ? 'Recherche…' : 'Rechercher' }}
        </button>
      </form>

      <!-- Error -->
      <div v-if="error" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-2">
        <ExclamationTriangleIcon class="w-5 h-5 shrink-0" />
        {{ error }}
      </div>

      <!-- Cancelled toast -->
      <div v-if="cancelled" class="mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm flex items-center gap-2">
        <CheckCircleIcon class="w-5 h-5 shrink-0" />
        Votre réservation a été annulée avec succès.
      </div>

      <!-- Booking result -->
      <div v-if="booking" class="mt-6 card overflow-hidden animate-slide-up">
        <!-- Status banner -->
        <div :class="['p-4 border-b flex items-center gap-3', statusConfig[booking.status]?.bg ?? 'bg-gray-50 border-gray-200']">
          <component :is="statusConfig[booking.status]?.icon ?? ClockIcon"
            :class="['w-6 h-6', statusConfig[booking.status]?.color ?? 'text-gray-500']" />
          <div>
            <p :class="['font-bold text-sm', statusConfig[booking.status]?.color ?? 'text-gray-700']">
              {{ statusConfig[booking.status]?.label ?? booking.status_label }}
            </p>
            <p class="text-xs text-gray-500">Réf. {{ booking.reference }}</p>
          </div>
        </div>

        <!-- Details -->
        <div class="p-5 space-y-3">
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Commerce</span>
            <span class="font-semibold text-gray-900">{{ booking.business_name }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Service</span>
            <span class="font-semibold text-gray-900">{{ booking.service }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Date</span>
            <span class="font-semibold text-gray-900 capitalize">{{ booking.date }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Heure</span>
            <span class="font-semibold text-gray-900">{{ booking.time }}</span>
          </div>

          <!-- Contact business -->
          <div v-if="booking.business_phone && booking.status !== 'cancelled'" class="pt-3 border-t border-gray-100">
            <a
              :href="`https://wa.me/${booking.business_phone.replace(/[^0-9]/g, '')}`"
              target="_blank"
              class="flex items-center justify-center gap-2 py-2.5 bg-[#25D366] hover:bg-[#1fb855] text-white text-sm font-bold rounded-xl transition-colors"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
              Contacter le commerce
            </a>
          </div>

          <!-- Cancel button -->
          <div v-if="booking.can_cancel && booking.status !== 'cancelled'" class="pt-2">
            <button
              @click="cancelBooking"
              :disabled="cancelling"
              class="w-full py-2.5 border-2 border-red-200 text-red-600 hover:bg-red-50 text-sm font-bold rounded-xl transition-colors"
            >
              {{ cancelling ? 'Annulation…' : 'Annuler ma réservation' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="text-center py-6 text-xs text-gray-300">
      Propulsé par <a href="/" class="font-semibold text-gray-400 hover:underline">Réserva</a>
    </div>
  </div>
</template>

<style scoped>
.animate-slide-up {
  animation: slideUp 0.25s ease-out;
}
@keyframes slideUp {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
