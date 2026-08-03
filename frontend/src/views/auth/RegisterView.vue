<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  ArrowLeft,
  ArrowRight,
  Check,
  ChevronDown,
  Eye,
  EyeOff,
  Star,
  X,
} from 'lucide-vue-next'
import { usePhoneInput } from '@/composables/usePhoneInput'
import BrandIcon from '@/components/ui/BrandIcon.vue'


const auth     = useAuthStore()
const showPass = ref(false)
const showConfirmPass = ref(false)
const errors   = ref({})
const step     = ref(1)
const googleLoading = ref(false)
const googleError = ref('')

// Phone input
const {
  countries,
  selectedCountry,
  phoneNumber,
  dropdownOpen,
  searchQuery,
  filteredCountries,
  fullPhone,
  selectCountry,
} = usePhoneInput()

const phoneDropdownRef = ref(null)

function onClickOutside(e) {
  if (phoneDropdownRef.value && !phoneDropdownRef.value.contains(e.target)) {
    dropdownOpen.value = false
  }
}

onMounted(() => document.addEventListener('click', onClickOutside, true))
onUnmounted(() => document.removeEventListener('click', onClickOutside, true))

const form = reactive({
  name:               '',
  email:              '',
  password:           '',
  password_confirmation: '',
  business_name:      '',
  business_category:  '',
  business_city:      '',
})

// ── Password strength ──
const passwordChecks = computed(() => [
  { label: '8 caractères minimum', pass: form.password.length >= 8 },
  { label: 'Une majuscule', pass: /[A-Z]/.test(form.password) },
  { label: 'Une minuscule', pass: /[a-z]/.test(form.password) },
  { label: 'Un chiffre', pass: /[0-9]/.test(form.password) },
  { label: 'Un caractère spécial', pass: /[^A-Za-z0-9]/.test(form.password) },
])

const passedChecks = computed(() => passwordChecks.value.filter(c => c.pass).length)

const strengthLevel = computed(() => {
  if (passedChecks.value <= 1) return { label: 'Très faible', color: 'bg-red-500', text: 'text-red-600', width: '20%' }
  if (passedChecks.value === 2) return { label: 'Faible', color: 'bg-orange-500', text: 'text-orange-600', width: '40%' }
  if (passedChecks.value === 3) return { label: 'Moyen', color: 'bg-amber-500', text: 'text-amber-600', width: '60%' }
  if (passedChecks.value === 4) return { label: 'Fort', color: 'bg-emerald-500', text: 'text-emerald-600', width: '80%' }
  return { label: 'Très fort', color: 'bg-emerald-500', text: 'text-emerald-600', width: '100%' }
})

const passwordsMatch = computed(() =>
  form.password_confirmation.length > 0 && form.password === form.password_confirmation
)
const passwordsMismatch = computed(() =>
  form.password_confirmation.length > 0 && form.password !== form.password_confirmation
)

const categories = [
  'Salon de coiffure', 'Salon de beauté', 'Spa & Massage',
  'Cabinet médical', 'Dentiste', 'Vétérinaire',
  'Restaurant', 'Hôtel', 'Studio photo',
  'Salle de sport', 'Coach sportif', 'Autre',
]

const benefits = [
  'Page de réservation en ligne en 5 minutes',
  'Notifications WhatsApp en temps réel',
  'Tableau de bord avec statistiques',
  'Essai gratuit de 14 jours, sans carte',
]

async function submit() {
  errors.value = {}
  try {
    await auth.register({ ...form, phone: fullPhone.value })
  } catch (e) {
    const errs = e.fieldErrors
    errors.value = errs
    const step1Fields = ['name', 'email', 'phone', 'password']
    if (step1Fields.some(f => errs[f])) step.value = 1
  }
}

function nextStep() {
  if (!form.name || !form.email || !phoneNumber.value || !form.password) return
  if (passedChecks.value < 3) return
  if (form.password !== form.password_confirmation) return
  step.value = 2
}

// Google Sign-In
let googleClient = null

function initGoogle() {
  if (!window.google?.accounts?.oauth2) return
  googleClient = window.google.accounts.oauth2.initTokenClient({
    client_id: import.meta.env.VITE_GOOGLE_CLIENT_ID,
    scope: 'email profile',
    callback: handleGoogleResponse,
  })
}

async function handleGoogleResponse(response) {
  if (!response.access_token) return
  googleError.value = ''
  googleLoading.value = true
  try {
    await auth.loginWithGoogle(response.access_token)
  } catch (e) {
    googleError.value = e.message || 'Erreur lors de la connexion avec Google.'
  } finally {
    googleLoading.value = false
  }
}

function registerWithGoogle() {
  if (googleClient) {
    googleClient.requestAccessToken()
  } else {
    googleError.value = 'Google Sign-In non disponible.'
  }
}

onMounted(() => {
  if (!document.getElementById('google-gsi')) {
    const script = document.createElement('script')
    script.id = 'google-gsi'
    script.src = 'https://accounts.google.com/gsi/client'
    script.async = true
    script.defer = true
    script.onload = initGoogle
    document.head.appendChild(script)
  } else {
    initGoogle()
  }
})
</script>

<template>
  <div class="min-h-screen flex">
    <!-- Left panel -->
    <div class="hidden lg:flex flex-col w-[520px] shrink-0 relative overflow-hidden">
      <!-- Vert profond plutôt que noir : la couleur du produit tient le
           panneau, elle ne fait pas que l'éclairer. -->
      <div class="absolute inset-0 bg-gradient-to-br from-primary-800 via-primary-950 to-primary-900" />

      <svg class="absolute inset-0 w-full h-full opacity-[0.05]" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <pattern id="reg-hex" width="60" height="60" patternUnits="userSpaceOnUse">
            <path d="M30 0L60 15L60 45L30 60L0 45L0 15Z" fill="none" stroke="white" stroke-width="0.5"/>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#reg-hex)" />
      </svg>

      <div class="absolute bottom-1/3 left-1/2 -translate-x-1/2 w-[400px] h-[400px] bg-violet-600/15 rounded-full blur-[100px]" />
      <svg class="absolute top-16 right-12 w-20 h-20 text-white/[0.04] animate-spin" style="animation-duration: 30s" viewBox="0 0 100 100"><polygon points="50,5 95,27.5 95,72.5 50,95 5,72.5 5,27.5" fill="currentColor"/></svg>

      <div class="relative flex flex-col justify-between h-full p-12">
        <RouterLink to="/" class="flex items-center gap-2.5">
          <img src="/logo.svg" alt="Nuvo" class="w-10 h-10" />
          <span class="font-display font-extrabold text-white text-xl tracking-tight">Nuvo</span>
        </RouterLink>

        <div>
          <h2 class="text-4xl font-extrabold text-white leading-tight mb-6 tracking-tight">
            Lancez votre page de
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-400 to-violet-400">réservation.</span>
          </h2>
          <div class="space-y-4">
            <div v-for="(item, i) in benefits" :key="i" class="flex items-center gap-3">
              <div class="w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center shrink-0">
                <Check class="w-3.5 h-3.5 text-emerald-400" />
              </div>
              <span class="text-gray-300 text-sm">{{ item }}</span>
            </div>
          </div>
        </div>

        <div class="bg-white/5 border border-white/10 rounded-2xl p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="flex -space-x-2">
              <div v-for="(a, i) in [
                { initials: 'MN', color: 'from-pink-500 to-rose-500' },
                { initials: 'JM', color: 'from-blue-500 to-cyan-500' },
                { initials: 'AD', color: 'from-primary-500 to-primary-700' },
                { initials: 'SE', color: 'from-emerald-500 to-teal-500' },
              ]" :key="i"
                :class="['w-8 h-8 rounded-full bg-gradient-to-br flex items-center justify-center text-white text-[10px] font-bold border-2 border-primary-950', a.color]">
                {{ a.initials }}
              </div>
            </div>
            <div class="flex items-center gap-0.5">
              <Star v-for="i in 5" :key="i" class="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
            </div>
          </div>
          <p class="text-white/70 text-sm leading-relaxed">
            "J'ai commence avec le plan gratuit. En deux semaines, je suis passee au Pro tellement ca marchait."
          </p>
          <p class="text-gray-500 text-xs mt-3">Aicha D. - Estheticienne, Abidjan</p>
        </div>
      </div>
    </div>

    <!-- Right panel -->
    <div class="flex-1 flex items-center justify-center p-6 sm:p-10 bg-gray-50">
      <div class="w-full max-w-[440px]">
        <RouterLink to="/" class="flex items-center gap-2 mb-10 lg:hidden">
          <img src="/logo.svg" alt="Nuvo" class="w-9 h-9" />
          <span class="font-display font-extrabold text-gray-900 text-lg">Nuvo</span>
        </RouterLink>

        <!-- Step progress -->
        <div class="flex items-center gap-3 mb-8">
          <div class="flex items-center gap-2 flex-1">
            <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300', step >= 1 ? 'bg-gradient-to-br from-primary-600 to-violet-600 text-white shadow-md shadow-primary-500/20' : 'bg-gray-100 text-gray-400']">
              <Check v-if="step > 1" class="w-4 h-4" />
              <span v-else>1</span>
            </div>
            <span :class="['text-xs font-semibold transition-colors', step >= 1 ? 'text-gray-900' : 'text-gray-400']">Votre compte</span>
          </div>
          <div :class="['flex-1 h-0.5 rounded transition-colors duration-300', step >= 2 ? 'bg-primary-500' : 'bg-gray-200']" />
          <div class="flex items-center gap-2 flex-1 justify-end">
            <span :class="['text-xs font-semibold transition-colors', step >= 2 ? 'text-gray-900' : 'text-gray-400']">Votre commerce</span>
            <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300', step >= 2 ? 'bg-gradient-to-br from-primary-600 to-violet-600 text-white shadow-md shadow-primary-500/20' : 'bg-gray-100 text-gray-400']">2</div>
          </div>
        </div>

        <h1 class="text-3xl font-extrabold text-gray-900 mb-2 tracking-tight">
          {{ step === 1 ? 'Créer un compte' : 'Votre commerce' }}
        </h1>
        <p class="text-gray-500 mb-6">
          Déjà un compte ?
          <RouterLink to="/login" class="text-primary-600 font-semibold hover:text-primary-700 transition-colors">Se connecter</RouterLink>
        </p>

        <!-- Google error -->
        <Transition name="fade">
          <div v-if="googleError" class="mb-4 px-4 py-3 bg-red-50 border border-red-100 rounded-xl text-red-600 text-sm font-medium">
            {{ googleError }}
          </div>
        </Transition>

        <!-- Google Sign-In (step 1 only) -->
        <div v-if="step === 1" class="mb-6">
          <button @click="registerWithGoogle" :disabled="googleLoading || auth.loading"
            class="w-full flex items-center justify-center gap-3 py-3 px-4 bg-clay-50 border border-gray-200 rounded-control text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 disabled:opacity-60 shadow-sm">
            <span v-if="googleLoading" class="w-5 h-5 border-2 border-gray-300 border-t-gray-600 rounded-full animate-spin" />
            <template v-else>
              <BrandIcon name="google" class="w-5 h-5" />
              <span>S'inscrire avec Google</span>
            </template>
          </button>

          <div class="flex items-center gap-4 my-6">
            <div class="flex-1 h-px bg-gray-200" />
            <span class="text-xs text-gray-400 font-medium">ou par email</span>
            <div class="flex-1 h-px bg-gray-200" />
          </div>
        </div>

        <form @submit.prevent="step === 1 ? nextStep() : submit()" class="space-y-4">
          <!-- Step 1 -->
          <Transition name="step" mode="out-in">
            <div v-if="step === 1" key="s1" class="space-y-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nom complet</label>
                <input v-model="form.name" type="text" class="input-field" placeholder="Marie Nguema" autocomplete="name" required />
                <p v-if="errors.name" class="text-red-500 text-xs mt-1.5">{{ errors.name[0] }}</p>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Adresse email</label>
                <input v-model="form.email" type="email" class="input-field" placeholder="vous@exemple.com" autocomplete="email" required />
                <p v-if="errors.email" class="text-red-500 text-xs mt-1.5">{{ errors.email[0] }}</p>
              </div>

              <!-- International phone input -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Téléphone (WhatsApp)</label>
                <div class="flex">
                  <!-- Country selector -->
                  <div ref="phoneDropdownRef" class="relative">
                    <button type="button" @click.stop="dropdownOpen = !dropdownOpen"
                      class="flex items-center gap-1.5 h-full px-3 bg-clay-50 border border-gray-200 border-r-0 rounded-l-xl hover:bg-gray-50 transition-colors text-sm min-w-[100px]">
                      <span class="text-lg leading-none">{{ selectedCountry.flag }}</span>
                      <span class="text-gray-700 font-medium">{{ selectedCountry.dial }}</span>
                      <ChevronDown class="w-3.5 h-3.5 text-gray-400 shrink-0 ml-auto" :class="{ 'rotate-180': dropdownOpen }" />
                    </button>

                    <!-- Dropdown -->
                    <Transition name="dropdown">
                      <div v-if="dropdownOpen" class="absolute top-full left-0 mt-1 w-72 bg-clay-50 border border-gray-200 rounded-xl shadow-xl shadow-black/10 z-50 overflow-hidden">
                        <!-- Search -->
                        <div class="p-2 border-b border-gray-100">
                          <input v-model="searchQuery" type="text" class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-100 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-300" placeholder="Rechercher un pays..." />
                        </div>
                        <!-- List -->
                        <div class="max-h-52 overflow-y-auto">
                          <button v-for="c in filteredCountries" :key="c.code" type="button"
                            @click="selectCountry(c)"
                            :class="['w-full flex items-center gap-3 px-3 py-2.5 text-sm hover:bg-gray-50 transition-colors text-left', selectedCountry.code === c.code ? 'bg-primary-50' : '']">
                            <span class="text-lg leading-none">{{ c.flag }}</span>
                            <span class="flex-1 text-gray-700">{{ c.name }}</span>
                            <span class="text-gray-400 text-xs font-mono">{{ c.dial }}</span>
                            <Check v-if="selectedCountry.code === c.code" class="w-4 h-4 text-primary-600 shrink-0" />
                          </button>
                          <p v-if="filteredCountries.length === 0" class="px-3 py-4 text-sm text-gray-400 text-center">Aucun résultat</p>
                        </div>
                      </div>
                    </Transition>
                  </div>

                  <!-- Phone number -->
                  <input v-model="phoneNumber" type="tel" class="input-field rounded-l-none flex-1" placeholder="6XX XXX XXX" autocomplete="tel" required />
                </div>
                <p v-if="errors.phone" class="text-red-500 text-xs mt-1.5">{{ errors.phone[0] }}</p>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Mot de passe</label>
                <div class="relative">
                  <input v-model="form.password" :type="showPass ? 'text' : 'password'" class="input-field pr-12" placeholder="8 caractères minimum" autocomplete="new-password" required minlength="8" />
                  <button type="button" @click="showPass = !showPass" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                    <component :is="showPass ? EyeOff : Eye" class="w-5 h-5" />
                  </button>
                </div>
                <p v-if="errors.password" class="text-red-500 text-xs mt-1.5">{{ errors.password[0] }}</p>

                <!-- Strength indicator -->
                <div v-if="form.password.length > 0" class="mt-3 space-y-2.5">
                  <div class="flex items-center gap-3">
                    <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                      <div :class="['h-full rounded-full transition-all duration-500', strengthLevel.color]" :style="{ width: strengthLevel.width }" />
                    </div>
                    <span :class="['text-xs font-semibold whitespace-nowrap', strengthLevel.text]">{{ strengthLevel.label }}</span>
                  </div>
                  <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                    <div v-for="check in passwordChecks" :key="check.label" class="flex items-center gap-1.5">
                      <Check v-if="check.pass" class="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                      <div v-else class="w-3.5 h-3.5 rounded-full border-2 border-gray-200 shrink-0" />
                      <span :class="['text-[11px]', check.pass ? 'text-emerald-600 font-medium' : 'text-gray-400']">{{ check.label }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Confirmer le mot de passe</label>
                <div class="relative">
                  <input v-model="form.password_confirmation" :type="showConfirmPass ? 'text' : 'password'" :class="['input-field pr-12', passwordsMatch ? 'ring-2 ring-emerald-500/30 border-emerald-300' : '', passwordsMismatch ? 'ring-2 ring-red-500/30 border-red-300' : '']" placeholder="Répétez le mot de passe" autocomplete="new-password" required />
                  <button type="button" @click="showConfirmPass = !showConfirmPass" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                    <component :is="showConfirmPass ? EyeOff : Eye" class="w-5 h-5" />
                  </button>
                </div>
                <div v-if="passwordsMatch" class="flex items-center gap-1.5 mt-1.5">
                  <Check class="w-3.5 h-3.5 text-emerald-500" />
                  <span class="text-xs text-emerald-600 font-medium">Les mots de passe correspondent</span>
                </div>
                <div v-else-if="passwordsMismatch" class="flex items-center gap-1.5 mt-1.5">
                  <X class="w-3.5 h-3.5 text-red-500" />
                  <span class="text-xs text-red-500 font-medium">Les mots de passe ne correspondent pas</span>
                </div>
              </div>
            </div>

            <!-- Step 2 -->
            <div v-else key="s2" class="space-y-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nom de votre commerce</label>
                <input v-model="form.business_name" type="text" class="input-field" placeholder="ex: Salon Élégance" required />
                <p v-if="errors.business_name" class="text-red-500 text-xs mt-1.5">{{ errors.business_name[0] }}</p>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Catégorie</label>
                <select v-model="form.business_category" class="input-field" required>
                  <option value="">Sélectionnez une catégorie</option>
                  <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                </select>
                <p v-if="errors.business_category" class="text-red-500 text-xs mt-1.5">{{ errors.business_category[0] }}</p>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Ville</label>
                <input v-model="form.business_city" type="text" class="input-field" placeholder="ex: Douala" required />
                <p v-if="errors.business_city" class="text-red-500 text-xs mt-1.5">{{ errors.business_city[0] }}</p>
              </div>

              <button type="button" @click="step = 1" class="w-full py-2.5 text-sm font-semibold text-gray-500 hover:text-gray-700 transition-colors flex items-center justify-center gap-2">
                <ArrowLeft class="w-4 h-4" />
                Retour
              </button>
            </div>
          </Transition>

          <button type="submit"
            class="w-full py-3.5 rounded-control bg-gradient-to-r from-primary-600 to-violet-600 text-white font-bold text-sm hover:shadow-lg hover:shadow-primary-500/25 hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-60 disabled:hover:translate-y-0 disabled:hover:shadow-none flex items-center justify-center gap-2"
            :disabled="auth.loading || googleLoading">
            <span v-if="auth.loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            <template v-else>
              <span>{{ step === 1 ? 'Continuer' : 'Créer mon compte' }}</span>
              <ArrowRight v-if="step === 1" class="w-4 h-4" />
            </template>
          </button>
        </form>

        <p class="text-center text-xs text-gray-400 mt-8">
          En créant un compte, vous acceptez nos
          <RouterLink to="/terms" class="underline hover:text-gray-600 transition-colors">conditions d'utilisation</RouterLink>
          et notre
          <RouterLink to="/privacy" class="underline hover:text-gray-600 transition-colors">politique de confidentialité</RouterLink>.
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.step-enter-active { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.step-leave-active { transition: all 0.15s ease-in; }
.step-enter-from   { opacity: 0; transform: translateX(16px); }
.step-leave-to     { opacity: 0; transform: translateX(-8px); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }

.dropdown-enter-active { transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1); }
.dropdown-leave-active { transition: all 0.15s ease-in; }
.dropdown-enter-from   { opacity: 0; transform: translateY(-4px) scale(0.98); }
.dropdown-leave-to     { opacity: 0; transform: translateY(-4px) scale(0.98); }
</style>
