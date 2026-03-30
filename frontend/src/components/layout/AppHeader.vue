<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Bars3Icon, BellIcon, ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline'

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
  return pageTitle[route.name] ?? 'Réserva'
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
  <header class="bg-white border-b border-gray-100 px-4 sm:px-6 lg:px-8 h-16 flex items-center gap-4 shrink-0">
    <!-- Mobile menu toggle -->
    <button
      @click="emit('toggle-sidebar')"
      class="p-2 -ml-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors lg:hidden"
      aria-label="Menu"
    >
      <Bars3Icon class="w-5 h-5" />
    </button>

    <!-- Page title -->
    <h1 class="font-bold text-gray-900 text-lg flex-1">
      {{ getTitle() }}
    </h1>

    <!-- Actions -->
    <div class="flex items-center gap-2">
      <!-- Notification bell -->
      <button class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors relative">
        <BellIcon class="w-5 h-5" />
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full animate-pulse" />
      </button>

      <!-- Avatar menu -->
      <div ref="menuRef" class="relative">
        <button
          @click.stop="showMenu = !showMenu"
          class="w-9 h-9 rounded-full bg-gradient-to-br from-primary-500 to-violet-500 flex items-center justify-center text-white font-semibold text-sm hover:opacity-90 transition-opacity focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:ring-offset-2"
        >
          {{ auth.user?.name?.charAt(0) }}
        </button>

        <Transition name="menu">
          <div
            v-if="showMenu"
            class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl shadow-black/10 border border-gray-100 overflow-hidden z-50"
          >
            <div class="px-4 py-3 border-b border-gray-50">
              <p class="font-semibold text-gray-900 text-sm truncate">{{ auth.user?.name }}</p>
              <p class="text-xs text-gray-500 truncate">{{ auth.user?.email }}</p>
            </div>
            <div class="py-1">
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
