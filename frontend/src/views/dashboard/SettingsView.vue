<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Bell, Clock, Palette, Store } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { businessApi } from '@/api'
import { defaultAccent } from '@/design/tokens'
import SettingsCommerce from '@/components/dashboard/settings/SettingsCommerce.vue'
import SettingsBranding from '@/components/dashboard/settings/SettingsBranding.vue'
import SettingsBooking from '@/components/dashboard/settings/SettingsBooking.vue'
import SettingsNotifications from '@/components/dashboard/settings/SettingsNotifications.vue'

/**
 * Les paramètres du commerce.
 *
 * Quatre sujets sans rapport se suivaient dans un même défilement de cinq
 * cents lignes : l'adresse publique, l'apparence, les règles de réservation et
 * les notifications. Ils passent en onglets, et chacun vit dans son fichier.
 */

const auth = useAuthStore()

const onglet = ref('commerce')
const saving = ref(false)
const saved = ref(false)
const errors = ref({})
const saveError = ref('')

const logoFile = ref(null)
const coverFile = ref(null)
const logoPreview = ref(null)
const coverPreview = ref(null)

// `window` n'est pas exposé aux gabarits : le lire là-bas levait une exception
// avant que la page puisse rendre. Résolu une fois, ici.
const origin = window.location.origin

// Calculé, et non figé au montage : au rafraîchissement, le commerce est encore
// en cours de chargement quand ce fichier s'exécute, et un instantané aurait
// gelé « /b/undefined ».
const publicUrl = computed(() =>
  auth.business?.slug ? `${origin}/b/${auth.business.slug}` : '',
)

const JOURS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche']

const form = reactive({
  name: '', slug: '', description: '', category: '', city: '', address: '',
  country: 'CM', phone: '', whatsapp: '',
  slot_duration: 30, booking_notice: 60,
  notifications_whatsapp: true, notifications_sms: false, notifications_email: true,
  accent_color: defaultAccent,
  working_hours: horairesParDefaut(),
})

const ONGLETS = [
  { valeur: 'commerce', label: 'Commerce', icone: Store },
  { valeur: 'apparence', label: 'Apparence', icone: Palette },
  { valeur: 'reservation', label: 'Réservation', icone: Clock },
  { valeur: 'notifications', label: 'Notifications', icone: Bell },
]

function horairesParDefaut() {
  return Object.fromEntries(JOURS.map((jour) => [jour, {
    is_open: jour !== 'dimanche',
    open: '08:00',
    close: '18:00',
  }]))
}

/**
 * Remplit le formulaire depuis le store.
 *
 * Observé plutôt que lu une fois au montage : au rafraîchissement, la session
 * charge encore quand ce composant se monte, et une lecture unique produisait
 * un formulaire vide qui, à l'enregistrement, écrasait les réglages du
 * commerçant avec du blanc.
 */
watch(() => auth.business, (business) => {
  if (!business) return

  Object.assign(form, {
    name: business.name ?? '',
    slug: business.slug ?? '',
    description: business.description ?? '',
    category: business.category ?? '',
    city: business.city ?? '',
    address: business.address ?? '',
    country: business.country ?? 'CM',
    phone: business.phone ?? '',
    whatsapp: business.whatsapp ?? '',
    slot_duration: business.slot_duration ?? 30,
    booking_notice: business.booking_notice ?? 60,
    notifications_whatsapp: business.notifications_whatsapp ?? true,
    notifications_sms: business.notifications_sms ?? false,
    notifications_email: business.notifications_email ?? true,
    accent_color: business.accent_color ?? defaultAccent,
    working_hours: business.working_hours ?? horairesParDefaut(),
  })

  logoPreview.value = business.logo_url ?? null
  coverPreview.value = business.cover_image_url ?? null
}, { immediate: true })

function versFormData() {
  const donnees = new FormData()

  for (const [cle, valeur] of Object.entries(form)) {
    if (cle === 'working_hours') donnees.append(cle, JSON.stringify(valeur))
    else if (typeof valeur === 'boolean') donnees.append(cle, valeur ? '1' : '0')
    else donnees.append(cle, valeur ?? '')
  }

  if (logoFile.value) donnees.append('logo', logoFile.value)
  if (coverFile.value) donnees.append('cover_image', coverFile.value)

  return donnees
}

function choisirLogo(fichier) {
  logoFile.value = fichier
  logoPreview.value = URL.createObjectURL(fichier)
}

function choisirCouverture(fichier) {
  coverFile.value = fichier
  coverPreview.value = URL.createObjectURL(fichier)
}

async function enregistrer() {
  errors.value = {}
  saveError.value = ''
  saving.value = true

  try {
    const avecFichiers = logoFile.value || coverFile.value
    const business = avecFichiers
      ? await businessApi.updateWithFiles(versFormData())
      : await businessApi.update(form)

    auth.setBusiness(business)
    logoFile.value = null
    coverFile.value = null

    saved.value = true
    setTimeout(() => { saved.value = false }, 3000)
  } catch (e) {
    errors.value = e.fieldErrors ?? {}
    // Un 402 signale un champ réservé au plan Pro : le message dit lequel.
    if (!Object.keys(errors.value).length) saveError.value = e.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="d-flex flex-column ga-6">
    <div>
      <h2 class="page__title">Paramètres</h2>
      <p class="text-body-2 text-medium-emphasis mb-0">
        Configurez votre commerce et vos préférences
      </p>
    </div>

    <v-tabs v-model="onglet" color="primary" density="comfortable" class="tabs">
      <v-tab v-for="o in ONGLETS" :key="o.valeur" :value="o.valeur" class="text-none">
        <component :is="o.icone" :size="16" class="mr-2" />
        {{ o.label }}
      </v-tab>
    </v-tabs>

    <v-window v-model="onglet" class="window">
      <v-window-item value="commerce">
        <SettingsCommerce
          :form="form"
          :errors="errors"
          :public-url="publicUrl"
          :is-pro="auth.isPro"
        />
      </v-window-item>

      <v-window-item value="apparence">
        <SettingsBranding
          :form="form"
          :logo-preview="logoPreview"
          :cover-preview="coverPreview"
          @logo="choisirLogo"
          @cover="choisirCouverture"
        />
      </v-window-item>

      <v-window-item value="reservation">
        <SettingsBooking :form="form" :errors="errors" />
      </v-window-item>

      <v-window-item value="notifications">
        <SettingsNotifications :form="form" />
      </v-window-item>
    </v-window>

    <!-- Les erreurs qui n'appartiennent à aucun champ : une fonction réservée
         au plan Pro, par exemple. -->
    <v-alert v-if="saveError" type="error" closable @click:close="saveError = ''">
      {{ saveError }}
    </v-alert>

    <!--
      La barre d'enregistrement colle au bas de la fenêtre. Posée en fin de
      page, elle était hors de vue depuis trois des quatre sections, et le
      commerçant devait redescendre pour valider.
    -->
    <div class="savebar">
      <v-btn
        color="primary"
        variant="flat"
        size="large"
        class="text-none px-8"
        :loading="saving"
        @click="enregistrer"
      >
        Enregistrer
      </v-btn>

      <v-fade-transition>
        <span v-if="saved" class="savebar__ok">Enregistré</span>
      </v-fade-transition>
    </div>
  </div>
</template>

<style scoped>
.page__title {
  font-family: 'Dekatron', Roboto, sans-serif;
  font-size: 22px;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--forest-950);
  margin: 0 0 2px;
}

.tabs {
  border-bottom: 1px solid var(--clay-200);
}

/* v-window rogne ce qui dépasse : sans cela, le panneau d'une liste déroulante
   ouverte près du bord était coupé. */
.window { overflow: visible; }
.window :deep(.v-window__container) { overflow: visible; }

.savebar {
  position: sticky;
  bottom: 0;
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 0;
  background: linear-gradient(to top, var(--clay-100) 70%, transparent);
}

.savebar__ok {
  font-size: 14px;
  font-weight: 600;
  color: var(--forest-600);
}
</style>
