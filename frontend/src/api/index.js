import axios from 'axios'

/**
 * The single HTTP client.
 *
 * Everything the app knows about the API's shape lives in this file: the token
 * header, how errors are normalised, and the endpoint list. Views call the
 * named helpers below and never build URLs themselves.
 */
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: { Accept: 'application/json' },
  withCredentials: false,
})

export const TOKEN_KEY = 'nuvo_token'

api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)
  if (token) config.headers.Authorization = `Bearer ${token}`

  // Let the browser set the multipart boundary itself; a hardcoded
  // Content-Type here silently corrupts every file upload.
  if (config.data instanceof FormData) delete config.headers['Content-Type']

  return config
})

/**
 * Turn every failure into the same shape, so views can render `err.message`
 * without a chain of optional lookups at each call site.
 */
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const { response } = error

    if (!response) {
      error.message = 'Connexion impossible. Vérifiez votre réseau.'
      error.fieldErrors = {}
      return Promise.reject(error)
    }

    error.status = response.status
    error.fieldErrors = response.data?.errors ?? {}
    error.message = response.data?.message ?? defaultMessageFor(response.status)
    error.payload = response.data

    // An expired or revoked token: clear it and let the router send the user to
    // the login screen. A hard redirect here would lose the current route, and
    // would fire on public pages that never had a session to begin with.
    if (response.status === 401) localStorage.removeItem(TOKEN_KEY)

    return Promise.reject(error)
  },
)

function defaultMessageFor(status) {
  switch (status) {
    case 401: return 'Session expirée. Reconnectez-vous.'
    case 402: return 'Cette fonctionnalité nécessite un abonnement.'
    case 403: return "Vous n'avez pas accès à cette ressource."
    case 404: return 'Ressource introuvable.'
    case 409: return 'Ce créneau vient d\'être pris.'
    case 429: return 'Trop de tentatives. Patientez un instant.'
    default:  return status >= 500
      ? 'Une erreur est survenue. Réessayez dans un instant.'
      : 'La requête n\'a pas abouti.'
  }
}

export default api

/** Laravel resources wrap single objects in `data`; unwrap once, here. */
const unwrap = (response) => response.data?.data ?? response.data

// ─── Auth ────────────────────────────────────────────────────────────────
export const authApi = {
  register:       (data) => api.post('/auth/register', data).then((r) => r.data),
  login:          (data) => api.post('/auth/login', data).then((r) => r.data),
  googleCallback: (token) => api.post('/auth/google/callback', { token }).then((r) => r.data),
  logout:         () => api.post('/auth/logout'),
  me:             () => api.get('/auth/me').then((r) => r.data),

  updateProfile:  (data) => api.put('/auth/profile', data).then((r) => r.data),
  updatePassword: (data) => api.put('/auth/password', data).then((r) => r.data),
  deleteAccount:  (data) => api.delete('/auth/account', { data }),

  forgotPassword: (email) => api.post('/auth/forgot-password', { email }).then((r) => r.data),
  resetPassword:  (data) => api.post('/auth/reset-password', data).then((r) => r.data),
}

// ─── Business ────────────────────────────────────────────────────────────
export const businessApi = {
  get:    () => api.get('/business').then((r) => r.data),
  update: (data) => api.put('/business', data).then((r) => r.data),

  /**
   * Images cannot travel as JSON, and PUT cannot carry multipart in PHP, hence
   * POST with a _method override, which Laravel unpacks back into a PUT.
   */
  updateWithFiles: (formData) => {
    formData.append('_method', 'PUT')
    return api.post('/business', formData).then((r) => r.data)
  },

  setup: (data) => api.post('/business/setup', data).then((r) => r.data),
}

// ─── Services ────────────────────────────────────────────────────────────
export const servicesApi = {
  list:   () => api.get('/services').then(unwrap),
  create: (formData) => api.post('/services', formData).then(unwrap),
  update: (id, formData) => {
    formData.append('_method', 'PUT')
    return api.post(`/services/${id}`, formData).then(unwrap)
  },
  toggle: (id) => api.patch(`/services/${id}/toggle`).then(unwrap),
  delete: (id) => api.delete(`/services/${id}`),
}

// ─── Bookings ────────────────────────────────────────────────────────────
export const bookingsApi = {
  list:         (params) => api.get('/bookings', { params }).then((r) => r.data),
  get:          (id) => api.get(`/bookings/${id}`).then(unwrap),
  updateStatus: (id, status) => api.patch(`/bookings/${id}/status`, { status }).then((r) => r.data),
  cancel:       (id) => api.delete(`/bookings/${id}`).then((r) => r.data),
  exportCsv:    () => api.get('/bookings/export/csv', { responseType: 'blob' }),
}

// ─── Dashboard ───────────────────────────────────────────────────────────
export const dashboardApi = {
  stats:     () => api.get('/dashboard/stats').then((r) => r.data),
  upcoming:  () => api.get('/dashboard/upcoming').then(unwrap),
  chart:     () => api.get('/dashboard/chart').then((r) => r.data),
  analytics: () => api.get('/dashboard/analytics').then((r) => r.data),
}

// ─── Payments ────────────────────────────────────────────────────────────
export const paymentsApi = {
  plans:        () => api.get('/payments/plans').then((r) => r.data),
  initiate:     (data) => api.post('/payments/initiate', data).then((r) => r.data),
  verify:       (txRef) => api.post('/payments/verify', { tx_ref: txRef }).then((r) => r.data),
  history:      () => api.get('/payments/history').then((r) => r.data),
  subscription: () => api.get('/payments/subscription').then((r) => r.data),
}

// ─── Public booking ──────────────────────────────────────────────────────
export const publicApi = {
  getBusiness: (slug) => api.get(`/b/${slug}`).then(unwrap),

  /** Slots depend on the service: a 3h service fits where a 30min one does not. */
  getSlots: (slug, serviceId, date) =>
    api.get(`/b/${slug}/slots`, { params: { service_id: serviceId, date } }).then((r) => r.data.slots),

  /** Per-day remaining capacity, so the calendar can grey out full days. */
  getAvailability: (slug, serviceId, from, to) =>
    api.get(`/b/${slug}/availability`, { params: { service_id: serviceId, from, to } })
      .then((r) => r.data.days),

  book: (slug, data) => api.post(`/b/${slug}/book`, data).then((r) => r.data),

  track:  (reference, phone) => api.post('/track-booking', { reference, phone }).then(unwrap),
  cancel: (reference, phone) => api.post('/cancel-booking', { reference, phone }).then((r) => r.data),
}
