import { defineStore } from 'pinia'
import { computed, reactive, ref } from 'vue'
import { bookingsApi } from '@/api'

export const useBookingsStore = defineStore('bookings', () => {
  const bookings   = ref([])
  const pagination = ref(null)

  /**
   * Le nombre de réservations par statut, calculé par le serveur.
   *
   * Il ne peut pas se déduire de `bookings` : cette liste est paginée et déjà
   * filtrée. Compter dedans donnait zéro « en attente » dès qu'on filtrait sur
   * « confirmé », et ne voyait jamais au-delà de la première page.
   */
  const counts = ref({ all: 0 })
  const loading    = ref(false)
  const error      = ref('')

  const filters = reactive({ status: '', search: '', date: '' })

  /**
   * Le tri et la pagination, tenus côté serveur.
   *
   * Le tableau ne peut pas trier ce qu'il a sous la main : il n'a qu'une page.
   * Trier localement aurait réordonné vingt lignes sur soixante et donné une
   * réponse fausse à « quelle est ma plus grosse réservation ».
   */
  const query = reactive({ sort: 'date', direction: 'desc', page: 1, perPage: 20 })

  const hasFilters = computed(() => Object.values(filters).some(Boolean))

  async function fetchBookings(params = {}) {
    loading.value = true
    error.value   = ''
    try {
      // Blank filters are dropped rather than sent as empty strings: the API
      // validates `status` against a fixed list, and "" is not on it.
      const requete = Object.fromEntries(
        Object.entries({
          ...filters,
          sort: query.sort,
          direction: query.direction,
          page: query.page,
          per_page: query.perPage,
          ...params,
        }).filter(([, value]) => value !== '' && value != null),
      )

      const response   = await bookingsApi.list(requete)
      bookings.value   = response.data
      counts.value     = response.meta.counts ?? { all: response.meta.total ?? 0 }
      query.page       = response.meta.current_page
      pagination.value = {
        currentPage: response.meta.current_page,
        lastPage:    response.meta.last_page,
        total:       response.meta.total,
        perPage:     response.meta.per_page,
      }
    } catch (e) {
      error.value    = e.message
      bookings.value = []
    } finally {
      loading.value = false
    }
  }

  /**
   * @returns the full response, including the WhatsApp link the API builds
   * when a booking moves to confirmed.
   */
  async function updateStatus(id, status) {
    const response = await bookingsApi.updateStatus(id, status)
    replace(response.booking.data ?? response.booking)
    return response
  }

  async function cancelBooking(id) {
    const response = await bookingsApi.cancel(id)
    replace(response.booking.data ?? response.booking)
  }

  async function exportCsv() {
    const response = await bookingsApi.exportCsv()

    const url  = URL.createObjectURL(new Blob([response.data], { type: 'text/csv;charset=utf-8' }))
    const link = document.createElement('a')
    link.href     = url
    link.download = `reservations-${new Date().toISOString().slice(0, 10)}.csv`
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  }

  /** Swap a row in place, so the list does not jump while the merchant works. */
  function replace(booking) {
    const index = bookings.value.findIndex((b) => b.id === booking.id)
    if (index !== -1) bookings.value[index] = booking
  }

  function resetFilters() {
    Object.assign(filters, { status: '', search: '', date: '' })
    query.page = 1
  }

  return {
    bookings, pagination, counts, loading, error, filters, query, hasFilters,
    fetchBookings, updateStatus, cancelBooking, exportCsv, resetFilters,
  }
})
