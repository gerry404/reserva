<script setup>
import { RouterLink } from 'vue-router'

const posts = [
  {
    title: 'Comment réduire les no-shows de 80% grâce aux rappels automatiques',
    excerpt: 'Les rendez-vous manqués coûtent cher. Découvrez comment les rappels WhatsApp automatiques ont transformé le quotidien de nos utilisateurs.',
    date: '22 mars 2026',
    category: 'Conseils',
    readTime: '4 min',
  },
  {
    title: '5 astuces pour remplir votre agenda en basse saison',
    excerpt: 'La saisonnalité affecte tous les commerces. Voici des stratégies testées par nos meilleurs utilisateurs pour maintenir un flux constant.',
    date: '15 mars 2026',
    category: 'Stratégie',
    readTime: '6 min',
  },
  {
    title: 'Réserva Pro : les nouvelles fonctionnalités de mars 2026',
    excerpt: 'Export CSV amélioré, statistiques de revenus, et personnalisation avancée de votre page de réservation.',
    date: '1 mars 2026',
    category: 'Produit',
    readTime: '3 min',
  },
  {
    title: 'Pourquoi chaque commerçant devrait avoir une page de réservation en ligne',
    excerpt: 'En 2026, ne pas proposer de réservation en ligne c\'est comme ne pas avoir de numéro de téléphone. Voici pourquoi.',
    date: '18 février 2026',
    category: 'Tendances',
    readTime: '5 min',
  },
  {
    title: 'Comment Marie a triplé sa clientèle en 3 mois avec Réserva',
    excerpt: 'Étude de cas : une coiffeuse à Douala partage son parcours, de 10 à 30 réservations par semaine.',
    date: '5 février 2026',
    category: 'Étude de cas',
    readTime: '7 min',
  },
  {
    title: 'Mobile Money et réservation : le duo gagnant',
    excerpt: 'Intégrer le paiement mobile à votre processus de réservation réduit les annulations et sécurise vos revenus.',
    date: '20 janvier 2026',
    category: 'Conseils',
    readTime: '4 min',
  },
]

const categoryColors = {
  'Conseils': 'bg-emerald-50 text-emerald-600',
  'Stratégie': 'bg-blue-50 text-blue-600',
  'Produit': 'bg-primary-50 text-primary-600',
  'Tendances': 'bg-amber-50 text-amber-600',
  'Étude de cas': 'bg-pink-50 text-pink-600',
}
</script>

<template>
  <div>
    <section class="py-20 px-4 sm:px-6 bg-gradient-to-b from-gray-50 to-white">
      <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 tracking-tight mb-6">Blog</h1>
        <p class="text-lg text-gray-500 leading-relaxed">Conseils, mises à jour produit et témoignages pour développer votre activité.</p>
      </div>
    </section>

    <section class="py-20 px-4 sm:px-6">
      <div class="max-w-4xl mx-auto">
        <div class="grid sm:grid-cols-2 gap-8">
          <article v-for="post in posts" :key="post.title"
            class="border border-gray-100 rounded-2xl p-6 hover:border-gray-200 hover:shadow-sm transition-all group cursor-pointer">
            <div class="flex items-center gap-3 mb-4">
              <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full', categoryColors[post.category] || 'bg-gray-50 text-gray-600']">{{ post.category }}</span>
              <span class="text-xs text-gray-400">{{ post.readTime }} de lecture</span>
            </div>
            <h2 class="font-bold text-gray-900 text-lg mb-3 group-hover:text-primary-600 transition-colors leading-snug">{{ post.title }}</h2>
            <p class="text-sm text-gray-500 leading-relaxed mb-4">{{ post.excerpt }}</p>
            <p class="text-xs text-gray-400">{{ post.date }}</p>
          </article>
        </div>
      </div>
    </section>
  </div>
</template>
