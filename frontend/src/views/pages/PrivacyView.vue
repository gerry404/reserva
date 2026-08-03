<template>
  <div>
    <!-- Header -->
    <section class="pt-20 pb-12 px-4 sm:px-6 relative overflow-hidden">
      <div class="absolute inset-0 -z-10">
        <div class="absolute top-0 right-1/3 w-[400px] h-[300px] bg-violet-100/40 rounded-full blur-[100px]" />
        <div class="absolute top-10 left-1/4 w-[300px] h-[250px] bg-primary-100/30 rounded-full blur-[80px]" />
      </div>
      <div class="max-w-3xl mx-auto">
        <div class="inline-flex items-center gap-2 text-sm text-gray-400 mb-6">
          <ShieldCheck class="w-4 h-4" />
          Protection des donnees
        </div>
        <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 tracking-tight mb-4">Politique de confidentialite</h1>
        <p class="text-lg text-gray-500">Comment nous collectons, utilisons et protegeons vos donnees personnelles.</p>
        <div class="flex items-center gap-4 mt-6 text-sm text-gray-400">
          <span>Derniere mise a jour : 1er janvier 2026</span>
          <span class="w-1 h-1 rounded-full bg-gray-300" />
          <span>10 sections</span>
        </div>
      </div>
    </section>

    <!-- Separator -->
    <div class="max-w-3xl mx-auto px-4 sm:px-6">
      <div class="h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent" />
    </div>

    <!-- Key points summary -->
    <section class="py-12 px-4 sm:px-6">
      <div class="max-w-3xl mx-auto">
        <div class="grid sm:grid-cols-3 gap-4 mb-14">
          <div v-for="point in [
            { icon: EyeOff, title: 'Pas de tracking', desc: 'Aucun cookie publicitaire ni tracker tiers' },
            { icon: Lock, title: 'Chiffrement', desc: 'SSL/TLS et hashage des mots de passe' },
            { icon: Trash2, title: 'Suppression', desc: 'Vos donnees effacees sous 30 jours' },
          ]" :key="point.title" class="bg-gray-50 border border-gray-100 rounded-xl p-5">
            <component :is="point.icon" class="w-5 h-5 text-primary-600 mb-3" />
            <p class="text-sm font-bold text-gray-900 mb-1">{{ point.title }}</p>
            <p class="text-xs text-gray-500">{{ point.desc }}</p>
          </div>
        </div>

        <!-- Table of contents -->
        <div class="bg-gray-50 border border-gray-100 rounded-2xl p-6 mb-14">
          <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Sommaire</p>
          <div class="grid sm:grid-cols-2 gap-2">
            <a v-for="(item, i) in [
              'Donnees collectees',
              'Utilisation des donnees',
              'Partage des donnees',
              'Donnees de vos clients',
              'Securite',
              'Conservation',
              'Vos droits',
              'Cookies',
              'Modifications',
              'Contact',
            ]" :key="i" :href="'#section-' + (i + 1)" class="flex items-center gap-2.5 px-3 py-2 rounded-control text-sm text-gray-600 hover:bg-clay-50 hover:text-gray-900 hover:shadow-sm transition-all duration-200 group">
              <span class="w-6 h-6 rounded-md bg-clay-50 group-hover:bg-violet-50 border border-gray-200 group-hover:border-violet-200 flex items-center justify-center text-[11px] font-bold text-gray-400 group-hover:text-violet-600 transition-colors">{{ i + 1 }}</span>
              {{ item }}
            </a>
          </div>
        </div>

        <!-- Sections -->
        <div class="space-y-12">
          <article id="section-1">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-violet-50 border border-violet-100 flex items-center justify-center text-sm font-bold text-violet-600">1</span>
              <h2 class="text-xl font-bold text-gray-900">Donnees collectees</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Nous collectons les donnees que vous nous fournissez directement : nom, adresse email, numero de telephone, informations de votre commerce, et donnees de reservation de vos clients.</p>
          </article>

          <article id="section-2">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-violet-50 border border-violet-100 flex items-center justify-center text-sm font-bold text-violet-600">2</span>
              <h2 class="text-xl font-bold text-gray-900">Utilisation des donnees</h2>
            </div>
            <div class="pl-11">
              <p class="text-gray-600 leading-relaxed mb-4">Vos donnees sont utilisees pour :</p>
              <ul class="space-y-2.5">
                <li v-for="item in [
                  'Fournir et maintenir le Service',
                  'Envoyer les notifications de reservation (WhatsApp, SMS, email)',
                  'Ameliorer le Service et developper de nouvelles fonctionnalites',
                  'Vous contacter pour le support et les informations relatives a votre compte',
                  'Generer des statistiques anonymisees',
                ]" :key="item" class="flex items-start gap-2.5 text-sm text-gray-600">
                  <Check class="w-4 h-4 text-violet-500 shrink-0 mt-0.5" />
                  {{ item }}
                </li>
              </ul>
            </div>
          </article>

          <article id="section-3">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-violet-50 border border-violet-100 flex items-center justify-center text-sm font-bold text-violet-600">3</span>
              <h2 class="text-xl font-bold text-gray-900">Partage des donnees</h2>
            </div>
            <div class="pl-11">
              <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 mb-4">
                <p class="text-sm font-semibold text-emerald-800">Nous ne vendons jamais vos donnees personnelles.</p>
              </div>
              <p class="text-gray-600 leading-relaxed mb-3">Vos donnees peuvent etre partagees avec :</p>
              <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex items-start gap-2.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-gray-300 shrink-0 mt-2" />
                  Nos prestataires techniques (hebergement, envoi de notifications)
                </li>
                <li class="flex items-start gap-2.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-gray-300 shrink-0 mt-2" />
                  Les autorites competentes si la loi l'exige
                </li>
              </ul>
            </div>
          </article>

          <article id="section-4">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-violet-50 border border-violet-100 flex items-center justify-center text-sm font-bold text-violet-600">4</span>
              <h2 class="text-xl font-bold text-gray-900">Donnees de vos clients</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Les donnees de reservation de vos clients (nom, telephone, email) sont stockees sur nos serveurs securises. Vous etes responsable de l'utilisation de ces donnees conformement aux lois applicables.</p>
          </article>

          <article id="section-5">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-violet-50 border border-violet-100 flex items-center justify-center text-sm font-bold text-violet-600">5</span>
              <h2 class="text-xl font-bold text-gray-900">Securite</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Nous mettons en place des mesures de securite techniques et organisationnelles pour proteger vos donnees : chiffrement SSL/TLS, hashage des mots de passe, acces restreint aux donnees, sauvegardes regulieres.</p>
          </article>

          <article id="section-6">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-violet-50 border border-violet-100 flex items-center justify-center text-sm font-bold text-violet-600">6</span>
              <h2 class="text-xl font-bold text-gray-900">Conservation</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Vos donnees sont conservees tant que votre compte est actif. Apres suppression de votre compte, vos donnees sont effacees sous 30 jours, sauf obligation legale de conservation.</p>
          </article>

          <article id="section-7">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-violet-50 border border-violet-100 flex items-center justify-center text-sm font-bold text-violet-600">7</span>
              <h2 class="text-xl font-bold text-gray-900">Vos droits</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Vous disposez d'un droit d'acces, de rectification, de suppression et de portabilite de vos donnees. Pour exercer ces droits, contactez-nous a <a href="mailto:privacy@nuvo.app" class="text-primary-600 font-medium hover:underline">privacy@nuvo.app</a>.</p>
          </article>

          <article id="section-8">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-violet-50 border border-violet-100 flex items-center justify-center text-sm font-bold text-violet-600">8</span>
              <h2 class="text-xl font-bold text-gray-900">Cookies</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Nous utilisons uniquement des cookies essentiels au fonctionnement du Service (authentification, preferences). Aucun cookie publicitaire ou de tracking tiers n'est utilise.</p>
          </article>

          <article id="section-9">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-violet-50 border border-violet-100 flex items-center justify-center text-sm font-bold text-violet-600">9</span>
              <h2 class="text-xl font-bold text-gray-900">Modifications</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Cette politique peut etre mise a jour. Nous vous notifierons par email en cas de changement significatif.</p>
          </article>

          <article id="section-10">
            <div class="flex items-center gap-3 mb-4">
              <span class="w-8 h-8 rounded-lg bg-violet-50 border border-violet-100 flex items-center justify-center text-sm font-bold text-violet-600">10</span>
              <h2 class="text-xl font-bold text-gray-900">Contact</h2>
            </div>
            <p class="text-gray-600 leading-relaxed pl-11">Pour toute question : <a href="mailto:privacy@nuvo.app" class="text-primary-600 font-medium hover:underline">privacy@nuvo.app</a></p>
          </article>
        </div>

        <!-- Bottom CTA -->
        <div class="mt-16 bg-gray-50 border border-gray-100 rounded-2xl p-8 text-center">
          <p class="text-gray-500 text-sm mb-4">Des questions sur la protection de vos donnees ?</p>
          <RouterLink to="/contact" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-gray-700 bg-clay-50 border border-gray-200 hover:border-gray-300 rounded-control hover:shadow-sm transition-all duration-200">
            Contactez-nous
            <ArrowRight class="w-4 h-4" />
          </RouterLink>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { ArrowRight, Check, EyeOff, Lock, ShieldCheck, Trash2 } from 'lucide-vue-next'
</script>
