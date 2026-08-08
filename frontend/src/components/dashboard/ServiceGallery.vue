<script setup>
import { computed, ref, watch } from 'vue'
import { ChevronLeft, ChevronRight, X } from 'lucide-vue-next'

/**
 * La visionneuse des photos d'une prestation.
 *
 * L'écoute du clavier était posée sur `document` au montage de la vue et
 * retirée au démontage : les flèches restaient captées même visionneuse
 * fermée, et une navigation entre deux services laissait l'écouteur derrière.
 * Ici elle vit et meurt avec la boîte de dialogue.
 */
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  service: { type: Object, default: null },
  startIndex: { type: Number, default: 0 },
})

defineEmits(['update:modelValue'])

const index = ref(0)

const images = computed(() => props.service?.images ?? [])
const courante = computed(() => images.value[index.value] ?? null)

watch(() => props.modelValue, (ouvert) => {
  if (ouvert) index.value = props.startIndex
})

function suivante() {
  if (!images.value.length) return
  index.value = (index.value + 1) % images.value.length
}

function precedente() {
  if (!images.value.length) return
  index.value = (index.value - 1 + images.value.length) % images.value.length
}
</script>

<template>
  <v-dialog
    :model-value="modelValue"
    max-width="900"
    @update:model-value="$emit('update:modelValue', $event)"
    @keydown.left="precedente"
    @keydown.right="suivante"
  >
    <v-card v-if="courante" color="clay-950" rounded="surface" class="gallery">
      <div class="d-flex align-center justify-space-between pa-4">
        <div class="min-width-0">
          <p class="text-body-2 font-weight-bold text-white text-truncate mb-0">
            {{ service?.name }}
          </p>
          <p class="text-caption text-white-50 mb-0">
            <span class="numeric-inline">{{ index + 1 }} / {{ images.length }}</span>
          </p>
        </div>
        <v-btn icon variant="text" color="white" size="small" aria-label="Fermer" @click="$emit('update:modelValue', false)">
          <X :size="18" />
        </v-btn>
      </div>

      <div class="gallery__stage">
        <img :src="courante.url" :alt="`${service?.name} — photo ${index + 1}`" />

        <template v-if="images.length > 1">
          <v-btn icon variant="flat" color="rgba(0,0,0,0.55)" class="gallery__nav gallery__nav--prev" aria-label="Photo précédente" @click="precedente">
            <ChevronLeft :size="20" color="#fff" />
          </v-btn>
          <v-btn icon variant="flat" color="rgba(0,0,0,0.55)" class="gallery__nav gallery__nav--next" aria-label="Photo suivante" @click="suivante">
            <ChevronRight :size="20" color="#fff" />
          </v-btn>
        </template>
      </div>

      <div v-if="images.length > 1" class="d-flex ga-2 pa-4 gallery__strip">
        <button
          v-for="(img, i) in images"
          :key="img.url"
          type="button"
          :class="['gallery__thumb', { 'is-on': i === index }]"
          :aria-label="`Photo ${i + 1}`"
          @click="index = i"
        >
          <img :src="img.url" alt="" />
        </button>
      </div>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.gallery__stage {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #000;
  min-height: 300px;
  max-height: 65vh;
}

.gallery__stage img {
  max-width: 100%;
  max-height: 65vh;
  object-fit: contain;
}

.gallery__nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
}

.gallery__nav--prev { left: 12px; }
.gallery__nav--next { right: 12px; }

.gallery__strip { overflow-x: auto; }

.gallery__thumb {
  width: 60px;
  height: 60px;
  flex-shrink: 0;
  border-radius: 10px;
  overflow: hidden;
  border: 2px solid transparent;
  opacity: 0.55;
  transition: opacity 0.15s ease, border-color 0.15s ease;
}

.gallery__thumb img { width: 100%; height: 100%; object-fit: cover; }

.gallery__thumb.is-on,
.gallery__thumb:hover { opacity: 1; border-color: #fff; }

.text-white-50 { color: rgb(255 255 255 / 0.6); }
.min-width-0 { min-width: 0; }

@media (prefers-reduced-motion: reduce) {
  .gallery__thumb { transition: none; }
}
</style>
