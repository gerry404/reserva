import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { authApi, TOKEN_KEY } from '@/api'
import router from '@/router'

/**
 * The signed-in merchant.
 *
 * `ready` matters more than it looks: on a page refresh the token is in storage
 * but the user is not loaded yet, and views that read auth.business in onMounted
 * used to render an empty form against it. Anything depending on loaded state
 * should await ready() rather than assume.
 */
export const useAuthStore = defineStore('auth', () => {
  const user     = ref(null)
  const business = ref(null)
  const token    = ref(localStorage.getItem(TOKEN_KEY))
  const loading  = ref(false)
  const ready    = ref(false)

  let initPromise = null

  const isAuthenticated = computed(() => !!token.value)
  const plan            = computed(() => user.value?.plan ?? 'free')
  const isPro           = computed(() => ['pro', 'business'].includes(plan.value))
  const onTrial         = computed(() => user.value?.on_trial === true)
  const needsSetup      = computed(() => isAuthenticated.value && ready.value && !business.value)

  const trialDaysLeft = computed(() => {
    if (!onTrial.value || !user.value?.plan_expires_at) return 0
    const ms = new Date(user.value.plan_expires_at) - new Date()
    return Math.max(0, Math.ceil(ms / 86_400_000))
  })

  /** Idempotent: concurrent callers share one in-flight request. */
  function init() {
    if (initPromise) return initPromise

    initPromise = (async () => {
      if (token.value) {
        try {
          applySession(await authApi.me())
        } catch {
          // The token is gone or revoked; drop it without bouncing the user,
          // who may be on a perfectly public page.
          clearSession()
        }
      }
      ready.value = true
    })()

    return initPromise
  }

  async function register(payload) {
    loading.value = true
    try {
      applySession(await authApi.register(payload))
      await router.push({ name: 'dashboard' })
    } finally {
      loading.value = false
    }
  }

  async function login(payload) {
    loading.value = true
    try {
      applySession(await authApi.login(payload))
      await router.push(redirectTarget())
    } finally {
      loading.value = false
    }
  }

  async function loginWithGoogle(googleToken) {
    loading.value = true
    try {
      const data = await authApi.googleCallback(googleToken)
      applySession(data)
      // A Google account arrives without a business; finish signup first.
      await router.push(data.needs_setup ? { name: 'onboarding' } : redirectTarget())
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch {
      // Already invalid server-side; the local session still has to go.
    }
    clearSession()
    await router.push({ name: 'login' })
  }

  async function refresh() {
    if (!token.value) return
    applySession(await authApi.me())
  }

  function applySession(data) {
    if (data.token) {
      token.value = data.token
      localStorage.setItem(TOKEN_KEY, data.token)
    }
    user.value     = data.user ?? null
    business.value = data.business ?? null
    ready.value    = true
  }

  function clearSession() {
    token.value    = null
    user.value     = null
    business.value = null
    localStorage.removeItem(TOKEN_KEY)
  }

  function setBusiness(updated) {
    business.value = updated
  }

  function redirectTarget() {
    const { redirect } = router.currentRoute.value.query
    // Only same-origin paths: a crafted ?redirect=https://… would otherwise
    // bounce a freshly signed-in merchant to somebody else's site.
    return typeof redirect === 'string' && redirect.startsWith('/')
      ? redirect
      : { name: 'dashboard' }
  }

  return {
    user, business, token, loading, ready,
    isAuthenticated, plan, isPro, onTrial, needsSetup, trialDaysLeft,
    init, register, login, loginWithGoogle, logout, refresh, setBusiness, clearSession,
  }
})
