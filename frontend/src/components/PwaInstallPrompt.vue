<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const STORAGE_KEY = 'reserva_pwa_dismissed_at'
const DISMISS_DAYS = 3 // re-propose après 3 jours

const visible      = ref(false)
const installing   = ref(false)
const installed    = ref(false)
const isIos        = ref(false)
const isInStandalone = ref(false)

let deferredPrompt = null

function shouldShow() {
  const dismissed = localStorage.getItem(STORAGE_KEY)
  if (!dismissed) return true
  const days = (Date.now() - Number(dismissed)) / (1000 * 60 * 60 * 24)
  return days >= DISMISS_DAYS
}

function handleBeforeInstall(e) {
  e.preventDefault()
  deferredPrompt = e
  // Délai de 4 secondes avant d'afficher — laisse l'app se charger
  setTimeout(() => {
    if (!installed.value && shouldShow()) {
      visible.value = true
    }
  }, 4000)
}

function handleAppInstalled() {
  installed.value = true
  visible.value   = false
  deferredPrompt  = null
}

async function install() {
  if (!deferredPrompt) return
  installing.value = true
  deferredPrompt.prompt()
  const { outcome } = await deferredPrompt.userChoice
  if (outcome === 'accepted') {
    installed.value = true
    visible.value   = false
  }
  installing.value = false
  deferredPrompt   = null
}

function dismiss() {
  visible.value = false
  localStorage.setItem(STORAGE_KEY, String(Date.now()))
}

onMounted(() => {
  // Déjà installée en mode standalone ?
  isInStandalone.value =
    window.matchMedia('(display-mode: standalone)').matches ||
    window.navigator.standalone === true

  if (isInStandalone.value) return

  // iOS (Safari) — pas de beforeinstallprompt, guide manuel
  const ua = window.navigator.userAgent
  isIos.value = /iphone|ipad|ipod/i.test(ua) && !/(chrome|crios|fxios)/i.test(ua)

  if (isIos.value && shouldShow()) {
    setTimeout(() => { visible.value = true }, 5000)
  }

  window.addEventListener('beforeinstallprompt', handleBeforeInstall)
  window.addEventListener('appinstalled', handleAppInstalled)
})

onUnmounted(() => {
  window.removeEventListener('beforeinstallprompt', handleBeforeInstall)
  window.removeEventListener('appinstalled', handleAppInstalled)
})
</script>

<template>
  <Teleport to="body">
    <Transition name="slide-bottom">
      <div
        v-if="visible && !isInStandalone"
        class="fixed bottom-0 inset-x-0 z-[100] p-4 sm:p-6 sm:max-w-sm sm:left-auto sm:right-4 sm:bottom-4"
      >
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
          <!-- Top gradient bar -->
          <div class="h-1 bg-gradient-to-r from-primary-500 to-violet-500" />

          <div class="p-5">
            <div class="flex items-start gap-4">
              <!-- App icon -->
              <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-500 to-violet-600 flex items-center justify-center shrink-0 shadow-md">
                <span class="text-white font-black text-2xl">R</span>
              </div>

              <div class="flex-1 min-w-0">
                <p class="font-bold text-gray-900 text-base leading-tight">Installer Réserva</p>
                <p class="text-sm text-gray-500 mt-0.5">Accès rapide depuis votre écran d'accueil, même hors ligne.</p>
              </div>

              <!-- Close -->
              <button
                @click="dismiss"
                class="text-gray-300 hover:text-gray-500 transition-colors shrink-0 -mt-1 -mr-1 p-1"
                aria-label="Fermer"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- Android/Desktop install button -->
            <template v-if="!isIos">
              <div class="flex gap-2 mt-4">
                <button @click="dismiss" class="btn-secondary flex-1 text-sm py-2">
                  Plus tard
                </button>
                <button
                  @click="install"
                  :disabled="installing"
                  class="btn-primary flex-1 text-sm py-2"
                >
                  <span v-if="installing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                  <span v-else>
                    <svg class="w-4 h-4 inline mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Installer
                  </span>
                </button>
              </div>
            </template>

            <!-- iOS guide manuel -->
            <template v-else>
              <div class="mt-4 p-3 bg-blue-50 rounded-xl text-sm text-blue-800">
                <p class="font-semibold mb-1">Sur iPhone / iPad :</p>
                <ol class="space-y-1 text-xs list-none">
                  <li>1. Appuyez sur
                    <span class="inline-flex items-center gap-0.5 font-medium">
                      <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a1 1 0 0 1 .707.293l4 4a1 1 0 0 1-1.414 1.414L13 5.414V16a1 1 0 0 1-2 0V5.414L8.707 7.707A1 1 0 0 1 7.293 6.293l4-4A1 1 0 0 1 12 2zM3 19a1 1 0 0 1 1-1h16a1 1 0 0 1 0 2H4a1 1 0 0 1-1-1z"/></svg>
                      Partager
                    </span>
                  </li>
                  <li>2. Faites défiler → <strong>« Sur l'écran d'accueil »</strong></li>
                  <li>3. Appuyez sur <strong>« Ajouter »</strong></li>
                </ol>
              </div>
              <button @click="dismiss" class="btn-secondary w-full mt-3 text-sm py-2">
                J'ai compris
              </button>
            </template>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.slide-bottom-enter-active {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.slide-bottom-leave-active {
  transition: all 0.25s ease-in;
}
.slide-bottom-enter-from,
.slide-bottom-leave-to {
  opacity: 0;
  transform: translateY(100%);
}
</style>
