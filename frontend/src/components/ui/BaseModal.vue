<script setup>
import { onBeforeUnmount, watch } from 'vue'

/**
 * La boîte de dialogue, avec ce que trois copies manuelles avaient oublié.
 *
 * Aucune des implémentations existantes ne fermait sur Échap, ne bloquait le
 * défilement de la page derrière, ni ne portait de rôle ARIA. Sur mobile, la
 * page continuait de défiler sous la modale, un défaut visible.
 */
const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
  /** `sm` pour une confirmation, `md` par défaut, `lg` pour un formulaire. */
  size: { type: String, default: 'md' },
  /** Un clic sur le fond ferme-t-il ? À désactiver sur un formulaire rempli. */
  dismissible: { type: Boolean, default: true },
})

const emit = defineEmits(['close'])

function close() {
  if (props.dismissible) emit('close')
}

function onKeydown(event) {
  if (event.key === 'Escape') close()
}

/**
 * Bloque le défilement de l'arrière-plan tant que la modale est ouverte, et le
 * restaure ensuite, y compris si le composant est démonté pendant qu'elle est
 * ouverte, cas où le blocage resterait sinon actif pour toujours.
 */
watch(
  () => props.open,
  (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : ''
    if (isOpen) {
      document.addEventListener('keydown', onKeydown)
    } else {
      document.removeEventListener('keydown', onKeydown)
    }
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  document.body.style.overflow = ''
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open" class="modal" role="dialog" aria-modal="true" :aria-label="title">
        <div class="modal__scrim" @click="close" />

        <div class="modal__panel" :class="`modal__panel--${size}`">
          <header v-if="title || $slots.header" class="modal__header">
            <slot name="header">
              <h2 class="modal__title">{{ title }}</h2>
            </slot>
          </header>

          <div class="modal__body">
            <slot />
          </div>

          <footer v-if="$slots.footer" class="modal__footer">
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.modal__scrim {
  position: absolute;
  inset: 0;
  background: rgb(31 27 22 / 0.45);
  backdrop-filter: blur(2px);
}

.modal__panel {
  position: relative;
  width: 100%;
  max-height: calc(100vh - 32px);
  display: flex;
  flex-direction: column;
  background: theme('colors.clay.50');
  border-radius: 20px;
  box-shadow: 0 20px 50px rgb(31 27 22 / 0.22);
  overflow: hidden;
}

.modal__panel--sm { max-width: 380px; }
.modal__panel--md { max-width: 520px; }
.modal__panel--lg { max-width: 720px; }

.modal__header {
  padding: 20px 22px 0;
}

.modal__title {
  margin: 0;
  font-size: 17px;
  font-weight: 700;
  color: theme('colors.clay.950');
}

.modal__body {
  padding: 16px 22px 22px;
  overflow-y: auto;
}

.modal__footer {
  padding: 14px 22px;
  border-top: 1px solid theme('colors.clay.200');
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.modal-enter-active,
.modal-leave-active { transition: opacity 0.18s ease; }

.modal-enter-active .modal__panel,
.modal-leave-active .modal__panel { transition: transform 0.18s ease; }

.modal-enter-from,
.modal-leave-to { opacity: 0; }

.modal-enter-from .modal__panel,
.modal-leave-to .modal__panel { transform: scale(0.96) translateY(6px); }

@media (prefers-reduced-motion: reduce) {
  .modal-enter-active .modal__panel,
  .modal-leave-active .modal__panel { transition: none; }
}
</style>
