<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { Calendar, ChevronDown, CreditCard, Settings, Zap } from 'lucide-vue-next'

const openFaq = ref(null)

const faqs = [
  {
    q: 'Comment créer ma page de réservation ?',
    a: 'Inscrivez-vous gratuitement, renseignez le nom de votre commerce, ajoutez vos services avec leurs durées et prix, puis définissez vos horaires d\'ouverture. Votre page est immédiatement disponible via un lien unique.',
  },
  {
    q: 'Comment mes clients réservent-ils ?',
    a: 'Partagez votre lien de réservation (par WhatsApp, SMS, réseaux sociaux ou carte de visite). Vos clients choisissent un service, une date, un créneau horaire, puis confirment avec leur nom et numéro de téléphone.',
  },
  {
    q: 'Comment suis-je notifié d\'une nouvelle réservation ?',
    a: 'Vous recevez une notification WhatsApp instantanée pour chaque nouvelle réservation. Avec le plan Pro, vos clients reçoivent aussi des rappels automatiques pour réduire les no-shows.',
  },
  {
    q: 'Puis-je personnaliser ma page ?',
    a: 'Oui. Vous pouvez choisir votre couleur d\'accent, ajouter une description, votre logo, et configurer vos jours et horaires d\'ouverture. Avec le plan Pro, le branding Nuvo est supprimé.',
  },
  {
    q: 'Comment fonctionne le plan gratuit ?',
    a: 'Le plan Découverte vous permet de recevoir jusqu\'à 15 réservations par mois avec 3 services maximum. Idéal pour tester la plateforme. Passez au plan Pro quand vous êtes prêt.',
  },
  {
    q: 'Comment annuler ou modifier une réservation ?',
    a: 'Depuis votre tableau de bord, accédez à la section Réservations. Vous pouvez confirmer, annuler ou reprogrammer chaque réservation en un clic.',
  },
  {
    q: 'Quels moyens de paiement acceptez-vous ?',
    a: 'Nous acceptons MTN Mobile Money, Orange Money, Wave, et les cartes bancaires (Visa, Mastercard) pour les abonnements.',
  },
  {
    q: 'Puis-je exporter mes données ?',
    a: 'Avec le plan Pro, vous pouvez exporter toutes vos réservations au format CSV pour les utiliser dans Excel ou tout autre outil.',
  },
]

// `icon` porte le composant lui-même, plus un tracé SVG : le dessin appartient
// à la bibliothèque, pas aux données de la page.
const categories = [
  { name: 'Premiers pas', icon: Zap,        count: 5 },
  { name: 'Réservations', icon: Calendar,   count: 8 },
  { name: 'Facturation',  icon: CreditCard, count: 4 },
  { name: 'Paramètres',   icon: Settings,   count: 6 },
]

function toggle(i) { openFaq.value = openFaq.value === i ? null : i }
</script>

<template>
  <div>
    <section class="py-20 px-4 sm:px-6 bg-gradient-to-b from-gray-50 to-white">
      <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 tracking-tight mb-6">Centre d'aide</h1>
        <p class="text-lg text-gray-500 leading-relaxed mb-8">Trouvez rapidement des réponses à vos questions.</p>
      </div>
    </section>

    <!-- Categories -->
    <section class="py-12 px-4 sm:px-6">
      <div class="max-w-4xl mx-auto grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="cat in categories" :key="cat.name"
          class="border border-gray-100 rounded-2xl p-5 hover:border-gray-200 hover:shadow-sm transition-all cursor-pointer group">
          <div class="w-10 h-10 rounded-xl bg-primary-50 flex items-center justify-center mb-3 group-hover:bg-primary-100 transition-colors">
            <component :is="cat.icon" class="w-5 h-5 text-primary-600" />
          </div>
          <h3 class="font-bold text-gray-900 text-sm mb-1">{{ cat.name }}</h3>
          <p class="text-xs text-gray-400">{{ cat.count }} articles</p>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="py-16 px-4 sm:px-6">
      <div class="max-w-3xl mx-auto">
        <h2 class="text-2xl font-extrabold text-gray-900 mb-8">Questions fréquentes</h2>
        <div class="space-y-3">
          <div v-for="(faq, i) in faqs" :key="i"
            class="border border-gray-100 rounded-xl overflow-hidden transition-all"
            :class="{ 'border-gray-200 shadow-sm': openFaq === i }">
            <button @click="toggle(i)" class="w-full flex items-center justify-between p-5 text-left">
              <span class="font-semibold text-gray-900 text-sm pr-4">{{ faq.q }}</span>
              <ChevronDown :class="['w-5 h-5 text-gray-400 shrink-0 transition-transform duration-200', openFaq === i ? 'rotate-180' : '']" />
            </button>
            <div v-if="openFaq === i" class="px-5 pb-5">
              <p class="text-sm text-gray-500 leading-relaxed">{{ faq.a }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact CTA -->
    <section class="py-16 px-4 sm:px-6 bg-gray-50">
      <div class="max-w-2xl mx-auto text-center">
        <h2 class="text-xl font-extrabold text-gray-900 mb-3">Vous ne trouvez pas votre réponse ?</h2>
        <p class="text-gray-500 text-sm mb-6">Notre équipe support est disponible du lundi au vendredi, 9h-18h.</p>
        <RouterLink to="/contact" class="btn-primary">Nous contacter</RouterLink>
      </div>
    </section>
  </div>
</template>
