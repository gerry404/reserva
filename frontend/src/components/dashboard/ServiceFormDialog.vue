<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Image as ImageIcon, Plus, X } from 'lucide-vue-next'
import { SERVICE_ICONS, guessIconKey } from '@/constants/serviceIcons'
import { defaultAccent, swatches } from '@/design/tokens'
import { MAX_IMAGES, useServiceImages } from '@/composables/useServiceImages'

/**
 * Le formulaire de création et de modification d'une prestation.
 *
 * Il vivait dans la vue, mêlé à la grille et à la visionneuse. Les deux modes
 * partagent tout sauf le titre et l'appel final : les séparer aurait dupliqué
 * le formulaire, les garder dans la vue le rendait illisible.
 */
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  /** La prestation à modifier, ou `null` pour une création. */
  service: { type: Object, default: null },
  errors: { type: Object, default: () => ({}) },
  saving: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'submit'])

const {
  vignettes, refus, total: totalImages, placeLibre,
  charger: chargerImages, ajouter: ajouterImages, retirer: retirerImage,
  reinitialiser: viderImages, remplir: remplirImages,
} = useServiceImages()
const fileInput = ref(null)

const modification = computed(() => Boolean(props.service))

const form = reactive({
  name: '', description: '', duration: 30, price: 0,
  category: '', color: defaultAccent, icon: null,
})

const DUREES = [
  { value: 15, title: '15 min' }, { value: 30, title: '30 min' },
  { value: 45, title: '45 min' }, { value: 60, title: '1 h' },
  { value: 90, title: '1 h 30' }, { value: 120, title: '2 h' },
  { value: 180, title: '3 h' },   { value: 240, title: '4 h' },
]

/**
 * L'icône suggérée d'après le nom, tant que le commerçant n'en a pas choisi.
 *
 * `form.icon` reste à null jusqu'au premier clic : c'est ce qui distingue
 * « pas encore décidé » de « a choisi la sonnette ». Le premier suit le nom au
 * fil de la frappe, le second ne bouge plus.
 */
const iconeSuggeree = computed(() => guessIconKey(form.name, form.category))
const iconeActive = computed(() => form.icon ?? iconeSuggeree.value)

/* Le formulaire se recharge à chaque ouverture, jamais à la fermeture : vidé en
 * sortie, le contenu disparaissait sous les yeux pendant l'animation. */
watch(() => props.modelValue, (ouvert) => {
  if (!ouvert) return

  const s = props.service
  Object.assign(form, {
    name: s?.name ?? '',
    description: s?.description ?? '',
    duration: s?.duration ?? 30,
    price: s?.price ?? 0,
    category: s?.category ?? '',
    color: s?.color ?? defaultAccent,
    icon: s?.icon ?? null,
  })

  if (s) chargerImages(s.images ?? [])
  else viderImages()
})

function choisirFichiers(evenement) {
  ajouterImages(evenement.target.files)
  // Remis à zéro, sinon choisir deux fois le même fichier n'émet plus rien.
  evenement.target.value = ''
}

function soumettre() {
  const donnees = new FormData()
  donnees.append('name', form.name)
  donnees.append('description', form.description || '')
  donnees.append('duration', form.duration)
  donnees.append('price', form.price)
  donnees.append('category', form.category || '')
  donnees.append('color', form.color)

  // Seul un choix explicite part au serveur : sans cela la suggestion serait
  // figée en base et ne suivrait plus un renommage de la prestation.
  if (form.icon) donnees.append('icon', form.icon)

  remplirImages(donnees, { modification: modification.value })

  emit('submit', donnees)
}

/** La première erreur d'un champ, telle que l'API la renvoie. */
function erreur(champ) {
  return props.errors?.[champ]?.[0]
}
</script>

<template>
  <v-dialog
    :model-value="modelValue"
    max-width="680"
    scrollable
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card rounded="surface">
      <v-card-title class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">
          {{ modification ? 'Modifier la prestation' : 'Nouvelle prestation' }}
        </span>
        <v-btn icon variant="text" size="small" aria-label="Fermer" @click="$emit('update:modelValue', false)">
          <X :size="18" />
        </v-btn>
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-5">
        <v-form @submit.prevent="soumettre">
          <div class="d-flex flex-column ga-5">
            <v-text-field
              v-model="form.name"
              label="Nom de la prestation"
              placeholder="ex : Coupe + brushing"
              :error-messages="erreur('name')"
              autofocus
            />

            <v-textarea
              v-model="form.description"
              label="Description"
              placeholder="Ce que le client doit savoir avant de réserver"
              rows="2"
              auto-grow
              :error-messages="erreur('description')"
            />

            <div class="d-flex flex-wrap ga-4">
              <v-select
                v-model="form.duration"
                :items="DUREES"
                label="Durée"
                class="flex-grow-1"
                style="min-width: 180px"
                :error-messages="erreur('duration')"
              />
              <v-text-field
                v-model.number="form.price"
                type="number"
                min="0"
                step="100"
                label="Prix (F CFA)"
                class="flex-grow-1"
                style="min-width: 180px"
                :error-messages="erreur('price')"
              />
            </div>

            <v-text-field
              v-model="form.category"
              label="Catégorie"
              placeholder="ex : Soin capillaire"
              :error-messages="erreur('category')"
            />

            <div>
              <p class="field__label">Icône</p>
              <div class="d-flex flex-wrap ga-2">
                <button
                  v-for="ic in SERVICE_ICONS"
                  :key="ic.key"
                  type="button"
                  :title="ic.label"
                  :aria-label="ic.label"
                  :aria-pressed="iconeActive === ic.key"
                  :class="['picker__icon', { 'is-on': iconeActive === ic.key }]"
                  :style="iconeActive === ic.key ? { backgroundColor: form.color, borderColor: form.color } : null"
                  @click="form.icon = ic.key"
                >
                  <component :is="ic.component" :size="18" />
                </button>
              </div>
              <p v-if="!form.icon" class="text-caption text-medium-emphasis mt-2 mb-0">
                Suggérée d'après le nom. Cliquez pour en imposer une autre.
              </p>
            </div>

            <div>
              <p class="field__label">Couleur</p>
              <div class="d-flex flex-wrap ga-2">
                <button
                  v-for="c in swatches"
                  :key="c"
                  type="button"
                  :aria-label="`Couleur ${c}`"
                  :aria-pressed="form.color === c"
                  :class="['picker__color', { 'is-on': form.color === c }]"
                  :style="{ backgroundColor: c }"
                  @click="form.color = c"
                />
              </div>
            </div>

            <div>
              <p class="field__label">
                Photos
                <span class="text-medium-emphasis font-weight-regular">
                  ({{ totalImages }}/{{ MAX_IMAGES }}, 2 Mo maximum)
                </span>
              </p>

              <div class="d-flex flex-wrap ga-3">
                <div v-for="(v, i) in vignettes" :key="v.url" class="thumb">
                  <img :src="v.url" alt="" />
                  <button
                    type="button"
                    class="thumb__remove"
                    aria-label="Retirer cette photo"
                    @click="retirerImage(i)"
                  >
                    <X :size="13" />
                  </button>
                </div>

                <button
                  v-if="placeLibre"
                  type="button"
                  class="thumb thumb--add"
                  @click="fileInput?.click()"
                >
                  <ImageIcon :size="20" />
                  <span class="text-caption mt-1">Ajouter</span>
                </button>
              </div>

              <input
                ref="fileInput"
                type="file"
                accept="image/*"
                multiple
                class="d-none"
                @change="choisirFichiers"
              />

              <!-- Les fichiers écartés étaient rejetés en silence : une photo
                   trop lourde n'apparaissait simplement pas. -->
              <v-alert
                v-if="refus"
                type="warning"
                density="compact"
                class="mt-3 text-body-2"
              >
                {{ refus }}
              </v-alert>
            </div>
          </div>
        </v-form>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4 ga-2">
        <v-spacer />
        <v-btn variant="text" class="text-none" :disabled="saving" @click="$emit('update:modelValue', false)">
          Annuler
        </v-btn>
        <v-btn color="primary" variant="flat" class="text-none px-5" :loading="saving" @click="soumettre">
          <Plus v-if="!modification" :size="16" class="mr-1" />
          {{ modification ? 'Enregistrer' : 'Créer la prestation' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.field__label {
  font-size: 13px;
  font-weight: 600;
  color: var(--clay-700);
  margin-bottom: 10px;
}

.picker__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: 1px solid var(--clay-200);
  border-radius: 999px;
  color: var(--clay-500);
  transition: color 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
}

.picker__icon:hover { color: var(--forest-800); border-color: var(--clay-300); }
.picker__icon.is-on { color: #fff; transform: scale(1.06); }

.picker__color {
  width: 32px;
  height: 32px;
  border-radius: 999px;
  border: 2px solid transparent;
  transition: transform 0.15s ease;
}

.picker__color:hover { transform: scale(1.1); }

.picker__color.is-on {
  transform: scale(1.12);
  box-shadow: 0 0 0 2px #fff, 0 0 0 4px var(--forest-950);
}

.thumb {
  position: relative;
  width: 84px;
  height: 84px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--clay-200);
  flex-shrink: 0;
}

.thumb img { width: 100%; height: 100%; object-fit: cover; }

.thumb__remove {
  position: absolute;
  top: 4px;
  right: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 999px;
  background: rgb(0 0 0 / 0.6);
  color: #fff;
}

.thumb__remove:hover { background: rgb(0 0 0 / 0.82); }

.thumb--add {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-style: dashed;
  color: var(--clay-500);
  background: var(--clay-100);
}

.thumb--add:hover { color: var(--forest-700); border-color: var(--forest-300); }

@media (prefers-reduced-motion: reduce) {
  .picker__icon, .picker__color { transition: none; }
}
</style>
