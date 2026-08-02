<script setup>
import { computed } from 'vue'
import { describeStatus } from '@/constants/bookingStatus'

/**
 * L'état d'une réservation, partout pareil.
 *
 * Remplace quatre implémentations divergentes : chacune avait sa propre liste
 * de libellés et de couleurs, et l'ajout du statut `no_show` en avait manqué
 * la moitié.
 */
const props = defineProps({
  status: { type: String, required: true },
  /** `dot` en liste dense, `badge` par défaut, `pill` en vedette. */
  variant: { type: String, default: 'badge' },
  showIcon: { type: Boolean, default: false },
  compact: { type: Boolean, default: false },
  /** Emploie le libellé adressé au client plutôt qu'au commerçant. */
  audience: { type: String, default: 'merchant' },
})

const descriptor = computed(() => describeStatus(props.status))

const label = computed(() => {
  if (props.audience === 'customer') return descriptor.value.customer
  return props.compact ? descriptor.value.short : descriptor.value.label
})

const style = computed(() => ({
  '--tone-bg': descriptor.value.tone.bg,
  '--tone-border': descriptor.value.tone.border,
  '--tone-text': descriptor.value.tone.text,
  '--tone-solid': descriptor.value.tone.solid,
}))
</script>

<template>
  <span v-if="variant === 'dot'" class="status-dot" :style="style" :title="descriptor.label">
    <span class="status-dot__mark" />
    <span class="status-dot__label">{{ label }}</span>
  </span>

  <span v-else class="status-badge" :class="`status-badge--${variant}`" :style="style">
    <span v-if="showIcon" aria-hidden="true">{{ descriptor.icon }}</span>
    {{ label }}
  </span>
</template>

<style scoped>
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 10px;
  border-radius: 999px;
  border: 1px solid var(--tone-border);
  background: var(--tone-bg);
  color: var(--tone-text);
  font-size: 12px;
  font-weight: 600;
  line-height: 1.5;
  white-space: nowrap;
}

.status-badge--pill {
  padding: 5px 14px;
  font-size: 13px;
}

.status-dot {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--tone-text);
}

.status-dot__mark {
  width: 7px;
  height: 7px;
  border-radius: 999px;
  background: var(--tone-solid);
  flex-shrink: 0;
}

.status-dot__label {
  font-weight: 500;
}
</style>
