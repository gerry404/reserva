<script setup>
import { computed, onMounted, ref } from 'vue'
import { LayoutGrid, Plus } from 'lucide-vue-next'
import { servicesApi } from '@/api'
import ServiceCard from '@/components/dashboard/ServiceCard.vue'
import ServiceFormDialog from '@/components/dashboard/ServiceFormDialog.vue'
import ServiceGallery from '@/components/dashboard/ServiceGallery.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

/**
 * Les prestations du commerce.
 *
 * La vue ne fait plus que charger la liste et arbitrer les boîtes de dialogue.
 * La carte, le formulaire, la visionneuse et l'état des images vivaient ici,
 * dans un fichier de plus de cinq cents lignes où le formulaire commençait à la
 * moitié du gabarit.
 */

const services = ref([])
const chargement = ref(true)

const formulaire = ref(false)
const enEdition = ref(null)
const erreurs = ref({})
const enregistrement = ref(false)

const visionneuse = ref(false)
const visionnee = ref(null)

const aSupprimer = ref(null)
const suppression = ref(false)

/** Le message d'échec d'une action, affiché plutôt que jeté dans une `alert()`. */
const echec = ref('')

const actifs = computed(() => services.value.filter((s) => s.is_active).length)

onMounted(charger)

async function charger() {
  chargement.value = true
  try {
    services.value = await servicesApi.list()
  } catch (e) {
    echec.value = e.message ?? 'Impossible de charger les prestations.'
  } finally {
    chargement.value = false
  }
}

function creer() {
  enEdition.value = null
  erreurs.value = {}
  formulaire.value = true
}

function modifier(service) {
  enEdition.value = service
  erreurs.value = {}
  formulaire.value = true
}

async function enregistrer(donnees) {
  erreurs.value = {}
  enregistrement.value = true

  try {
    if (enEdition.value) {
      const mise = await servicesApi.update(enEdition.value.id, donnees)
      const i = services.value.findIndex((s) => s.id === enEdition.value.id)
      if (i !== -1) services.value[i] = mise
    } else {
      services.value.unshift(await servicesApi.create(donnees))
    }
    formulaire.value = false
  } catch (e) {
    // Les erreurs de champ retournent au formulaire, qui les pose sous chaque
    // libellé. Une erreur sans champ n'y a pas sa place : elle va en bandeau.
    erreurs.value = e.fieldErrors ?? {}
    if (!e.fieldErrors) echec.value = e.message ?? "L'enregistrement a échoué."
  } finally {
    enregistrement.value = false
  }
}

async function basculer(service) {
  try {
    const mise = await servicesApi.toggle(service.id)
    const i = services.value.findIndex((s) => s.id === service.id)
    if (i !== -1) services.value[i] = mise
  } catch (e) {
    echec.value = e.message ?? 'Impossible de changer l’état de cette prestation.'
  }
}

async function supprimer() {
  suppression.value = true
  try {
    await servicesApi.delete(aSupprimer.value.id)
    services.value = services.value.filter((s) => s.id !== aSupprimer.value.id)
    aSupprimer.value = null
  } catch (e) {
    // Le serveur refuse de supprimer une prestation qui porte des rendez-vous
    // à venir. Ce refus était rendu par `alert()`, hors de toute mise en page.
    echec.value = e.message ?? 'Impossible de supprimer cette prestation.'
    aSupprimer.value = null
  } finally {
    suppression.value = false
  }
}

function ouvrirGalerie(service) {
  visionnee.value = service
  visionneuse.value = true
}
</script>

<template>
  <div class="d-flex flex-column ga-6">
    <div class="d-flex flex-wrap align-center justify-space-between ga-4">
      <div>
        <h2 class="page__title">Prestations</h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          <span class="numeric-inline">{{ services.length }}</span> au catalogue,
          <span class="numeric-inline">{{ actifs }}</span> visibles par vos clients
        </p>
      </div>
      <v-btn color="primary" variant="flat" class="text-none px-5" @click="creer">
        <Plus :size="17" class="mr-2" />
        Nouvelle prestation
      </v-btn>
    </div>

    <v-alert v-if="echec" type="error" closable @click:close="echec = ''">
      {{ echec }}
    </v-alert>

    <v-row v-if="chargement" dense>
      <v-col v-for="i in 6" :key="i" cols="12" sm="6" lg="4">
        <v-skeleton-loader type="image, article, actions" />
      </v-col>
    </v-row>

    <v-card v-else-if="services.length === 0" class="pa-12 text-center">
      <div class="empty__icon">
        <LayoutGrid :size="28" />
      </div>
      <h3 class="text-subtitle-1 font-weight-bold mt-4 mb-2">Aucune prestation</h3>
      <p class="text-body-2 text-medium-emphasis mb-5">
        Vos clients ne peuvent rien réserver tant que le catalogue est vide.
      </p>
      <v-btn color="primary" variant="flat" class="text-none px-5" @click="creer">
        <Plus :size="17" class="mr-2" />
        Créer la première
      </v-btn>
    </v-card>

    <v-row v-else dense>
      <v-col v-for="service in services" :key="service.id" cols="12" sm="6" lg="4">
        <ServiceCard
          :service="service"
          @edit="modifier"
          @delete="aSupprimer = $event"
          @toggle="basculer"
          @open-gallery="ouvrirGalerie"
        />
      </v-col>
    </v-row>

    <ServiceFormDialog
      v-model="formulaire"
      :service="enEdition"
      :errors="erreurs"
      :saving="enregistrement"
      @submit="enregistrer"
    />

    <ServiceGallery v-model="visionneuse" :service="visionnee" />

    <ConfirmDialog
      :model-value="Boolean(aSupprimer)"
      :title="`Supprimer « ${aSupprimer?.name} » ?`"
      message="Les réservations passées la conservent en mémoire. Les rendez-vous à venir empêchent la suppression."
      confirm-label="Supprimer"
      tone="error"
      :loading="suppression"
      @update:model-value="aSupprimer = null"
      @confirm="supprimer"
    />
  </div>
</template>

<style scoped>
.page__title {
  font-family: 'Dekatron', Roboto, sans-serif;
  font-size: 22px;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--forest-950);
  margin: 0 0 2px;
}

.empty__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 64px;
  height: 64px;
  border-radius: 18px;
  background: var(--clay-100);
  color: var(--clay-400);
}
</style>
