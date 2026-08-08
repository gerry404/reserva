<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { ExternalLink } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { NAV_ITEMS } from '@/constants/navigation'
import UserAvatar from '@/components/ui/UserAvatar.vue'

/**
 * Le contenu du tiroir de navigation.
 *
 * Ce fichier portait le même balisage deux fois, une version bureau et une
 * version mobile, avec leurs propres classes de position et leur propre
 * transition. `v-navigation-drawer` gère le passage en superposition sous le
 * point de rupture, ce qui rend la seconde copie inutile.
 */

const route = useRoute()
const auth = useAuthStore()

const publicUrl = computed(() => {
  const slug = auth.business?.slug
  return slug ? `/b/${slug}` : null
})

/**
 * Le palier d'abonnement affiché sous le nom.
 *
 * Les couleurs viennent du thème plutôt que de classes écrites à la main :
 * le palier Business portait `bg-violet-100`, un reste de l'ancienne identité
 * que le remappage des jetons rendait vert sans que le nom le dise.
 */
const planBadge = computed(() => ({
  free:     { label: 'Découverte', color: 'clay-200',  text: 'clay-700' },
  pro:      { label: 'Pro',        color: 'forest-100', text: 'forest-800' },
  business: { label: 'Business',   color: 'forest-700', text: 'white' },
}[auth.user?.plan ?? 'free']))

function isActive(item) {
  if (item.to === '/dashboard') return route.path === '/dashboard'
  return route.path.startsWith(item.to)
}
</script>

<template>
  <div class="d-flex flex-column h-100">
    <div class="sidebar__brand">
      <img src="/logo.svg" alt="" class="sidebar__logo" />
      <span class="sidebar__name">Nuvo</span>
    </div>

    <v-list nav class="flex-grow-1 pa-3">
      <v-list-item
        v-for="item in NAV_ITEMS"
        :key="item.to"
        :to="item.to"
        :active="isActive(item)"
        active-color="primary"
        rounded="control"
        class="mb-1"
      >
        <template #prepend>
          <component
            :is="item.icon"
            :size="19"
            :stroke-width="isActive(item) ? 2.3 : 1.9"
            class="mr-3"
          />
        </template>
        <v-list-item-title class="text-body-2 font-weight-medium">
          {{ item.label }}
        </v-list-item-title>
      </v-list-item>
    </v-list>

    <div v-if="publicUrl" class="pa-3 sidebar__section">
      <v-btn
        :href="publicUrl"
        target="_blank"
        variant="tonal"
        color="primary"
        block
        class="justify-start text-none"
      >
        <ExternalLink :size="16" class="mr-2" />
        Ma page publique
      </v-btn>
    </div>

    <div class="pa-3 sidebar__section">
      <div class="d-flex align-center ga-3 px-2 py-1">
        <UserAvatar :name="auth.user?.name" size="sm" />
        <div class="flex-grow-1 min-width-0">
          <p class="text-body-2 font-weight-bold text-truncate mb-1">{{ auth.user?.name }}</p>
          <v-chip
            :color="planBadge?.color"
            :text-color="planBadge?.text"
            size="x-small"
            variant="flat"
            class="font-weight-bold"
          >
            {{ planBadge?.label }}
          </v-chip>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.sidebar__brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 20px;
  border-bottom: 1px solid var(--clay-200);
}

.sidebar__logo {
  width: 34px;
  height: 34px;
  flex-shrink: 0;
}

.sidebar__name {
  font-family: 'Dekatron', Roboto, sans-serif;
  font-weight: 800;
  font-size: 19px;
  letter-spacing: -0.02em;
  color: var(--forest-950);
}

.sidebar__section {
  border-top: 1px solid var(--clay-200);
}

.min-width-0 {
  min-width: 0;
}
</style>
