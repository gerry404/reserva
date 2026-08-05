<script setup>
import { RouterLink, RouterView, useRouter } from 'vue-router'
import BrandIcon from '@/components/ui/BrandIcon.vue'
import { ChevronLeft } from 'lucide-vue-next'


const router = useRouter()
function goBack() {
  if (window.history.length > 1) router.back()
  else router.push('/')
}
</script>

<template>
  <div class="min-h-screen bg-clay-50 flex flex-col">
    <!-- Navbar -->
    <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-2xl border-b border-gray-900/5 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 h-[60px] flex items-center justify-between">
        <div class="flex items-center gap-3">
          <!-- Back button -->
          <button @click="goBack" class="group flex items-center justify-center w-9 h-9 rounded-control hover:bg-gray-100 transition-all duration-200" aria-label="Retour">
            <ChevronLeft class="w-5 h-5 text-gray-400 group-hover:text-gray-700 transition-colors" />
          </button>
          <div class="w-px h-5 bg-gray-200" />
          <RouterLink to="/" class="flex items-center gap-2">
            <img src="/logo.svg" alt="Nuvo" class="w-8 h-8" />
            <span class="font-display font-extrabold text-gray-900 text-lg tracking-tight">Nuvo</span>
          </RouterLink>
        </div>
        <div class="flex items-center gap-2">
          <RouterLink to="/login" class="px-4 py-2 text-sm font-semibold text-gray-700 hover:text-gray-900 rounded-control hover:bg-gray-100/70 transition-all duration-200 hidden sm:block">Se connecter</RouterLink>
          <RouterLink to="/register" class="px-4 py-2 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-control transition-all duration-200 shadow-sm">Démarrer gratuitement</RouterLink>
        </div>
      </div>
    </nav>

    <!-- Page content -->
    <main class="flex-1">
      <RouterView />
    </main>

    <!-- Footer -->
    <footer class="bg-primary-950 text-primary-200">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-10">
          <!-- Brand column -->
          <div class="col-span-2 md:col-span-1">
            <div class="flex items-center gap-2 mb-4">
              <img src="/logo.svg" alt="Nuvo" class="w-8 h-8" />
              <span class="font-display font-extrabold text-white text-lg">Nuvo</span>
            </div>
            <p class="text-sm leading-relaxed mb-6">La plateforme de réservation en ligne pensée pour les commerçants ambitieux.</p>
            <div class="flex items-center gap-3">
              <a href="#" class="w-9 h-9 rounded-control bg-gray-800 hover:bg-gray-700 flex items-center justify-center transition-colors" aria-label="Facebook">
                <BrandIcon name="facebook" class="w-4 h-4" />
              </a>
              <a href="#" class="w-9 h-9 rounded-control bg-gray-800 hover:bg-gray-700 flex items-center justify-center transition-colors" aria-label="Instagram">
                <BrandIcon name="instagram" class="w-4 h-4" />
              </a>
              <a href="#" class="w-9 h-9 rounded-control bg-gray-800 hover:bg-gray-700 flex items-center justify-center transition-colors" aria-label="Twitter">
                <BrandIcon name="x" class="w-4 h-4" />
              </a>
              <a href="#" class="w-9 h-9 rounded-control bg-gray-800 hover:bg-gray-700 flex items-center justify-center transition-colors" aria-label="WhatsApp">
                <BrandIcon name="whatsapp" class="w-4 h-4" />
              </a>
            </div>
          </div>

          <!-- Produit -->
          <div>
            <h4 class="font-bold text-white text-sm mb-4">Produit</h4>
            <ul class="space-y-2.5">
              <li><RouterLink to="/register" class="text-sm hover:text-white transition-colors">Créer un compte</RouterLink></li>
              <li><RouterLink to="/" class="text-sm hover:text-white transition-colors">Fonctionnalités</RouterLink></li>
              <li><RouterLink to="/" class="text-sm hover:text-white transition-colors">Tarifs</RouterLink></li>
              
            </ul>
          </div>

          <!-- Ressources -->
          <div>
            <h4 class="font-bold text-white text-sm mb-4">Ressources</h4>
            <ul class="space-y-2.5">
              <li><RouterLink to="/help" class="text-sm hover:text-white transition-colors">Centre d'aide</RouterLink></li>
              <li><RouterLink to="/guide" class="text-sm hover:text-white transition-colors">Guide de démarrage</RouterLink></li>
              
            </ul>
          </div>

          <!-- Entreprise -->
          <div>
            <h4 class="font-bold text-white text-sm mb-4">Entreprise</h4>
            <ul class="space-y-2.5">
              <li><RouterLink to="/about" class="text-sm hover:text-white transition-colors">À propos</RouterLink></li>
              
              <li><RouterLink to="/contact" class="text-sm hover:text-white transition-colors">Contact</RouterLink></li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Bottom bar -->
      <div class="border-t border-gray-800">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p class="text-sm text-gray-500">&copy; {{ new Date().getFullYear() }} Nuvo. Tous droits réservés.</p>
          <div class="flex items-center gap-6">
            <RouterLink to="/terms" class="text-sm text-gray-500 hover:text-gray-300 transition-colors">Conditions d'utilisation</RouterLink>
            <RouterLink to="/privacy" class="text-sm text-gray-500 hover:text-gray-300 transition-colors">Politique de confidentialité</RouterLink>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
