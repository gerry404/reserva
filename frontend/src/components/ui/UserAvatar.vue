<script setup>
import { computed } from 'vue'

/**
 * L'initiale d'une personne ou d'un commerce.
 *
 * Cinq implémentations coexistaient, chacune avec sa taille, son dégradé et sa
 * gestion — ou son absence de gestion — des noms vides.
 */
const props = defineProps({
  name: { type: String, default: '' },
  /** Photo, si elle existe : elle prime toujours sur l'initiale. */
  src: { type: String, default: null },
  /** Teinte imposée — celle d'un service, par exemple. */
  color: { type: String, default: null },
  size: { type: String, default: 'md' },
})

const dimensions = { xs: '28px', sm: '34px', md: '40px', lg: '48px', xl: '80px' }
const fontSizes = { xs: '11px', sm: '13px', md: '15px', lg: '17px', xl: '30px' }

/**
 * L'initiale.
 *
 * `trim` d'abord : un nom saisi avec une espace initiale — cas courant sur un
 * clavier tactile — produisait une pastille vide.
 */
const initial = computed(() => {
  const clean = String(props.name ?? '').trim()
  return clean ? clean.charAt(0).toUpperCase() : '?'
})

const style = computed(() => ({
  '--avatar-size': dimensions[props.size] ?? dimensions.md,
  '--avatar-font': fontSizes[props.size] ?? fontSizes.md,
  ...(props.color ? { '--avatar-bg': props.color } : {}),
}))
</script>

<template>
  <span class="avatar" :class="{ 'avatar--tinted': color }" :style="style">
    <img v-if="src" :src="src" :alt="name" class="avatar__image" />
    <template v-else>{{ initial }}</template>
  </span>
</template>

<style scoped>
.avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: var(--avatar-size);
  height: var(--avatar-size);
  border-radius: 999px;
  overflow: hidden;
  flex-shrink: 0;
  font-size: var(--avatar-font);
  font-weight: 700;
  color: #fff;
  background: linear-gradient(
    135deg,
    theme('colors.primary.500'),
    theme('colors.primary.700')
  );
}

.avatar--tinted {
  background: var(--avatar-bg);
}

.avatar__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
