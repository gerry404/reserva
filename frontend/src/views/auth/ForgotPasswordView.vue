<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { authApi } from '@/api'
import { Mail, CircleCheck } from 'lucide-vue-next'

const email    = ref('')
const sending  = ref(false)
const sent     = ref(false)
const error    = ref('')

async function submit() {
  error.value   = ''
  sending.value = true
  try {
    await authApi.forgotPassword(email.value)
    // The API answers the same way whether or not the address is registered,
    // and so does this screen: a different message here would leak who has an
    // account just as effectively.
    sent.value = true
  } catch (e) {
    error.value = e.message
  } finally {
    sending.value = false
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
        <template v-if="!sent">
          <h1 class="text-xl font-black text-gray-900 mb-1.5">Mot de passe oublié</h1>
          <p class="text-sm text-gray-500 mb-6">
            Indiquez votre email : nous vous enverrons un lien pour choisir un nouveau mot de passe.
          </p>

          <div v-if="error" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
            {{ error }}
          </div>

          <form class="space-y-4" @submit.prevent="submit">
            <div>
              <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
              <input
                id="email"
                v-model="email"
                type="email"
                autocomplete="email"
                required
                class="input-field"
                placeholder="vous@exemple.com"
              />
            </div>

            <button type="submit" class="btn-primary w-full py-3" :disabled="sending">
              <span v-if="sending" class="flex items-center justify-center gap-2">
                <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                Envoi…
              </span>
              <span v-else>Envoyer le lien</span>
            </button>
          </form>
        </template>

        <template v-else>
          <div class="text-center py-4">
            <div class="w-14 h-14 rounded-full bg-emerald-50 flex items-center justify-center mx-auto mb-4">
              <CircleCheck class="w-7 h-7 text-emerald-500" />
            </div>
            <h1 class="text-xl font-black text-gray-900 mb-2">Vérifiez votre boîte mail</h1>
            <p class="text-sm text-gray-500 leading-relaxed">
              Si un compte existe avec <strong class="text-gray-700">{{ email }}</strong>,
              un lien de réinitialisation vient d'y être envoyé. Il est valable une heure.
            </p>
            <p class="text-xs text-gray-400 mt-4 flex items-center justify-center gap-1.5">
              <Mail class="w-4 h-4" />
              Pensez à regarder dans les spams.
            </p>
          </div>
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
