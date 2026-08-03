<script setup>
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Silently redirecting every unknown URL to the homepage (the previous
// behaviour) hides typos in a shared booking link, which is exactly the case
// where the visitor needs to know something went wrong.
const auth = useAuthStore()
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="text-center max-w-md">
      <p class="text-7xl font-black text-gray-200 mb-2">404</p>
      <h1 class="text-2xl font-black text-gray-900 mb-3">Page introuvable</h1>
      <p class="text-gray-500 leading-relaxed mb-8">
        Cette page n'existe pas ou a été déplacée. Si vous avez suivi un lien de
        réservation, vérifiez qu'il a été copié en entier.
      </p>

      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <RouterLink :to="auth.isAuthenticated ? { name: 'dashboard' } : { name: 'landing' }" class="btn-primary px-6 py-3">
          {{ auth.isAuthenticated ? 'Retour au tableau de bord' : "Retour à l'accueil" }}
        </RouterLink>
        <RouterLink :to="{ name: 'track-booking' }" class="btn-secondary px-6 py-3">
          Suivre une réservation
        </RouterLink>
      </div>
    </div>
  </div>
</template>
