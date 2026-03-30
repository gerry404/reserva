<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <div>
    <section class="py-20 px-4 sm:px-6 bg-gradient-to-b from-gray-50 to-white">
      <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 tracking-tight mb-6">API Réserva</h1>
        <p class="text-lg text-gray-500 leading-relaxed">Intégrez la réservation dans vos propres outils grâce à notre API REST.</p>
        <div class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-gray-300 rounded-xl text-sm font-mono">
          <span class="text-emerald-400">GET</span> https://api.reserva.cm/v1/
        </div>
      </div>
    </section>

    <section class="py-20 px-4 sm:px-6">
      <div class="max-w-4xl mx-auto">
        <div class="grid md:grid-cols-2 gap-8 mb-16">
          <div v-for="feature in [
            { title: 'RESTful', desc: 'Endpoints clairs et prévisibles. JSON partout. Pagination, filtres et tri inclus.' },
            { title: 'Authentification', desc: 'Authentification par token Bearer via Laravel Sanctum. Sécurisé et simple.' },
            { title: 'Webhooks', desc: 'Recevez des notifications en temps réel pour chaque nouvelle réservation ou annulation.' },
            { title: 'Rate Limiting', desc: '60 requêtes par minute pour le plan Pro, 200 pour Business. Headers informatifs inclus.' },
          ]" :key="feature.title"
            class="border border-gray-100 rounded-2xl p-6">
            <h3 class="font-bold text-gray-900 mb-2">{{ feature.title }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed">{{ feature.desc }}</p>
          </div>
        </div>

        <h2 class="text-2xl font-extrabold text-gray-900 mb-6">Endpoints principaux</h2>
        <div class="space-y-3">
          <div v-for="ep in [
            { method: 'GET', path: '/v1/business', desc: 'Informations de votre commerce' },
            { method: 'GET', path: '/v1/services', desc: 'Liste de vos services' },
            { method: 'POST', path: '/v1/services', desc: 'Créer un service' },
            { method: 'GET', path: '/v1/bookings', desc: 'Liste des réservations' },
            { method: 'PATCH', path: '/v1/bookings/:id', desc: 'Modifier le statut d\'une réservation' },
            { method: 'GET', path: '/v1/slots/:date', desc: 'Créneaux disponibles pour une date' },
            { method: 'GET', path: '/v1/stats', desc: 'Statistiques de votre commerce' },
          ]" :key="ep.path"
            class="bg-gray-50 rounded-xl p-4 flex items-center gap-4">
            <span :class="['text-xs font-bold px-2.5 py-1 rounded-md font-mono', ep.method === 'GET' ? 'bg-emerald-100 text-emerald-700' : ep.method === 'POST' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700']">{{ ep.method }}</span>
            <code class="text-sm text-gray-700 font-mono flex-1">{{ ep.path }}</code>
            <span class="text-sm text-gray-400 hidden sm:block">{{ ep.desc }}</span>
          </div>
        </div>

        <div class="mt-16 bg-gray-900 rounded-2xl p-6 overflow-x-auto">
          <p class="text-xs text-gray-500 mb-3 font-mono">Exemple de requête</p>
          <pre class="text-sm text-gray-300 font-mono leading-relaxed"><span class="text-emerald-400">curl</span> -X GET https://api.reserva.cm/v1/bookings \
  -H <span class="text-amber-400">"Authorization: Bearer YOUR_TOKEN"</span> \
  -H <span class="text-amber-400">"Accept: application/json"</span></pre>
        </div>

        <div class="mt-12 bg-primary-50 border border-primary-100 rounded-2xl p-8 text-center">
          <h3 class="font-bold text-gray-900 text-lg mb-3">L'API est disponible sur les plans Pro et Business</h3>
          <p class="text-gray-500 text-sm mb-6">Créez votre compte et accédez à votre clé API depuis les paramètres.</p>
          <RouterLink to="/register" class="btn-primary">Commencer</RouterLink>
        </div>
      </div>
    </section>
  </div>
</template>
