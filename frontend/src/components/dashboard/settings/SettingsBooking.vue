<script setup>
import { computed } from 'vue'

/**
 * Les règles de réservation et les horaires d'ouverture.
 *
 * Ce que le commerçant règle ici, le moteur de disponibilité l'applique sans
 * qu'il ait à y penser : un créneau n'apparaît que si la prestation tient
 * entière avant la fermeture, et que le délai minimum est respecté.
 */
const props = defineProps({
  form: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
})

const JOURS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche']

const CRENEAUX = [
  { value: 15, title: '15 min' }, { value: 30, title: '30 min' },
  { value: 45, title: '45 min' }, { value: 60, title: '1 heure' },
  { value: 90, title: '1 h 30' }, { value: 120, title: '2 heures' },
]

const DELAIS = [
  { value: 0, title: 'Immédiatement' },
  { value: 30, title: '30 minutes avant' },
  { value: 60, title: '1 heure avant' },
  { value: 120, title: '2 heures avant' },
  { value: 240, title: '4 heures avant' },
  { value: 1440, title: '24 heures avant' },
]

/** De 6 h à 22 h par pas de trente minutes. */
const HEURES = computed(() => {
  const liste = []
  for (let h = 6; h <= 22; h++) {
    for (let m = 0; m < 60; m += 30) {
      liste.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`)
    }
  }
  return liste
})

/**
 * Une journée dont la fermeture précède l'ouverture ne produit aucun créneau.
 *
 * Le serveur la refuse, mais après l'envoi : le signaler ici évite au
 * commerçant d'attendre un aller-retour pour apprendre qu'il s'est trompé de
 * champ.
 */
function incoherent(jour) {
  const config = props.form.working_hours?.[jour]
  return Boolean(config?.is_open && config.open && config.close && config.close <= config.open)
}

const jourFautif = computed(() => JOURS.find(incoherent) ?? null)

function erreur(champ) {
  return props.errors?.[champ]?.[0]
}
</script>

<template>
  <div class="d-flex flex-column ga-5">
    <v-card class="pa-5">
      <p class="section__label">Règles de réservation</p>

      <div class="d-flex flex-wrap ga-4">
        <v-select
          v-model="form.slot_duration"
          :items="CRENEAUX"
          label="Pas des créneaux"
          class="flex-grow-1"
          style="min-width: 220px"
          :error-messages="erreur('slot_duration')"
          hint="L'écart entre deux heures proposées."
          persistent-hint
        />
        <v-select
          v-model="form.booking_notice"
          :items="DELAIS"
          label="Délai minimum"
          class="flex-grow-1"
          style="min-width: 220px"
          :error-messages="erreur('booking_notice')"
          hint="Le temps qu'il vous faut avant un rendez-vous."
          persistent-hint
        />
      </div>
    </v-card>

    <v-card class="pa-5">
      <p class="section__label">Horaires d'ouverture</p>

      <v-alert v-if="jourFautif" type="warning" density="compact" class="mb-4 text-body-2">
        Le {{ jourFautif }}, la fermeture précède l'ouverture : aucun créneau ne
        sera proposé ce jour-là.
      </v-alert>

      <div class="d-flex flex-column ga-3">
        <div v-for="jour in JOURS" :key="jour" class="jour">
          <v-switch
            :model-value="form.working_hours[jour]?.is_open ?? false"
            hide-details
            density="compact"
            class="flex-grow-0"
            :aria-label="`Ouvert le ${jour}`"
            @update:model-value="form.working_hours[jour].is_open = $event"
          />

          <span class="jour__nom">{{ jour }}</span>

          <template v-if="form.working_hours[jour]?.is_open">
            <v-select
              v-model="form.working_hours[jour].open"
              :items="HEURES"
              density="compact"
              class="jour__heure"
              :aria-label="`Heure d'ouverture le ${jour}`"
            />
            <span class="text-medium-emphasis text-body-2">à</span>
            <v-select
              v-model="form.working_hours[jour].close"
              :items="HEURES"
              density="compact"
              class="jour__heure"
              :error="incoherent(jour)"
              :aria-label="`Heure de fermeture le ${jour}`"
            />
          </template>
          <span v-else class="text-body-2 text-medium-emphasis font-italic">Fermé</span>
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

.jour {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.jour__nom {
  width: 88px;
  flex-shrink: 0;
  font-size: 14px;
  font-weight: 500;
  text-transform: capitalize;
}

.jour__heure {
  flex: 0 1 128px;
  min-width: 112px;
}
</style>
