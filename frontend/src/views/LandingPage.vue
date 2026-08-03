<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue'
import { RouterLink } from 'vue-router'
import FinalCta from '@/components/marketing/FinalCta.vue'
import HowItWorks from '@/components/marketing/HowItWorks.vue'
import TradeShowcase from '@/components/marketing/TradeShowcase.vue'
import { useAuthStore } from '@/stores/auth'
import {
  ArrowRight,
  Calendar,
  ChartColumn,
  Check,
  ChevronLeft,
  ChevronRight,
  CircleCheck,
  Globe,
  Lock,
  Smartphone,
  X,
  Zap,
} from 'lucide-vue-next'
import BrandIcon from '@/components/ui/BrandIcon.vue'

const auth = useAuthStore()

const features = [
  {
    icon: Smartphone,
    title: 'WhatsApp & SMS',
    desc: 'Recevez chaque réservation en temps réel sur WhatsApp ou par SMS. Pas besoin de rester collé à votre téléphone.',
    gradient: 'from-emerald-500 to-teal-400',
    bg: 'bg-emerald-500/10',
  },
  {
    icon: Zap,
    title: 'Configuration en 5 min',
    desc: "Créez votre compte, configurez vos services et partagez votre lien. C'est tout. Aucune compétence technique requise.",
    gradient: 'from-amber-500 to-orange-400',
    bg: 'bg-amber-500/10',
  },
  {
    icon: ChartColumn,
    title: 'Statistiques claires',
    desc: 'Visualisez vos revenus, le taux de remplissage et les services les plus demandés depuis votre tableau de bord.',
    gradient: 'from-blue-500 to-cyan-400',
    bg: 'bg-blue-500/10',
  },
  {
    icon: Globe,
    title: 'Pensé pour tous',
    desc: 'Interface en français et anglais, connexion lente tolérée, paiements MTN/Orange Money. Nuvo comprend votre réalité.',
    gradient: 'from-violet-500 to-purple-400',
    bg: 'bg-violet-500/10',
  },
]

// ── Pricing toggle ──
const isAnnual = ref(true) // default annual = more revenue

const plans = computed(() => [
  {
    name: 'Découverte',
    priceMonthly: '0',
    priceAnnual: '0',
    period: 'F CFA',
    desc: 'Pour démarrer sans risque',
    color: '',
    cta: 'Commencer gratuitement',
    ctaClass: 'block w-full py-3 rounded-xl text-sm font-bold text-center border-2 border-gray-200 text-gray-700 hover:border-gray-300 hover:bg-gray-50 transition-all',
    features: [
      'Jusqu\'à 30 réservations/mois',
      'Page de réservation publique',
      'Notifications WhatsApp (wa.me)',
      'Tableau de bord basique',
      'Export CSV',
    ],
    limitations: [
      'Pas de rappels automatiques',
      'Statistiques limitées',
    ],
  },
  {
    name: 'Pro',
    priceMonthly: '2 900',
    priceAnnual: '2 075',
    savingsAnnual: '9 900',
    period: 'F CFA/mois',
    desc: 'Pour les commerces qui tournent tous les jours',
    color: 'border-primary-500 ring-2 ring-primary-500/20',
    popular: true,
    cta: 'Essai gratuit de 14 jours',
    ctaClass: 'btn-primary block w-full py-3 text-sm text-center',
    features: [
      'Réservations illimitées',
      'Rappels email automatiques',
      'URL personnalisée',
      'Statistiques avancées & revenus',
      'Export CSV illimité',
      'Support prioritaire',
    ],
  },
  {
    name: 'Business',
    priceMonthly: '7 900',
    priceAnnual: '5 825',
    savingsAnnual: '24 900',
    period: 'F CFA/mois',
    desc: 'Pour les équipes & multi-sites',
    color: 'border-gray-200',
    cta: 'Essai gratuit de 14 jours',
    ctaClass: 'block w-full py-3 rounded-xl text-sm font-bold text-center border-2 border-primary-600 text-primary-700 hover:bg-primary-600 hover:text-white transition-all',
    features: [
      'Tout le plan Pro',
      'Multi-employés (bientôt)',
      'SMS automatiques',
      'Tableau de bord multi-sites',
      'Intégration API personnalisée',
      'Gestionnaire de compte dédié',
    ],
  },
])

/*
 * Illustrative use cases, not testimonials.
 *
 * This array used to hold four named people with photos, five-star ratings and
 * hard numbers ("+60% de réservations", "-80% de no-shows"). None of them
 * existed and none of those figures were measured. Invented social proof is
 * both a legal exposure and the fastest way to lose a merchant's trust the day
 * they look closely, so the section now describes what the product does for a
 * kind of business, and claims nothing on anybody's behalf.
 */


// ── Navbar scroll ──
const scrolled = ref(false)
const navHidden = ref(false)
const lastScrollY = ref(0)
const mobileMenuOpen = ref(false)

// ── Scroll-reveal ──
const heroVisible = ref(false)
const heroTextEl = ref(null)

// ── Animated browser mockup ──
const mockupStep = ref(1)
const mockupSelectedService = ref(null)
const mockupSelectedDate = ref(null)
const mockupSelectedTime = ref(null)
const mockupTypedName = ref('')
const mockupTypedPhone = ref('')
const mockupShowCursor = ref(true)

const services = [
  { name: 'Coiffure naturelle', duration: '1h', price: '5 000 F', color: '#8b5cf6' },
  { name: 'Tresses africaines', duration: '2h30', price: '12 000 F', color: '#ec4899' },
  { name: 'Soin capillaire', duration: '45min', price: '3 500 F', color: '#06b6d4' },
  { name: 'Maquillage complet', duration: '1h15', price: '8 000 F', color: '#f59e0b' },
]

const calendarDays = [
  { day: 24, disabled: true }, { day: 25, disabled: true }, { day: 26, disabled: false },
  { day: 27, disabled: false }, { day: 28, disabled: false }, { day: 29, disabled: false },
  { day: 30, disabled: false }, { day: 31, disabled: false }, { day: 1, disabled: false, nextMonth: true },
  { day: 2, disabled: false, nextMonth: true }, { day: 3, disabled: false, nextMonth: true },
  { day: 4, disabled: false, nextMonth: true }, { day: 5, disabled: false, nextMonth: true },
  { day: 6, disabled: false, nextMonth: true },
]

const timeSlots = ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '14:00', '14:30']

let animTimer = null
let typeTimer = null

function typeText(target, text, cb) {
  let i = 0
  typeTimer = setInterval(() => {
    if (i <= text.length) {
      target.value = text.slice(0, i)
      i++
    } else {
      clearInterval(typeTimer)
      if (cb) setTimeout(cb, 400)
    }
  }, 70)
}

function startAnimation() {
  // Reset
  mockupStep.value = 1
  mockupSelectedService.value = null
  mockupSelectedDate.value = null
  mockupSelectedTime.value = null
  mockupTypedName.value = ''
  mockupTypedPhone.value = ''

  // Step 1 → select service after 2s
  animTimer = setTimeout(() => {
    mockupSelectedService.value = services[1] // Tresses
    setTimeout(() => {
      mockupStep.value = 2

      // Step 2 → select date after 1.5s
      setTimeout(() => {
        mockupSelectedDate.value = 28
        setTimeout(() => {
          mockupStep.value = 3

          // Step 3 → select time after 1.5s
          setTimeout(() => {
            mockupSelectedTime.value = '09:30'
            setTimeout(() => {
              mockupStep.value = 4

              // Step 4 → type name then phone
              setTimeout(() => {
                typeText(mockupTypedName, 'Amina Bello', () => {
                  typeText(mockupTypedPhone, '+237 691 234 567', () => {
                    // Step 5 → success after 1s
                    setTimeout(() => {
                      mockupStep.value = 5

                      // Restart after 3.5s
                      setTimeout(() => {
                        startAnimation()
                      }, 3500)
                    }, 1000)
                  })
                })
              }, 500)
            }, 600)
          }, 1500)
        }, 600)
      }, 1500)
    }, 600)
  }, 2000)
}

// Cursor blink
let cursorTimer = null

function onScroll() {
  const y = window.scrollY
  scrolled.value = y > 20
  navHidden.value = y > 300 && y > lastScrollY.value
  lastScrollY.value = y
}
onMounted(() => {
  startAnimation()
  cursorTimer = setInterval(() => {
    mockupShowCursor.value = !mockupShowCursor.value
  }, 530)
  window.addEventListener('scroll', onScroll, { passive: true })
  nextTick(() => { setTimeout(() => { heroVisible.value = true }, 100) })
})

onUnmounted(() => {
  clearTimeout(animTimer)
  clearInterval(typeTimer)
  clearInterval(cursorTimer)
  window.removeEventListener('scroll', onScroll)
})
</script>

<template>
  <div class="min-h-screen bg-clay-50">
    <!-- ══════ FLOATING NAVBAR ══════ -->
    <div :class="[
      'fixed top-0 inset-x-0 z-50 transition-all duration-500 flex justify-center',
      navHidden ? '-translate-y-full' : 'translate-y-0'
    ]" :style="{ paddingTop: scrolled ? '8px' : '16px' }">
      <nav :class="[
        'transition-all duration-500 flex items-center justify-between',
        scrolled
          ? 'max-w-3xl w-full mx-4 px-4 py-2 bg-white/70 backdrop-blur-2xl backdrop-saturate-[1.8] shadow-[0_8px_32px_rgba(0,0,0,0.08),0_0_0_1px_rgba(0,0,0,0.04)] rounded-2xl'
          : 'max-w-6xl w-full mx-4 sm:mx-6 px-6 py-3 bg-transparent rounded-2xl'
      ]">
        <!-- Logo -->
        <RouterLink to="/" class="flex items-center gap-2 shrink-0">
          <img src="/logo.svg" alt="Nuvo" :class="['transition-all duration-300', scrolled ? 'w-7 h-7' : 'w-8 h-8']" />
          <span :class="[
            'font-display font-black tracking-tight transition-all duration-300',
            scrolled ? 'text-base text-gray-900' : 'text-lg text-gray-900'
          ]">Nuvo</span>
        </RouterLink>

        <!-- Center nav links -->
        <!-- Logo text uses display font via global h* rule or explicit class -->
        <div class="hidden md:flex items-center">
          <div :class="[
            'flex items-center gap-0.5 rounded-full px-1 py-0.5 transition-all duration-300',
            scrolled ? '' : 'bg-white/50 backdrop-blur-sm border border-white/60'
          ]">
            <a href="#features" class="nav-pill">Fonctionnalités</a>
            <a href="#pricing" class="nav-pill">Tarifs</a>
            <RouterLink to="/guide" class="nav-pill">Guide</RouterLink>
            <RouterLink to="/contact" class="nav-pill">Contact</RouterLink>
          </div>
        </div>

        <!-- Right actions -->
        <div class="hidden sm:flex items-center gap-1.5">
          <template v-if="auth.isAuthenticated">
            <RouterLink to="/dashboard" class="inline-flex items-center gap-2 px-5 py-1.5 text-sm font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-control transition-all duration-200 shadow-md shadow-primary-900/20 hover:shadow-lg hover:shadow-primary-900/25 hover:-translate-y-px">
              Mon tableau de bord
              <ArrowRight class="w-3.5 h-3.5" />
            </RouterLink>
          </template>
          <template v-else>
            <RouterLink to="/login" :class="[
              'px-4 py-1.5 text-sm font-semibold rounded-control transition-all duration-200',
              scrolled ? 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' : 'text-gray-700 hover:text-gray-900 hover:bg-white/60'
            ]">Se connecter</RouterLink>
            <RouterLink to="/register" class="px-5 py-1.5 text-sm font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-control transition-all duration-200 shadow-md shadow-primary-900/20 hover:shadow-lg hover:shadow-primary-900/25 hover:-translate-y-px">Démarrer</RouterLink>
          </template>
        </div>

        <!-- Mobile hamburger -->
        <button @click="mobileMenuOpen = !mobileMenuOpen" class="sm:hidden flex flex-col gap-1 p-2">
          <span :class="['block w-5 h-0.5 bg-gray-900 rounded-full transition-all duration-300', mobileMenuOpen ? 'rotate-45 translate-y-[6px]' : '']" />
          <span :class="['block w-5 h-0.5 bg-gray-900 rounded-full transition-all duration-300', mobileMenuOpen ? 'opacity-0' : '']" />
          <span :class="['block w-5 h-0.5 bg-gray-900 rounded-full transition-all duration-300', mobileMenuOpen ? '-rotate-45 -translate-y-[6px]' : '']" />
        </button>
      </nav>
    </div>

    <!-- Mobile menu -->
    <Transition name="mobile-menu">
      <div v-if="mobileMenuOpen" class="fixed inset-0 z-40 bg-white/95 backdrop-blur-2xl flex flex-col items-center justify-center gap-6 sm:hidden">
        <a @click="mobileMenuOpen = false" href="#features" class="text-2xl font-bold text-gray-900">Fonctionnalités</a>
        <a @click="mobileMenuOpen = false" href="#pricing" class="text-2xl font-bold text-gray-900">Tarifs</a>
        <RouterLink @click="mobileMenuOpen = false" to="/guide" class="text-2xl font-bold text-gray-900">Guide</RouterLink>
        <RouterLink @click="mobileMenuOpen = false" to="/contact" class="text-2xl font-bold text-gray-900">Contact</RouterLink>
        <div class="flex flex-col gap-3 mt-4 w-56">
          <template v-if="auth.isAuthenticated">
            <RouterLink @click="mobileMenuOpen = false" to="/dashboard" class="py-3 text-center text-base font-bold text-white bg-primary-600 rounded-control">Mon tableau de bord</RouterLink>
          </template>
          <template v-else>
            <RouterLink @click="mobileMenuOpen = false" to="/login" class="py-3 text-center text-base font-semibold text-gray-700 border-2 border-gray-200 rounded-control">Se connecter</RouterLink>
            <RouterLink @click="mobileMenuOpen = false" to="/register" class="py-3 text-center text-base font-bold text-white bg-primary-600 rounded-control">Démarrer</RouterLink>
          </template>
        </div>
      </div>
    </Transition>

    <!-- ══════ HERO ══════ -->
    <section class="relative min-h-[100vh] flex flex-col justify-center px-4 sm:px-6 overflow-hidden">
      <!-- Background -->
      <div class="absolute inset-0 -z-10">
        <div class="absolute top-[-20%] left-[10%] w-[600px] h-[600px] bg-primary-100/50 rounded-full blur-[150px] animate-float-slow" />
        <div class="absolute top-[10%] right-[5%] w-[500px] h-[500px] bg-violet-100/40 rounded-full blur-[130px] animate-float-slow" style="animation-delay: -3s" />
        <div class="absolute bottom-[-10%] left-[40%] w-[500px] h-[400px] bg-amber-50/40 rounded-full blur-[120px]" />
        <div class="absolute inset-0 opacity-[0.025]" style="background-image: radial-gradient(circle at 1px 1px, #6366f1 0.5px, transparent 0); background-size: 40px 40px;" />
      </div>

      <div class="max-w-4xl mx-auto text-center pt-28 pb-12">
        <!-- Title -->
        <h1 :class="[
          'text-5xl sm:text-6xl lg:text-7xl font-black text-gray-900 leading-[1.05] tracking-tight mb-7 transition-all duration-700 delay-150',
          heroVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'
        ]">
          Vos réservations,<br />
          <span class="hero-gradient-text">en pilote automatique</span>
        </h1>

        <!-- Subtitle -->
        <p :class="[
          'text-lg sm:text-xl text-gray-500 leading-relaxed max-w-2xl mx-auto mb-10 transition-all duration-700 delay-300',
          heroVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'
        ]">
          Créez votre page de réservation en 5 minutes. Vos clients réservent en ligne,
          vous recevez une notification WhatsApp. <strong class="text-gray-700 font-semibold">Zéro appel manqué.</strong>
        </p>

        <!-- CTA buttons -->
        <div :class="[
          'flex flex-col sm:flex-row gap-3 justify-center mb-12 transition-all duration-700 delay-[450ms]',
          heroVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'
        ]">
          <RouterLink to="/register"
            class="group inline-flex items-center justify-center gap-2 px-8 py-4 text-base font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-control transition-all duration-300 shadow-xl shadow-primary-900/25 hover:shadow-2xl hover:shadow-primary-900/30 hover:-translate-y-0.5">
            Créer mon compte gratuit
            <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-200" />
          </RouterLink>
          <a href="#features"
            class="inline-flex items-center justify-center gap-2 px-8 py-4 text-base font-semibold text-gray-700 bg-white/80 backdrop-blur-sm border border-gray-200 hover:border-gray-300 hover:bg-clay-50 rounded-control transition-all duration-300 hover:-translate-y-0.5">
            Voir les fonctionnalités
          </a>
        </div>

        <!-- Social proof -->
        <div :class="[
          'flex items-center justify-center gap-4 transition-all duration-700 delay-[600ms]',
          heroVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
        ]">
          <!--
            A "4.9/5" rating and a row of invented customer avatars used to sit
            here. Both were fabricated. Replaced with something we can actually
            stand behind: what the product does, and what it costs to try.
          -->
          <p class="text-sm text-gray-500">
            <span class="font-semibold text-gray-700">14 jours d'essai Pro</span>
            · sans carte bancaire, sans engagement.
          </p>
        </div>
      </div>

      <!-- ══════ ANIMATED BROWSER MOCKUP ══════ -->
      <div :class="[
        'w-full max-w-[min(90vw,1280px)] mx-auto mt-4 px-0 pb-20 transition-all duration-1000 delay-700',
        heroVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'
      ]">
        <div class="relative rounded-2xl overflow-hidden shadow-2xl ring-1 ring-black/5">
          <!-- Browser chrome -->
          <div class="bg-gray-800 px-4 py-3 flex items-center gap-2">
            <div class="flex gap-1.5">
              <div class="w-3 h-3 rounded-full bg-red-400" />
              <div class="w-3 h-3 rounded-full bg-yellow-400" />
              <div class="w-3 h-3 rounded-full bg-green-400" />
            </div>
            <div class="flex-1 bg-gray-700 rounded-lg px-3 py-1 text-xs text-gray-300 text-center max-w-xs mx-auto flex items-center justify-center gap-1.5">
              <Lock class="w-3 h-3 text-green-400" />
              nuvo.app/salon-elegance-douala
            </div>
          </div>

          <!-- Browser content -->
          <div class="bg-gray-50 min-h-[440px] lg:min-h-[540px] xl:min-h-[620px] overflow-hidden">
            <!-- Mini business header -->
            <div class="bg-gradient-to-r from-violet-600 to-primary-500 px-6 py-4 text-white text-center">
              <div class="w-11 h-11 rounded-full bg-white/20 border-2 border-white/30 mx-auto mb-1.5 flex items-center justify-center">
                <span class="text-base font-extrabold">S</span>
              </div>
              <h3 class="font-extrabold text-sm">Salon Élégance Douala</h3>
              <p class="text-white/60 text-[11px] mt-0.5">Salon de coiffure · Douala, Cameroun</p>
            </div>

            <!-- Step indicators (animated) -->
            <div class="flex items-center justify-center gap-1 py-3">
              <template v-for="i in 4" :key="i">
                <div :class="[
                  'w-5 h-5 rounded-full text-[9px] font-bold flex items-center justify-center transition-all duration-500',
                  mockupStep > i ? 'bg-emerald-500 text-white' : mockupStep === i ? 'bg-primary-500 text-white scale-110' : 'bg-gray-200 text-gray-400'
                ]">
                  <Check v-if="mockupStep > i" class="w-3 h-3" :stroke-width="3" />
                  <span v-else>{{ i }}</span>
                </div>
                <div v-if="i < 4" :class="['w-5 h-0.5 transition-all duration-500', mockupStep > i ? 'bg-emerald-400' : 'bg-gray-200']" />
              </template>
            </div>

            <!-- ── STEP 1: Services ── -->
            <Transition name="mockup-slide" mode="out-in">
              <div v-if="mockupStep === 1" key="ms1" class="px-5 pb-5">
                <p class="text-[11px] font-extrabold text-gray-800 mb-2">Quel service souhaitez-vous réserver ?</p>
                <div class="space-y-2">
                  <div v-for="svc in services" :key="svc.name"
                    :class="[
                      'bg-clay-50 rounded-xl p-2.5 flex items-center gap-2.5 border transition-all duration-300 cursor-pointer',
                      mockupSelectedService?.name === svc.name ? 'border-primary-400 shadow-md ring-2 ring-primary-100 scale-[1.02]' : 'border-gray-100 shadow-sm'
                    ]">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs shrink-0"
                      :style="{ backgroundColor: svc.color }">
                      ✦
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-[11px] font-bold text-gray-900">{{ svc.name }}</p>
                      <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-[9px] text-gray-400">{{ svc.duration }}</span>
                        <span class="text-[9px] font-semibold" :style="{ color: svc.color }">{{ svc.price }}</span>
                      </div>
                    </div>
                    <ChevronRight class="w-3.5 h-3.5 text-gray-300 shrink-0" />
                  </div>
                </div>
              </div>

              <!-- ── STEP 2: Calendar ── -->
              <div v-else-if="mockupStep === 2" key="ms2" class="px-5 pb-5">
                <p class="text-[11px] font-extrabold text-gray-800 mb-2">Choisissez une date</p>
                <!-- Selected service pill -->
                <div class="flex items-center gap-2 p-2 rounded-lg mb-3" style="background-color: #ec489915">
                  <div class="w-7 h-7 rounded-lg bg-pink-500 flex items-center justify-center text-white text-[10px]">✦</div>
                  <div>
                    <p class="text-[10px] font-bold text-gray-900">Tresses africaines</p>
                    <p class="text-[9px] text-gray-400">2h30 · 12 000 F</p>
                  </div>
                </div>
                <!-- Mini calendar -->
                <div class="bg-clay-50 rounded-xl p-3 shadow-sm border border-gray-100">
                  <div class="flex items-center justify-between mb-2">
                    <ChevronLeft class="w-3.5 h-3.5 text-gray-400" />
                    <span class="text-[11px] font-bold text-gray-800">Mars 2026</span>
                    <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                  </div>
                  <div class="grid grid-cols-7 gap-0.5 mb-1">
                    <div v-for="d in ['Lu','Ma','Me','Je','Ve','Sa','Di']" :key="d" class="text-center text-[8px] font-semibold text-gray-400 py-0.5">{{ d }}</div>
                  </div>
                  <div class="grid grid-cols-7 gap-0.5">
                    <!-- Empty cells for offset (March 2026 starts on Sunday, so 6 empty) -->
                    <div v-for="i in 6" :key="'pad'+i" />
                    <template v-for="d in calendarDays" :key="d.day + (d.nextMonth ? 'n' : '')">
                      <div :class="[
                        'aspect-square rounded-lg text-[10px] font-medium flex items-center justify-center transition-all duration-500',
                        d.disabled ? 'text-gray-300' : mockupSelectedDate === d.day && !d.nextMonth ? 'bg-primary-500 text-white scale-110 shadow-md' : 'text-gray-700 hover:bg-gray-100',
                        d.nextMonth ? 'text-gray-300' : ''
                      ]">{{ d.day }}</div>
                    </template>
                  </div>
                </div>
              </div>

              <!-- ── STEP 3: Time slots ── -->
              <div v-else-if="mockupStep === 3" key="ms3" class="px-5 pb-5">
                <p class="text-[11px] font-extrabold text-gray-800 mb-1">Choisissez un horaire</p>
                <p class="text-[9px] text-gray-400 mb-3 flex items-center gap-1">
                  <Calendar class="w-3 h-3" />
                  Samedi 28 mars 2026
                </p>
                <div class="grid grid-cols-5 gap-1.5">
                  <div v-for="t in timeSlots" :key="t"
                    :class="[
                      'py-2 rounded-lg text-[10px] font-semibold text-center border-2 transition-all duration-500 cursor-pointer',
                      mockupSelectedTime === t ? 'bg-primary-500 text-white border-primary-500 scale-105 shadow-md' : 'border-gray-200 text-gray-600'
                    ]">
                    {{ t }}
                  </div>
                </div>
              </div>

              <!-- ── STEP 4: Form ── -->
              <div v-else-if="mockupStep === 4" key="ms4" class="px-5 pb-5">
                <p class="text-[11px] font-extrabold text-gray-800 mb-2">Vos coordonnées</p>
                <!-- Mini summary -->
                <div class="bg-clay-50 rounded-lg p-2.5 shadow-sm border border-gray-100 mb-3 space-y-1">
                  <div class="flex justify-between text-[9px]">
                    <span class="text-gray-400">Service</span>
                    <span class="font-semibold text-gray-700">Tresses africaines</span>
                  </div>
                  <div class="flex justify-between text-[9px]">
                    <span class="text-gray-400">Date</span>
                    <span class="font-semibold text-gray-700">Sam. 28 mars</span>
                  </div>
                  <div class="flex justify-between text-[9px]">
                    <span class="text-gray-400">Heure</span>
                    <span class="font-semibold text-gray-700">09:30</span>
                  </div>
                  <div class="flex justify-between text-[9px]">
                    <span class="text-gray-400">Prix</span>
                    <span class="font-semibold text-pink-500">12 000 F CFA</span>
                  </div>
                </div>
                <!-- Form fields with typing animation -->
                <div class="space-y-2">
                  <div>
                    <label class="text-[9px] font-semibold text-gray-600 mb-0.5 block">Nom complet *</label>
                    <div class="bg-clay-50 border border-gray-200 rounded-lg px-2.5 py-1.5 text-[10px] text-gray-800 flex items-center"
                      :class="{ 'border-primary-400 ring-1 ring-primary-100': mockupTypedName.length > 0 && !mockupTypedPhone }">
                      {{ mockupTypedName || '' }}<span v-if="mockupTypedName.length > 0 && !mockupTypedPhone && mockupShowCursor" class="inline-block w-[1px] h-3 bg-primary-500 ml-px animate-none" />
                      <span v-if="!mockupTypedName" class="text-gray-300">Votre nom</span>
                    </div>
                  </div>
                  <div>
                    <label class="text-[9px] font-semibold text-gray-600 mb-0.5 block">Téléphone *</label>
                    <div class="bg-clay-50 border border-gray-200 rounded-lg px-2.5 py-1.5 text-[10px] text-gray-800 flex items-center"
                      :class="{ 'border-primary-400 ring-1 ring-primary-100': mockupTypedPhone.length > 0 }">
                      {{ mockupTypedPhone || '' }}<span v-if="mockupTypedPhone.length > 0 && mockupTypedPhone.length < 16 && mockupShowCursor" class="inline-block w-[1px] h-3 bg-primary-500 ml-px" />
                      <span v-if="!mockupTypedPhone" class="text-gray-300">+237 6XX XXX XXX</span>
                    </div>
                  </div>
                  <button class="w-full py-2 rounded-control text-white font-bold text-[10px] bg-primary-500 mt-1 transition-all"
                    :class="{ 'opacity-50': !mockupTypedPhone, 'hover:opacity-90 shadow-md': mockupTypedPhone }">
                    Confirmer ma réservation
                  </button>
                </div>
              </div>

              <!-- ── STEP 5: Success ── -->
              <div v-else-if="mockupStep === 5" key="ms5" class="px-5 pb-5 text-center">
                <div class="w-14 h-14 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-3 mockup-pop">
                  <Check class="w-7 h-7 text-emerald-500" />
                </div>
                <h4 class="text-sm font-extrabold text-gray-900 mb-1">Réservation confirmée !</h4>
                <p class="text-[10px] text-gray-400 mb-3">Salon Élégance Douala vous confirme sous peu.</p>
                <div class="bg-clay-50 rounded-xl p-3 shadow-sm border border-gray-100 text-left space-y-1.5 mb-3">
                  <div class="flex justify-between text-[9px]">
                    <span class="text-gray-400">Référence</span>
                    <span class="font-mono font-bold text-gray-900">RSV-2026-4F8A</span>
                  </div>
                  <div class="flex justify-between text-[9px]">
                    <span class="text-gray-400">Service</span>
                    <span class="font-semibold text-gray-700">Tresses africaines</span>
                  </div>
                  <div class="flex justify-between text-[9px]">
                    <span class="text-gray-400">Date</span>
                    <span class="font-semibold text-gray-700">Sam. 28 mars 2026</span>
                  </div>
                  <div class="flex justify-between text-[9px]">
                    <span class="text-gray-400">Heure</span>
                    <span class="font-semibold text-gray-700">09:30</span>
                  </div>
                </div>
                <div class="inline-flex items-center gap-1.5 py-1.5 px-4 bg-green-500 text-white text-[10px] font-semibold rounded-lg">
                  <BrandIcon name="whatsapp" class="w-3 h-3" />
                  Contacter sur WhatsApp
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-28 px-4 sm:px-6 relative overflow-hidden">
      <!-- Background decorations -->
      <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[800px] bg-gradient-to-b from-primary-50/40 to-transparent rounded-full blur-3xl -z-10" />

      <div class="max-w-6xl mx-auto">
        <div class="text-center mb-20">
          <h2 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mb-5 tracking-tight">
            Tout ce dont vous avez besoin.
            <br /><span class="text-gradient">Rien de superflu.</span>
          </h2>
          <p class="text-lg text-gray-500 max-w-xl mx-auto">Des outils puissants et simples, pensés pour votre quotidien de commerçant.</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-5">
          <div
            v-for="(feature, idx) in features"
            :key="feature.title"
            class="group relative bg-clay-50 rounded-2xl p-7 border border-gray-100 hover:border-gray-200 transition-all duration-500 hover:-translate-y-1 hover:shadow-xl hover:shadow-black/5"
          >
            <!-- Gradient glow on hover -->
            <div :class="['absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 -z-10 blur-xl', feature.bg]" />

            <div :class="['w-14 h-14 rounded-2xl bg-gradient-to-br flex items-center justify-center mb-5 shadow-lg group-hover:scale-110 transition-transform duration-300', feature.gradient]">
              <component :is="feature.icon" class="w-7 h-7 text-white" />
            </div>
            <h3 class="font-bold text-gray-900 text-lg mb-2">{{ feature.title }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed">{{ feature.desc }}</p>

            <!-- Bottom accent line -->
            <div :class="['h-0.5 w-0 group-hover:w-12 transition-all duration-500 mt-5 rounded-full bg-gradient-to-r', feature.gradient]" />
          </div>
        </div>
      </div>
    </section>

    <HowItWorks />

    <!-- Pricing -->
    <section id="pricing" class="py-28 px-4 sm:px-6">
      <div class="max-w-5xl mx-auto">
        <div class="text-center mb-14">
          <h2 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mb-4 tracking-tight">Des tarifs simples, sans surprises</h2>
          <p class="text-lg text-gray-500 mb-10">Paiement par mobile money (MTN, Orange, Wave) ou carte bancaire.</p>

          <!-- Billing toggle -->
          <div class="inline-flex items-center bg-gray-100 rounded-full p-1 gap-0.5">
            <button
              @click="isAnnual = false"
              :class="['px-5 py-2 rounded-full text-sm font-semibold transition-all duration-200', !isAnnual ? 'bg-clay-50 text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']"
            >Mensuel</button>
            <button
              @click="isAnnual = true"
              :class="['px-5 py-2 rounded-full text-sm font-semibold transition-all duration-200 flex items-center gap-2', isAnnual ? 'bg-clay-50 text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']"
            >
              Annuel
              <span class="text-[10px] font-bold bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">-33%</span>
            </button>
          </div>
        </div>

        <div class="grid md:grid-cols-3 gap-6 items-start">
          <div
            v-for="plan in plans"
            :key="plan.name"
            :class="[
              'rounded-2xl p-8 relative flex flex-col border-2 transition-all duration-300',
              plan.popular ? 'border-primary-500 ring-4 ring-primary-500/10 shadow-xl shadow-primary-500/10 md:-mt-4 md:mb-4' : plan.color || 'border-gray-100',
              plan.popular ? 'bg-clay-50' : 'bg-clay-50'
            ]"
          >
            <!-- Popular badge -->
            <div v-if="plan.popular"
              class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-primary-600 to-violet-600 text-white text-xs font-bold px-4 py-1.5 rounded-full whitespace-nowrap shadow-lg">
              Le plus populaire
            </div>

            <div class="mb-6">
              <h3 class="font-bold text-gray-900 text-lg mb-1">{{ plan.name }}</h3>
              <p :class="['text-sm mb-5', plan.popular ? 'text-primary-600 font-medium' : 'text-gray-400']">{{ plan.desc }}</p>

              <!-- Price display -->
              <div class="flex items-end gap-2">
                <span class="text-4xl font-extrabold text-gray-900">{{ isAnnual ? plan.priceAnnual : plan.priceMonthly }}</span>
                <div class="pb-1">
                  <span v-if="isAnnual && plan.priceMonthly !== '0'" class="text-sm text-gray-400 line-through block leading-none">{{ plan.priceMonthly }}</span>
                  <span class="text-gray-400 text-sm">{{ plan.period }}</span>
                </div>
              </div>
              <!-- Annual savings callout -->
              <p v-if="isAnnual && plan.savingsAnnual" class="text-xs text-emerald-600 font-semibold mt-2">
                Vous économisez {{ plan.savingsAnnual }} F CFA/an
              </p>
            </div>

            <!-- Features -->
            <ul class="space-y-3 flex-1 mb-6">
              <li v-for="f in plan.features" :key="f" class="flex items-start gap-2.5 text-sm text-gray-600">
                <CircleCheck class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" />
                {{ f }}
              </li>
            </ul>

            <!-- Limitations (free plan) -->
            <ul v-if="plan.limitations" class="space-y-2.5 mb-6 pt-4 border-t border-gray-100">
              <li v-for="l in plan.limitations" :key="l" class="flex items-start gap-2.5 text-sm text-gray-400">
                <X class="w-5 h-5 text-gray-300 shrink-0 mt-0.5" />
                {{ l }}
              </li>
            </ul>

            <RouterLink to="/register" :class="plan.ctaClass">{{ plan.cta }}</RouterLink>

            <!-- Trial note for paid plans -->
            <p v-if="plan.priceMonthly !== '0'" class="text-center text-[11px] text-gray-400 mt-3">
              Sans engagement. Annulable à tout moment.
            </p>
          </div>
        </div>
      </div>
    </section>

    <TradeShowcase />

    <FinalCta />

    <!-- ══════ REAL FOOTER ══════ -->
    <footer class="bg-primary-950 text-primary-200">
      <!-- Main footer -->
      <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-10">
          <!-- Brand column -->
          <div class="col-span-2 md:col-span-1">
            <div class="flex items-center gap-2 mb-4">
              <img src="/logo.svg" alt="Nuvo" class="w-8 h-8" />
              <span class="font-display font-extrabold text-white text-lg">Nuvo</span>
            </div>
            <p class="text-sm leading-relaxed mb-6">La plateforme de réservation en ligne pensée pour les commerçants ambitieux.</p>
            <!-- Social links -->
            <div class="flex items-center gap-3">
              <a href="#" class="w-9 h-9 rounded-control bg-gray-800 hover:bg-gray-700 flex items-center justify-center transition-colors" aria-label="Facebook">
                <BrandIcon name="facebook" class="w-4 h-4" />
              </a>
              <a href="#" class="w-9 h-9 rounded-control bg-gray-800 hover:bg-gray-700 flex items-center justify-center transition-colors" aria-label="Instagram">
                <BrandIcon name="instagram" class="w-4 h-4" />
              </a>
              <a href="#" class="w-9 h-9 rounded-control bg-gray-800 hover:bg-gray-700 flex items-center justify-center transition-colors" aria-label="Twitter">
                <BrandIcon name="x" class="w-4 h-4" />
              </a>
              <a href="#" class="w-9 h-9 rounded-control bg-gray-800 hover:bg-gray-700 flex items-center justify-center transition-colors" aria-label="WhatsApp">
                <BrandIcon name="whatsapp" class="w-4 h-4" />
              </a>
            </div>
          </div>

          <!-- Produit -->
          <div>
            <h4 class="font-bold text-white text-sm mb-4">Produit</h4>
            <ul class="space-y-2.5">
              <li><RouterLink to="/register" class="text-sm hover:text-white transition-colors">Créer un compte</RouterLink></li>
              <li><a href="#features" class="text-sm hover:text-white transition-colors">Fonctionnalités</a></li>
              <li><a href="#pricing" class="text-sm hover:text-white transition-colors">Tarifs</a></li>
              <li><RouterLink to="/track" class="text-sm hover:text-white transition-colors">Suivre une réservation</RouterLink></li>
              
            </ul>
          </div>

          <!-- Ressources -->
          <div>
            <h4 class="font-bold text-white text-sm mb-4">Ressources</h4>
            <ul class="space-y-2.5">
              <li><RouterLink to="/help" class="text-sm hover:text-white transition-colors">Centre d'aide</RouterLink></li>
              <li><RouterLink to="/guide" class="text-sm hover:text-white transition-colors">Guide de démarrage</RouterLink></li>
              
            </ul>
          </div>

          <!-- Entreprise -->
          <div>
            <h4 class="font-bold text-white text-sm mb-4">Entreprise</h4>
            <ul class="space-y-2.5">
              <li><RouterLink to="/about" class="text-sm hover:text-white transition-colors">À propos</RouterLink></li>
              
              <li><RouterLink to="/contact" class="text-sm hover:text-white transition-colors">Contact</RouterLink></li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Bottom bar -->
      <div class="border-t border-gray-800">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p class="text-sm text-gray-500">&copy; {{ new Date().getFullYear() }} Nuvo. Tous droits réservés.</p>
          <div class="flex items-center gap-6">
            <RouterLink to="/terms" class="text-sm text-gray-500 hover:text-gray-300 transition-colors">Conditions d'utilisation</RouterLink>
            <RouterLink to="/privacy" class="text-sm text-gray-500 hover:text-gray-300 transition-colors">Politique de confidentialité</RouterLink>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.mockup-slide-enter-active { transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1); }
.mockup-slide-leave-active { transition: all 0.2s ease-in; }
.mockup-slide-enter-from   { opacity: 0; transform: translateX(20px); }
.mockup-slide-leave-to     { opacity: 0; transform: translateX(-10px); }

.mockup-pop {
  animation: popIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes popIn {
  0%   { transform: scale(0); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}

/* Nav pill links */
.nav-pill {
  @apply px-3.5 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-full hover:bg-white/80 transition-all duration-200;
}

/* Hero gradient text */
.hero-gradient-text {
  /* Les jetons, pas des hex : ce dégradé était le dernier violet de
     l'application, invisible au remappage parce qu'écrit en CSS brut. */
  background: linear-gradient(
    135deg,
    var(--forest-600) 0%,
    var(--forest-800) 25%,
    var(--forest-500) 50%,
    var(--forest-800) 75%,
    var(--forest-600) 100%
  );
  background-size: 200% auto;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: gradientShift 4s ease-in-out infinite;
}

@keyframes gradientShift {
  0%, 100% { background-position: 0% center; }
  50% { background-position: 100% center; }
}

/* Float animation for bg blobs */
.animate-float-slow {
  animation: floatSlow 8s ease-in-out infinite;
}
@keyframes floatSlow {
  0%, 100% { transform: translate(0, 0); }
  50% { transform: translate(20px, -20px); }
}

/* Mobile menu transitions */
.mobile-menu-enter-active { transition: all 0.3s ease-out; }
.mobile-menu-leave-active { transition: all 0.2s ease-in; }
.mobile-menu-enter-from { opacity: 0; }
.mobile-menu-leave-to { opacity: 0; }
</style>
