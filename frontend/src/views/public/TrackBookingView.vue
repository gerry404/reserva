<script setup>
import { onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { publicApi } from '@/api'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import { describeStatus } from '@/constants/bookingStatus'
import {
  Search,
  CircleCheck,
  Clock,
  CircleX,
  TriangleAlert,
} from 'lucide-vue-next'
import BrandIcon from '@/components/ui/BrandIcon.vue'

const route = useRoute()

const loading  = ref(false)
const error    = ref('')
const booking  = ref(null)
const cancelling = ref(false)
const cancelled  = ref(false)

const form = reactive({
  reference: '',
  phone: '',
})


async function search() {
  error.value = ''
  booking.value = null
  cancelled.value = false
  loading.value = true
  try {
    booking.value = await publicApi.track(form.reference.trim().toUpperCase(), form.phone.trim())
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

// Arriving from a confirmation email or the success screen: the reference is
// already in the URL, so only the phone number is left to type.
onMounted(() => {
  if (typeof route.query.ref === 'string') form.reference = route.query.ref
})

async function cancelBooking() {
  if (!confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) return
  error.value = ''
  cancelling.value = true
  try {
    // Take the server's version of the booking rather than patching the local
    // copy: it knows the resulting state, including whether cancelling is
    // still allowed at all.
    const response = await publicApi.cancel(booking.value.reference, form.phone.trim())
    booking.value  = response.booking?.data ?? response.booking
    cancelled.value = true
  } catch (e) {
    error.value = e.message
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
        <img src="/logo.svg" alt="Nuvo" class="w-8 h-8" />
        <span class="font-display font-extrabold text-white text-lg">Nuvo</span>
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
          <Search v-if="!loading" class="w-4 h-4" />
          <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
          {{ loading ? 'Recherche…' : 'Rechercher' }}
        </button>
      </form>

      <!-- Error -->
      <div v-if="error" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-2">
        <TriangleAlert class="w-5 h-5 shrink-0" />
        {{ error }}
      </div>

      <!-- Cancelled toast -->
      <div v-if="cancelled" class="mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm flex items-center gap-2">
        <CircleCheck class="w-5 h-5 shrink-0" />
        Votre réservation a été annulée avec succès.
      </div>

      <!-- Booking result -->
      <div v-if="booking" class="mt-6 card overflow-hidden animate-slide-up">
        <!--
          La bannière tire ses couleurs des mêmes jetons que le badge : une
          seule définition du sens de chaque statut, deux rendus.
        -->
        <div
          class="p-4 border-b flex items-center gap-3"
          :style="{
            backgroundColor: describeStatus(booking.status).tone.bg,
            borderColor: describeStatus(booking.status).tone.border,
          }"
        >
          <span class="text-xl" aria-hidden="true">{{ describeStatus(booking.status).icon }}</span>
          <div>
            <p class="font-bold text-sm" :style="{ color: describeStatus(booking.status).tone.text }">
              {{ describeStatus(booking.status).customer }}
            </p>
            <p class="text-xs text-gray-600">Réf. <span class="numeric">{{ booking.reference }}</span></p>
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
            <span class="font-semibold text-gray-900 capitalize">{{ booking.date_label }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Heure</span>
            <span class="font-semibold text-gray-900">
              {{ booking.time }}<template v-if="booking.ends_at_time"> – {{ booking.ends_at_time }}</template>
            </span>
          </div>

          <!-- Contact business -->
          <div v-if="booking.business_phone && booking.status !== 'cancelled'" class="pt-3 border-t border-gray-100">
            <a
              :href="`https://wa.me/${booking.business_phone.replace(/[^0-9]/g, '')}`"
              target="_blank"
              class="btn-whatsapp w-full py-2.5 text-sm"
            >
              <BrandIcon name="whatsapp" class="w-4 h-4" />
              Contacter le commerce
            </a>
          </div>

          <!-- Cancel button -->
          <div v-if="booking.can_cancel && booking.status !== 'cancelled'" class="pt-2">
            <button
              @click="cancelBooking"
              :disabled="cancelling"
              class="w-full py-2.5 border-2 border-red-200 text-red-600 hover:bg-red-50 text-sm font-bold rounded-control transition-colors"
            >
              {{ cancelling ? 'Annulation…' : 'Annuler ma réservation' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="text-center py-6 text-xs text-gray-300">
      Propulsé par <RouterLink to="/" class="font-semibold text-gray-400 hover:underline">Nuvo</RouterLink>
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
