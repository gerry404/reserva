<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <div>
    <!-- Hero -->
    <section class="py-20 px-4 sm:px-6 bg-gradient-to-b from-gray-50 to-white">
      <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 tracking-tight mb-6">À propos de Réserva</h1>
        <p class="text-lg text-gray-500 leading-relaxed">Nous construisons la plateforme de réservation la plus simple et la plus accessible pour les commerçants indépendants.</p>
      </div>
    </section>

    <!-- Story -->
    <section class="py-20 px-4 sm:px-6">
      <div class="max-w-3xl mx-auto">
        <h2 class="text-2xl font-extrabold text-gray-900 mb-6">Notre histoire</h2>
        <div class="prose prose-gray max-w-none space-y-5 text-gray-600 leading-relaxed">
          <p>Réserva est née d'un constat simple : des millions de commerçants perdent des clients chaque jour parce qu'ils ne peuvent pas répondre au téléphone pendant qu'ils travaillent.</p>
          <p>Un coiffeur en plein service, un médecin en consultation, un restaurateur en plein rush. Tous ces professionnels manquent des appels. Chaque appel manqué est un client perdu.</p>
          <p>Nous avons créé Réserva pour résoudre ce problème. Une page de réservation en ligne que n'importe qui peut créer en 5 minutes, sans aucune compétence technique. Le client réserve, le commerçant reçoit une notification WhatsApp. Simple.</p>
        </div>

        <div class="grid sm:grid-cols-3 gap-6 mt-16">
          <div class="text-center p-6 bg-gray-50 rounded-2xl">
            <p class="text-3xl font-extrabold text-primary-600 mb-2">2024</p>
            <p class="text-sm text-gray-500">Année de lancement</p>
          </div>
          <div class="text-center p-6 bg-gray-50 rounded-2xl">
            <p class="text-3xl font-extrabold text-primary-600 mb-2">+500</p>
            <p class="text-sm text-gray-500">Commerces actifs</p>
          </div>
          <div class="text-center p-6 bg-gray-50 rounded-2xl">
            <p class="text-3xl font-extrabold text-primary-600 mb-2">+50k</p>
            <p class="text-sm text-gray-500">Réservations traitées</p>
          </div>
        </div>

        <h2 class="text-2xl font-extrabold text-gray-900 mb-6 mt-20">Nos valeurs</h2>
        <div class="grid sm:grid-cols-2 gap-6">
          <div v-for="v in [
            { title: 'Simplicité', desc: 'Chaque fonctionnalité doit être utilisable sans formation. Si c\'est compliqué, c\'est notre faute.' },
            { title: 'Accessibilité', desc: 'Un plan gratuit généreux, des tarifs justes, et une app qui fonctionne même avec une connexion lente.' },
            { title: 'Fiabilité', desc: 'Vos réservations sont votre gagne-pain. Notre plateforme doit fonctionner 24h/24, sans exception.' },
            { title: 'Proximité', desc: 'Nous écoutons nos utilisateurs. Chaque fonctionnalité vient d\'un besoin réel remonté par un commerçant.' },
          ]" :key="v.title" class="border border-gray-100 rounded-2xl p-6">
            <h3 class="font-bold text-gray-900 mb-2">{{ v.title }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed">{{ v.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="py-16 px-4 sm:px-6 bg-gray-50">
      <div class="max-w-2xl mx-auto text-center">
        <h2 class="text-2xl font-extrabold text-gray-900 mb-4">Envie de nous rejoindre ?</h2>
        <p class="text-gray-500 mb-8">Nous recrutons des personnes passionnées pour construire l'avenir de la réservation en ligne.</p>
        <RouterLink to="/contact" class="btn-primary">Nous contacter</RouterLink>
      </div>
    </section>
  </div>
</template>
