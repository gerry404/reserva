<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { businessApi } from '@/api'
import {
  LinkIcon,
  CheckCircleIcon,
  DevicePhoneMobileIcon,
  ClockIcon,
  GlobeAltIcon,
} from '@heroicons/vue/24/outline'

const auth    = useAuthStore()
const saving  = ref(false)
const saved   = ref(false)
const linkCopied = ref(false)
const errors  = ref({})

const form = reactive({
  name:                '',
  description:         '',
  category:            '',
  city:                '',
  address:             '',
  phone:               '',
  whatsapp:            '',
  slot_duration:       30,
  booking_notice:      60,
  notifications_whatsapp: true,
  notifications_sms:   false,
  accent_color:        '#6366f1',
  working_hours:       {},
})

const days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche']
const timeOptions = []
for (let h = 6; h <= 22; h++) {
  for (let m = 0; m < 60; m += 30) {
    timeOptions.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`)
  }
}

const slotOptions = [
  { v: 15, l: '15 min' }, { v: 30, l: '30 min' }, { v: 45, l: '45 min' },
  { v: 60, l: '1 heure' }, { v: 90, l: '1h 30' }, { v: 120, l: '2 heures' },
]

const noticeOptions = [
  { v: 0,   l: 'Immédiatement' },
  { v: 30,  l: '30 minutes avant' },
  { v: 60,  l: '1 heure avant' },
  { v: 120, l: '2 heures avant' },
  { v: 240, l: '4 heures avant' },
  { v: 1440, l: '24 heures avant' },
]

const accentColors = [
  '#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#ef4444', '#06b6d4', '#f97316',
]

onMounted(() => {
  const b = auth.business
  if (!b) return
  Object.assign(form, {
    name:                b.name ?? '',
    description:         b.description ?? '',
    category:            b.category ?? '',
    city:                b.city ?? '',
    address:             b.address ?? '',
    phone:               b.phone ?? '',
    whatsapp:            b.whatsapp ?? '',
    slot_duration:       b.slot_duration ?? 30,
    booking_notice:      b.booking_notice ?? 60,
    notifications_whatsapp: b.notifications_whatsapp ?? true,
    notifications_sms:   b.notifications_sms ?? false,
    accent_color:        b.accent_color ?? '#6366f1',
    working_hours:       b.working_hours ?? defaultWorkingHours(),
  })
})

function defaultWorkingHours() {
  return Object.fromEntries(days.map(d => [d, {
    is_open: d !== 'dimanche',
    open:    '08:00',
    close:   '18:00',
  }]))
}

async function save() {
  errors.value = {}
  saving.value = true
  try {
    const { data } = await businessApi.update(form)
    auth.updateBusiness(data)
    saved.value = true
    setTimeout(() => saved.value = false, 3000)
  } catch (e) {
    errors.value = e.response?.data?.errors ?? {}
  } finally {
    saving.value = false
  }
}

const publicUrl = `${window.location.origin}/b/${auth.business?.slug}`

async function copyLink() {
  await navigator.clipboard.writeText(publicUrl)
  linkCopied.value = true
  setTimeout(() => linkCopied.value = false, 2000)
}
</script>

<template>
  <div class="max-w-2xl mx-auto space-y-8 animate-fade-in">
    <div>
      <h2 class="text-xl font-black text-gray-900">Paramètres</h2>
      <p class="text-sm text-gray-500 mt-0.5">Configurez votre commerce et vos préférences</p>
    </div>

    <!-- Public URL -->
    <div class="card p-5">
      <h3 class="font-bold text-gray-900 mb-1 flex items-center gap-2">
        <LinkIcon class="w-5 h-5 text-primary-500" />
        Votre lien de réservation
      </h3>
      <p class="text-sm text-gray-500 mb-3">Partagez ce lien sur WhatsApp, Instagram, Facebook…</p>
      <div class="flex gap-2">
        <div class="flex-1 flex items-center px-4 py-2.5 bg-primary-50 border border-primary-200 rounded-xl text-sm font-mono text-primary-700 overflow-hidden">
          <span class="truncate">{{ publicUrl }}</span>
        </div>
        <button @click="copyLink" class="btn-primary px-4 whitespace-nowrap">
          {{ linkCopied ? '✓ Copié' : 'Copier' }}
        </button>
        <a :href="publicUrl" target="_blank" class="btn-secondary px-3">↗</a>
      </div>
    </div>

    <form @submit.prevent="save" class="space-y-6">
      <!-- Commerce info -->
      <div class="card p-6 space-y-5">
        <h3 class="font-bold text-gray-900 flex items-center gap-2">
          <GlobeAltIcon class="w-5 h-5 text-gray-400" />
          Informations du commerce
        </h3>

        <div class="grid sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nom du commerce *</label>
            <input v-model="form.name" type="text" class="input-field" required />
            <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
          </div>

          <div class="sm:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Description</label>
            <textarea v-model="form.description" class="input-field resize-none" rows="3" placeholder="Décrivez votre commerce en quelques mots…" />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Catégorie</label>
            <input v-model="form.category" type="text" class="input-field" placeholder="ex: Salon de coiffure" />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Ville</label>
            <input v-model="form.city" type="text" class="input-field" placeholder="ex: Douala" />
          </div>

          <div class="sm:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Adresse</label>
            <input v-model="form.address" type="text" class="input-field" placeholder="ex: Akwa, Rue de la Liberté" />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Téléphone</label>
            <input v-model="form.phone" type="tel" class="input-field" placeholder="+237 6XX XXX XXX" />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">WhatsApp</label>
            <input v-model="form.whatsapp" type="tel" class="input-field" placeholder="+237 6XX XXX XXX" />
          </div>
        </div>

        <!-- Accent color -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">Couleur principale</label>
          <div class="flex gap-2 flex-wrap">
            <button v-for="c in accentColors" :key="c" type="button"
              @click="form.accent_color = c"
              class="w-8 h-8 rounded-lg transition-transform hover:scale-110 ring-offset-2"
              :class="form.accent_color === c ? 'ring-2 ring-gray-900 scale-110' : ''"
              :style="{ backgroundColor: c }"
            />
          </div>
        </div>
      </div>

      <!-- Booking settings -->
      <div class="card p-6 space-y-5">
        <h3 class="font-bold text-gray-900 flex items-center gap-2">
          <ClockIcon class="w-5 h-5 text-gray-400" />
          Paramètres de réservation
        </h3>

        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Durée des créneaux</label>
            <select v-model="form.slot_duration" class="input-field">
              <option v-for="o in slotOptions" :key="o.v" :value="o.v">{{ o.l }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Délai minimum de réservation</label>
            <select v-model="form.booking_notice" class="input-field">
              <option v-for="o in noticeOptions" :key="o.v" :value="o.v">{{ o.l }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Working hours -->
      <div class="card p-6 space-y-4">
        <h3 class="font-bold text-gray-900 flex items-center gap-2">
          <ClockIcon class="w-5 h-5 text-gray-400" />
          Horaires d'ouverture
        </h3>

        <div class="space-y-3">
          <div v-for="day in days" :key="day" class="flex items-center gap-3">
            <!-- Day toggle -->
            <button
              type="button"
              @click="form.working_hours[day].is_open = !form.working_hours[day].is_open"
              :class="['relative inline-flex h-5 w-9 shrink-0 rounded-full transition-colors', form.working_hours[day]?.is_open ? 'bg-primary-600' : 'bg-gray-200']"
            >
              <span :class="['absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform', form.working_hours[day]?.is_open ? 'translate-x-4' : 'translate-x-0']" />
            </button>

            <span class="w-20 text-sm font-medium text-gray-700 capitalize">{{ day }}</span>

            <template v-if="form.working_hours[day]?.is_open">
              <select v-model="form.working_hours[day].open" class="input-field py-1.5 text-xs flex-1 max-w-[90px]">
                <option v-for="t in timeOptions" :key="t" :value="t">{{ t }}</option>
              </select>
              <span class="text-gray-400 text-sm">–</span>
              <select v-model="form.working_hours[day].close" class="input-field py-1.5 text-xs flex-1 max-w-[90px]">
                <option v-for="t in timeOptions" :key="t" :value="t">{{ t }}</option>
              </select>
            </template>
            <span v-else class="text-sm text-gray-400 italic">Fermé</span>
          </div>
        </div>
      </div>

      <!-- Notifications -->
      <div class="card p-6 space-y-4">
        <h3 class="font-bold text-gray-900 flex items-center gap-2">
          <DevicePhoneMobileIcon class="w-5 h-5 text-gray-400" />
          Notifications
        </h3>

        <label class="flex items-center justify-between cursor-pointer group">
          <div>
            <p class="font-medium text-gray-900 text-sm">WhatsApp Business</p>
            <p class="text-xs text-gray-400">Recevez chaque réservation sur WhatsApp</p>
          </div>
          <div
            @click="form.notifications_whatsapp = !form.notifications_whatsapp"
            :class="['relative inline-flex h-6 w-11 rounded-full transition-colors cursor-pointer', form.notifications_whatsapp ? 'bg-primary-600' : 'bg-gray-200']"
          >
            <span :class="['absolute top-1 left-1 w-4 h-4 rounded-full bg-white shadow transition-transform', form.notifications_whatsapp ? 'translate-x-5' : 'translate-x-0']" />
          </div>
        </label>

        <label class="flex items-center justify-between cursor-pointer group">
          <div>
            <p class="font-medium text-gray-900 text-sm">SMS (Africa's Talking)</p>
            <p class="text-xs text-gray-400">Recevez aussi une notification par SMS</p>
          </div>
          <div
            @click="form.notifications_sms = !form.notifications_sms"
            :class="['relative inline-flex h-6 w-11 rounded-full transition-colors cursor-pointer', form.notifications_sms ? 'bg-primary-600' : 'bg-gray-200']"
          >
            <span :class="['absolute top-1 left-1 w-4 h-4 rounded-full bg-white shadow transition-transform', form.notifications_sms ? 'translate-x-5' : 'translate-x-0']" />
          </div>
        </label>
      </div>

      <!-- Save button -->
      <div class="flex items-center gap-4">
        <button type="submit" class="btn-primary px-8 py-3" :disabled="saving">
          <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
          <span v-else>Enregistrer les modifications</span>
        </button>
        <Transition name="fade">
          <div v-if="saved" class="flex items-center gap-1.5 text-emerald-600 text-sm font-semibold">
            <CheckCircleIcon class="w-5 h-5" /> Enregistré !
          </div>
        </Transition>
      </div>
    </form>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
