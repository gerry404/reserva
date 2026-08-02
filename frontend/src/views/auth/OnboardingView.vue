<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { businessApi } from '@/api'
import { useAuthStore } from '@/stores/auth'
import { COUNTRIES } from '@/composables/usePhoneInput'
import { BuildingStorefrontIcon } from '@heroicons/vue/24/outline'

/**
 * Finishes signup for accounts created through Google, which arrive with no
 * business attached. Without this step every dashboard request comes back 409.
 */
const auth   = useAuthStore()
const router = useRouter()

const form = reactive({
  name: '',
  category: '',
  city: '',
  country: 'CM',
  phone: '',
})

const saving      = ref(false)
const error       = ref('')
const fieldErrors = ref({})

const categories = [
  'Salon de coiffure', 'Barbier', 'Institut de beauté', 'Spa & massage',
  'Clinique / cabinet', 'Photographe', 'Coach sportif', 'Garage automobile',
  'Restaurant', 'Autre',
]

async function submit() {
  error.value       = ''
  fieldErrors.value = {}
  saving.value      = true
  try {
    const business = await businessApi.setup(form)
    auth.setBusiness(business)
    await router.push({ name: 'dashboard' })
  } catch (e) {
    error.value       = e.message
    fieldErrors.value = e.fieldErrors
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 px-4 py-12">
    <div class="max-w-lg mx-auto">
      <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-primary-50 flex items-center justify-center mx-auto mb-4">
          <BuildingStorefrontIcon class="w-7 h-7 text-primary-600" />
        </div>
        <h1 class="text-2xl font-black text-gray-900">Bienvenue{{ auth.user?.name ? `, ${auth.user.name.split(' ')[0]}` : '' }} !</h1>
        <p class="text-sm text-gray-500 mt-1.5">
          Une dernière étape : parlez-nous de votre commerce.
        </p>
      </div>

      <div class="card p-6 sm:p-8">
        <div v-if="error" class="mb-5 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
          {{ error }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
          <div>
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5">
              Nom du commerce *
            </label>
            <input id="name" v-model="form.name" type="text" required class="input-field" placeholder="ex : Salon Élégance" />
            <p v-if="fieldErrors.name" class="text-red-500 text-xs mt-1">{{ fieldErrors.name[0] }}</p>
          </div>

          <div>
            <label for="category" class="block text-sm font-semibold text-gray-700 mb-1.5">
              Activité *
            </label>
            <select id="category" v-model="form.category" required class="input-field">
              <option value="" disabled>Choisissez votre activité</option>
              <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
            <p v-if="fieldErrors.category" class="text-red-500 text-xs mt-1">{{ fieldErrors.category[0] }}</p>
          </div>

          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label for="country" class="block text-sm font-semibold text-gray-700 mb-1.5">Pays *</label>
              <select id="country" v-model="form.country" required class="input-field">
                <option v-for="c in COUNTRIES" :key="c.code" :value="c.code">
                  {{ c.flag }} {{ c.name }}
                </option>
              </select>
              <!-- Country sets the timezone and currency server-side, which is
                   why it is asked here rather than buried in settings. -->
              <p class="text-[11px] text-gray-400 mt-1">Définit votre fuseau horaire et votre devise.</p>
            </div>

            <div>
              <label for="city" class="block text-sm font-semibold text-gray-700 mb-1.5">Ville *</label>
              <input id="city" v-model="form.city" type="text" required class="input-field" placeholder="ex : Douala" />
              <p v-if="fieldErrors.city" class="text-red-500 text-xs mt-1">{{ fieldErrors.city[0] }}</p>
            </div>
          </div>

          <div>
            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1.5">
              Téléphone / WhatsApp *
            </label>
            <input
              id="phone"
              v-model="form.phone"
              type="tel"
              required
              class="input-field"
              placeholder="+237 6XX XXX XXX"
            />
            <p v-if="fieldErrors.phone" class="text-red-500 text-xs mt-1">{{ fieldErrors.phone[0] }}</p>
            <p v-else class="text-[11px] text-gray-400 mt-1">
              C'est là que vous recevrez vos réservations.
            </p>
          </div>

          <button type="submit" class="btn-primary w-full py-3" :disabled="saving">
            <span v-if="saving" class="flex items-center justify-center gap-2">
              <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              Création…
            </span>
            <span v-else>Créer mon espace</span>
          </button>
        </form>
      </div>

      <p class="text-center text-xs text-gray-400 mt-6">
        Vous pourrez modifier tout cela à tout moment dans vos paramètres.
      </p>
    </div>
  </div>
</template>
