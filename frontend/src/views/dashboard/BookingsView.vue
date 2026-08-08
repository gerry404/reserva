<script setup>
import { computed, ref, watch } from 'vue'
import { format, parseISO } from 'date-fns'
import { fr } from 'date-fns/locale'
import {
  CalendarDays, Check, Download, Ellipsis, Mail, MessageCircle, Phone, X,
} from 'lucide-vue-next'

import { useBookingsStore } from '@/stores/bookings'
import { ACTIONABLE_STATUSES, describeStatus } from '@/constants/bookingStatus'
import { iconForService } from '@/constants/serviceIcons'
import BookingFilters from '@/components/dashboard/BookingFilters.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import DurationBar from '@/components/time/DurationBar.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import UserAvatar from '@/components/ui/UserAvatar.vue'
import BrandIcon from '@/components/ui/BrandIcon.vue'

/**
 * La liste des réservations.
 *
 * Elle était rendue en cartes empilées, sans tri possible et avec une
 * pagination réduite à deux boutons. Le commerçant ne pouvait ni classer par
 * montant, ni remonter au premier rendez-vous d'une série : il lui restait
 * l'export CSV et un tableur.
 *
 * `v-data-table-server` trie et pagine côté serveur. Le tri local aurait
 * réordonné les vingt lignes de la page courante et répondu faux à « ma plus
 * grosse réservation », puisque les autres pages ne sont pas chargées.
 */

const store = useBookingsStore()

const detail = ref(null)
const confirmation = ref(null)
const enCours = ref(false)
const toast = ref(null)

const headers = [
  { title: 'Client', key: 'customer_name', minWidth: '220' },
  { title: 'Prestation', key: 'service', sortable: false, minWidth: '200' },
  { title: 'Quand', key: 'date', minWidth: '170' },
  { title: 'Montant', key: 'price', align: 'end', minWidth: '110' },
  { title: 'Statut', key: 'status', align: 'center', minWidth: '130' },
  { title: '', key: 'actions', sortable: false, align: 'end', width: 60 },
]

/**
 * Le tableau annonce ses changements de page et de tri d'un seul coup.
 *
 * Vuetify émet `update:options` pour les trois à la fois. Écouter chaque prop
 * séparément déclenchait trois requêtes pour un seul clic sur un en-tête.
 */
function onOptions({ page, itemsPerPage, sortBy }) {
  store.query.page = page
  store.query.perPage = itemsPerPage
  store.query.sort = sortBy?.[0]?.key ?? 'date'
  store.query.direction = sortBy?.[0]?.order ?? 'desc'
  store.fetchBookings()
}

/*
 * Les filtres repartent toujours de la première page : filtrer alors qu'on est
 * en page 3 renvoyait une page vide, qui se lisait comme « aucun résultat ».
 */
let rechercheTimer = null
watch(() => store.filters.search, () => {
  clearTimeout(rechercheTimer)
  rechercheTimer = setTimeout(() => {
    store.query.page = 1
    store.fetchBookings()
  }, 400)
})

watch(() => [store.filters.status, store.filters.date], () => {
  store.query.page = 1
  store.fetchBookings()
})

const sortBy = computed(() => [{ key: store.query.sort, order: store.query.direction }])

function jourCourt(iso) {
  try { return format(parseISO(iso), 'EEE d MMM', { locale: fr }) } catch { return iso }
}

function jourLong(iso) {
  try { return format(parseISO(iso), 'EEEE d MMMM yyyy', { locale: fr }) } catch { return iso }
}

/** Les transitions offertes pour une réservation, selon son statut courant. */
function transitions(booking) {
  const libelles = {
    confirmed: { label: 'Confirmer', icon: Check, tone: 'success' },
    completed: { label: 'Marquer terminée', icon: Check, tone: 'info' },
    no_show:   { label: 'Noter non présentée', icon: X, tone: 'warning' },
    cancelled: { label: 'Annuler', icon: X, tone: 'error' },
  }

  return ACTIONABLE_STATUSES
    .filter((statut) => statut !== booking.status)
    .map((statut) => ({ statut, ...libelles[statut] }))
}

function demander(booking, statut) {
  const { customer } = describeStatus(statut)
  confirmation.value = {
    booking,
    statut,
    titre: `Passer la réservation en « ${customer} » ?`,
    destructif: statut === 'cancelled',
  }
}

async function appliquer() {
  const { booking, statut } = confirmation.value
  enCours.value = true

  try {
    if (statut === 'cancelled') {
      await store.cancelBooking(booking.id)
    } else {
      const reponse = await store.updateStatus(booking.id, statut)
      const ligne = reponse.booking.data ?? reponse.booking

      if (statut === 'confirmed' && reponse.whatsapp_link) {
        toast.value = {
          message: ligne.customer_email
            ? `Confirmée. Un email est parti chez ${ligne.customer_name}.`
            : `Confirmée. Prévenez ${ligne.customer_name} sur WhatsApp :`,
          lien: reponse.whatsapp_link,
        }
      }
    }

    // Les compteurs par statut viennent du serveur : sans ce rechargement,
    // les pastilles annonceraient encore l'ancienne répartition.
    await store.fetchBookings()
    confirmation.value = null
  } finally {
    enCours.value = false
  }
}
</script>

<template>
  <div class="d-flex flex-column ga-6">
    <div class="d-flex flex-wrap align-center justify-space-between ga-4">
      <div>
        <h2 class="page__title">Réservations</h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Gérez et suivez toutes vos réservations
        </p>
      </div>
      <v-btn variant="tonal" color="primary" class="text-none" @click="store.exportCsv()">
        <Download :size="16" class="mr-2" />
        Exporter CSV
      </v-btn>
    </div>

    <v-alert
      v-if="toast"
      type="success"
      closable
      @click:close="toast = null"
    >
      <div class="d-flex flex-wrap align-center ga-3">
        <span class="text-body-2">{{ toast.message }}</span>
        <v-btn
          v-if="toast.lien"
          :href="toast.lien"
          target="_blank"
          size="small"
          class="btn-whatsapp px-4 text-none"
        >
          <BrandIcon name="whatsapp" class="mr-2" style="width: 16px; height: 16px" />
          Envoyer sur WhatsApp
        </v-btn>
      </div>
    </v-alert>

    <BookingFilters
      :filters="store.filters"
      :counts="store.counts"
      @reset="store.resetFilters(); store.fetchBookings()"
    />

    <v-card>
      <v-data-table-server
        :headers="headers"
        :items="store.bookings"
        :items-length="store.pagination?.total ?? 0"
        :loading="store.loading"
        :page="store.query.page"
        :items-per-page="store.query.perPage"
        :sort-by="sortBy"
        :items-per-page-options="[10, 20, 50, 100]"
        item-value="id"
        must-sort
        items-per-page-text="Par page"
        no-data-text="Aucune réservation ne correspond à ces filtres."
        loading-text="Chargement…"
        @update:options="onOptions"
      >
        <template #[`item.customer_name`]="{ item }">
          <div class="d-flex align-center ga-3 py-2">
            <UserAvatar :name="item.customer_name" :color="item.service?.color" size="sm" />
            <div class="min-width-0">
              <p class="text-body-2 font-weight-bold text-truncate mb-0">{{ item.customer_name }}</p>
              <p class="cell__ref mb-0">{{ item.reference }}</p>
            </div>
          </div>
        </template>

        <template #[`item.service`]="{ item }">
          <div v-if="item.service" class="py-2">
            <div class="d-flex align-center ga-2">
              <component
                :is="iconForService(item.service)"
                :size="15"
                :style="{ color: item.service.color }"
              />
              <span class="text-body-2 text-truncate">{{ item.service.name }}</span>
            </div>
            <!-- La même barre que sur la page publique : une seule échelle de
                 temps dans tout le produit. -->
            <DurationBar
              :minutes="item.duration"
              :color="item.service.color"
              size="sm"
              class="mt-1"
              style="max-width: 150px"
            />
          </div>
          <span v-else class="text-medium-emphasis text-body-2">Service supprimé</span>
        </template>

        <template #[`item.date`]="{ item }">
          <div class="py-2">
            <p class="text-body-2 mb-0">{{ jourCourt(item.date) }}</p>
            <p class="numeric text-caption text-medium-emphasis mb-0">
              {{ item.time_slot }} – {{ item.ends_at_time }}
            </p>
          </div>
        </template>

        <template #[`item.price`]="{ item }">
          <span class="numeric text-body-2 font-weight-semibold">{{ item.formatted_price }}</span>
        </template>

        <template #[`item.status`]="{ item }">
          <StatusBadge :status="item.status" />
        </template>

        <template #[`item.actions`]="{ item }">
          <v-menu location="bottom end">
            <template #activator="{ props: activator }">
              <v-btn
                v-bind="activator"
                icon
                variant="text"
                size="small"
                :aria-label="`Actions pour ${item.customer_name}`"
              >
                <Ellipsis :size="18" />
              </v-btn>
            </template>

            <v-list density="compact" nav min-width="220">
              <v-list-item
                rounded="control"
                @click="detail = detail?.id === item.id ? null : item"
              >
                <v-list-item-title class="text-body-2">
                  {{ detail?.id === item.id ? 'Masquer le détail' : 'Voir le détail' }}
                </v-list-item-title>
              </v-list-item>

              <v-divider class="my-1" />

              <v-list-item
                v-for="t in transitions(item)"
                :key="t.statut"
                rounded="control"
                :base-color="t.tone"
                @click="demander(item, t.statut)"
              >
                <template #prepend><component :is="t.icon" :size="16" class="mr-3" /></template>
                <v-list-item-title class="text-body-2">{{ t.label }}</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>
        </template>
      </v-data-table-server>
    </v-card>

    <!-- Le détail, sous le tableau plutôt qu'inséré dans une ligne : glissé
         entre deux lignes, il décalait tout ce qui suit à chaque ouverture. -->
    <v-expand-transition>
      <v-card v-if="detail" class="pa-5">
        <div class="d-flex align-center justify-space-between ga-4 mb-4">
          <h3 class="text-subtitle-1 font-weight-bold mb-0">
            {{ detail.customer_name }}
          </h3>
          <v-btn icon variant="text" size="small" aria-label="Fermer le détail" @click="detail = null">
            <X :size="18" />
          </v-btn>
        </div>

        <v-row dense>
          <v-col cols="12" sm="6" md="3">
            <p class="detail__label">Téléphone</p>
            <a :href="`tel:${detail.customer_phone}`" class="detail__value">
              <Phone :size="14" class="mr-1 text-medium-emphasis" />{{ detail.customer_phone }}
            </a>
          </v-col>
          <v-col v-if="detail.customer_email" cols="12" sm="6" md="3">
            <p class="detail__label">Email</p>
            <a :href="`mailto:${detail.customer_email}`" class="detail__value text-truncate">
              <Mail :size="14" class="mr-1 text-medium-emphasis" />{{ detail.customer_email }}
            </a>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <p class="detail__label">Date complète</p>
            <p class="detail__value text-capitalize">{{ jourLong(detail.date) }}</p>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <p class="detail__label">Référence</p>
            <p class="detail__value numeric text-primary">{{ detail.reference }}</p>
          </v-col>
        </v-row>

        <v-alert v-if="detail.notes" variant="tonal" color="clay-600" class="mt-4">
          <p class="detail__label mb-1">
            <MessageCircle :size="13" class="mr-1" />Notes du client
          </p>
          <p class="text-body-2 mb-0">{{ detail.notes }}</p>
        </v-alert>
      </v-card>
    </v-expand-transition>

    <ConfirmDialog
      :model-value="Boolean(confirmation)"
      :title="confirmation?.titre ?? ''"
      message="Le client en sera informé."
      :tone="confirmation?.destructif ? 'error' : 'primary'"
      :loading="enCours"
      @update:model-value="confirmation = null"
      @confirm="appliquer"
    />
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

.cell__ref {
  font-family: 'Yuzo', Roboto, monospace;
  font-size: 10px;
  letter-spacing: 0.04em;
  color: var(--clay-400);
}

.detail__label {
  font-size: 11px;
  font-weight: 600;
  color: var(--clay-400);
  margin-bottom: 2px;
}

.detail__value {
  display: inline-flex;
  align-items: center;
  font-size: 14px;
  font-weight: 600;
  color: var(--forest-950);
  text-decoration: none;
}

a.detail__value:hover { color: var(--forest-600); }

.min-width-0 { min-width: 0; }
</style>
