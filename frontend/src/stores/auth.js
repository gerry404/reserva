import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  const user     = ref(null)
  const business = ref(null)
  const token    = ref(localStorage.getItem('reserva_token') || null)
  const loading  = ref(false)

  const isAuth      = computed(() => !!token.value)
  const merchantPlan = computed(() => user.value?.plan ?? 'free')
  const isPro       = computed(() => ['pro', 'business'].includes(merchantPlan.value))

  async function init() {
    if (!token.value) return
    try {
      const { data } = await authApi.me()
      user.value     = data.user
      business.value = data.business
    } catch {
      logout()
    }
  }

  async function register(payload) {
    loading.value = true
    try {
      const { data } = await authApi.register(payload)
      setSession(data)
      await router.push({ name: 'dashboard' })
    } finally {
      loading.value = false
    }
  }

  async function login(payload) {
    loading.value = true
    try {
      const { data } = await authApi.login(payload)
      setSession(data)
      const redirect = router.currentRoute.value.query.redirect
      await router.push(redirect || { name: 'dashboard' })
    } finally {
      loading.value = false
    }
  }

  async function loginWithGoogle(googleToken) {
    loading.value = true
    try {
      const { data } = await authApi.googleCallback(googleToken)
      setSession(data)
      if (data.needs_setup) {
        await router.push({ name: 'settings' })
      } else {
        const redirect = router.currentRoute.value.query.redirect
        await router.push(redirect || { name: 'dashboard' })
      }
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try { await authApi.logout() } catch {}
    clearSession()
    await router.push({ name: 'login' })
  }

  function setSession(data) {
    token.value    = data.token
    user.value     = data.user
    business.value = data.business
    localStorage.setItem('reserva_token', data.token)
  }

  function clearSession() {
    token.value    = null
    user.value     = null
    business.value = null
    localStorage.removeItem('reserva_token')
  }

  function updateBusiness(updated) {
    business.value = { ...business.value, ...updated }
  }

  return {
    user, business, token, loading,
    isAuth, merchantPlan, isPro,
    init, register, login, loginWithGoogle, logout, updateBusiness,
  }
})
