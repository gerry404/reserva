<script setup>
import { computed } from 'vue'
import { Check } from 'lucide-vue-next'

/**
 * Une offre, dans la grille des plans.
 *
 * Les cartes étaient trois blocs recopiés, avec chacun son dégradé écrit en
 * classes utilitaires — dont un `to-violet-600` hérité de l'ancienne identité,
 * que le remappage des jetons rendait vert sans que personne le sache.
 */
const props = defineProps({
  plan: { type: Object, required: true },
  cycle: { type: String, default: 'monthly' },
  /** Le plan effectivement en cours, échéance comprise. */
  current: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
})

defineEmits(['choose'])

const prix = computed(() =>
  props.cycle === 'yearly' ? props.plan.yearly : props.plan.monthly,
)

const periode = computed(() => (props.cycle === 'yearly' ? 'par an' : 'par mois'))

/** L'économie annuelle, arrondie : au centime près elle ne dit rien de plus. */
const economie = computed(() => {
  if (!props.plan.monthly || !props.plan.yearly) return 0
  return Math.round((1 - props.plan.yearly / (props.plan.monthly * 12)) * 100)
})
</script>

<template>
  <v-card :class="['h-100 d-flex flex-column pa-6', { 'plan--on': current, 'plan--star': plan.highlighted }]">
    <div class="d-flex align-center ga-3">
      <span class="plan__icon" :class="`bg-${plan.tone}`">
        <component :is="plan.icon" :size="19" />
      </span>
      <div>
        <h3 class="text-h6 font-weight-bold mb-0">{{ plan.name }}</h3>
        <p class="text-caption text-medium-emphasis mb-0">{{ plan.tagline }}</p>
      </div>
      <v-spacer />
      <v-chip v-if="current" color="success" size="small" variant="flat">Actuel</v-chip>
    </div>

    <div class="mt-5 d-flex align-baseline ga-2">
      <span class="plan__price numeric">{{ Number(prix).toLocaleString('fr-FR') }}</span>
      <span class="text-body-2 text-medium-emphasis">F CFA {{ periode }}</span>
    </div>

    <p v-if="cycle === 'yearly' && economie > 0" class="text-caption text-success font-weight-bold mt-1 mb-0">
      <span class="numeric-inline">{{ economie }}</span> % d'économie sur l'année
    </p>
    <p v-else class="text-caption text-medium-emphasis mt-1 mb-0">&nbsp;</p>

    <v-divider class="my-5" />

    <ul class="plan__features flex-grow-1">
      <li v-for="f in plan.features" :key="f">
        <Check :size="15" class="plan__check" />
        <span class="text-body-2">{{ f }}</span>
      </li>
    </ul>

    <v-btn
      v-if="current"
      disabled
      variant="tonal"
      block
      class="text-none mt-6"
    >
      Votre plan actuel
    </v-btn>
    <v-btn
      v-else-if="plan.id"
      :color="plan.tone"
      variant="flat"
      block
      class="text-none mt-6"
      :loading="loading"
      @click="$emit('choose', plan.id)"
    >
      Passer au plan {{ plan.name }}
    </v-btn>
  </v-card>
</template>

<style scoped>
.plan--on { border-color: var(--forest-400); }

.plan--star {
  border-color: var(--forest-600);
  box-shadow: 0 0 0 1px var(--forest-600);
}

.plan__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  flex-shrink: 0;
  border-radius: 13px;
  color: #fff;
}

.plan__price {
  font-size: 32px;
  font-weight: 800;
  line-height: 1;
  color: var(--forest-950);
}

.plan__features {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 10px;
}

.plan__features li {
  display: flex;
  align-items: flex-start;
  gap: 9px;
}

.plan__check {
  flex-shrink: 0;
  margin-top: 3px;
  color: var(--forest-600);
}
</style>
