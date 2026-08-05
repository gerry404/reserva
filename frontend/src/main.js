import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
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

app.mount('#app')
