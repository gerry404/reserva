<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <div>
    <section class="py-20 px-4 sm:px-6 bg-gradient-to-b from-gray-50 to-white">
      <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 tracking-tight mb-6">Guide de démarrage</h1>
        <p class="text-lg text-gray-500 leading-relaxed">Soyez opérationnel en moins de 10 minutes. Suivez ces étapes simples.</p>
      </div>
    </section>

    <section class="py-20 px-4 sm:px-6">
      <div class="max-w-3xl mx-auto">
        <div class="space-y-12">
          <div v-for="(step, i) in [
            {
              n: '01',
              title: 'Créez votre compte',
              desc: 'Rendez-vous sur la page d\'inscription et renseignez vos informations personnelles : nom, email et mot de passe. C\'est gratuit et sans engagement.',
              details: [
                'Utilisez une adresse email que vous consultez régulièrement',
                'Renseignez votre numéro WhatsApp pour recevoir les notifications',
                'Choisissez un mot de passe d\'au moins 8 caractères',
              ]
            },
            {
              n: '02',
              title: 'Configurez votre commerce',
              desc: 'Donnez un nom à votre commerce, choisissez votre catégorie et indiquez votre ville. Ces informations apparaîtront sur votre page de réservation publique.',
              details: [
                'Le nom de votre commerce sera utilisé pour générer votre URL unique',
                'Ajoutez une description courte pour présenter votre activité',
                'Choisissez une couleur d\'accent qui représente votre marque',
              ]
            },
            {
              n: '03',
              title: 'Ajoutez vos services',
              desc: 'Créez la liste des prestations que vous proposez. Pour chaque service, indiquez le nom, la durée, le prix et une description optionnelle.',
              details: [
                'Soyez précis sur la durée pour éviter les chevauchements',
                'Le prix affiché aide vos clients à choisir',
                'Vous pouvez ajouter ou modifier vos services à tout moment',
              ]
            },
            {
              n: '04',
              title: 'Définissez vos horaires',
              desc: 'Indiquez vos jours et horaires d\'ouverture dans les paramètres. Les créneaux de réservation sont générés automatiquement en fonction de la durée de chaque service.',
              details: [
                'Définissez des horaires différents pour chaque jour',
                'Fermez un jour en désactivant le toggle correspondant',
                'Ajoutez une pause déjeuner si nécessaire',
              ]
            },
            {
              n: '05',
              title: 'Partagez votre lien',
              desc: 'Votre page de réservation est prête. Copiez votre lien unique et partagez-le partout : WhatsApp, Instagram, Facebook, carte de visite.',
              details: [
                'Ajoutez le lien dans votre bio Instagram',
                'Envoyez-le à vos clients réguliers par WhatsApp',
                'Imprimez un QR code pour votre vitrine',
              ]
            },
          ]" :key="i" class="flex gap-6">
            <div class="flex flex-col items-center shrink-0">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-600 to-violet-600 text-white font-extrabold text-sm flex items-center justify-center shadow-lg shadow-primary-500/20">
                {{ step.n }}
              </div>
              <div v-if="i < 4" class="w-px flex-1 bg-gray-200 mt-4" />
            </div>
            <div class="pb-12">
              <h3 class="font-bold text-gray-900 text-lg mb-2">{{ step.title }}</h3>
              <p class="text-gray-500 leading-relaxed mb-4">{{ step.desc }}</p>
              <ul class="space-y-2">
                <li v-for="d in step.details" :key="d" class="flex items-start gap-2 text-sm text-gray-500">
                  <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                  {{ d }}
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="mt-8 bg-primary-50 border border-primary-100 rounded-2xl p-8 text-center">
          <h3 class="font-bold text-gray-900 text-lg mb-3">Prêt à commencer ?</h3>
          <p class="text-gray-500 text-sm mb-6">Créez votre page de réservation gratuitement en quelques minutes.</p>
          <RouterLink to="/register" class="btn-primary">Créer mon compte gratuit</RouterLink>
        </div>
      </div>
    </section>
  </div>
</template>
