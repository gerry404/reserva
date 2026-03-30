<script setup>
</script>

<template>
  <div>
    <section class="py-20 px-4 sm:px-6 bg-gradient-to-b from-gray-50 to-white">
      <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 tracking-tight mb-6">Carrières</h1>
        <p class="text-lg text-gray-500 leading-relaxed">Rejoignez une équipe passionnée qui construit des outils pour des milliers de commerçants.</p>
      </div>
    </section>

    <section class="py-20 px-4 sm:px-6">
      <div class="max-w-3xl mx-auto">
        <h2 class="text-2xl font-extrabold text-gray-900 mb-4">Pourquoi Réserva ?</h2>
        <div class="grid sm:grid-cols-3 gap-6 mb-16">
          <div v-for="p in [
            { title: 'Impact réel', desc: 'Chaque ligne de code aide un commerçant à mieux vivre de son activité.' },
            { title: 'Flexibilité', desc: 'Travail à distance, horaires flexibles. On juge sur les résultats, pas les heures.' },
            { title: 'Croissance', desc: 'Startup en pleine croissance. Votre travail sera visible et reconnu.' },
          ]" :key="p.title" class="bg-gray-50 rounded-2xl p-6">
            <h3 class="font-bold text-gray-900 mb-2">{{ p.title }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed">{{ p.desc }}</p>
          </div>
        </div>

        <h2 class="text-2xl font-extrabold text-gray-900 mb-8">Postes ouverts</h2>
        <div class="space-y-4">
          <div v-for="job in [
            { title: 'Développeur Full-Stack (Laravel/Vue)', type: 'CDI', location: 'Remote', team: 'Engineering' },
            { title: 'Designer UI/UX', type: 'CDI', location: 'Remote', team: 'Product' },
            { title: 'Chargé de croissance / Growth', type: 'CDI', location: 'Douala / Remote', team: 'Marketing' },
            { title: 'Support Client Bilingue (FR/EN)', type: 'CDD', location: 'Douala', team: 'Support' },
          ]" :key="job.title"
            class="border border-gray-100 rounded-2xl p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-gray-200 hover:shadow-sm transition-all">
            <div>
              <h3 class="font-bold text-gray-900">{{ job.title }}</h3>
              <div class="flex items-center gap-3 mt-2">
                <span class="text-xs font-semibold text-primary-600 bg-primary-50 px-2.5 py-1 rounded-full">{{ job.team }}</span>
                <span class="text-xs text-gray-400">{{ job.type }}</span>
                <span class="text-xs text-gray-400">{{ job.location }}</span>
              </div>
            </div>
            <a href="mailto:careers@reserva.cm" class="btn-secondary text-sm shrink-0">Postuler</a>
          </div>
        </div>

        <div class="mt-16 bg-gray-50 rounded-2xl p-8 text-center">
          <h3 class="font-bold text-gray-900 mb-2">Votre poste n'est pas listé ?</h3>
          <p class="text-sm text-gray-500 mb-4">Envoyez-nous une candidature spontanée. Nous sommes toujours à la recherche de talents.</p>
          <a href="mailto:careers@reserva.cm" class="text-primary-600 font-semibold text-sm hover:underline">careers@reserva.cm</a>
        </div>
      </div>
    </section>
  </div>
</template>
