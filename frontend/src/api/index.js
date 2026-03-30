import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
  withCredentials: false,
})

// Attach token to every request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('reserva_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Handle 401 globally
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('reserva_token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api

// ─── Auth ────────────────────────────────────────────────────────────────────
export const authApi = {
  register:       (data) => api.post('/auth/register', data),
  login:          (data) => api.post('/auth/login', data),
  googleCallback: (token) => api.post('/auth/google/callback', { token }),
  logout:         ()     => api.post('/auth/logout'),
  me:             ()     => api.get('/auth/me'),
}

// ─── Business ────────────────────────────────────────────────────────────────
export const businessApi = {
  get:    ()     => api.get('/business'),
  update: (data) => api.put('/business', data),
}

// ─── Services ────────────────────────────────────────────────────────────────
export const servicesApi = {
  list:   ()         => api.get('/services'),
  create: (data)     => api.post('/services', data),
  update: (id, data) => api.put(`/services/${id}`, data),
  toggle: (id)       => api.patch(`/services/${id}/toggle`),
  delete: (id)       => api.delete(`/services/${id}`),
}

// ─── Bookings ─────────────────────────────────────────────────────────────────
export const bookingsApi = {
  list:         (params)       => api.get('/bookings', { params }),
  get:          (id)           => api.get(`/bookings/${id}`),
  updateStatus: (id, status)   => api.patch(`/bookings/${id}/status`, { status }),
  cancel:       (id)           => api.delete(`/bookings/${id}`),
  exportCsv:    ()             => api.get('/bookings/export/csv', { responseType: 'blob' }),
}

// ─── Dashboard ───────────────────────────────────────────────────────────────
export const dashboardApi = {
  stats:    () => api.get('/dashboard/stats'),
  upcoming: () => api.get('/dashboard/upcoming'),
  chart:    () => api.get('/dashboard/chart'),
}

// ─── Public ──────────────────────────────────────────────────────────────────
export const publicApi = {
  getBusiness:  (slug)              => api.get(`/b/${slug}`),
  getSlots:     (slug, date)        => api.get(`/b/${slug}/slots`, { params: { date } }),
  book:         (slug, data)        => api.post(`/b/${slug}/book`, data),
}
