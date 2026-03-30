<script setup>
import { ref, reactive } from 'vue'

const sent = ref(false)
const form = reactive({ name: '', email: '', subject: '', message: '' })

function submit() {
  // In production, this would call an API
  sent.value = true
}
</script>

<template>
  <div>
    <section class="py-20 px-4 sm:px-6 bg-gradient-to-b from-gray-50 to-white">
      <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 tracking-tight mb-6">Contactez-nous</h1>
        <p class="text-lg text-gray-500 leading-relaxed">Une question, une suggestion, un partenariat ? Nous vous répondons sous 24h.</p>
      </div>
    </section>

    <section class="py-20 px-4 sm:px-6">
      <div class="max-w-4xl mx-auto grid md:grid-cols-5 gap-12">
        <!-- Contact info -->
        <div class="md:col-span-2 space-y-8">
          <div>
            <h3 class="font-bold text-gray-900 mb-2">Email</h3>
            <a href="mailto:contact@reserva.cm" class="text-primary-600 font-medium hover:underline">contact@reserva.cm</a>
          </div>
          <div>
            <h3 class="font-bold text-gray-900 mb-2">WhatsApp</h3>
            <a href="https://wa.me/237600000000" class="text-primary-600 font-medium hover:underline">+237 6XX XXX XXX</a>
          </div>
          <div>
            <h3 class="font-bold text-gray-900 mb-2">Adresse</h3>
            <p class="text-gray-500 text-sm leading-relaxed">Douala, Cameroun</p>
          </div>
          <div>
            <h3 class="font-bold text-gray-900 mb-2">Horaires</h3>
            <p class="text-gray-500 text-sm leading-relaxed">Lundi - Vendredi<br/>9h00 - 18h00 (GMT+1)</p>
          </div>
        </div>

        <!-- Form -->
        <div class="md:col-span-3">
          <div v-if="sent" class="bg-emerald-50 border border-emerald-100 rounded-2xl p-8 text-center">
            <div class="w-14 h-14 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
              <svg class="w-7 h-7 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="font-bold text-gray-900 text-lg mb-2">Message envoyé</h3>
            <p class="text-gray-500 text-sm">Nous vous répondrons dans les plus brefs délais.</p>
          </div>

          <form v-else @submit.prevent="submit" class="space-y-5">
            <div class="grid sm:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nom</label>
                <input v-model="form.name" type="text" class="input-field" placeholder="Votre nom" required />
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                <input v-model="form.email" type="email" class="input-field" placeholder="vous@exemple.com" required />
              </div>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Sujet</label>
              <select v-model="form.subject" class="input-field" required>
                <option value="">Sélectionnez un sujet</option>
                <option>Question générale</option>
                <option>Support technique</option>
                <option>Partenariat</option>
                <option>Presse</option>
                <option>Autre</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Message</label>
              <textarea v-model="form.message" class="input-field resize-none" rows="5" placeholder="Comment pouvons-nous vous aider ?" required />
            </div>
            <button type="submit" class="btn-primary w-full py-3">Envoyer le message</button>
          </form>
        </div>
      </div>
    </section>
  </div>
</template>
