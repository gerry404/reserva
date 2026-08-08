<script setup>
import { computed, ref } from 'vue'
import { Check, Copy, ExternalLink } from 'lucide-vue-next'
import { COUNTRIES } from '@/composables/usePhoneInput'

/**
 * L'identité du commerce et son adresse publique.
 *
 * Le formulaire est muté sur place plutôt que recopié : les sections partagent
 * un seul objet, et le bouton d'enregistrement n'a pas à rassembler quatre
 * fragments au moment de partir.
 */
const props = defineProps({
  form: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  publicUrl: { type: String, default: '' },
  isPro: { type: Boolean, default: false },
})

const copie = ref(false)

/** Drapeau et nom réunis : la liste affiche un libellé, pas deux colonnes. */
const pays = COUNTRIES.map((c) => ({ value: c.code, title: `${c.flag} ${c.name}` }))

function erreur(champ) {
  return props.errors?.[champ]?.[0]
}

async function copier() {
  if (!props.publicUrl) return
  await navigator.clipboard.writeText(props.publicUrl)
  copie.value = true
  setTimeout(() => { copie.value = false }, 2000)
}
</script>

<template>
  <div class="d-flex flex-column ga-5">
    <v-card class="pa-5">
      <p class="section__label">Votre adresse de réservation</p>
      <div class="d-flex flex-wrap align-center ga-3">
        <code class="link">{{ publicUrl || '…' }}</code>
        <v-btn variant="tonal" color="primary" size="small" class="text-none" @click="copier">
          <component :is="copie ? Check : Copy" :size="15" class="mr-2" />
          {{ copie ? 'Copié' : 'Copier' }}
        </v-btn>
        <v-btn
          v-if="publicUrl"
          :href="publicUrl"
          target="_blank"
          variant="text"
          size="small"
          class="text-none"
        >
          <ExternalLink :size="15" class="mr-2" />
          Ouvrir
        </v-btn>
      </div>
      <p class="text-caption text-medium-emphasis mt-3 mb-0">
        C'est le lien que vos clients ouvrent. Partagez-le sur WhatsApp, Instagram
        ou en boutique.
      </p>
    </v-card>

    <v-card class="pa-5">
      <p class="section__label">Le commerce</p>

      <div class="d-flex flex-column ga-5">
        <v-text-field
          v-model="form.name"
          label="Nom du commerce"
          :error-messages="erreur('name')"
        />

        <div>
          <v-text-field
            v-model="form.slug"
            label="Adresse personnalisée"
            :prefix="'…/b/'"
            :disabled="!isPro"
            :error-messages="erreur('slug')"
            :hint="isPro
              ? 'Lettres, chiffres et tirets. Changer ce lien casse celui que vos clients ont déjà enregistré.'
              : 'Réservé au plan Pro.'"
            persistent-hint
          />
        </div>

        <v-textarea
          v-model="form.description"
          label="Description"
          rows="3"
          auto-grow
          :error-messages="erreur('description')"
        />

        <div class="d-flex flex-wrap ga-4">
          <v-text-field
            v-model="form.category"
            label="Catégorie"
            placeholder="ex : Salon de coiffure"
            class="flex-grow-1"
            style="min-width: 200px"
            :error-messages="erreur('category')"
          />
          <v-text-field
            v-model="form.city"
            label="Ville"
            class="flex-grow-1"
            style="min-width: 200px"
            :error-messages="erreur('city')"
          />
        </div>

        <v-text-field
          v-model="form.address"
          label="Adresse"
          :error-messages="erreur('address')"
        />

        <div>
          <v-select
            v-model="form.country"
            :items="pays"
            label="Pays"
            :error-messages="erreur('country')"
          />
          <!-- Pas cosmétique : le pays fixe le fuseau contre lequel vos horaires
               sont lus, et la devise dans laquelle vos prix s'affichent. -->
          <p class="text-caption text-medium-emphasis mt-2 mb-0">
            Fixe le fuseau horaire de vos créneaux et la devise de vos prix.
          </p>
        </div>

        <div class="d-flex flex-wrap ga-4">
          <v-text-field
            v-model="form.phone"
            label="Téléphone"
            class="flex-grow-1"
            style="min-width: 200px"
            :error-messages="erreur('phone')"
          />
          <v-text-field
            v-model="form.whatsapp"
            label="WhatsApp"
            class="flex-grow-1"
            style="min-width: 200px"
            :error-messages="erreur('whatsapp')"
          />
        </div>
      </div>
    </v-card>
  </div>
</template>

<style scoped>
.section__label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--clay-400);
  margin-bottom: 16px;
}

.link {
  padding: 7px 12px;
  border-radius: 999px;
  background: var(--clay-100);
  border: 1px solid var(--clay-200);
  font-family: 'Yuzo', Roboto, monospace;
  font-size: 12px;
  color: var(--forest-800);
  word-break: break-all;
}
</style>
