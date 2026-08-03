<script setup>
import { ref, reactive, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Eye, EyeOff } from 'lucide-vue-next'
import BrandIcon from '@/components/ui/BrandIcon.vue'


const auth     = useAuthStore()
const showPass = ref(false)
const error    = ref('')
const googleLoading = ref(false)

const form = reactive({
  email:    '',
  password: '',
})

async function submit() {
  error.value = ''
  try {
    await auth.login(form)
  } catch (e) {
    error.value = e.message
  }
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
  error.value = ''
  googleLoading.value = true
  try {
    await auth.loginWithGoogle(response.access_token)
  } catch (e) {
    error.value = e.message || 'Erreur lors de la connexion avec Google.'
  } finally {
    googleLoading.value = false
  }
}

function loginWithGoogle() {
  if (googleClient) {
    googleClient.requestAccessToken()
  } else {
    error.value = 'Google Sign-In non disponible. Vérifiez votre connexion.'
  }
}

onMounted(() => {
  // Load Google GSI script dynamically
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

      <!-- Polygon pattern -->
      <svg class="absolute inset-0 w-full h-full opacity-[0.05]" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <pattern id="login-hex" width="60" height="60" patternUnits="userSpaceOnUse">
            <path d="M30 0L60 15L60 45L30 60L0 45L0 15Z" fill="none" stroke="white" stroke-width="0.5"/>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#login-hex)" />
      </svg>

      <div class="absolute top-1/3 left-1/2 -translate-x-1/2 w-[400px] h-[400px] bg-primary-600/15 rounded-full blur-[100px]" />

      <div class="relative flex flex-col justify-between h-full p-12">
        <RouterLink to="/" class="flex items-center gap-2.5">
          <img src="/logo.svg" alt="Nuvo" class="w-10 h-10" />
          <span class="font-display font-extrabold text-white text-xl tracking-tight">Nuvo</span>
        </RouterLink>

        <div>
          <h2 class="text-4xl font-extrabold text-white leading-tight mb-4 tracking-tight">
            Bon retour
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-400 to-violet-400">parmi nous.</span>
          </h2>
          <p class="text-gray-400 text-lg leading-relaxed max-w-sm">
            Votre tableau de bord vous attend. Vos réservations tournent en pilote automatique.
          </p>
        </div>

        <!--
          Ici s'affichaient « +500 commerces actifs » et « 98 % de satisfaction ».
          Aucun des deux n'était mesuré. Remplacés par ce que le produit fait
          réellement — vérifiable, et au moins aussi convaincant pour quelqu'un
          qui est déjà en train de se connecter.
        -->
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-white/5 border border-white/10 rounded-2xl p-5">
            <p class="text-3xl font-extrabold text-white mb-1">24h/24</p>
            <p class="text-sm text-gray-500">Vos clients réservent</p>
          </div>
          <div class="bg-white/5 border border-white/10 rounded-2xl p-5">
            <p class="text-3xl font-extrabold text-white mb-1">0</p>
            <p class="text-sm text-gray-500">Appel manqué</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Right panel -->
    <div class="flex-1 flex items-center justify-center p-6 sm:p-10 bg-gray-50">
      <div class="w-full max-w-[420px]">
        <RouterLink to="/" class="flex items-center gap-2 mb-10 lg:hidden">
          <img src="/logo.svg" alt="Nuvo" class="w-9 h-9" />
          <span class="font-display font-extrabold text-gray-900 text-lg">Nuvo</span>
        </RouterLink>

        <h1 class="text-3xl font-extrabold text-gray-900 mb-2 tracking-tight">Connexion</h1>
        <p class="text-gray-500 mb-8">
          Pas encore de compte ?
          <RouterLink to="/register" class="text-primary-600 font-semibold hover:text-primary-700 transition-colors">Créer un compte</RouterLink>
        </p>

        <Transition name="fade">
          <div v-if="error" class="mb-6 px-4 py-3 bg-red-50 border border-red-100 rounded-xl text-red-600 text-sm font-medium">
            {{ error }}
          </div>
        </Transition>

        <!-- Google Sign-In -->
        <button @click="loginWithGoogle" :disabled="googleLoading || auth.loading"
          class="w-full flex items-center justify-center gap-3 py-3 px-4 bg-clay-50 border border-gray-200 rounded-control text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 disabled:opacity-60 mb-6 shadow-sm">
          <span v-if="googleLoading" class="w-5 h-5 border-2 border-gray-300 border-t-gray-600 rounded-full animate-spin" />
          <template v-else>
            <BrandIcon name="google" class="w-5 h-5" />
            <span>Continuer avec Google</span>
          </template>
        </button>

        <div class="flex items-center gap-4 mb-6">
          <div class="flex-1 h-px bg-gray-200" />
          <span class="text-xs text-gray-400 font-medium">ou par email</span>
          <div class="flex-1 h-px bg-gray-200" />
        </div>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Adresse email</label>
            <input v-model="form.email" type="email" class="input-field" placeholder="vous@exemple.com" autocomplete="email" required />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Mot de passe</label>
            <div class="relative">
              <input v-model="form.password" :type="showPass ? 'text' : 'password'" class="input-field pr-12" placeholder="Votre mot de passe" autocomplete="current-password" required />
              <button type="button" @click="showPass = !showPass" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                <component :is="showPass ? EyeOff : Eye" class="w-5 h-5" />
              </button>
            </div>
          </div>

          <button type="submit"
            class="w-full py-3.5 rounded-control bg-gradient-to-r from-primary-600 to-violet-600 text-white font-bold text-sm hover:shadow-lg hover:shadow-primary-500/25 hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-60 disabled:hover:translate-y-0 disabled:hover:shadow-none flex items-center justify-center gap-2"
            :disabled="auth.loading || googleLoading">
            <span v-if="auth.loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            <span v-else>Se connecter</span>
          </button>
        </form>

        <p class="text-center text-xs text-gray-400 mt-8">
          En vous connectant, vous acceptez nos
          <RouterLink to="/terms" class="underline hover:text-gray-600 transition-colors">conditions d'utilisation</RouterLink>.
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
