import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // Landing
    {
      path: '/',
      name: 'landing',
      component: () => import('@/views/LandingPage.vue'),
      meta: { guest: true },
    },

    // Auth
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/auth/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/auth/RegisterView.vue'),
      meta: { guest: true },
    },

    // Dashboard (protected)
    {
      path: '/dashboard',
      component: () => import('@/components/layout/AppLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => import('@/views/dashboard/DashboardView.vue'),
        },
        {
          path: 'bookings',
          name: 'bookings',
          component: () => import('@/views/dashboard/BookingsView.vue'),
        },
        {
          path: 'services',
          name: 'services',
          component: () => import('@/views/dashboard/ServicesView.vue'),
        },
        {
          path: 'settings',
          name: 'settings',
          component: () => import('@/views/dashboard/SettingsView.vue'),
        },
        {
          path: 'billing',
          name: 'billing',
          component: () => import('@/views/dashboard/BillingView.vue'),
        },
      ],
    },

    // Public booking page
    {
      path: '/b/:slug',
      name: 'public-booking',
      component: () => import('@/views/public/PublicBookingView.vue'),
    },

    // Static pages (with shared layout)
    {
      path: '/',
      component: () => import('@/views/pages/StaticLayout.vue'),
      children: [
        { path: 'about', name: 'about', component: () => import('@/views/pages/AboutView.vue') },
        { path: 'careers', name: 'careers', component: () => import('@/views/pages/CareersView.vue') },
        { path: 'contact', name: 'contact', component: () => import('@/views/pages/ContactView.vue') },
        { path: 'blog', name: 'blog', component: () => import('@/views/pages/BlogView.vue') },
        { path: 'help', name: 'help', component: () => import('@/views/pages/HelpView.vue') },
        { path: 'guide', name: 'guide', component: () => import('@/views/pages/GuideView.vue') },
        { path: 'api-docs', name: 'api-docs', component: () => import('@/views/pages/ApiDocsView.vue') },
        { path: 'terms', name: 'terms', component: () => import('@/views/pages/TermsView.vue') },
        { path: 'privacy', name: 'privacy', component: () => import('@/views/pages/PrivacyView.vue') },
      ],
    },

    // Catch all
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
  scrollBehavior: (to, from, savedPosition) => {
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    if (savedPosition) return savedPosition
    return { top: 0 }
  },
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('reserva_token')
  const isAuth = !!token

  if (to.meta.requiresAuth && !isAuth) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  if (to.meta.guest && isAuth && to.name !== 'landing') {
    return next({ name: 'dashboard' })
  }

  next()
})

export default router
