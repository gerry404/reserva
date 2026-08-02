<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  Bars3Icon,
  ArrowRightOnRectangleIcon,
  Cog6ToothIcon,
  UserCircleIcon,
  SparklesIcon,
} from '@heroicons/vue/24/outline'

const emit = defineEmits(['toggle-sidebar'])
const route = useRoute()
const auth  = useAuthStore()
const showMenu = ref(false)
const menuRef = ref(null)

const pageTitle = {
  dashboard: 'Tableau de bord',
  bookings:  'Réservations',
  services:  'Services',
  settings:  'Paramètres',
}

function getTitle() {
  return pageTitle[route.name] ?? 'Nuvo'
}

async function handleLogout() {
  showMenu.value = false
  await auth.logout()
}

// Click outside handler
function onClickOutside(e) {
  if (menuRef.value && !menuRef.value.contains(e.target)) {
    showMenu.value = false
  }
}

onMounted(() => document.addEventListener('click', onClickOutside, true))
onUnmounted(() => document.removeEventListener('click', onClickOutside, true))
</script>

<template>
  <header class="bg-white/80 backdrop-blur-xl border-b border-gray-100 px-4 sm:px-6 lg:px-8 h-16 flex items-center gap-4 shrink-0 sticky top-0 z-30">
    <!-- Mobile menu toggle -->
    <button
      @click="emit('toggle-sidebar')"
      class="p-2 -ml-2 rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors lg:hidden"
      aria-label="Menu"
    >
      <Bars3Icon class="w-5 h-5" />
    </button>

    <!-- Logo (visible on mobile when sidebar hidden) -->
    <div class="flex items-center gap-2 lg:hidden">
      <img src="/logo.svg" alt="Nuvo" class="w-7 h-7" />
      <span class="font-display font-extrabold text-gray-900 text-base tracking-tight">Nuvo</span>
    </div>

    <!-- Page title + business name -->
    <div class="flex-1 hidden lg:flex items-center gap-3">
      <h1 class="font-display font-bold text-gray-900 text-lg">{{ getTitle() }}</h1>
      <span v-if="auth.business?.name" class="text-gray-300">·</span>
      <span v-if="auth.business?.name" class="text-sm text-gray-400 truncate max-w-[200px]">{{ auth.business.name }}</span>
    </div>
    <div class="flex-1 lg:hidden" />

    <!-- Actions -->
    <div class="flex items-center gap-1.5">
      <!-- Trial countdown, then the upgrade nudge. Pointing at billing, where
           the upgrade actually happens, rather than at settings. -->
      <RouterLink
        v-if="auth.onTrial"
        :to="{ name: 'billing' }"
        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 rounded-full transition-colors"
      >
        <SparklesIcon class="w-3.5 h-3.5" />
        Essai Pro — {{ auth.trialDaysLeft }} j
      </RouterLink>
      <RouterLink
        v-else-if="!auth.isPro"
        :to="{ name: 'billing' }"
        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold text-primary-700 bg-primary-50 hover:bg-primary-100 rounded-full transition-colors"
      >
        <SparklesIcon class="w-3.5 h-3.5" />
        Passer Pro
      </RouterLink>

      <!--
        The notification bell used to live here with a permanently pulsing red
        dot behind no notification system at all. Removed rather than faked: an
        indicator that never means anything trains merchants to ignore it.
      -->

      <!-- Avatar menu -->
      <div ref="menuRef" class="relative">
        <button
          @click.stop="showMenu = !showMenu"
          class="w-9 h-9 rounded-full bg-gradient-to-br from-primary-500 to-violet-500 flex items-center justify-center text-white font-bold text-sm hover:opacity-90 transition-opacity focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:ring-offset-2"
        >
          {{ auth.user?.name?.charAt(0)?.toUpperCase() }}
        </button>

        <Transition name="menu">
          <div
            v-if="showMenu"
            class="absolute right-0 mt-2.5 w-64 bg-clay-50 rounded-2xl shadow-2xl shadow-black/12 border border-gray-100 overflow-hidden z-50"
          >
            <!-- User info -->
            <div class="px-4 py-4 border-b border-gray-50 bg-gray-50/50">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500 to-violet-500 flex items-center justify-center text-white font-bold text-sm shrink-0">
                  {{ auth.user?.name?.charAt(0)?.toUpperCase() }}
                </div>
                <div class="min-w-0">
                  <p class="font-bold text-gray-900 text-sm truncate">{{ auth.user?.name }}</p>
                  <p class="text-xs text-gray-500 truncate">{{ auth.user?.email }}</p>
                </div>
              </div>
              <div v-if="auth.business?.name" class="mt-2.5 px-2.5 py-1.5 bg-clay-50 rounded-lg border border-gray-100">
                <p class="text-[11px] text-gray-400 font-medium">Commerce</p>
                <p class="text-xs font-semibold text-gray-700 truncate">{{ auth.business.name }}</p>
              </div>
            </div>

            <!-- Menu links -->
            <div class="py-1.5">
              <RouterLink @click="showMenu = false" to="/dashboard/settings"
                class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                <Cog6ToothIcon class="w-4 h-4 text-gray-400" />
                Paramètres
              </RouterLink>
              <RouterLink @click="showMenu = false" to="/"
                class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                <UserCircleIcon class="w-4 h-4 text-gray-400" />
                Voir le site
              </RouterLink>
            </div>

            <!-- Logout -->
            <div class="border-t border-gray-50 py-1.5">
              <button
                @click="handleLogout"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
              >
                <ArrowRightOnRectangleIcon class="w-4 h-4" />
                Se déconnecter
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </header>
</template>

<style scoped>
.menu-enter-active { transition: all 0.15s ease-out; }
.menu-leave-active { transition: all 0.1s ease-in; }
.menu-enter-from   { opacity: 0; transform: scale(0.95) translateY(-4px); }
.menu-leave-to     { opacity: 0; transform: scale(0.95) translateY(-4px); }
</style>
