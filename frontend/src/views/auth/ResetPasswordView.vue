<script setup>
import { computed, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { authApi } from '@/api'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'

const route  = useRoute()
const router = useRouter()

const form = reactive({
  token: route.query.token ?? '',
  email: route.query.email ?? '',
  password: '',
  password_confirmation: '',
})

const showPassword = ref(false)
const saving       = ref(false)
const error        = ref('')
const fieldErrors  = ref({})

// A link that lost its query string cannot work; say so instead of showing a
// form that is guaranteed to fail on submit.
const linkIsUsable = computed(() => !!form.token && !!form.email)

async function submit() {
  error.value       = ''
  fieldErrors.value = {}
  saving.value      = true
  try {
    await authApi.resetPassword(form)
    await router.push({ name: 'login', query: { reset: '1' } })
  } catch (e) {
    error.value       = e.message
    fieldErrors.value = e.fieldErrors
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-12">
    <div class="w-full max-w-md">
      <RouterLink to="/" class="flex items-center justify-center gap-2.5 mb-8">
        <img src="/logo.svg" alt="" class="w-9 h-9" />
        <span class="font-display font-extrabold text-gray-900 text-xl">Nuvo</span>
      </RouterLink>

      <div class="card p-8">
        <h1 class="text-xl font-black text-gray-900 mb-1.5">Nouveau mot de passe</h1>

        <template v-if="linkIsUsable">
          <p class="text-sm text-gray-500 mb-6">
            Choisissez un mot de passe pour <strong class="text-gray-700">{{ form.email }}</strong>.
          </p>

          <div v-if="error" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
            {{ error }}
          </div>

          <form class="space-y-4" @submit.prevent="submit">
            <div>
              <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">
                Mot de passe
              </label>
              <div class="relative">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="new-password"
                  required
                  class="input-field pr-11"
                  placeholder="8 caractères minimum"
                />
                <button
                  type="button"
                  class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-600"
                  :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                  @click="showPassword = !showPassword"
                >
                  <EyeSlashIcon v-if="showPassword" class="w-5 h-5" />
                  <EyeIcon v-else class="w-5 h-5" />
                </button>
              </div>
              <p v-if="fieldErrors.password" class="text-red-500 text-xs mt-1">
                {{ fieldErrors.password[0] }}
              </p>
              <p v-else class="text-xs text-gray-400 mt-1">
                Au moins 8 caractères, avec des lettres et des chiffres.
              </p>
            </div>

            <div>
              <label for="confirm" class="block text-sm font-semibold text-gray-700 mb-1.5">
                Confirmer le mot de passe
              </label>
              <input
                id="confirm"
                v-model="form.password_confirmation"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="new-password"
                required
                class="input-field"
              />
            </div>

            <button type="submit" class="btn-primary w-full py-3" :disabled="saving">
              <span v-if="saving" class="flex items-center justify-center gap-2">
                <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                Enregistrement…
              </span>
              <span v-else>Changer mon mot de passe</span>
            </button>
          </form>
        </template>

        <template v-else>
          <p class="text-sm text-gray-500 mt-2 leading-relaxed">
            Ce lien est incomplet ou a expiré. Demandez-en un nouveau — les liens
            de réinitialisation ne restent valables qu'une heure.
          </p>
          <RouterLink :to="{ name: 'forgot-password' }" class="btn-primary w-full py-3 mt-6 inline-block text-center">
            Demander un nouveau lien
          </RouterLink>
        </template>

        <p class="text-center text-sm text-gray-500 mt-6">
          <RouterLink :to="{ name: 'login' }" class="font-semibold text-primary-600 hover:underline">
            ← Retour à la connexion
          </RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>
