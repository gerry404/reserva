<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { publicApi } from '@/api'
import { useAccent } from '@/composables/useAccent'
import {
  CalendarDaysIcon, CheckCircleIcon, ChevronLeftIcon, ChevronRightIcon,
  ClockIcon, MapPinIcon,
} from '@heroicons/vue/24/outline'
import {
  addMonths, eachDayOfInterval, endOfMonth, format, getDay, isBefore,
  isSameDay, isSameMonth, isToday, startOfMonth, subMonths,
} from 'date-fns'
import { fr } from 'date-fns/locale'

const route = useRoute()
const slug  = computed(() => route.params.slug)

const business = ref(null)
const loading  = ref(true)
const notFound = ref(false)

const STEPS = { SERVICE: 1, DATE: 2, TIME: 3, DETAILS: 4, DONE: 5 }
const step = ref(STEPS.SERVICE)

const selected = reactive({ service: null, date: null, time: null })

const slots        = ref([])
const slotsLoading = ref(false)

/** Remaining slot count per Y-m-d, so full days are greyed out before a tap. */
const dayCapacity     = ref({})
const capacityLoading = ref(false)

const form = reactive({
  customer_name: '', customer_phone: '', customer_email: '', notes: '',
  website: '', // Honeypot: hidden from people, filled in by bots.
})
const submitting  = ref(false)
const submitError = ref('')
const booking     = ref(null)

const { style: accentStyle } = useAccent(() => business.value?.accent_color)

// ─── Calendar ──────────────────────────────────────────────────────────────
const currentMonth = ref(new Date())

const calendar = computed(() => {
  const start = startOfMonth(currentMonth.value)
  return {
    days: eachDayOfInterval({ start, end: endOfMonth(currentMonth.value) }),
    // date-fns counts from Sunday; the grid starts on Monday.
    startPad: (getDay(start) + 6) % 7,
  }
})

const canGoBack = computed(() => !isSameMonth(currentMonth.value, new Date()))

function dayKey(date) {
  return format(date, 'yyyy-MM-dd')
}

function isPast(date) {
  return isBefore(date, new Date()) && !isToday(date)
}

/**
 * A day is bookable when the server says it still has slots for *this* service.
 * Until the month's capacity has loaded we only rule out the past, so the grid
 * does not flash every day as unavailable.
 */
function isDayAvailable(date) {
  if (isPast(date)) return false
  const capacity = dayCapacity.value[dayKey(date)]
  return capacity === undefined ? true : capacity > 0
}

async function loadMonthCapacity() {
  if (!selected.service) return

  const from = startOfMonth(currentMonth.value)
  const to   = endOfMonth(currentMonth.value)

  capacityLoading.value = true
  try {
    dayCapacity.value = await publicApi.getAvailability(
      slug.value, selected.service.id, dayKey(from), dayKey(to),
    )
  } catch {
    // Non-fatal: the calendar stays usable, the customer just discovers full
    // days on the next screen instead of in the grid.
    dayCapacity.value = {}
  } finally {
    capacityLoading.value = false
  }
}

watch([currentMonth, () => selected.service], loadMonthCapacity)

// ─── Slots ─────────────────────────────────────────────────────────────────
watch(() => selected.date, async (date) => {
  if (!date || !selected.service) return

  slots.value        = []
  slotsLoading.value = true
  try {
    slots.value = await publicApi.getSlots(slug.value, selected.service.id, dayKey(date))
  } catch {
    slots.value = []
  } finally {
    slotsLoading.value = false
  }
})

const morningSlots   = computed(() => slots.value.filter((t) => Number(t.slice(0, 2)) < 12))
const afternoonSlots = computed(() => slots.value.filter((t) => Number(t.slice(0, 2)) >= 12))

// ─── Flow ──────────────────────────────────────────────────────────────────
function selectService(service) {
  selected.service = service
  selected.date    = null
  selected.time    = null
  step.value       = STEPS.DATE
}

function selectDate(date) {
  if (!isDayAvailable(date)) return
  selected.date = date
  selected.time = null
  step.value    = STEPS.TIME
}

function selectTime(time) {
  selected.time = time
  step.value    = STEPS.DETAILS
}

async function submitBooking() {
  submitError.value = ''
  submitting.value  = true
  try {
    const response = await publicApi.book(slug.value, {
      service_id:     selected.service.id,
      customer_name:  form.customer_name,
      customer_phone: form.customer_phone,
      customer_email: form.customer_email || null,
      notes:          form.notes || null,
      website:        form.website,
      date:           dayKey(selected.date),
      time_slot:      selected.time,
    })
    booking.value = response.data
    step.value    = STEPS.DONE
  } catch (e) {
    submitError.value = e.message

    // 409 means the slot went while the form was being filled in. Send the
    // customer back to a freshly loaded list rather than leaving them pressing
    // a button that cannot succeed.
    if (e.status === 409) {
      step.value = STEPS.TIME
      selected.time = null
      const date = selected.date
      selected.date = null
      selected.date = date
      loadMonthCapacity()
    }
  } finally {
    submitting.value = false
  }
}

function startOver() {
  Object.assign(form, { customer_name: '', customer_phone: '', customer_email: '', notes: '', website: '' })
  Object.assign(selected, { service: null, date: null, time: null })
  booking.value = null
  step.value    = STEPS.SERVICE
}

// ─── Confirmation ──────────────────────────────────────────────────────────
const whatsappLink = computed(() => {
  const phone = booking.value?.business_phone?.replace(/\D/g, '')
  if (!phone) return null

  const message = [
    'Bonjour,',
    '',
    `Je viens de réserver chez *${business.value?.name}*.`,
    '',
    `• Service : ${booking.value.service}`,
    `• Date : ${booking.value.date_label}`,
    `• Heure : ${booking.value.time}`,
    `• Réf : ${booking.value.reference}`,
    `• Nom : ${booking.value.customer_name}`,
    '',
    'Merci de confirmer ma réservation.',
  ].join('\n')

  return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`
})

async function loadBusiness() {
  try {
    business.value = await publicApi.getBusiness(slug.value)
  } catch {
    notFound.value = true
  } finally {
    loading.value = false
  }
}

loadBusiness()
</script>

<template>
  <div v-if="loading" class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin" />
  </div>

  <div v-else-if="notFound" class="min-h-screen flex items-center justify-center bg-gray-50 p-6">
    <div class="text-center max-w-sm">
      <div class="text-6xl mb-4">🔍</div>
      <h1 class="text-2xl font-black text-gray-900 mb-2">Page introuvable</h1>
      <p class="text-gray-500">Ce commerce n'existe pas ou n'accepte plus de réservations en ligne.</p>
    </div>
  </div>

  <div v-else class="min-h-screen bg-gray-50" :style="accentStyle">
    <!-- Business header -->
    <header class="py-10 px-4 accent-gradient">
      <div class="max-w-2xl mx-auto text-center">
        <div class="w-20 h-20 rounded-full border-4 border-white/30 overflow-hidden mx-auto mb-4 bg-white/20 flex items-center justify-center">
          <img v-if="business.logo" :src="business.logo" :alt="business.name" class="w-full h-full object-cover" />
          <span v-else class="text-3xl font-black">{{ business.name?.charAt(0) }}</span>
        </div>
        <h1 class="text-2xl font-black mb-1">{{ business.name }}</h1>
        <p class="opacity-80 text-sm">{{ business.category }}</p>
        <p v-if="business.city" class="flex items-center justify-center gap-1 mt-3 text-sm opacity-75">
          <MapPinIcon class="w-4 h-4" /> {{ business.city }}
        </p>
        <p v-if="business.description" class="mt-4 opacity-80 text-sm max-w-md mx-auto leading-relaxed">
          {{ business.description }}
        </p>
      </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-8">
      <!-- Progress -->
      <ol v-if="step < STEPS.DONE" class="flex items-center justify-center gap-1 mb-8" aria-label="Étapes">
        <template v-for="i in 4" :key="i">
          <li
            class="w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center transition-colors"
            :class="step > i ? 'bg-emerald-500 text-white' : step === i ? 'accent-bg' : 'bg-gray-200 text-gray-400'"
            :aria-current="step === i ? 'step' : undefined"
          >
            <CheckCircleIcon v-if="step > i" class="w-4 h-4" />
            <span v-else>{{ i }}</span>
          </li>
          <li v-if="i < 4" class="w-8 h-0.5" :class="step > i ? 'bg-emerald-400' : 'bg-gray-200'" />
        </template>
      </ol>

      <!-- STEP 1 — service -->
      <section v-if="step === STEPS.SERVICE" class="space-y-3">
        <h2 class="text-lg font-black text-gray-900 mb-4">Quel service souhaitez-vous réserver ?</h2>

        <p v-if="!business.services.length" class="card p-10 text-center text-gray-400">
          Aucun service n'est disponible pour l'instant.
        </p>

        <button
          v-for="service in business.services"
          :key="service.id"
          type="button"
          class="w-full card p-4 flex items-center gap-4 text-left transition-shadow hover:shadow-md"
          @click="selectService(service)"
        >
          <img
            v-if="service.images?.length"
            :src="service.images[0].url"
            :alt="service.name"
            class="w-12 h-12 rounded-xl object-cover shrink-0"
          />
          <span
            v-else
            class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl shrink-0"
            :style="{ backgroundColor: service.color }"
          >✦</span>

          <span class="flex-1 min-w-0">
            <span class="block font-bold text-gray-900">{{ service.name }}</span>
            <span v-if="service.description" class="block text-sm text-gray-400 mt-0.5 truncate">
              {{ service.description }}
            </span>
            <span class="flex items-center gap-3 mt-1.5">
              <span class="flex items-center gap-1 text-xs text-gray-500">
                <ClockIcon class="w-3.5 h-3.5" /> {{ service.formatted_duration }}
              </span>
              <span class="text-xs font-semibold" :style="{ color: service.color }">
                {{ service.formatted_price }}
              </span>
            </span>
          </span>

          <ChevronRightIcon class="w-5 h-5 text-gray-300 shrink-0" />
        </button>
      </section>

      <!-- STEP 2 — date -->
      <section v-else-if="step === STEPS.DATE" class="space-y-4">
        <div class="flex items-center gap-3 mb-6">
          <button type="button" class="btn-ghost p-2" aria-label="Retour" @click="step = STEPS.SERVICE">
            <ChevronLeftIcon class="w-5 h-5" />
          </button>
          <h2 class="text-lg font-black text-gray-900">Choisissez une date</h2>
        </div>

        <div class="flex items-center gap-3 p-4 rounded-xl" style="background-color: var(--accent-soft)">
          <span
            class="w-10 h-10 rounded-xl flex items-center justify-center text-white shrink-0"
            :style="{ backgroundColor: selected.service?.color }"
          >✦</span>
          <span>
            <span class="block font-semibold text-gray-900 text-sm">{{ selected.service?.name }}</span>
            <span class="block text-xs text-gray-500">
              {{ selected.service?.formatted_duration }} · {{ selected.service?.formatted_price }}
            </span>
          </span>
        </div>

        <div class="card p-4">
          <div class="flex items-center justify-between mb-4">
            <button
              type="button"
              class="btn-ghost p-2 disabled:opacity-30 disabled:cursor-not-allowed"
              :disabled="!canGoBack"
              aria-label="Mois précédent"
              @click="currentMonth = subMonths(currentMonth, 1)"
            >
              <ChevronLeftIcon class="w-4 h-4" />
            </button>
            <h3 class="font-semibold text-gray-900 capitalize">
              {{ format(currentMonth, 'MMMM yyyy', { locale: fr }) }}
            </h3>
            <button
              type="button"
              class="btn-ghost p-2"
              aria-label="Mois suivant"
              @click="currentMonth = addMonths(currentMonth, 1)"
            >
              <ChevronRightIcon class="w-4 h-4" />
            </button>
          </div>

          <div class="grid grid-cols-7 mb-2">
            <span v-for="d in ['Lu','Ma','Me','Je','Ve','Sa','Di']" :key="d"
              class="text-center text-xs font-semibold text-gray-400 py-1">{{ d }}</span>
          </div>

          <div class="grid grid-cols-7 gap-1" :class="capacityLoading ? 'opacity-60' : ''">
            <span v-for="i in calendar.startPad" :key="`pad-${i}`" />
            <button
              v-for="day in calendar.days"
              :key="day.toISOString()"
              type="button"
              class="day-cell"
              :class="{
                'is-selected': selected.date && isSameDay(day, selected.date),
                'is-today': isToday(day),
                'is-full': !isPast(day) && dayCapacity[dayKey(day)] === 0,
              }"
              :disabled="!isDayAvailable(day)"
              :aria-label="format(day, 'EEEE d MMMM', { locale: fr })"
              @click="selectDate(day)"
            >
              {{ format(day, 'd') }}
            </button>
          </div>

          <p class="text-[11px] text-gray-400 mt-3 text-center">
            Les jours grisés sont fermés ou déjà complets pour ce service.
          </p>
        </div>
      </section>

      <!-- STEP 3 — time -->
      <section v-else-if="step === STEPS.TIME" class="space-y-4">
        <div class="flex items-center gap-3 mb-6">
          <button type="button" class="btn-ghost p-2" aria-label="Retour" @click="step = STEPS.DATE">
            <ChevronLeftIcon class="w-5 h-5" />
          </button>
          <h2 class="text-lg font-black text-gray-900">Choisissez un horaire</h2>
        </div>

        <p class="text-sm text-gray-500 flex items-center gap-2 capitalize">
          <CalendarDaysIcon class="w-4 h-4" />
          {{ selected.date ? format(selected.date, 'EEEE d MMMM yyyy', { locale: fr }) : '' }}
        </p>

        <div v-if="slotsLoading" class="grid grid-cols-4 gap-2">
          <span v-for="i in 8" :key="i" class="h-11 bg-gray-100 rounded-xl animate-pulse" />
        </div>

        <div v-else-if="!slots.length" class="card p-8 text-center">
          <ClockIcon class="w-12 h-12 text-gray-200 mx-auto mb-3" />
          <p class="text-gray-500 font-medium">Aucun créneau disponible</p>
          <p class="text-gray-400 text-sm mt-1">
            Ce service dure {{ selected.service?.formatted_duration }} — essayez une autre date.
          </p>
          <button type="button" class="btn-secondary mt-4 mx-auto text-sm" @click="step = STEPS.DATE">
            ← Changer de date
          </button>
        </div>

        <template v-else>
          <div v-for="group in [
            { label: 'Matin', times: morningSlots },
            { label: 'Après-midi', times: afternoonSlots },
          ]" :key="group.label">
            <template v-if="group.times.length">
              <h3 class="text-xs font-bold uppercase tracking-wide text-gray-400 mb-2 mt-4">
                {{ group.label }}
              </h3>
              <div class="grid grid-cols-4 sm:grid-cols-5 gap-2">
                <button
                  v-for="time in group.times"
                  :key="time"
                  type="button"
                  class="slot-button"
                  :class="{ 'is-selected': selected.time === time }"
                  @click="selectTime(time)"
                >
                  {{ time }}
                </button>
              </div>
            </template>
          </div>
        </template>
      </section>

      <!-- STEP 4 — details -->
      <section v-else-if="step === STEPS.DETAILS" class="space-y-4">
        <div class="flex items-center gap-3 mb-6">
          <button type="button" class="btn-ghost p-2" aria-label="Retour" @click="step = STEPS.TIME">
            <ChevronLeftIcon class="w-5 h-5" />
          </button>
          <h2 class="text-lg font-black text-gray-900">Vos coordonnées</h2>
        </div>

        <dl class="card p-4 space-y-2 text-sm">
          <div class="flex justify-between">
            <dt class="text-gray-500">Service</dt>
            <dd class="font-semibold">{{ selected.service?.name }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">Date</dt>
            <dd class="font-semibold capitalize">
              {{ selected.date ? format(selected.date, 'EEE d MMM', { locale: fr }) : '' }}
            </dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">Heure</dt>
            <dd class="font-semibold">{{ selected.time }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">Durée</dt>
            <dd class="font-semibold">{{ selected.service?.formatted_duration }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">Prix</dt>
            <dd class="font-semibold" style="color: var(--accent)">{{ selected.service?.formatted_price }}</dd>
          </div>
        </dl>

        <p v-if="submitError" class="px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
          {{ submitError }}
        </p>

        <form class="space-y-4" @submit.prevent="submitBooking">
          <div>
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5">Nom complet *</label>
            <input id="name" v-model="form.customer_name" type="text" required autocomplete="name"
              class="input-field" placeholder="Votre nom" />
          </div>

          <div>
            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1.5">Téléphone *</label>
            <input id="phone" v-model="form.customer_phone" type="tel" required autocomplete="tel"
              class="input-field" placeholder="+237 6XX XXX XXX" />
            <p class="text-[11px] text-gray-400 mt-1">
              C'est par là que {{ business.name }} vous confirmera le rendez-vous.
            </p>
          </div>

          <div>
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">
              Email <span class="text-gray-400 font-normal">(recommandé)</span>
            </label>
            <input id="email" v-model="form.customer_email" type="email" autocomplete="email"
              class="input-field" placeholder="vous@exemple.com" />
            <p class="text-[11px] text-gray-400 mt-1">
              Pour recevoir votre confirmation et un rappel la veille.
            </p>
          </div>

          <div>
            <label for="notes" class="block text-sm font-semibold text-gray-700 mb-1.5">
              Notes <span class="text-gray-400 font-normal">(optionnel)</span>
            </label>
            <textarea id="notes" v-model="form.notes" rows="2" class="input-field resize-none"
              placeholder="Précisions, demandes particulières…" />
          </div>

          <!-- Honeypot. Hidden from people and from screen readers; bots that
               fill every field give themselves away. -->
          <div class="hidden" aria-hidden="true">
            <label for="website">Site web</label>
            <input id="website" v-model="form.website" type="text" tabindex="-1" autocomplete="off" />
          </div>

          <button type="submit" class="w-full py-3.5 rounded-xl font-bold text-base accent-bg disabled:opacity-60"
            :disabled="submitting">
            <span v-if="submitting" class="flex items-center justify-center gap-2">
              <span class="w-4 h-4 border-2 border-current/30 border-t-current rounded-full animate-spin" />
              Réservation en cours…
            </span>
            <span v-else>Confirmer ma réservation</span>
          </button>

          <p class="text-center text-xs text-gray-400">
            En réservant, vous acceptez que vos coordonnées soient transmises à {{ business.name }}.
          </p>
        </form>
      </section>

      <!-- STEP 5 — done -->
      <section v-else-if="booking" class="text-center py-8 space-y-6">
        <div class="w-20 h-20 rounded-full bg-emerald-100 flex items-center justify-center mx-auto">
          <CheckCircleIcon class="w-10 h-10 text-emerald-500" />
        </div>

        <div>
          <h2 class="text-2xl font-black text-gray-900 mb-2">Demande envoyée !</h2>
          <p class="text-gray-500">{{ business.name }} vous confirmera sous peu.</p>
        </div>

        <dl class="card p-5 text-left space-y-3 text-sm">
          <div class="flex justify-between">
            <dt class="text-gray-500">Référence</dt>
            <dd class="font-mono font-bold text-gray-900">{{ booking.reference }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">Service</dt>
            <dd class="font-semibold">{{ booking.service }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">Date</dt>
            <dd class="font-semibold capitalize">{{ booking.date_label }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">Heure</dt>
            <dd class="font-semibold">{{ booking.time }} – {{ booking.ends_at_time }}</dd>
          </div>
        </dl>

        <div v-if="whatsappLink" class="space-y-2">
          <p class="text-sm text-gray-500">Prévenez le commerçant directement :</p>
          <a :href="whatsappLink" target="_blank" rel="noopener"
            class="inline-flex items-center justify-center gap-2 py-3.5 px-6 bg-[#25D366] hover:bg-[#1fb855] text-white font-bold rounded-xl transition-colors shadow-lg shadow-green-500/20">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
            Envoyer sur WhatsApp
          </a>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <RouterLink :to="{ name: 'track-booking', query: { ref: booking.reference } }" class="btn-secondary text-sm">
            Suivre ma réservation
          </RouterLink>
          <button type="button" class="btn-secondary text-sm" @click="startOver">
            Faire une autre réservation
          </button>
        </div>
      </section>
    </main>

    <footer class="text-center py-6 text-xs text-gray-400">
      Propulsé par <RouterLink to="/" class="font-semibold hover:underline">Réserva</RouterLink>
    </footer>
  </div>
</template>

<style scoped>
/*
 * Interaction states are CSS, not inline mouse handlers.
 *
 * The previous version bound :onmouseover/:onmouseleave on every calendar cell
 * and time button. Those never fire on a touchscreen — which is where nearly
 * every customer opens this page — so the whole grid felt dead on mobile.
 */
.accent-gradient {
  background: linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 80%, black));
  color: var(--accent-fg);
}

.accent-bg {
  background-color: var(--accent);
  color: var(--accent-fg);
}

.day-cell {
  aspect-ratio: 1;
  border-radius: 0.75rem;
  font-size: 0.875rem;
  font-weight: 500;
  transition: background-color 0.15s, color 0.15s, transform 0.15s;
}

.day-cell:not(:disabled):hover {
  background-color: var(--accent-hover);
}

.day-cell:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

.day-cell:disabled {
  color: #d1d5db;
  cursor: not-allowed;
}

/* Fully booked reads differently from closed: there was something here. */
.day-cell.is-full:disabled {
  color: #9ca3af;
  text-decoration: line-through;
}

.day-cell.is-today:not(.is-selected) {
  color: var(--accent);
  font-weight: 700;
  box-shadow: inset 0 0 0 2px var(--accent-ring);
}

.day-cell.is-selected {
  background-color: var(--accent);
  color: var(--accent-fg);
  transform: scale(1.05);
}

.slot-button {
  padding: 0.625rem 0;
  border-radius: 0.75rem;
  border: 2px solid #e5e7eb;
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  transition: border-color 0.15s, color 0.15s, background-color 0.15s;
}

.slot-button:hover {
  border-color: var(--accent);
  color: var(--accent);
}

.slot-button:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

.slot-button.is-selected {
  background-color: var(--accent);
  border-color: var(--accent);
  color: var(--accent-fg);
}
</style>
