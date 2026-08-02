<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import PwaInstallPrompt from '@/components/PwaInstallPrompt.vue'

const route = useRoute()

/**
 * The install prompt belongs to merchants, not to their customers.
 *
 * It used to render on every route, so someone booking a haircut was invited to
 * install "Réserva" — an app whose start_url is a dashboard they have no
 * account for. It now appears only inside the merchant area.
 */
const showInstallPrompt = computed(() => route.path.startsWith('/dashboard'))
</script>

<template>
  <!--
    The session is loaded by the router guard before the first route resolves,
    so views can read the store on mount without racing it.
  -->
  <RouterView />
  <PwaInstallPrompt v-if="showInstallPrompt" />
</template>
