<script setup>
import { computed, reactive } from 'vue'

const CONTACT_EMAIL = 'contact@reserva.cm'

const form = reactive({ name: '', email: '', subject: '', message: '' })

/**
 * Opens the visitor's mail client with the message pre-written.
 *
 * This form used to flip a flag and say "message envoyé" while sending
 * absolutely nothing — every prospect who wrote in was silently dropped. A
 * mailto: hand-off is unglamorous, but it actually delivers, and it needs no
 * inbox infrastructure to stand behind it.
 */
const mailtoLink = computed(() => {
  const subject = `[Réserva] ${form.subject || 'Contact'}`
  const body = [
    `Nom : ${form.name}`,
    `Email : ${form.email}`,
    '',
    form.message,
  ].join('\n')

  return `mailto:${CONTACT_EMAIL}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
})

function submit() {
  window.location.href = mailtoLink.value
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
          <form @submit.prevent="submit" class="space-y-5">
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

            <p class="text-xs text-gray-400 text-center">
              Le message s'ouvrira dans votre application de messagerie. Vous pouvez
              aussi nous écrire directement à
              <a :href="`mailto:${CONTACT_EMAIL}`" class="text-primary-600 font-medium hover:underline">
                {{ CONTACT_EMAIL }}
              </a>.
            </p>
          </form>
        </div>
      </div>
    </section>
  </div>
</template>
