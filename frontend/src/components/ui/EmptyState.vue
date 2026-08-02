<script setup>
/**
 * L'écran vide, traité comme un moment du produit.
 *
 * Six variantes existaient, du simple « Aucun résultat » gris à un bloc
 * complet avec icône et bouton. Un état vide est souvent le premier écran
 * qu'un commerçant voit après son inscription : il mérite de dire quoi faire,
 * pas seulement qu'il n'y a rien.
 */
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  /** Composant d'icône, typiquement un heroicon. */
  icon: { type: [Object, Function], default: null },
  /** Taille du bloc : `sm` dans une carte, `md` en pleine page. */
  size: { type: String, default: 'md' },
})
</script>

<template>
  <div class="empty" :class="`empty--${size}`">
    <div v-if="icon" class="empty__icon">
      <component :is="icon" class="empty__glyph" />
    </div>

    <p class="empty__title">{{ title }}</p>
    <p v-if="description" class="empty__description">{{ description }}</p>

    <!-- L'action qui sort de l'état vide : un bouton, un lien, ce que la vue
         juge pertinent. -->
    <div v-if="$slots.action" class="empty__action">
      <slot name="action" />
    </div>
  </div>
</template>

<style scoped>
.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.empty--sm { padding: 28px 16px; }
.empty--md { padding: 48px 24px; }

.empty__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 18px;
  background: theme('colors.clay.100');
  margin-bottom: 14px;
}

.empty__glyph {
  width: 26px;
  height: 26px;
  color: theme('colors.clay.400');
}

.empty__title {
  font-weight: 600;
  color: theme('colors.clay.900');
  margin: 0;
}

.empty__description {
  margin: 6px 0 0;
  font-size: 14px;
  line-height: 1.5;
  color: theme('colors.clay.600');
  max-width: 34ch;
}

.empty__action {
  margin-top: 18px;
}
</style>
