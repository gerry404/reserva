import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import vuetify from './plugins/vuetify'

/*
 * L'ordre de ces deux importations n'est pas indifférent.
 *
 * Vuetify et Tailwind embarquent chacun sa réinitialisation. Chargée en
 * second, celle de Vuetify reprenait la main sur les éléments nus — titres,
 * paragraphes, boutons — et la landing perdait sa typographie au profit de
 * Roboto/Material.
 *
 * Vuetify passe donc en premier : Tailwind a le dernier mot sur les sélecteurs
 * d'élément, tandis que les composants `v-*`, stylés par classe, gardent la
 * priorité là où ils s'appliquent.
 */
import 'vuetify/styles'
import './style.css'

/*
 * L'instance Pinia est exportée pour être passée explicitement aux stores
 * consultés hors composant : le garde de navigation, notamment.
 *
 * Sans cela, useAuthStore() s'appuyait sur la pinia « active », une variable
 * globale que Vue installe au montage. Le garde s'exécutant pendant
 * l'installation du routeur, elle n'était pas encore posée : l'application ne
 * montait pas du tout, sur un écran vide et une seule ligne en console.
 */
export const pinia = createPinia()

const app = createApp(App)

app.use(pinia)
app.use(router)
app.use(vuetify)

app.mount('#app')
