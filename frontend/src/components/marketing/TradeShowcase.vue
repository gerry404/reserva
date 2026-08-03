<script setup>
import { computed, ref } from 'vue'
import { Scissors, Sparkles, Waves, Brush } from 'lucide-vue-next'
import DurationBar from '@/components/time/DurationBar.vue'

/**
 * « Pensé pour votre métier ».
 *
 * La version précédente affichait quatre cartes rédigées à la première
 * personne, signées d'un métier et d'une ville, avec la mention « Exemple
 * d'usage » en petit. C'est la forme d'un témoignage sans en être un : le
 * visiteur lit une preuve sociale qui n'existe pas, et n'apprend rien de
 * concret sur le produit.
 *
 * Ici, chaque métier montre sa configuration réelle. Les durées sont celles
 * qu'un commerçant saisit vraiment, rendues avec la même barre que dans
 * l'application : la longueur porte l'information avant le chiffre. Ce que la
 * section démontre est donc vérifiable : c'est le produit, pas un discours
 * sur le produit.
 *
 * Chaque métier porte aussi la règle de disponibilité qui le concerne le plus.
 * Les quatre décrivent le même moteur sous des angles différents, celui qui
 * parle au métier affiché.
 */

const trades = [
  {
    id: 'coiffure',
    name: 'Salon de coiffure',
    icon: Scissors,
    /** Ce que le métier a de particulier, et que la durée fixe ne gère pas. */
    tension: 'Des prestations de 30 minutes à 4 heures, dans le même agenda.',
    services: [
      { name: 'Tresses collées', minutes: 180 },
      { name: 'Défrisage', minutes: 90 },
      { name: 'Coupe + brushing', minutes: 45 },
      { name: 'Retouche racines', minutes: 60 },
    ],
    rule: {
      title: 'La prestation doit tenir avant la fermeture',
      body: 'Vous fermez à 18 h : une pose de 3 h ne s\'affiche plus après 15 h. Le créneau disparaît de lui-même, personne n\'a à y penser.',
    },
  },
  {
    id: 'barbier',
    name: 'Barbier',
    icon: Brush,
    tension: 'Beaucoup de passages courts, et les mains occupées toute la journée.',
    services: [
      { name: 'Coupe homme', minutes: 30 },
      { name: 'Coupe + barbe', minutes: 45 },
      { name: 'Taille de barbe', minutes: 20 },
      { name: 'Rasage traditionnel', minutes: 40 },
    ],
    rule: {
      title: 'Vos clients réservent pendant que vous coupez',
      body: 'Le lien se partage sur WhatsApp et Instagram. La demande arrive sur votre téléphone. Vous la confirmez entre deux clients, pas pendant.',
    },
  },
  {
    id: 'beaute',
    name: 'Institut de beauté',
    icon: Sparkles,
    tension: 'Des rendez-vous pris trois semaines à l\'avance, et oubliés.',
    services: [
      { name: 'Soin visage complet', minutes: 75 },
      { name: 'Pose de gel', minutes: 90 },
      { name: 'Épilation jambes', minutes: 45 },
      { name: 'Manucure', minutes: 40 },
    ],
    rule: {
      title: 'Le rappel part la veille, tout seul',
      body: 'Chaque réservation confirmée déclenche un email, puis un rappel la veille. Le rendez-vous manqué se traite avant d\'arriver.',
    },
  },
  {
    id: 'spa',
    name: 'Spa & massage',
    icon: Waves,
    tension: 'Des séances longues, et des plages de fermeture à faire respecter.',
    services: [
      { name: 'Massage signature', minutes: 120 },
      { name: 'Massage dos & nuque', minutes: 45 },
      { name: 'Gommage corps', minutes: 60 },
      { name: 'Rituel duo', minutes: 150 },
    ],
    rule: {
      title: 'Vos jours de fermeture sont opposables',
      body: 'Horaires, jours fermés, délai minimum avant réservation : le formulaire ne propose que ce que vous avez ouvert. Rien à refuser après coup.',
    },
  },
]

const activeId = ref(trades[0].id)
const active = computed(() => trades.find((t) => t.id === activeId.value))

/**
 * La prestation la plus longue du métier, pour situer les autres.
 *
 * Sans repère, une barre à 60 % ne dit rien. Nommer la plus longue donne
 * l'échelle à laquelle toutes les autres se lisent.
 */
const longest = computed(() =>
  active.value.services.reduce((a, b) => (b.minutes > a.minutes ? b : a)),
)

/**
 * La durée en toutes lettres.
 *
 * Le libellé intégré à DurationBar se pose après la piste, où il tombe à
 * l'extrémité droite d'une barre courte, loin du nom qu'il qualifie. Ici il
 * revient sur la ligne du nom, à l'aplomb du regard.
 */
function humanDuration(minutes) {
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  if (!h) return `${m} min`
  return m ? `${h} h ${m}` : `${h} h`
}
</script>

<template>
  <section class="trades">
    <div class="trades__inner">
      <header class="trades__head">
        <h2 class="trades__title">
          Pensé pour<br />votre métier
        </h2>
        <p class="trades__lede">
          Une prestation ne dure pas le même temps chez un barbier et dans un
          institut. Nuvo travaille avec vos durées réelles. Voici ce que ça donne,
          métier par métier.
        </p>
      </header>

      <div class="trades__tabs" role="tablist" aria-label="Métiers">
        <button
          v-for="t in trades"
          :key="t.id"
          role="tab"
          :aria-selected="t.id === activeId"
          :class="['trades__tab', { 'is-active': t.id === activeId }]"
          @click="activeId = t.id"
        >
          <component :is="t.icon" :size="17" :stroke-width="1.9" />
          <span>{{ t.name }}</span>
        </button>
      </div>

      <div class="trades__panel">
        <p class="trades__tension">{{ active.tension }}</p>

        <div class="trades__grid">
          <div class="trades__services">
            <p class="trades__label">Vos prestations</p>

            <!--
              La clé porte l'identifiant du métier : sans elle, Vue réutilise
              les nœuds d'un onglet à l'autre et les barres glissent d'une
              durée à la suivante au lieu de se redessiner.
            -->
            <ul class="trades__list">
              <li v-for="s in active.services" :key="`${active.id}-${s.name}`" class="trades__service">
                <div class="trades__service-row">
                  <span class="trades__service-name">{{ s.name }}</span>
                  <span class="trades__service-time numeric-inline">{{ humanDuration(s.minutes) }}</span>
                </div>
                <DurationBar :minutes="s.minutes" size="sm" />
              </li>
            </ul>

            <p class="trades__scale">
              La plus longue, {{ longest.name }}, mobilise
              {{ humanDuration(longest.minutes) }} de votre journée.
            </p>
          </div>

          <div class="trades__rule">
            <p class="trades__label">Ce que ça change</p>
            <h3 class="trades__rule-title">{{ active.rule.title }}</h3>
            <p class="trades__rule-body">{{ active.rule.body }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.trades {
  padding: 112px 24px;
  background: var(--clay-50);
}

.trades__inner {
  max-width: 1120px;
  margin: 0 auto;
}

.trades__head {
  display: grid;
  gap: 24px;
  margin-bottom: 44px;
}

@media (min-width: 820px) {
  .trades__head {
    grid-template-columns: auto 1fr;
    align-items: end;
    gap: 64px;
  }
}

.trades__title {
  margin: 0;
  font-size: clamp(34px, 5.2vw, 52px);
  font-weight: 800;
  line-height: 1.04;
  letter-spacing: -0.028em;
  color: var(--forest-950);
}

.trades__lede {
  margin: 0;
  max-width: 46ch;
  font-size: 16px;
  line-height: 1.65;
  color: var(--clay-600);
}

.trades__tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 28px;
}

.trades__tab {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  padding: 10px 18px;
  border: 1px solid var(--clay-200);
  border-radius: var(--radius-control);
  background: transparent;
  font-size: 14px;
  font-weight: 600;
  color: var(--clay-600);
  transition: color 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
}

.trades__tab:hover {
  color: var(--forest-800);
  border-color: var(--forest-300);
}

.trades__tab.is-active {
  background: var(--forest-900);
  border-color: var(--forest-900);
  color: #fff;
}

.trades__panel {
  padding: 34px;
  border: 1px solid var(--clay-200);
  border-radius: var(--radius-surface);
  background: #fff;
}

.trades__tension {
  margin: 0 0 28px;
  padding-bottom: 24px;
  border-bottom: 1px solid var(--clay-100);
  font-size: 17px;
  font-weight: 600;
  color: var(--forest-900);
}

.trades__grid {
  display: grid;
  gap: 40px;
}

@media (min-width: 820px) {
  .trades__grid {
    grid-template-columns: 1.25fr 1fr;
    gap: 56px;
  }
}

.trades__label {
  margin: 0 0 18px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  color: var(--clay-400);
}

.trades__list {
  display: grid;
  gap: 18px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.trades__service {
  display: grid;
  gap: 7px;
}

.trades__service-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 16px;
}

.trades__service-name {
  font-size: 14px;
  font-weight: 500;
  color: var(--forest-950);
}

.trades__service-time {
  font-size: 12px;
  color: var(--clay-500);
  white-space: nowrap;
}

.trades__scale {
  margin: 26px 0 0;
  font-size: 13px;
  line-height: 1.6;
  color: var(--clay-500);
}

/*
 * La conséquence est posée sur un fond teinté plutôt que sur un simple filet :
 * la colonne porte trois lignes face à une liste de quatre prestations, et sans
 * matière elle flottait en haut d'une grande réserve de blanc.
 */
.trades__rule {
  align-self: start;
  padding: 26px 28px 30px;
  border-radius: var(--radius-surface);
  background: var(--forest-50);
}

.trades__rule-title {
  margin: 0 0 12px;
  font-size: 19px;
  font-weight: 700;
  line-height: 1.3;
  letter-spacing: -0.012em;
  color: var(--forest-800);
}

.trades__rule-body {
  margin: 0;
  font-size: 15px;
  line-height: 1.68;
  color: var(--clay-600);
}
</style>
