import { defineStore } from 'pinia'
import { ref } from 'vue'
import { bookingsApi } from '@/api'

export const useBookingsStore = defineStore('bookings', () => {
  const bookings    = ref([])
  const pagination  = ref(null)
  const loading     = ref(false)
  const filters     = ref({ status: '', search: '', date: '' })

  async function fetchBookings(params = {}) {
    loading.value = true
    try {
      const { data } = await bookingsApi.list({ ...filters.value, ...params })
      bookings.value   = data.data
      pagination.value = {
        current_page: data.current_page,
        last_page:    data.last_page,
        total:        data.total,
        per_page:     data.per_page,
      }
    } finally {
      loading.value = false
    }
  }

  async function updateStatus(id, status) {
    const { data } = await bookingsApi.updateStatus(id, status)
    const idx = bookings.value.findIndex(b => b.id === id)
    if (idx !== -1) bookings.value[idx] = data
    return data
  }

  async function cancelBooking(id) {
    await bookingsApi.cancel(id)
    const idx = bookings.value.findIndex(b => b.id === id)
    if (idx !== -1) bookings.value[idx].status = 'cancelled'
  }

  async function exportCsv() {
    const { data } = await bookingsApi.exportCsv()
    const url  = URL.createObjectURL(new Blob([data]))
    const link = document.createElement('a')
    link.href  = url
    link.setAttribute('download', `reservations-${new Date().toISOString().slice(0, 10)}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  }

  function setFilter(key, value) {
    filters.value[key] = value
  }

  function resetFilters() {
    filters.value = { status: '', search: '', date: '' }
  }

  return {
    bookings, pagination, loading, filters,
    fetchBookings, updateStatus, cancelBooking, exportCsv,
    setFilter, resetFilters,
  }
})
