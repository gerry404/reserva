<script setup>
import BrandIcon from '@/components/ui/BrandIcon.vue'
import { Mail, MessageSquare } from 'lucide-vue-next'

/**
 * Par où le commerçant veut être prévenu.
 *
 * Les trois bascules étaient trois blocs recopiés, chacun avec sa piste, son
 * curseur et sa translation écrites à la main dans les classes. Elles se
 * décrivent maintenant en données.
 */
defineProps({
  form: { type: Object, required: true },
})

const CANAUX = [
  {
    champ: 'notifications_whatsapp',
    titre: 'WhatsApp',
    detail: 'Chaque réservation arrive sur votre téléphone.',
    marque: 'whatsapp',
  },
  {
    champ: 'notifications_email',
    titre: 'Email',
    detail: 'Une trace écrite, utile pour retrouver une réservation.',
    icone: Mail,
  },
  {
    champ: 'notifications_sms',
    titre: 'SMS',
    detail: "Passe même sans connexion de données, via Africa's Talking.",
    icone: MessageSquare,
  },
]
</script>

<template>
  <v-card class="pa-5">
    <p class="section__label">Notifications</p>

    <div class="d-flex flex-column ga-1">
      <label v-for="canal in CANAUX" :key="canal.champ" class="canal">
        <span class="canal__icone">
          <BrandIcon v-if="canal.marque" :name="canal.marque" style="width: 18px; height: 18px" />
          <component :is="canal.icone" v-else :size="18" />
        </span>

        <span class="flex-grow-1 min-width-0">
          <span class="d-block text-body-2 font-weight-semibold">{{ canal.titre }}</span>
          <span class="d-block text-caption text-medium-emphasis">{{ canal.detail }}</span>
        </span>

        <v-switch
          v-model="form[canal.champ]"
          hide-details
          density="compact"
          class="flex-grow-0"
          :aria-label="canal.titre"
        />
      </label>
    </div>
  </v-card>
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

.canal {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 10px 4px;
  cursor: pointer;
}

.canal + .canal { border-top: 1px solid var(--clay-100); }

.canal__icone {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  border-radius: 12px;
  background: var(--clay-100);
  color: var(--clay-600);
}

.min-width-0 { min-width: 0; }
</style>
