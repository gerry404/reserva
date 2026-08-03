<template>
  <div>
    <!-- Header -->
    <section class="pt-20 pb-12 px-4 sm:px-6 relative overflow-hidden">
      <div class="absolute inset-0 -z-10">
        <div class="absolute top-0 left-1/3 w-[400px] h-[300px] bg-primary-100/40 rounded-full blur-[100px]" />
        <div class="absolute top-10 right-1/4 w-[300px] h-[250px] bg-violet-100/30 rounded-full blur-[80px]" />
      </div>
      <div class="max-w-3xl mx-auto">
        <div class="inline-flex items-center gap-2 text-sm text-gray-400 mb-6">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
          Document juridique
        </div>
        <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 tracking-tight mb-4">Conditions d'utilisation</h1>
        <p class="text-lg text-gray-500">Les regles qui encadrent l'utilisation de la plateforme Reserva.</p>
        <div class="flex items-center gap-4 mt-6 text-sm text-gray-400">
          <span>Derniere mise a jour : 1er janvier 2026</span>
          <span class="w-1 h-1 rounded-full bg-gray-300" />
          <span>11 articles</span>
        </div>
      </div>
    </section>

    <!-- Separator -->
    <div class="max-w-3xl mx-auto px-4 sm:px-6">
      <div class="h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent" />
    </div>

    <!-- Content -->
    <section class="py-16 px-4 sm:px-6">
      <div class="max-w-3xl mx-auto">
        <!-- Table of contents -->
        <div class="bg-gray-50 border border-gray-100 rounded-2xl p-6 mb-14">
          <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Sommaire</p>
          <div class="grid sm:grid-cols-2 gap-2">
            <a v-for="(item, i) in [
              'Acceptation des conditions',
              'Description du service',
              'Inscription et compte',
              'Plans et facturation',
              'Utilisation acceptable',
              'Donnees personnelles',
              'Propriete intellectuelle',
              'Limitation de responsabilite',
              'Resiliation',
              'Modifications',
              'Contact',
            ]" :key="i" :href="'#article-' + (i + 1)" class="flex items-center gap-2.5 px-3 py-2 rounded-control text-sm text-gray-600 hover:bg-clay-50 hover:text-gray-900 hover:shadow-sm transition-all duration-200 group">
              <span class="w-6 h-6 rounded-md bg-clay-50 group-hover:bg-primary-50 border border-gray-200 group-hover:border-primary-200 flex items-center justify-center text-[11px] font-bold text-gray-400 group-hover:text-primary-600 transition-colors">{{ i + 1 }}</span>
              {{ item }}
            </a>
          </div>
        </div>

        <!-- Articles -->
        <div class="space-y-12">
          <article id="article-1">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center text-sm font-bold text-primary-600">1</span>
              <h2 class="text-xl font-bold text-gray-900">Acceptation des conditions</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">En accedant et en utilisant la plateforme Reserva ("le Service"), vous acceptez d'etre lie par les presentes conditions d'utilisation. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser le Service.</p>
          </article>

          <article id="article-2">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center text-sm font-bold text-primary-600">2</span>
              <h2 class="text-xl font-bold text-gray-900">Description du service</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Reserva est une plateforme de gestion de reservations en ligne permettant aux commercants de creer une page de reservation publique, de gerer leurs rendez-vous et de recevoir des notifications.</p>
          </article>

          <article id="article-3">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center text-sm font-bold text-primary-600">3</span>
              <h2 class="text-xl font-bold text-gray-900">Inscription et compte</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Pour utiliser le Service, vous devez creer un compte en fournissant des informations exactes et completes. Vous etes responsable de la confidentialite de vos identifiants de connexion et de toute activite effectuee sous votre compte.</p>
          </article>

          <article id="article-4">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center text-sm font-bold text-primary-600">4</span>
              <h2 class="text-xl font-bold text-gray-900">Plans et facturation</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Reserva propose plusieurs plans d'abonnement. Les tarifs sont indiques en Francs CFA (XAF). Les abonnements sont renouveles automatiquement sauf annulation prealable. Les paiements sont non remboursables, sauf dans les cas prevus par la loi.</p>
          </article>

          <article id="article-5">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center text-sm font-bold text-primary-600">5</span>
              <h2 class="text-xl font-bold text-gray-900">Utilisation acceptable</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Vous vous engagez a utiliser le Service de maniere legale et conforme aux presentes conditions. Il est interdit d'utiliser le Service pour des activites illegales, frauduleuses ou portant atteinte aux droits de tiers.</p>
          </article>

          <article id="article-6">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center text-sm font-bold text-primary-600">6</span>
              <h2 class="text-xl font-bold text-gray-900">Donnees personnelles</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">La collecte et le traitement de vos donnees personnelles sont regis par notre <RouterLink to="/privacy" class="text-primary-600 font-medium hover:underline">Politique de confidentialite</RouterLink>. En utilisant le Service, vous consentez a la collecte et au traitement de vos donnees conformement a cette politique.</p>
          </article>

          <article id="article-7">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center text-sm font-bold text-primary-600">7</span>
              <h2 class="text-xl font-bold text-gray-900">Propriete intellectuelle</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Le Service, son contenu, ses fonctionnalites et son design sont proteges par les lois sur la propriete intellectuelle. Vous conservez la propriete de vos donnees et contenus uploades sur la plateforme.</p>
          </article>

          <article id="article-8">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center text-sm font-bold text-primary-600">8</span>
              <h2 class="text-xl font-bold text-gray-900">Limitation de responsabilite</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Reserva s'efforce d'assurer la disponibilite et la fiabilite du Service, mais ne peut garantir un fonctionnement ininterrompu. Reserva ne saurait etre tenu responsable des pertes indirectes liees a l'utilisation du Service.</p>
          </article>

          <article id="article-9">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center text-sm font-bold text-primary-600">9</span>
              <h2 class="text-xl font-bold text-gray-900">Resiliation</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Vous pouvez resilier votre compte a tout moment depuis vos parametres. Reserva se reserve le droit de suspendre ou resilier votre compte en cas de violation des presentes conditions.</p>
          </article>

          <article id="article-10">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center text-sm font-bold text-primary-600">10</span>
              <h2 class="text-xl font-bold text-gray-900">Modifications</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Reserva se reserve le droit de modifier les presentes conditions a tout moment. Les modifications prennent effet des leur publication. Votre utilisation continue du Service constitue une acceptation des conditions modifiees.</p>
          </article>

          <article id="article-11">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center text-sm font-bold text-primary-600">11</span>
              <h2 class="text-xl font-bold text-gray-900">Contact</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Pour toute question relative aux presentes conditions, vous pouvez nous contacter a l'adresse : <a href="mailto:legal@nuvo.app" class="text-primary-600 font-medium hover:underline">legal@nuvo.app</a></p>
          </article>
        </div>

        <!-- Bottom CTA -->
        <div class="mt-16 bg-gray-50 border border-gray-100 rounded-2xl p-8 text-center">
          <p class="text-gray-500 text-sm mb-4">Des questions sur nos conditions ?</p>
          <RouterLink to="/contact" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-gray-700 bg-clay-50 border border-gray-200 hover:border-gray-300 rounded-control hover:shadow-sm transition-all duration-200">
            Contactez-nous
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
          </RouterLink>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
</script>
