<script setup>
import { RouterLink, RouterView, useRouter } from 'vue-router'

const router = useRouter()
function goBack() {
  if (window.history.length > 1) router.back()
  else router.push('/')
}
</script>

<template>
  <div class="min-h-screen bg-white flex flex-col">
    <!-- Navbar -->
    <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-2xl border-b border-gray-900/5 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 h-[60px] flex items-center justify-between">
        <div class="flex items-center gap-3">
          <!-- Back button -->
          <button @click="goBack" class="group flex items-center justify-center w-9 h-9 rounded-lg hover:bg-gray-100 transition-all duration-200" aria-label="Retour">
            <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
          </button>
          <div class="w-px h-5 bg-gray-200" />
          <RouterLink to="/" class="flex items-center gap-2">
            <img src="/logo.png" alt="Réserva" class="w-8 h-8" />
            <span class="font-display font-extrabold text-gray-900 text-lg tracking-tight">Réserva</span>
          </RouterLink>
        </div>
        <div class="flex items-center gap-2">
          <RouterLink to="/login" class="px-4 py-2 text-sm font-semibold text-gray-700 hover:text-gray-900 rounded-lg hover:bg-gray-100/70 transition-all duration-200 hidden sm:block">Se connecter</RouterLink>
          <RouterLink to="/register" class="px-4 py-2 text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 rounded-lg transition-all duration-200 shadow-sm">Démarrer gratuitement</RouterLink>
        </div>
      </div>
    </nav>

    <!-- Page content -->
    <main class="flex-1">
      <RouterView />
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-10">
          <!-- Brand column -->
          <div class="col-span-2 md:col-span-1">
            <div class="flex items-center gap-2 mb-4">
              <img src="/logo.png" alt="Réserva" class="w-8 h-8" />
              <span class="font-display font-extrabold text-white text-lg">Réserva</span>
            </div>
            <p class="text-sm leading-relaxed mb-6">La plateforme de réservation en ligne pensée pour les commerçants ambitieux.</p>
            <div class="flex items-center gap-3">
              <a href="#" class="w-9 h-9 rounded-lg bg-gray-800 hover:bg-gray-700 flex items-center justify-center transition-colors" aria-label="Facebook">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
              </a>
              <a href="#" class="w-9 h-9 rounded-lg bg-gray-800 hover:bg-gray-700 flex items-center justify-center transition-colors" aria-label="Instagram">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
              </a>
              <a href="#" class="w-9 h-9 rounded-lg bg-gray-800 hover:bg-gray-700 flex items-center justify-center transition-colors" aria-label="Twitter">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
              </a>
              <a href="#" class="w-9 h-9 rounded-lg bg-gray-800 hover:bg-gray-700 flex items-center justify-center transition-colors" aria-label="WhatsApp">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
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
          <p class="text-sm text-gray-500">&copy; {{ new Date().getFullYear() }} Réserva. Tous droits réservés.</p>
          <div class="flex items-center gap-6">
            <RouterLink to="/terms" class="text-sm text-gray-500 hover:text-gray-300 transition-colors">Conditions d'utilisation</RouterLink>
            <RouterLink to="/privacy" class="text-sm text-gray-500 hover:text-gray-300 transition-colors">Politique de confidentialité</RouterLink>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
