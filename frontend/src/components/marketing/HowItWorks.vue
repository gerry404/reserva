<script setup>
import { Link2, UserRound, BellRing } from 'lucide-vue-next'

/**
 * « Opérationnel en 3 étapes », et ses transitions de vague.
 *
 * L'ancienne version posait deux SVG figés en haut et en bas de la section :
 * une courbe unique, immobile, remplie d'une couleur écrite en dur qui ne
 * correspondait même plus au fond depuis le changement de palette — un liseré
 * noir apparaissait sur les bords.
 *
 * Ici la vague est faite de trois couches qui dérivent à des vitesses
 * différentes. C'est ce décalage qui produit la sensation de profondeur :
 * une seule courbe animée glisse, plusieurs courbes désynchronisées ondulent.
 * Chaque couche est un motif dupliqué horizontalement et translaté d'exactement
 * sa propre largeur, si bien que la boucle est invisible.
 *
 * Les couleurs viennent des jetons : la vague ne peut plus se désaccorder du
 * fond qu'elle relie.
 */

const steps = [
  {
    n: '01',
    title: 'Créez votre compte',
    desc: "Inscrivez-vous en 5 minutes. Ajoutez le nom de votre commerce, vos services et vos horaires d'ouverture.",
    icon: UserRound,
  },
  {
    n: '02',
    title: 'Partagez votre lien',
    desc: 'Vous recevez une adresse unique — nuvo.app/mon-salon. Partagez-la sur WhatsApp, Instagram ou Facebook.',
    icon: Link2,
  },
  {
    n: '03',
    title: 'Recevez des réservations',
    desc: 'Les demandes arrivent en temps réel. Confirmez, annulez ou reprogrammez en un geste, depuis votre téléphone.',
    icon: BellRing,
  },
]
</script>

<template>
  <div class="hiw">
    <!-- ── Vague d'entrée ── -->
    <div class="wave wave--top" aria-hidden="true">
      <svg class="wave__layer wave__layer--back" viewBox="0 0 2880 200" preserveAspectRatio="none">
        <path d="M0,600 L0,105 C30,105 340,48 480,48 C620,48 820,85 960,85 C1100,85 1410,105 1440,105 C1470,105 1780,48 1920,48 C2060,48 2260,85 2400,85 C2540,85 2850,105 2880,105 L2880,600 Z" />
      </svg>
      <svg class="wave__layer wave__layer--mid" viewBox="0 0 2880 200" preserveAspectRatio="none">
        <path d="M0,600 L0,125 C30,125 250,68 360,68 C470,68 790,115 900,115 C1010,115 1410,125 1440,125 C1470,125 1690,68 1800,68 C1910,68 2230,115 2340,115 C2450,115 2850,125 2880,125 L2880,600 Z" />
      </svg>
      <svg class="wave__layer wave__layer--front" viewBox="0 0 2880 200" preserveAspectRatio="none">
        <path d="M0,600 L0,140 C30,140 480,92 700,92 C920,92 1060,134 1150,134 C1240,134 1410,140 1440,140 C1470,140 1920,92 2140,92 C2360,92 2500,134 2590,134 C2680,134 2850,140 2880,140 L2880,600 Z" />
      </svg>
    </div>

    <section class="hiw__body">
      <!--
        Le fond hérite du motif de rythme employé sur les pages publiques :
        une trame de blocs de temps, très effacée. Rien d'abstrait — c'est
        toujours la même idée qui circule d'un bout à l'autre du produit.
      -->
      <div class="hiw__texture" aria-hidden="true" />

      <div class="hiw__content">
        <header class="hiw__header">
          <h2 class="hiw__title">
            Opérationnel en <span class="hiw__title-accent">3 étapes</span>
          </h2>
          <p class="hiw__lede">Pas de formation, pas de consultant. Juste vous et cinq minutes.</p>
        </header>

        <ol class="hiw__steps">
          <li v-for="(step, index) in steps" :key="step.n" class="hiw__step">
            <!-- Le trait qui relie une étape à la suivante : la progression se
                 voit, au lieu d'être seulement numérotée. -->
            <span v-if="index < steps.length - 1" class="hiw__link" aria-hidden="true" />

            <span class="hiw__number numeric">{{ step.n }}</span>

            <span class="hiw__icon" aria-hidden="true">
              <component :is="step.icon" :size="22" :stroke-width="1.6" />
            </span>

            <h3 class="hiw__step-title">{{ step.title }}</h3>
            <p class="hiw__step-desc">{{ step.desc }}</p>
          </li>
        </ol>
      </div>
    </section>

    <!-- ── Vague de sortie ── -->
    <div class="wave wave--bottom" aria-hidden="true">
      <svg class="wave__layer wave__layer--back" viewBox="0 0 2880 200" preserveAspectRatio="none">
        <path d="M0,-400 L0,132 C30,132 370,89 520,89 C670,89 850,124 1000,124 C1150,124 1410,132 1440,132 C1470,132 1810,89 1960,89 C2110,89 2290,124 2440,124 C2590,124 2850,132 2880,132 L2880,-400 Z" />
      </svg>
      <svg class="wave__layer wave__layer--mid" viewBox="0 0 2880 200" preserveAspectRatio="none">
        <path d="M0,-400 L0,104 C30,104 280,64 400,64 C520,64 830,99 950,99 C1070,99 1410,104 1440,104 C1470,104 1720,64 1840,64 C1960,64 2270,99 2390,99 C2510,99 2850,104 2880,104 L2880,-400 Z" />
      </svg>
      <svg class="wave__layer wave__layer--front" viewBox="0 0 2880 200" preserveAspectRatio="none">
        <path d="M0,-400 L0,79 C30,79 440,34 640,34 C840,34 1090,72 1180,72 C1270,72 1410,79 1440,79 C1470,79 1880,34 2080,34 C2280,34 2530,72 2620,72 C2710,72 2850,79 2880,79 L2880,-400 Z" />
      </svg>
    </div>
  </div>
</template>

<style scoped>
.hiw {
  position: relative;
}

/* ── Les vagues ────────────────────────────────────────────────────── */

.wave {
  position: relative;
  height: clamp(190px, 23vw, 330px);
  overflow: hidden;
  /* Les couches se chevauchent d'un pixel avec les sections voisines : sans
     cela, un liseré clair apparaît sur certains facteurs de zoom. */
  margin-bottom: -1px;
  line-height: 0;
}

.wave--bottom {
  margin-bottom: 0;
  margin-top: -1px;
}

.wave__layer {
  position: absolute;
  inset: 0;
  width: 200%;
  height: 100%;
  /* Les tracés débordent volontairement du viewBox pour couvrir les coins
     que l'inclinaison dégage ; c'est `.wave` qui découpe la bande. */
  overflow: visible;
  transform-origin: center;
  /*
   * Chaque couche contient deux fois le même motif et se translate d'une
   * largeur exacte : au moment où l'animation reboucle, le dessin est
   * strictement identique et la répétition ne se voit pas.
   */
  animation: wave-drift linear infinite;
}

.wave__layer--back {
  fill: var(--forest-600);
  opacity: 0.5;
  animation-duration: 24s;
}

.wave__layer--mid {
  fill: var(--forest-800);
  opacity: 0.85;
  animation-duration: 17s;
  animation-direction: reverse;
}

/* La couche de devant porte la couleur pleine de la section : c'est elle qui
   ferme la jonction, les autres ne font que donner du relief. */
.wave__layer--front {
  fill: var(--forest-950);
  animation-duration: 13s;
}

/*
 * L'inclinaison de la vague d'origine.
 *
 * Elle est portée par la transformation, pas par le tracé : une pente écrite
 * dans le chemin s'accumulerait d'une copie à l'autre et la boucle
 * deviendrait visible. Elle n'est pas non plus déclarée en propriété `rotate`
 * séparée — celle-ci s'applique après la translation, si bien que la vague
 * dériverait en diagonale au lieu de glisser le long de sa propre crête.
 *
 * L'ordre compte : la translation agit dans le repère du calque, puis
 * l'ensemble est agrandi et incliné. L'agrandissement comble les coins que la
 * rotation dégagerait.
 */
@keyframes wave-drift {
  from { transform: rotate(-4.2deg) scale(1.08) translateX(0); }
  to   { transform: rotate(-4.2deg) scale(1.08) translateX(-50%); }
}

/* ── La section ────────────────────────────────────────────────────── */

.hiw__body {
  position: relative;
  padding: clamp(64px, 9vw, 112px) 24px clamp(72px, 10vw, 120px);
  background: var(--forest-950);
  color: #fff;
  overflow: hidden;
  isolation: isolate;
}

.hiw__texture {
  position: absolute;
  inset: 0;
  z-index: -1;
  opacity: 0.5;
  background-image:
    repeating-linear-gradient(
      to right,
      rgb(255 255 255 / 0.028) 0 2px,
      transparent 2px 96px
    ),
    repeating-linear-gradient(
      to bottom,
      rgb(255 255 255 / 0.02) 0 1px,
      transparent 1px 64px
    );
  mask-image: radial-gradient(ellipse 80% 70% at 50% 45%, #000 20%, transparent 85%);
  -webkit-mask-image: radial-gradient(ellipse 80% 70% at 50% 45%, #000 20%, transparent 85%);
}

.hiw__content {
  position: relative;
  max-width: 1040px;
  margin: 0 auto;
}

.hiw__header {
  text-align: center;
  margin-bottom: clamp(48px, 7vw, 80px);
}

.hiw__title {
  margin: 0 0 14px;
  font-size: clamp(30px, 4.6vw, 48px);
  letter-spacing: -0.025em;
  line-height: 1.1;
}

.hiw__title-accent {
  color: var(--forest-300);
}

.hiw__lede {
  margin: 0 auto;
  max-width: 42ch;
  font-size: clamp(15px, 1.8vw, 17px);
  line-height: 1.6;
  color: rgb(255 255 255 / 0.58);
}

/* ── Les étapes ────────────────────────────────────────────────────── */

.hiw__steps {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: clamp(22px, 3vw, 34px);
  margin: 0;
  padding: 0;
  list-style: none;
  counter-reset: step;
}

.hiw__step {
  position: relative;
  padding: 30px 26px 32px;
  border-radius: 18px;
  border: 1px solid rgb(255 255 255 / 0.08);
  background: rgb(255 255 255 / 0.032);
  backdrop-filter: blur(2px);
  transition: border-color 0.25s ease, transform 0.25s ease, background 0.25s ease;
}

.hiw__step:hover {
  transform: translateY(-3px);
  border-color: color-mix(in srgb, var(--forest-300) 34%, transparent);
  background: rgb(255 255 255 / 0.055);
}

/*
 * Le trait de liaison. Il ne se dessine qu'à partir de trois colonnes : dès
 * que la grille se replie, les cartes s'empilent et un trait horizontal
 * pointerait dans le vide.
 */
.hiw__link {
  display: none;
}

@media (min-width: 860px) {
  .hiw__link {
    display: block;
    position: absolute;
    top: 52px;
    right: calc(-1 * clamp(22px, 3vw, 34px));
    width: clamp(22px, 3vw, 34px);
    height: 1px;
    background: linear-gradient(
      to right,
      color-mix(in srgb, var(--forest-300) 40%, transparent),
      transparent
    );
  }
}

.hiw__number {
  display: block;
  font-size: 12px;
  letter-spacing: 0.18em;
  color: var(--forest-300);
  opacity: 0.75;
  margin-bottom: 18px;
}

.hiw__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  border-radius: 14px;
  margin-bottom: 20px;
  color: var(--forest-200);
  background: color-mix(in srgb, var(--forest-500) 22%, transparent);
  border: 1px solid color-mix(in srgb, var(--forest-300) 22%, transparent);
}

.hiw__icon svg {
  width: 22px;
  height: 22px;
}

.hiw__step-title {
  margin: 0 0 10px;
  font-size: 17px;
  font-weight: 700;
  color: #fff;
}

.hiw__step-desc {
  margin: 0;
  font-size: 14.5px;
  line-height: 1.62;
  color: rgb(255 255 255 / 0.56);
}

/*
 * Sans mouvement, les vagues se figent — elles gardent tout leur relief, seul
 * le glissement disparaît.
 */
@media (prefers-reduced-motion: reduce) {
  .wave__layer {
    animation: none;
  }

  .hiw__step {
    transition: none;
  }
}
</style>
