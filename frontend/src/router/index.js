import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'landing', component: () => import('@/views/LandingPage.vue'), meta: { guestOnly: true } },

    // ─── Auth ────────────────────────────────────────────────────────────
    { path: '/login',    name: 'login',    component: () => import('@/views/auth/LoginView.vue'),    meta: { guestOnly: true } },
    { path: '/register', name: 'register', component: () => import('@/views/auth/RegisterView.vue'), meta: { guestOnly: true } },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('@/views/auth/ForgotPasswordView.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: () => import('@/views/auth/ResetPasswordView.vue'),
      meta: { guestOnly: true },
    },

    // Where a Google signup lands: authenticated, but with no business yet, so
    // it deliberately sits outside the dashboard layout.
    {
      path: '/bienvenue',
      name: 'onboarding',
      component: () => import('@/views/auth/OnboardingView.vue'),
      meta: { requiresAuth: true },
    },

    // ─── Dashboard ───────────────────────────────────────────────────────
    {
      path: '/dashboard',
      component: () => import('@/components/layout/AppLayout.vue'),
      meta: { requiresAuth: true, requiresBusiness: true },
      children: [
        { path: '',          name: 'dashboard', component: () => import('@/views/dashboard/DashboardView.vue') },
        { path: 'bookings',  name: 'bookings',  component: () => import('@/views/dashboard/BookingsView.vue') },
        { path: 'services',  name: 'services',  component: () => import('@/views/dashboard/ServicesView.vue') },
        { path: 'settings',  name: 'settings',  component: () => import('@/views/dashboard/SettingsView.vue') },
        { path: 'billing',   name: 'billing',   component: () => import('@/views/dashboard/BillingView.vue') },
      ],
    },

    // ─── Public ──────────────────────────────────────────────────────────
    { path: '/b/:slug', name: 'public-booking', component: () => import('@/views/public/PublicBookingView.vue') },
    { path: '/track',   name: 'track-booking',  component: () => import('@/views/public/TrackBookingView.vue') },

    {
      path: '/',
      component: () => import('@/views/pages/StaticLayout.vue'),
      children: [
        { path: 'about',   name: 'about',   component: () => import('@/views/pages/AboutView.vue') },
        { path: 'contact', name: 'contact', component: () => import('@/views/pages/ContactView.vue') },
        { path: 'help',    name: 'help',    component: () => import('@/views/pages/HelpView.vue') },
        { path: 'guide',   name: 'guide',   component: () => import('@/views/pages/GuideView.vue') },
        { path: 'terms',   name: 'terms',   component: () => import('@/views/pages/TermsView.vue') },
        { path: 'privacy', name: 'privacy', component: () => import('@/views/pages/PrivacyView.vue') },
      ],
    },

    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/views/NotFoundView.vue') },
  ],

  scrollBehavior(to, from, savedPosition) {
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    return savedPosition ?? { top: 0 }
  },
})

/**
 * One guard, three questions: signed in, business configured, and — for the
 * marketing and auth pages — not signed in already.
 *
 * It awaits auth.init() so a page opened directly by URL is judged on a loaded
 * session rather than on the mere presence of a token in storage.
 */
router.beforeEach(async (to) => {
  const auth = useAuthStore()
  await auth.init()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  // A signed-in merchant who has not finished setup gets sent to it, from
  // anywhere — otherwise every dashboard request comes back 409.
  if (to.meta.requiresBusiness && auth.needsSetup) {
    return { name: 'onboarding' }
  }

  if (to.name === 'onboarding' && auth.isAuthenticated && !auth.needsSetup) {
    return { name: 'dashboard' }
  }

  // The landing page stays readable while signed in — it is the marketing site.
  if (to.meta.guestOnly && auth.isAuthenticated && to.name !== 'landing') {
    return { name: 'dashboard' }
  }

  return true
})

export default router
