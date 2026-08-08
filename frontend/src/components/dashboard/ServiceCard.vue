<script setup>
import { computed } from 'vue'
import { Banknote, Clock, Image as ImageIcon, Pencil, Trash2 } from 'lucide-vue-next'
import { iconForService } from '@/constants/serviceIcons'
import { defaultAccent } from '@/design/tokens'
import DurationBar from '@/components/time/DurationBar.vue'

/**
 * Une prestation dans la grille.
 *
 * Sortie de la vue, qui rendait la carte, le formulaire, la visionneuse et la
 * confirmation de suppression dans un seul gabarit de trois cents lignes.
 */
const props = defineProps({
  service: { type: Object, required: true },
})

defineEmits(['edit', 'delete', 'toggle', 'open-gallery'])

const couleur = computed(() => props.service.color ?? defaultAccent)

function prix(valeur) {
  return Number(valeur) === 0 ? 'Gratuit' : `${Number(valeur).toLocaleString('fr-FR')} F`
}

function duree(minutes) {
  if (minutes < 60) return `${minutes} min`
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return m ? `${h} h ${m}` : `${h} h`
}
</script>

<template>
  <v-card :class="['h-100 d-flex flex-column', { 'card--off': !service.is_active }]">
    <div
      v-if="service.images?.length"
      class="card__banner"
      role="button"
      :aria-label="`Voir les photos de ${service.name}`"
      @click="$emit('open-gallery', service)"
    >
      <img :src="service.images[0].url" :alt="service.name" />
      <div class="card__banner-veil">
        <ImageIcon :size="22" color="#fff" />
      </div>
      <span v-if="service.images.length > 1" class="card__banner-count">
        +{{ service.images.length - 1 }}
      </span>
    </div>

    <div class="pa-5 d-flex flex-column ga-4 flex-grow-1">
      <div class="d-flex align-start justify-space-between ga-3">
        <div class="d-flex align-center ga-3 min-width-0">
          <span class="card__icon" :style="{ backgroundColor: couleur }">
            <component :is="iconForService(service)" :size="19" />
          </span>
          <div class="min-width-0">
            <h3 class="text-body-1 font-weight-bold text-truncate mb-0">{{ service.name }}</h3>
            <p v-if="service.category" class="text-caption text-medium-emphasis mb-0">
              {{ service.category }}
            </p>
          </div>
        </div>

        <v-switch
          :model-value="service.is_active"
          hide-details
          density="compact"
          :aria-label="service.is_active ? 'Désactiver ce service' : 'Activer ce service'"
          @update:model-value="$emit('toggle', service)"
        />
      </div>

      <p v-if="service.description" class="text-body-2 text-medium-emphasis mb-0 card__desc">
        {{ service.description }}
      </p>

      <div class="d-flex align-center ga-5">
        <span class="d-flex align-center ga-1 text-body-2">
          <Clock :size="15" class="text-medium-emphasis" />
          <span class="numeric-inline">{{ duree(service.duration) }}</span>
        </span>
        <span class="d-flex align-center ga-1 text-body-2 font-weight-semibold">
          <Banknote :size="15" class="text-medium-emphasis" />
          <span class="numeric-inline">{{ prix(service.price) }}</span>
        </span>
      </div>

      <!-- La même échelle de temps que sur la page publique : la durée se lit
           comme une longueur avant de se lire comme un chiffre. -->
      <DurationBar :minutes="service.duration" :color="couleur" size="sm" />

      <v-spacer />

      <div class="d-flex ga-2">
        <v-btn variant="tonal" color="primary" size="small" class="text-none flex-grow-1" @click="$emit('edit', service)">
          <Pencil :size="15" class="mr-2" />
          Modifier
        </v-btn>
        <v-btn
          variant="text"
          size="small"
          color="error"
          icon
          :aria-label="`Supprimer ${service.name}`"
          @click="$emit('delete', service)"
        >
          <Trash2 :size="16" />
        </v-btn>
      </div>
    </div>
  </v-card>
</template>

<style scoped>
.card--off { opacity: 0.58; }

.card__banner {
  position: relative;
  height: 148px;
  overflow: hidden;
  cursor: pointer;
  background: var(--clay-200);
}

.card__banner img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.35s ease;
}

.card__banner:hover img { transform: scale(1.05); }

.card__banner-veil {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgb(0 0 0 / 0);
  opacity: 0;
  transition: background-color 0.25s ease, opacity 0.25s ease;
}

.card__banner:hover .card__banner-veil {
  background: rgb(0 0 0 / 0.28);
  opacity: 1;
}

.card__banner-count {
  position: absolute;
  right: 10px;
  bottom: 10px;
  padding: 2px 8px;
  border-radius: 999px;
  background: rgb(0 0 0 / 0.6);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
}

.card__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  border-radius: 12px;
  color: #fff;
}

/* Trois lignes puis une coupure : sans borne, une description longue étirait
   sa carte et cassait l'alignement de toute la rangée. */
.card__desc {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.min-width-0 { min-width: 0; }

@media (prefers-reduced-motion: reduce) {
  .card__banner img { transition: none; }
}
</style>
