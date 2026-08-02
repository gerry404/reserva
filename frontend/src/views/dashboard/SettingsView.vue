<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { businessApi } from '@/api'
import { COUNTRIES } from '@/composables/usePhoneInput'
import {
  LinkIcon,
  CheckCircleIcon,
  DevicePhoneMobileIcon,
  ClockIcon,
  GlobeAltIcon,
  CameraIcon,
  PhotoIcon,
} from '@heroicons/vue/24/outline'

const auth       = useAuthStore()
const saving     = ref(false)
const saved      = ref(false)
const linkCopied = ref(false)
const errors     = ref({})
const saveError  = ref('')

// `window` is not exposed to templates — reading it there threw before the page
// could render. Resolve it once, here.
const origin = window.location.origin

// Computed, not a one-off const: on a page refresh the business is still being
// fetched when this file runs, and a snapshot would freeze `/b/undefined`.
const publicUrl = computed(() =>
  auth.business?.slug ? `${origin}/b/${auth.business.slug}` : '',
)

const logoFile    = ref(null)
const coverFile   = ref(null)
const logoPreview  = ref(null)
const coverPreview = ref(null)

function onLogoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  logoFile.value = file
  logoPreview.value = URL.createObjectURL(file)
}

function onCoverChange(e) {
  const file = e.target.files[0]
  if (!file) return
  coverFile.value = file
  coverPreview.value = URL.createObjectURL(file)
}

const form = reactive({
  name:                '',
  slug:                '',
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
  notifications_email: true,
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

/**
 * Fill the form from the store.
 *
 * Watched rather than read once in onMounted: on a page refresh the session is
 * still loading when this component mounts, so a one-shot read produced an
 * empty form and, on save, wiped the merchant's settings with blanks.
 */
watch(
  () => auth.business,
  (business) => {
    if (!business) return

    Object.assign(form, {
      name:                   business.name ?? '',
      slug:                   business.slug ?? '',
      description:            business.description ?? '',
      category:               business.category ?? '',
      city:                   business.city ?? '',
      address:                business.address ?? '',
      country:                business.country ?? 'CM',
      phone:                  business.phone ?? '',
      whatsapp:               business.whatsapp ?? '',
      slot_duration:          business.slot_duration ?? 30,
      booking_notice:         business.booking_notice ?? 60,
      notifications_whatsapp: business.notifications_whatsapp ?? true,
      notifications_sms:      business.notifications_sms ?? false,
      notifications_email:    business.notifications_email ?? true,
      accent_color:           business.accent_color ?? '#6366f1',
      working_hours:          business.working_hours ?? defaultWorkingHours(),
    })

    logoPreview.value  = business.logo_url ?? null
    coverPreview.value = business.cover_image_url ?? null
  },
  { immediate: true, deep: false },
)

function defaultWorkingHours() {
  return Object.fromEntries(days.map((day) => [day, {
    is_open: day !== 'dimanche',
    open: '08:00',
    close: '18:00',
  }]))
}

function toFormData() {
  const data = new FormData()

  for (const [key, value] of Object.entries(form)) {
    if (key === 'working_hours') {
      data.append(key, JSON.stringify(value))
    } else if (typeof value === 'boolean') {
      data.append(key, value ? '1' : '0')
    } else {
      data.append(key, value ?? '')
    }
  }

  if (logoFile.value) data.append('logo', logoFile.value)
  if (coverFile.value) data.append('cover_image', coverFile.value)

  return data
}

async function save() {
  errors.value    = {}
  saveError.value = ''
  saving.value    = true

  try {
    const hasFiles = logoFile.value || coverFile.value
    const business = hasFiles
      ? await businessApi.updateWithFiles(toFormData())
      : await businessApi.update(form)

    auth.setBusiness(business)
    logoFile.value  = null
    coverFile.value = null

    saved.value = true
    setTimeout(() => { saved.value = false }, 3000)
  } catch (e) {
    errors.value = e.fieldErrors
    // 402 means a Pro-only field (the custom link); the message explains which.
    if (!Object.keys(e.fieldErrors).length) saveError.value = e.message
  } finally {
    saving.value = false
  }
}

async function copyLink() {
  if (!publicUrl.value) return
  await navigator.clipboard.writeText(publicUrl.value)
  linkCopied.value = true
  setTimeout(() => { linkCopied.value = false }, 2000)
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
        <button type="button" class="btn-primary px-4 whitespace-nowrap" @click="copyLink">
          {{ linkCopied ? '✓ Copié' : 'Copier' }}
        </button>
        <a :href="publicUrl" target="_blank" rel="noopener" class="btn-secondary px-3" aria-label="Ouvrir la page publique">↗</a>
      </div>
    </div>

    <form @submit.prevent="save" class="space-y-6">
      <!-- Profil visuel -->
      <div class="card overflow-hidden">
        <!-- Cover image -->
        <div class="relative w-full h-[200px] bg-gray-100">
          <img v-if="coverPreview" :src="coverPreview" class="w-full h-full object-cover" alt="Cover" />
          <div v-else class="w-full h-full border-2 border-dashed border-gray-300 flex items-center justify-center">
            <div class="text-center text-gray-400">
              <PhotoIcon class="w-10 h-10 mx-auto mb-1" />
              <span class="text-sm">Ajouter une bannière</span>
            </div>
          </div>
          <label class="absolute bottom-3 right-3 bg-white/90 backdrop-blur rounded-lg px-3 py-1.5 text-sm font-medium text-gray-700 cursor-pointer hover:bg-white transition shadow-sm flex items-center gap-1.5">
            <CameraIcon class="w-4 h-4" />
            Changer la bannière
            <input type="file" accept="image/*" class="hidden" @change="onCoverChange" />
          </label>
        </div>

        <!-- Logo -->
        <div class="relative px-6 pb-5">
          <label class="absolute -top-10 left-6 cursor-pointer group">
            <div class="w-20 h-20 rounded-full border-4 border-white shadow-lg overflow-hidden bg-gray-200 flex items-center justify-center">
              <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-cover" alt="Logo" />
              <span v-else class="text-2xl font-bold text-gray-400">{{ form.name?.charAt(0)?.toUpperCase() || '?' }}</span>
            </div>
            <div class="absolute inset-0 rounded-full bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
              <CameraIcon class="w-5 h-5 text-white" />
            </div>
            <input type="file" accept="image/*" class="hidden" @change="onLogoChange" />
          </label>
          <div class="pt-12 pl-1">
            <p class="text-sm font-semibold text-gray-700">Profil visuel</p>
            <p class="text-xs text-gray-400">Logo et image de couverture de votre commerce</p>
          </div>
        </div>
      </div>

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
            <label for="slug" class="block text-sm font-semibold text-gray-700 mb-1.5">
              Lien personnalisé
              <span v-if="!auth.isPro" class="ml-1.5 text-[10px] font-bold uppercase tracking-wide bg-primary-50 text-primary-600 px-1.5 py-0.5 rounded">
                Pro
              </span>
            </label>
            <div class="flex items-stretch">
              <span class="px-3 py-2.5 bg-gray-100 border border-r-0 border-gray-200 rounded-l-xl text-sm text-gray-500 whitespace-nowrap flex items-center">
                {{ origin }}/b/
              </span>
              <input
                id="slug"
                v-model="form.slug"
                type="text"
                class="input-field rounded-l-none flex-1 disabled:bg-gray-50 disabled:text-gray-400"
                placeholder="mon-salon"
                :disabled="!auth.isPro"
              />
            </div>
            <p v-if="errors.slug" class="text-red-500 text-xs mt-1">{{ errors.slug[0] }}</p>
            <p v-else-if="!auth.isPro" class="text-[11px] text-gray-400 mt-1">
              <RouterLink :to="{ name: 'billing' }" class="text-primary-600 font-semibold hover:underline">
                Passez au plan Pro
              </RouterLink>
              pour choisir votre propre lien.
            </p>
            <p v-else class="text-[11px] text-gray-400 mt-1">
              Lettres minuscules, chiffres et tirets uniquement.
            </p>
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
            <label for="city" class="block text-sm font-semibold text-gray-700 mb-1.5">Ville</label>
            <input id="city" v-model="form.city" type="text" class="input-field" placeholder="ex : Douala" />
          </div>

          <div class="sm:col-span-2">
            <label for="country" class="block text-sm font-semibold text-gray-700 mb-1.5">Pays</label>
            <select id="country" v-model="form.country" class="input-field">
              <option v-for="c in COUNTRIES" :key="c.code" :value="c.code">
                {{ c.flag }} {{ c.name }}
              </option>
            </select>
            <!-- Not cosmetic: the country sets the clock your opening hours are
                 read against, and the currency your prices are shown in. -->
            <p class="text-[11px] text-gray-400 mt-1">
              Détermine votre fuseau horaire et votre devise.
            </p>
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
            <p class="font-medium text-gray-900 text-sm">Email</p>
            <p class="text-xs text-gray-400">Recevez un email à chaque nouvelle réservation</p>
          </div>
          <div
            @click="form.notifications_email = !form.notifications_email"
            :class="['relative inline-flex h-6 w-11 rounded-full transition-colors cursor-pointer', form.notifications_email ? 'bg-primary-600' : 'bg-gray-200']"
          >
            <span :class="['absolute top-1 left-1 w-4 h-4 rounded-full bg-white shadow transition-transform', form.notifications_email ? 'translate-x-5' : 'translate-x-0']" />
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

      <!-- Errors that belong to no single field (e.g. a Pro-only feature) -->
      <p v-if="saveError" class="px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
        {{ saveError }}
      </p>

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
