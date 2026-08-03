<script setup>
import { computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  HomeIcon,
  CalendarDaysIcon,
  Squares2X2Icon,
  Cog6ToothIcon,
  CreditCardIcon,
  ArrowTopRightOnSquareIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({ open: Boolean })
const emit  = defineEmits(['close'])

const route = useRoute()
const auth  = useAuthStore()

const navItems = [
  { name: 'Tableau de bord', to: '/dashboard',          icon: HomeIcon },
  { name: 'Réservations',    to: '/dashboard/bookings', icon: CalendarDaysIcon },
  { name: 'Services',        to: '/dashboard/services', icon: Squares2X2Icon },
  { name: 'Paramètres',      to: '/dashboard/settings', icon: Cog6ToothIcon },
  { name: 'Abonnement',     to: '/dashboard/billing',  icon: CreditCardIcon },
]

const publicUrl = computed(() => {
  const slug = auth.business?.slug
  return slug ? `/b/${slug}` : null
})

const planBadge = computed(() => ({
  free:     { label: 'Gratuit', cls: 'bg-gray-100 text-gray-600' },
  pro:      { label: 'Pro',     cls: 'bg-primary-100 text-primary-700' },
  business: { label: 'Business',cls: 'bg-violet-100 text-violet-700' },
}[auth.user?.plan ?? 'free']))

function isActive(path) {
  if (path === '/dashboard') return route.path === '/dashboard'
  return route.path.startsWith(path)
}

function onNav() {
  emit('close')
}
</script>

<template>
  <!-- Desktop sidebar -->
  <aside class="hidden lg:flex flex-col w-64 bg-clay-50 border-r border-gray-100 h-full shrink-0">
    <div class="flex flex-col flex-1 overflow-hidden">
      <!-- Logo -->
      <div class="flex items-center gap-2.5 px-5 py-5 border-b border-gray-100">
        <img src="/logo.svg" alt="Nuvo" class="w-9 h-9 shrink-0" />
        <span class="font-display font-extrabold text-gray-900 text-lg tracking-tight">Nuvo</span>
      </div>

      <!-- Nav -->
      <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-control text-sm font-medium transition-all duration-200',
            isActive(item.to)
              ? 'bg-primary-50 text-primary-700 font-semibold shadow-sm'
              : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
          ]"
        >
          <component :is="item.icon" :class="['w-5 h-5 shrink-0', isActive(item.to) ? 'text-primary-600' : 'text-gray-400']" />
          {{ item.name }}
        </RouterLink>
      </nav>

      <!-- Public link -->
      <div v-if="publicUrl" class="px-3 py-3 border-t border-gray-100">
        <a
          :href="publicUrl"
          target="_blank"
          class="flex items-center gap-2 px-3 py-2.5 text-sm font-medium text-primary-600 hover:bg-primary-50 rounded-control transition-colors"
        >
          <ArrowTopRightOnSquareIcon class="w-4 h-4 shrink-0" />
          <span class="truncate">Ma page publique</span>
        </a>
      </div>

      <!-- User info -->
      <div class="px-3 py-4 border-t border-gray-100">
        <div class="flex items-center gap-3 px-3 py-2">
          <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary-500 to-violet-500 flex items-center justify-center shrink-0">
            <span class="text-white font-semibold text-sm">{{ auth.user?.name?.charAt(0) }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 truncate">{{ auth.user?.name }}</p>
            <span :class="['text-xs font-medium px-1.5 py-0.5 rounded-full', planBadge?.cls]">
              {{ planBadge?.label }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </aside>

  <!-- Mobile drawer -->
  <Transition name="slide-in">
    <aside
      v-if="open"
      class="fixed inset-y-0 left-0 z-30 w-72 bg-clay-50 shadow-2xl flex flex-col lg:hidden"
    >
      <!-- Mobile header -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <div class="flex items-center gap-2.5">
          <img src="/logo.svg" alt="Nuvo" class="w-8 h-8" />
          <span class="font-display font-extrabold text-gray-900 text-lg tracking-tight">Nuvo</span>
        </div>
        <button @click="emit('close')" class="p-1.5 rounded-control text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Mobile nav -->
      <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-control text-sm font-medium transition-all duration-200',
            isActive(item.to)
              ? 'bg-primary-50 text-primary-700 font-semibold'
              : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
          ]"
          @click="onNav"
        >
          <component :is="item.icon" :class="['w-5 h-5 shrink-0', isActive(item.to) ? 'text-primary-600' : 'text-gray-400']" />
          {{ item.name }}
        </RouterLink>
      </nav>

      <!-- Public link (mobile) -->
      <div v-if="publicUrl" class="px-3 py-3 border-t border-gray-100">
        <a :href="publicUrl" target="_blank"
          class="flex items-center gap-2 px-3 py-2.5 text-sm font-medium text-primary-600 hover:bg-primary-50 rounded-control transition-colors">
          <ArrowTopRightOnSquareIcon class="w-4 h-4 shrink-0" />
          <span class="truncate">Ma page publique</span>
        </a>
      </div>

      <!-- User info (mobile) -->
      <div class="px-3 py-4 border-t border-gray-100">
        <div class="flex items-center gap-3 px-3 py-2">
          <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary-500 to-violet-500 flex items-center justify-center shrink-0">
            <span class="text-white font-semibold text-sm">{{ auth.user?.name?.charAt(0) }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 truncate">{{ auth.user?.name }}</p>
            <span :class="['text-xs font-medium px-1.5 py-0.5 rounded-full', planBadge?.cls]">
              {{ planBadge?.label }}
            </span>
          </div>
        </div>
      </div>
    </aside>
  </Transition>
</template>

<style scoped>
.slide-in-enter-active { transition: transform 0.25s ease-out; }
.slide-in-leave-active { transition: transform 0.2s ease-in; }
.slide-in-enter-from, .slide-in-leave-to { transform: translateX(-100%); }
</style>
