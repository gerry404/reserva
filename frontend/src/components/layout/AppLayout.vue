<script setup>
import { ref, watch } from 'vue'
import { useDisplay } from 'vuetify'
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'

/**
 * L'ossature du tableau de bord.
 *
 * `v-app` n'est monté qu'ici, et non dans App.vue : la landing et la page
 * publique de réservation portent le langage visuel du produit, et le contexte
 * de mise en page de Vuetify n'a rien à y faire.
 *
 * Le tiroir se replie tout seul sous le point de rupture. La version
 * précédente maintenait un booléen, une superposition sombre et deux copies du
 * balisage, l'une masquée en `hidden lg:flex` et l'autre en `lg:hidden`.
 */

const { lgAndUp } = useDisplay()

/*
 * Ouvert sur grand écran, fermé sinon.
 *
 * Initialisé à `true` sans condition, le tiroir s'ouvrait par-dessus le
 * contenu au premier chargement sur téléphone : le commerçant arrivait sur son
 * tableau de bord masqué par le menu.
 *
 * Il suit ensuite les changements de largeur, mais seulement quand on franchit
 * le seuil : le forcer à chaque redimensionnement aurait refermé le tiroir
 * qu'on venait d'ouvrir à la main.
 */
const drawer = ref(lgAndUp.value)

watch(lgAndUp, (grand) => { drawer.value = grand })
</script>

<template>
  <v-app>
    <v-navigation-drawer
      v-model="drawer"
      :permanent="lgAndUp"
      :temporary="!lgAndUp"
      width="264"
      color="surface"
      border="0 1 0 0"
    >
      <AppSidebar />
    </v-navigation-drawer>

    <v-app-bar flat height="64" color="surface" border="0 0 1 0">
      <div class="layout__bar">
        <AppHeader @toggle-drawer="drawer = !drawer" />
      </div>
    </v-app-bar>

    <v-main class="layout__main">
      <div class="layout__content">
        <!--
          Pas de :key ici. Le poser sur le chemin remontait chaque vue à chaque
          navigation : tout le tableau de bord rechargeait ses données et
          perdait son état sur un aller-retour entre deux onglets.
        -->
        <RouterView />
      </div>
    </v-main>
  </v-app>
</template>

<style scoped>
.layout__bar {
  width: 100%;
  padding: 0 16px;
}

@media (min-width: 600px) {
  .layout__bar { padding: 0 24px; }
}

.layout__main {
  background: var(--clay-100);
}

.layout__content {
  max-width: 1280px;
  margin: 0 auto;
  padding: 28px 16px 56px;
}

@media (min-width: 600px) {
  .layout__content { padding: 32px 24px 64px; }
}
</style>
