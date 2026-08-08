<script setup>
import { ref } from 'vue'
import { Camera, Image as ImageIcon } from 'lucide-vue-next'
import { swatches } from '@/design/tokens'

/**
 * Ce que le client voit en tête de la page de réservation.
 *
 * La bannière et le logo remontent en fichiers plutôt qu'en URL : c'est le
 * serveur qui construit l'adresse une fois l'image stockée, et la vue n'a pas
 * à deviner où elle atterrira.
 */
defineProps({
  form: { type: Object, required: true },
  logoPreview: { type: String, default: null },
  coverPreview: { type: String, default: null },
})

const emit = defineEmits(['logo', 'cover'])

const logoInput = ref(null)
const coverInput = ref(null)

function choisir(evenement, canal) {
  const fichier = evenement.target.files?.[0]
  if (fichier) emit(canal, fichier)
  evenement.target.value = ''
}
</script>

<template>
  <v-card class="pa-5">
    <p class="section__label">Apparence de votre page</p>

    <div class="cover" role="button" aria-label="Changer la bannière" @click="coverInput?.click()">
      <img v-if="coverPreview" :src="coverPreview" alt="" />
      <div v-else class="cover__empty">
        <ImageIcon :size="24" />
        <span class="text-caption mt-1">Ajouter une bannière</span>
      </div>
      <div class="cover__veil">
        <Camera :size="20" color="#fff" />
        <span class="text-caption text-white ml-2">Changer</span>
      </div>
    </div>

    <div class="d-flex align-center ga-4 mt-4">
      <div class="logo" role="button" aria-label="Changer le logo" @click="logoInput?.click()">
        <img v-if="logoPreview" :src="logoPreview" alt="" />
        <Camera v-else :size="18" />
      </div>
      <div>
        <p class="text-body-2 font-weight-semibold mb-0">Logo</p>
        <p class="text-caption text-medium-emphasis mb-0">Carré, 2 Mo maximum.</p>
      </div>
    </div>

    <input ref="coverInput" type="file" accept="image/*" class="d-none" @change="choisir($event, 'cover')" />
    <input ref="logoInput" type="file" accept="image/*" class="d-none" @change="choisir($event, 'logo')" />

    <v-divider class="my-6" />

    <p class="section__label">Couleur d'accent</p>
    <div class="d-flex flex-wrap ga-2">
      <button
        v-for="c in swatches"
        :key="c"
        type="button"
        :aria-label="`Couleur ${c}`"
        :aria-pressed="form.accent_color === c"
        :class="['swatch', { 'is-on': form.accent_color === c }]"
        :style="{ backgroundColor: c }"
        @click="form.accent_color = c"
      />
    </div>
    <p class="text-caption text-medium-emphasis mt-3 mb-0">
      Elle habille votre page de réservation : le bandeau, les boutons et les
      créneaux choisis.
    </p>
  </v-card>
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

.cover {
  position: relative;
  height: 150px;
  border-radius: 14px;
  overflow: hidden;
  cursor: pointer;
  background: var(--clay-100);
  border: 1px dashed var(--clay-300);
}

.cover img { width: 100%; height: 100%; object-fit: cover; }

.cover__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--clay-400);
}

.cover__veil {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgb(0 0 0 / 0.3);
  opacity: 0;
  transition: opacity 0.2s ease;
}

.cover:hover .cover__veil { opacity: 1; }

.logo {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 62px;
  height: 62px;
  flex-shrink: 0;
  border-radius: 16px;
  overflow: hidden;
  cursor: pointer;
  background: var(--clay-100);
  border: 1px dashed var(--clay-300);
  color: var(--clay-400);
}

.logo img { width: 100%; height: 100%; object-fit: cover; }
.logo:hover { border-color: var(--forest-400); color: var(--forest-600); }

.swatch {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  border: 2px solid transparent;
  transition: transform 0.15s ease;
}

.swatch:hover { transform: scale(1.1); }

.swatch.is-on {
  transform: scale(1.12);
  box-shadow: 0 0 0 2px var(--clay-50), 0 0 0 4px var(--forest-950);
}

@media (prefers-reduced-motion: reduce) {
  .swatch, .cover__veil { transition: none; }
}
</style>
