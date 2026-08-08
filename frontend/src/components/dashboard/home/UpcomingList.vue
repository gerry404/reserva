<script setup>
import { format, parseISO } from 'date-fns'
import { fr } from 'date-fns/locale'
import { CalendarDays, Check, X } from 'lucide-vue-next'
import { RouterLink } from 'vue-router'
import { iconForService } from '@/constants/serviceIcons'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import UserAvatar from '@/components/ui/UserAvatar.vue'

/**
 * Les prochains rendez-vous, avec de quoi les traiter sans changer d'écran.
 *
 * C'est le seul endroit du produit où confirmer prend un clic : la liste des
 * réservations demande d'ouvrir un menu puis de confirmer une intention.
 * L'écart est voulu, ce sont deux gestes différents — expédier le flux du jour
 * d'un côté, revenir sur un cas précis de l'autre.
 */
defineProps({
  bookings: { type: Array, default: () => [] },
  busyId: { type: [Number, String], default: null },
})

defineEmits(['confirm', 'cancel'])

function jour(iso) {
  try { return format(parseISO(iso), 'EEE d MMM', { locale: fr }) } catch { return iso }
}
</script>

<template>
  <v-card class="h-100 d-flex flex-column">
    <div class="d-flex align-center justify-space-between pa-5 pb-3">
      <h3 class="text-subtitle-2 font-weight-bold mb-0">Prochains rendez-vous</h3>
      <v-btn :to="{ name: 'bookings' }" variant="text" size="small" class="text-none">
        Tout voir
      </v-btn>
    </div>

    <v-divider />

    <div v-if="bookings.length === 0" class="empty flex-grow-1">
      <CalendarDays :size="26" />
      <p class="text-body-2 mt-3 mb-0">Rien de prévu pour l'instant</p>
    </div>

    <v-list v-else class="py-0 flex-grow-1 upcoming__list">
      <template v-for="(b, i) in bookings" :key="b.id">
        <v-divider v-if="i > 0" />
        <v-list-item class="px-5 py-3">
          <template #prepend>
            <UserAvatar :name="b.customer_name" :color="b.service?.color" size="sm" class="mr-3" />
          </template>

          <v-list-item-title class="text-body-2 font-weight-bold">
            {{ b.customer_name }}
          </v-list-item-title>

          <v-list-item-subtitle class="d-flex align-center ga-2 mt-1">
            <component
              v-if="b.service"
              :is="iconForService(b.service)"
              :size="13"
              :style="{ color: b.service.color }"
            />
            <span class="text-caption">{{ b.service?.name ?? 'Service supprimé' }}</span>
            <span class="text-caption text-disabled">·</span>
            <span class="text-caption numeric-inline">{{ jour(b.date) }} {{ b.time_slot }}</span>
          </v-list-item-subtitle>

          <template #append>
            <div class="d-flex align-center ga-2">
              <StatusBadge v-if="b.status !== 'pending'" :status="b.status" />

              <template v-else>
                <v-btn
                  icon
                  size="x-small"
                  variant="tonal"
                  color="success"
                  :loading="busyId === b.id"
                  :aria-label="`Confirmer le rendez-vous de ${b.customer_name}`"
                  @click="$emit('confirm', b.id)"
                >
                  <Check :size="15" />
                </v-btn>
                <v-btn
                  icon
                  size="x-small"
                  variant="tonal"
                  color="error"
                  :loading="busyId === b.id"
                  :aria-label="`Annuler le rendez-vous de ${b.customer_name}`"
                  @click="$emit('cancel', b.id)"
                >
                  <X :size="15" />
                </v-btn>
              </template>
            </div>
          </template>
        </v-list-item>
      </template>
    </v-list>
  </v-card>
</template>

<style scoped>
/* Au-delà de six lignes la liste défile : sans plafond, elle imposait sa
   hauteur au graphique voisin, qui se retrouvait avec un grand vide sous ses
   barres. */
.upcoming__list {
  max-height: 430px;
  overflow-y: auto;
}

.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 16px;
  color: var(--clay-400);
}
</style>
