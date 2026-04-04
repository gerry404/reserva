<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { publicApi } from '@/api'
import {
  MapPinIcon,
  ClockIcon,
  CheckCircleIcon,
  CalendarDaysIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  PhoneIcon,
} from '@heroicons/vue/24/outline'
import { format, addMonths, subMonths, startOfMonth, endOfMonth, eachDayOfInterval,
         getDay, isBefore, isToday, isSameDay, parseISO } from 'date-fns'
import { fr } from 'date-fns/locale'

const route    = useRoute()
const slug     = computed(() => route.params.slug)

const business  = ref(null)
const loading   = ref(true)
const notFound  = ref(false)

// Booking flow steps: service → date → time → form → success
const step       = ref(1)
const selected   = reactive({ service: null, date: null, time: null })
const slots      = ref([])
const slotsLoading = ref(false)

const form = reactive({ customer_name: '', customer_phone: '', customer_email: '', notes: '' })
const submitting  = ref(false)
const submitError = ref('')
const booking     = ref(null) // success response

// Calendar
const currentMonth = ref(new Date())
const calendarDays = computed(() => {
  const start = startOfMonth(currentMonth.value)
  const end   = endOfMonth(currentMonth.value)
  const days  = eachDayOfInterval({ start, end })
  // Pad start (Monday = 0)
  const startDow = (getDay(start) + 6) % 7
  return { days, startPad: startDow }
})

function isDateDisabled(date) {
  return isBefore(date, new Date()) && !isToday(date)
}

function isDayOpen(date) {
  if (!business.value?.working_hours) return true
  const dayFr = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'][getDay(date)]
  return business.value.working_hours[dayFr]?.is_open !== false
}

onMounted(async () => {
  try {
    const { data } = await publicApi.getBusiness(slug.value)
    business.value = data
    // Apply accent color
    document.documentElement.style.setProperty('--accent', data.accent_color ?? '#6366f1')
  } catch {
    notFound.value = true
  } finally {
    loading.value = false
  }
})

watch(() => selected.date, async (date) => {
  if (!date || !selected.service) return
  slots.value     = []
  slotsLoading.value = true
  try {
    const { data } = await publicApi.getSlots(slug.value, format(date, 'yyyy-MM-dd'))
    slots.value = data.slots
  } finally {
    slotsLoading.value = false
  }
})

async function submitBooking() {
  submitError.value = ''
  submitting.value  = true
  try {
    const { data } = await publicApi.book(slug.value, {
      service_id:    selected.service.id,
      customer_name:  form.customer_name,
      customer_phone: form.customer_phone,
      customer_email: form.customer_email,
      notes:          form.notes,
      date:           format(selected.date, 'yyyy-MM-dd'),
      time_slot:      selected.time,
    })
    booking.value = data.booking
    step.value    = 5
  } catch (e) {
    submitError.value = e.response?.data?.message ?? 'Une erreur est survenue. Réessayez.'
  } finally {
    submitting.value = false
  }
}

function selectService(svc) {
  selected.service = svc
  selected.date    = null
  selected.time    = null
  step.value       = 2
}

function selectDate(date) {
  if (isDateDisabled(date) || !isDayOpen(date)) return
  selected.date  = date
  selected.time  = null
  step.value     = 3
}

function selectTime(time) {
  selected.time = time
  step.value    = 4
}

const stepLabels = ['', 'Service', 'Date', 'Heure', 'Coordonnées', 'Confirmé']

const backendUrl = import.meta.env.VITE_API_URL?.replace('/api', '') || ''
function svcImageUrl(path) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${backendUrl}/storage/${path}`
}

const whatsappLink = computed(() => {
  if (!booking.value?.business_phone) return '#'
  const phone = booking.value.business_phone.replace(/[^0-9]/g, '')
  const svc = selectedService.value?.name || booking.value.service
  const msg = encodeURIComponent(
    `Bonjour,\n\n` +
    `Je viens de réserver chez *${business.value.name}*.\n\n` +
    `📋 *Détails :*\n` +
    `• Service : ${svc}\n` +
    `• Date : ${booking.value.date}\n` +
    `• Heure : ${booking.value.time}\n` +
    `• Réf : ${booking.value.reference}\n` +
    `• Nom : ${form.customer_name}\n` +
    `• Tél : ${form.customer_phone}\n\n` +
    `Merci de confirmer ma réservation ! 🙏`
  )
  return `https://wa.me/${phone}?text=${msg}`
})
</script>

<template>
  <!-- Loading -->
  <div v-if="loading" class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-8 h-8 border-3 border-primary-500 border-t-transparent rounded-full animate-spin" />
  </div>

  <!-- Not found -->
  <div v-else-if="notFound" class="min-h-screen flex items-center justify-center bg-gray-50 p-6">
    <div class="text-center">
      <div class="text-6xl mb-4">🔍</div>
      <h1 class="text-2xl font-black text-gray-900 mb-2">Page introuvable</h1>
      <p class="text-gray-500">Ce commerce n'existe pas ou a désactivé ses réservations.</p>
    </div>
  </div>

  <!-- Booking page -->
  <div v-else class="min-h-screen bg-gray-50">
    <!-- Business header -->
    <div class="text-white py-10 px-4" :style="{ background: `linear-gradient(135deg, ${business.accent_color ?? '#6366f1'}, ${business.accent_color ?? '#6366f1'}cc)` }">
      <div class="max-w-2xl mx-auto text-center">
        <div v-if="business.logo" class="w-20 h-20 rounded-full border-4 border-white/30 overflow-hidden mx-auto mb-4">
          <img :src="business.logo" :alt="business.name" class="w-full h-full object-cover" />
        </div>
        <div v-else class="w-20 h-20 rounded-full bg-white/20 border-4 border-white/30 mx-auto mb-4 flex items-center justify-center">
          <span class="text-3xl font-black">{{ business.name?.charAt(0) }}</span>
        </div>
        <h1 class="text-2xl font-black mb-1">{{ business.name }}</h1>
        <p class="text-white/80 text-sm">{{ business.category }}</p>
        <div class="flex items-center justify-center gap-4 mt-3 text-sm text-white/70">
          <div v-if="business.city" class="flex items-center gap-1">
            <MapPinIcon class="w-4 h-4" /> {{ business.city }}
          </div>
        </div>
        <p v-if="business.description" class="mt-4 text-white/80 text-sm max-w-md mx-auto leading-relaxed">
          {{ business.description }}
        </p>
      </div>
    </div>

    <!-- Main content -->
    <div class="max-w-2xl mx-auto px-4 py-8">
      <!-- Step indicator -->
      <div v-if="step < 5" class="flex items-center justify-center gap-1 mb-8">
        <template v-for="i in 4" :key="i">
          <div :class="['w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center transition-colors', step > i ? 'bg-green-500 text-white' : step === i ? 'text-white' : 'bg-gray-200 text-gray-400']"
            :style="step === i ? { backgroundColor: business.accent_color ?? '#6366f1' } : {}">
            <CheckCircleIcon v-if="step > i" class="w-4 h-4" />
            <span v-else>{{ i }}</span>
          </div>
          <div v-if="i < 4" :class="['w-8 h-0.5 transition-colors', step > i ? 'bg-green-400' : 'bg-gray-200']" />
        </template>
      </div>

      <!-- STEP 1: Choose service -->
      <Transition name="slide-up" mode="out-in">
        <div v-if="step === 1" key="step1" class="space-y-3">
          <h2 class="text-lg font-black text-gray-900 mb-4">Quel service souhaitez-vous réserver ?</h2>

          <div v-if="business.services.length === 0" class="card p-10 text-center text-gray-400">
            Aucun service disponible pour l'instant.
          </div>

          <button
            v-for="svc in business.services"
            :key="svc.id"
            @click="selectService(svc)"
            class="w-full card p-4 flex items-center gap-4 hover:shadow-md transition-all duration-200 text-left group"
          >
            <div v-if="svc.images && svc.images.length" class="w-12 h-12 rounded-xl overflow-hidden shrink-0 group-hover:scale-105 transition-transform">
              <img :src="svcImageUrl(svc.images[0])" :alt="svc.name" class="w-full h-full object-cover" />
            </div>
            <div v-else class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl shrink-0 group-hover:scale-105 transition-transform"
              :style="{ backgroundColor: svc.color ?? '#6366f1' }">
              ✦
            </div>
            <div class="flex-1">
              <p class="font-bold text-gray-900">{{ svc.name }}</p>
              <p v-if="svc.description" class="text-sm text-gray-400 mt-0.5">{{ svc.description }}</p>
              <div class="flex items-center gap-3 mt-1.5">
                <span class="flex items-center gap-1 text-xs text-gray-500">
                  <ClockIcon class="w-3.5 h-3.5" /> {{ svc.formatted_duration }}
                </span>
                <span class="text-xs font-semibold" :style="{ color: svc.color ?? '#6366f1' }">
                  {{ svc.formatted_price }}
                </span>
              </div>
            </div>
            <ChevronRightIcon class="w-5 h-5 text-gray-300 group-hover:text-gray-500 shrink-0" />
          </button>
        </div>

        <!-- STEP 2: Choose date (calendar) -->
        <div v-else-if="step === 2" key="step2" class="space-y-4">
          <div class="flex items-center gap-3 mb-6">
            <button @click="step = 1" class="btn-ghost p-2">
              <ChevronLeftIcon class="w-5 h-5" />
            </button>
            <h2 class="text-lg font-black text-gray-900">Choisissez une date</h2>
          </div>

          <!-- Service summary -->
          <div class="flex items-center gap-3 p-4 rounded-xl" :style="{ backgroundColor: (selected.service?.color ?? '#6366f1') + '15' }">
            <div v-if="selected.service?.images?.length" class="w-10 h-10 rounded-xl overflow-hidden shrink-0">
              <img :src="svcImageUrl(selected.service.images[0])" :alt="selected.service.name" class="w-full h-full object-cover" />
            </div>
            <div v-else class="w-10 h-10 rounded-xl flex items-center justify-center text-white shrink-0"
              :style="{ backgroundColor: selected.service?.color ?? '#6366f1' }">✦</div>
            <div>
              <p class="font-semibold text-gray-900 text-sm">{{ selected.service?.name }}</p>
              <p class="text-xs text-gray-500">{{ selected.service?.formatted_duration }} · {{ selected.service?.formatted_price }}</p>
            </div>
          </div>

          <!-- Calendar -->
          <div class="card p-4">
            <!-- Calendar header -->
            <div class="flex items-center justify-between mb-4">
              <button @click="currentMonth = subMonths(currentMonth, 1)" class="btn-ghost p-2">
                <ChevronLeftIcon class="w-4 h-4" />
              </button>
              <h3 class="font-semibold text-gray-900 capitalize">
                {{ format(currentMonth, 'MMMM yyyy', { locale: fr }) }}
              </h3>
              <button @click="currentMonth = addMonths(currentMonth, 1)" class="btn-ghost p-2">
                <ChevronRightIcon class="w-4 h-4" />
              </button>
            </div>

            <!-- Day labels -->
            <div class="grid grid-cols-7 mb-2">
              <div v-for="d in ['Lu','Ma','Me','Je','Ve','Sa','Di']" :key="d" class="text-center text-xs font-semibold text-gray-400 py-1">{{ d }}</div>
            </div>

            <!-- Calendar grid -->
            <div class="grid grid-cols-7 gap-1">
              <div v-for="i in calendarDays.startPad" :key="'pad-' + i" />
              <button
                v-for="day in calendarDays.days"
                :key="day.toISOString()"
                @click="selectDate(day)"
                :disabled="isDateDisabled(day) || !isDayOpen(day)"
                :class="[
                  'aspect-square rounded-xl text-sm font-medium transition-all duration-150',
                  isSameDay(day, selected.date) ? 'text-white scale-105' : '',
                  isToday(day) && !isSameDay(day, selected.date) ? 'ring-2 font-bold' : '',
                  isDateDisabled(day) || !isDayOpen(day) ? 'text-gray-300 cursor-not-allowed' : 'hover:opacity-80 cursor-pointer',
                  !isSameDay(day, selected.date) && !isDateDisabled(day) && isDayOpen(day) ? 'hover:text-white' : '',
                ]"
                :style="isSameDay(day, selected.date)
                  ? { backgroundColor: business.accent_color ?? '#6366f1' }
                  : isToday(day)
                    ? { color: business.accent_color ?? '#6366f1', borderColor: business.accent_color ?? '#6366f1' }
                    : {}"
                :onmouseover="!isSameDay(day, selected.date) && !isDateDisabled(day) && isDayOpen(day)
                  ? (e) => { if (!isSameDay(day, selected.date)) e.target.style.backgroundColor = (business.accent_color ?? '#6366f1') + '33' }
                  : null"
                :onmouseleave="!isSameDay(day, selected.date) ? (e) => { e.target.style.backgroundColor = '' } : null"
              >
                {{ format(day, 'd') }}
              </button>
            </div>
          </div>
        </div>

        <!-- STEP 3: Choose time -->
        <div v-else-if="step === 3" key="step3" class="space-y-4">
          <div class="flex items-center gap-3 mb-6">
            <button @click="step = 2" class="btn-ghost p-2">
              <ChevronLeftIcon class="w-5 h-5" />
            </button>
            <h2 class="text-lg font-black text-gray-900">Choisissez un horaire</h2>
          </div>

          <p class="text-sm text-gray-500 flex items-center gap-2">
            <CalendarDaysIcon class="w-4 h-4" />
            {{ selected.date ? format(selected.date, 'EEEE d MMMM yyyy', { locale: fr }) : '' }}
          </p>

          <div v-if="slotsLoading" class="grid grid-cols-4 gap-2">
            <div v-for="i in 8" :key="i" class="h-11 bg-gray-100 rounded-xl animate-pulse" />
          </div>

          <div v-else-if="slots.length === 0" class="card p-8 text-center">
            <ClockIcon class="w-12 h-12 text-gray-200 mx-auto mb-3" />
            <p class="text-gray-500 font-medium">Aucun créneau disponible</p>
            <p class="text-gray-400 text-sm mt-1">Essayez une autre date</p>
            <button @click="step = 2" class="btn-secondary mt-4 mx-auto text-sm">← Changer de date</button>
          </div>

          <div v-else class="grid grid-cols-4 sm:grid-cols-5 gap-2">
            <button
              v-for="time in slots"
              :key="time"
              @click="selectTime(time)"
              class="py-2.5 rounded-xl text-sm font-semibold border-2 transition-all duration-150"
              :class="selected.time === time ? 'text-white border-transparent' : 'border-gray-200 text-gray-700 hover:border-current'"
              :style="selected.time === time
                ? { backgroundColor: business.accent_color ?? '#6366f1', borderColor: business.accent_color ?? '#6366f1' }
                : { '--tw-text-opacity': 1, color: undefined }"
              :onmouseover="selected.time !== time ? (e) => { e.target.style.borderColor = business.accent_color ?? '#6366f1'; e.target.style.color = business.accent_color ?? '#6366f1' } : null"
              :onmouseleave="selected.time !== time ? (e) => { e.target.style.borderColor = ''; e.target.style.color = '' } : null"
            >
              {{ time }}
            </button>
          </div>
        </div>

        <!-- STEP 4: Customer form -->
        <div v-else-if="step === 4" key="step4" class="space-y-4">
          <div class="flex items-center gap-3 mb-6">
            <button @click="step = 3" class="btn-ghost p-2">
              <ChevronLeftIcon class="w-5 h-5" />
            </button>
            <h2 class="text-lg font-black text-gray-900">Vos coordonnées</h2>
          </div>

          <!-- Summary -->
          <div class="card p-4 space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Service</span>
              <span class="font-semibold">{{ selected.service?.name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Date</span>
              <span class="font-semibold">{{ selected.date ? format(selected.date, 'EEE d MMM', { locale: fr }) : '' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Heure</span>
              <span class="font-semibold">{{ selected.time }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Prix</span>
              <span class="font-semibold" :style="{ color: business.accent_color ?? '#6366f1' }">{{ selected.service?.formatted_price }}</span>
            </div>
          </div>

          <Transition name="fade">
            <div v-if="submitError" class="px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
              {{ submitError }}
            </div>
          </Transition>

          <form @submit.prevent="submitBooking" class="space-y-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nom complet *</label>
              <input v-model="form.customer_name" type="text" class="input-field" placeholder="Votre nom" required />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Téléphone *</label>
              <input v-model="form.customer_phone" type="tel" class="input-field" placeholder="+237 6XX XXX XXX" required />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email <span class="text-gray-400 font-normal">(optionnel)</span></label>
              <input v-model="form.customer_email" type="email" class="input-field" placeholder="votre@email.com" />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Notes <span class="text-gray-400 font-normal">(optionnel)</span></label>
              <textarea v-model="form.notes" class="input-field resize-none" rows="2" placeholder="Précisions, demandes spéciales…" />
            </div>

            <button
              type="submit"
              class="w-full py-3.5 rounded-xl text-white font-bold text-base transition-opacity hover:opacity-90 disabled:opacity-60"
              :style="{ backgroundColor: business.accent_color ?? '#6366f1' }"
              :disabled="submitting"
            >
              <span v-if="submitting" class="flex items-center justify-center gap-2">
                <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                Réservation en cours…
              </span>
              <span v-else>Confirmer ma réservation</span>
            </button>

            <p class="text-center text-xs text-gray-400">
              En réservant, vous acceptez que vos coordonnées soient transmises à {{ business.name }}.
            </p>
          </form>
        </div>

        <!-- STEP 5: Success -->
        <div v-else-if="step === 5 && booking" key="step5" class="text-center py-8 space-y-6 animate-slide-up">
          <div class="w-20 h-20 rounded-full bg-emerald-100 flex items-center justify-center mx-auto">
            <CheckCircleIcon class="w-10 h-10 text-emerald-500" />
          </div>

          <div>
            <h2 class="text-2xl font-black text-gray-900 mb-2">Réservation envoyée !</h2>
            <p class="text-gray-500">{{ business.name }} vous confirme sous peu.</p>
          </div>

          <div class="card p-5 text-left space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Référence</span>
              <span class="font-mono font-bold text-gray-900">{{ booking.reference }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Service</span>
              <span class="font-semibold">{{ booking.service }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Date</span>
              <span class="font-semibold capitalize">{{ booking.date }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Heure</span>
              <span class="font-semibold">{{ booking.time }}</span>
            </div>
          </div>

          <div v-if="booking.business_phone" class="flex flex-col gap-3">
            <p class="text-sm text-gray-500">Envoyez votre réservation au commerçant via WhatsApp :</p>
            <a
              :href="whatsappLink"
              target="_blank"
              class="flex items-center justify-center gap-2 py-3.5 px-6 bg-[#25D366] hover:bg-[#1fb855] text-white font-bold rounded-xl transition-colors mx-auto shadow-lg shadow-green-500/20"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
              Envoyer sur WhatsApp
            </a>
            <p class="text-[11px] text-gray-400">Un message pré-rempli s'ouvrira dans WhatsApp</p>
          </div>

          <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <RouterLink
              :to="{ path: '/track', query: { ref: booking.reference } }"
              class="btn-secondary text-sm"
            >
              📋 Suivre ma réservation
            </RouterLink>
            <button @click="step = 1; Object.assign(form, { customer_name: '', customer_phone: '', customer_email: '', notes: '' }); booking = null" class="btn-secondary text-sm">
              Faire une autre réservation
            </button>
          </div>
        </div>
      </Transition>
    </div>

    <!-- Footer -->
    <div class="text-center py-6 text-xs text-gray-300">
      Propulsé par <a href="/" class="font-semibold text-gray-400 hover:underline">Réserva</a>
    </div>
  </div>
</template>

<style scoped>
.slide-up-enter-active { transition: all 0.25s ease-out; }
.slide-up-leave-active { transition: all 0.15s ease-in; }
.slide-up-enter-from   { opacity: 0; transform: translateX(12px); }
.slide-up-leave-to     { opacity: 0; transform: translateX(-8px); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
