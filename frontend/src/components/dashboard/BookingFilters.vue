<script setup>
import { computed } from 'vue'
import { Search, X } from 'lucide-vue-next'
import { STATUS_FILTER_OPTIONS } from '@/constants/bookingStatus'

/**
 * Les compteurs par statut et les filtres de la liste des réservations.
 *
 * Sortis de la vue, qui mélangeait ce bandeau, le tableau, le panneau de
 * détail, deux menus et une boîte de dialogue dans un seul fichier de plus de
 * quatre cents lignes.
 */
const props = defineProps({
  filters: { type: Object, required: true },
  counts: { type: Object, required: true },
})

const emit = defineEmits(['reset'])

/**
 * Les pastilles cliquables.
 *
 * `null` sur la première : « au total » relâche le filtre au lieu d'en poser
 * un, et le distinguer d'un statut évite une condition dans le gabarit.
 */
const pills = computed(() => [
  { statut: '', label: 'au total', valeur: props.counts.all ?? 0, couleur: 'clay-900' },
  { statut: 'pending', label: 'en attente', valeur: props.counts.pending ?? 0, couleur: 'warning' },
  { statut: 'confirmed', label: 'confirmés', valeur: props.counts.confirmed ?? 0, couleur: 'success' },
])

const actifs = computed(() => Boolean(props.filters.search || props.filters.status || props.filters.date))

/** Un second clic sur la pastille active relâche le filtre. */
function basculer(statut) {
  props.filters.status = props.filters.status === statut ? '' : statut
}

/**
 * Le pont entre le filtre, qui porte une date ISO, et le calendrier, qui parle
 * en objets `Date`.
 *
 * La conversion passe par l'heure locale et non par `toISOString()` seul : à
 * l'ouest de Greenwich, une date choisie le 14 repartait en « 13 » une fois
 * ramenée en UTC.
 */
const dateChoisie = computed({
  get: () => (props.filters.date ? new Date(`${props.filters.date}T00:00:00`) : null),
  set: (valeur) => {
    if (!valeur) {
      props.filters.date = ''
      return
    }
    const local = new Date(valeur.getTime() - valeur.getTimezoneOffset() * 60000)
    props.filters.date = local.toISOString().slice(0, 10)
  },
})
</script>

<template>
  <div class="d-flex flex-column ga-4">
    <div class="d-flex flex-wrap ga-2">
      <v-chip
        v-for="pill in pills"
        :key="pill.label"
        :color="pill.couleur"
        :variant="filters.status === pill.statut ? 'flat' : 'tonal'"
        size="default"
        class="font-weight-medium"
        @click="basculer(pill.statut)"
      >
        <span class="font-weight-bold mr-1">{{ pill.valeur }}</span>
        {{ pill.label }}
      </v-chip>
    </div>

    <div class="d-flex flex-wrap ga-3 align-center">
      <v-text-field
        v-model="filters.search"
        placeholder="Rechercher un client, une référence…"
        clearable
        class="filters__search"
        aria-label="Rechercher une réservation"
      >
        <template #prepend-inner><Search :size="17" class="text-medium-emphasis" /></template>
      </v-text-field>

      <v-select
        v-model="filters.status"
        :items="STATUS_FILTER_OPTIONS"
        item-title="label"
        item-value="value"
        placeholder="Tous les statuts"
        class="filters__status"
        aria-label="Filtrer par statut"
      />

      <v-menu :close-on-content-click="false">
        <template #activator="{ props: activator }">
          <v-text-field
            v-bind="activator"
            :model-value="filters.date"
            placeholder="Toutes les dates"
            readonly
            class="filters__date"
            aria-label="Filtrer par date"
          />
        </template>
        <v-date-picker v-model="dateChoisie" hide-header />
      </v-menu>

      <v-btn
        v-if="actifs"
        variant="text"
        class="text-none"
        @click="emit('reset')"
      >
        <X :size="16" class="mr-1" />
        Réinitialiser
      </v-btn>
    </div>
  </div>
</template>

<style scoped>
.filters__search { flex: 1 1 240px; min-width: 220px; }
.filters__status { flex: 0 1 200px; }
.filters__date   { flex: 0 1 200px; }
</style>
