<script setup>
import { computed, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { CircleUser, LogOut, Settings, Sparkles } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { titleForRoute } from '@/constants/navigation'
import UserAvatar from '@/components/ui/UserAvatar.vue'

/**
 * La barre du tableau de bord.
 *
 * Le menu de compte était un `<div>` positionné à la main, avec son propre
 * écouteur de clic extérieur, sa propre transition et aucun piège à focus.
 * `v-menu` fait les trois, et rend le tout au niveau du document plutôt que
 * dans le flux, ce qui supprime les conflits d'empilement avec la barre.
 */

defineProps({ rail: Boolean })
const emit = defineEmits(['toggle-drawer'])

const route = useRoute()
const auth = useAuthStore()
const menu = ref(false)

const title = computed(() => titleForRoute(route.name))

async function logout() {
  menu.value = false
  await auth.logout()
}
</script>

<template>
  <div class="d-flex align-center ga-3 w-100">
    <v-app-bar-nav-icon
      class="d-lg-none"
      aria-label="Ouvrir le menu"
      @click="emit('toggle-drawer')"
    />

    <div class="d-flex d-lg-none align-center ga-2">
      <img src="/logo.svg" alt="" width="26" height="26" />
      <span class="header__brand">Nuvo</span>
    </div>

    <div class="d-none d-lg-flex align-center ga-3 flex-grow-1 min-width-0">
      <h1 class="header__title">{{ title }}</h1>
      <template v-if="auth.business?.name">
        <span class="text-disabled">·</span>
        <span class="text-body-2 text-medium-emphasis text-truncate">{{ auth.business.name }}</span>
      </template>
    </div>

    <v-spacer class="d-lg-none" />

    <!--
      Le compte à rebours d'essai, puis l'incitation à passer Pro. Les deux
      pointent vers la facturation, où la bascule a réellement lieu.
    -->
    <v-chip
      v-if="auth.onTrial"
      :to="{ name: 'billing' }"
      color="warning"
      variant="tonal"
      size="small"
      class="d-none d-sm-flex font-weight-bold"
    >
      <Sparkles :size="14" class="mr-1" />
      Essai Pro · {{ auth.trialDaysLeft }} j
    </v-chip>
    <v-chip
      v-else-if="!auth.isPro"
      :to="{ name: 'billing' }"
      color="primary"
      variant="tonal"
      size="small"
      class="d-none d-sm-flex font-weight-bold"
    >
      <Sparkles :size="14" class="mr-1" />
      Passer Pro
    </v-chip>

    <!--
      La cloche de notifications vivait ici, avec un point rouge qui pulsait en
      permanence sans qu'aucun système de notification existe. Retirée plutôt
      que simulée : un indicateur qui ne signifie jamais rien apprend au
      commerçant à ne plus le regarder.
    -->

    <v-menu v-model="menu" location="bottom end" offset="10" :close-on-content-click="false">
      <template #activator="{ props: activator }">
        <button v-bind="activator" class="header__avatar" aria-label="Mon compte">
          <UserAvatar :name="auth.user?.name" size="sm" />
        </button>
      </template>

      <v-card min-width="264" rounded="surface">
        <div class="pa-4 header__menu-head">
          <div class="d-flex align-center ga-3">
            <UserAvatar :name="auth.user?.name" size="md" />
            <div class="min-width-0">
              <p class="text-body-2 font-weight-bold text-truncate mb-0">{{ auth.user?.name }}</p>
              <p class="text-caption text-medium-emphasis text-truncate mb-0">{{ auth.user?.email }}</p>
            </div>
          </div>

          <div v-if="auth.business?.name" class="header__business mt-3">
            <p class="text-caption text-medium-emphasis mb-0">Commerce</p>
            <p class="text-body-2 font-weight-semibold text-truncate mb-0">{{ auth.business.name }}</p>
          </div>
        </div>

        <v-list density="comfortable" nav class="py-2">
          <v-list-item :to="{ name: 'settings' }" rounded="control" @click="menu = false">
            <template #prepend><Settings :size="17" class="mr-3 text-medium-emphasis" /></template>
            <v-list-item-title class="text-body-2">Paramètres</v-list-item-title>
          </v-list-item>
          <v-list-item to="/" rounded="control" @click="menu = false">
            <template #prepend><CircleUser :size="17" class="mr-3 text-medium-emphasis" /></template>
            <v-list-item-title class="text-body-2">Voir le site</v-list-item-title>
          </v-list-item>
        </v-list>

        <v-divider />

        <v-list density="comfortable" nav class="py-2">
          <v-list-item rounded="control" base-color="error" @click="logout">
            <template #prepend><LogOut :size="17" class="mr-3" /></template>
            <v-list-item-title class="text-body-2">Se déconnecter</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-card>
    </v-menu>
  </div>
</template>

<style scoped>
.header__brand,
.header__title {
  font-family: 'Dekatron', Roboto, sans-serif;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--forest-950);
}

.header__brand { font-size: 16px; }
.header__title { font-size: 18px; margin: 0; }

.header__avatar {
  border-radius: 999px;
  line-height: 0;
  transition: opacity 0.15s ease;
}

.header__avatar:hover { opacity: 0.85; }

.header__avatar:focus-visible {
  outline: 2px solid var(--forest-500);
  outline-offset: 2px;
}

.header__menu-head { border-bottom: 1px solid var(--clay-200); }

.header__business {
  padding: 8px 10px;
  border: 1px solid var(--clay-200);
  border-radius: 10px;
  background: var(--clay-100);
}

.min-width-0 { min-width: 0; }
</style>
