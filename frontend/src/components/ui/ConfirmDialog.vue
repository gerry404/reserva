<script setup>
/**
 * La demande de confirmation avant une action conséquente.
 *
 * Le tableau de bord en portait trois copies : une dans les réservations, une
 * dans les services, une dans les paramètres, chacune avec son propre balisage
 * de superposition, sa propre transition et son propre libellé de bouton. La
 * troisième ne fermait pas sur Échap.
 *
 * `v-dialog` apporte le piège à focus, la fermeture sur Échap et le verrou de
 * défilement, qu'aucune des trois n'avait toutes les trois.
 */
defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, required: true },
  message: { type: String, default: '' },
  /** Le libellé du bouton qui engage. Jamais « OK » : il ne dit pas ce qui va arriver. */
  confirmLabel: { type: String, default: 'Confirmer' },
  cancelLabel: { type: String, default: 'Annuler' },
  /** `error` pour une action destructrice, `primary` sinon. */
  tone: { type: String, default: 'primary' },
  loading: { type: Boolean, default: false },
})

defineEmits(['update:modelValue', 'confirm'])
</script>

<template>
  <v-dialog
    :model-value="modelValue"
    max-width="440"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card rounded="surface" class="pa-2">
      <v-card-title class="text-h6 font-weight-bold pt-4">{{ title }}</v-card-title>

      <v-card-text v-if="message" class="text-body-2 text-medium-emphasis pb-2">
        {{ message }}
      </v-card-text>

      <v-card-actions class="pa-4 pt-2 ga-2">
        <v-spacer />
        <v-btn
          variant="text"
          class="text-none"
          :disabled="loading"
          @click="$emit('update:modelValue', false)"
        >
          {{ cancelLabel }}
        </v-btn>
        <v-btn
          :color="tone"
          variant="flat"
          class="text-none px-5"
          :loading="loading"
          @click="$emit('confirm')"
        >
          {{ confirmLabel }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
