<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import PwaInstallPrompt from '@/components/PwaInstallPrompt.vue'

const auth = useAuthStore()
onMounted(() => auth.init())
</script>

<template>
  <RouterView />
  <PwaInstallPrompt />
</template>
