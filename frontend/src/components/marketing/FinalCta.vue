<script setup>
import { RouterLink } from 'vue-router'

/*
 * ── La semaine qui se remplit, en fond du dernier appel à l'action ──
 *
 * Le fond de cette section n'est pas décoratif : c'est le produit. Une semaine
 * de commerce dont les rendez-vous apparaissent un à un.
 *
 * Le temps court à l'horizontale, comme dans le ruban de la page de
 * réservation et comme dans les barres de durée : une prestation longue est
 * un bloc long. En colonnes, les blocs devenaient des carrés et se lisaient
 * comme des cartes — la durée ne se voyait plus, et le langage du produit
 * était contredit à l'endroit même où il devrait convaincre.
 *
 * Les positions sont fixes plutôt que tirées au sort : un tirage donnerait un
 * agenda différent — parfois incohérent — à chaque visite, là où ce dessin
 * ressemble à une vraie semaine, avec des matinées denses, un creux à midi et
 * un dimanche fermé.
 */
const ctaWeek = (() => {
  const DAY_START = 8
  const DAY_HOURS = 11

  const days = [
    { label: 'Lun', blocks: [[8, 90], [10, 60], [13, 180], [17, 60]] },
    { label: 'Mar', blocks: [[8, 60], [9.5, 45], [11, 120], [15, 90]] },
    { label: 'Mer', blocks: [[9, 180], [14, 60], [16, 90]] },
    { label: 'Jeu', blocks: [[8, 45], [9, 60], [10.5, 90], [14, 180]] },
    { label: 'Ven', blocks: [[8, 120], [11, 60], [13.5, 90], [15.5, 150]] },
    { label: 'Sam', blocks: [[8, 180], [12, 120], [15, 60]] },
    { label: 'Dim', blocks: [] },
  ]

  return days.map((day, dayIndex) => ({
    label: day.label,
    slots: day.blocks.map(([hour, minutes], slotIndex) => ({
      id: `${dayIndex}-${slotIndex}`,
      left: ((hour - DAY_START) / DAY_HOURS) * 100,
      width: (minutes / 60 / DAY_HOURS) * 100,
      // La semaine se remplit de haut en bas et de gauche à droite, comme on
      // la lit.
      delay: dayIndex * 150 + slotIndex * 110,
    })),
  }))
})()
</script>

<template>
    <!--
      ══════ CTA final ══════

      Refonte complète. L'ancienne version cumulait ce que les pages qui
      convertissent évitent : deux boutons qui se disputent l'attention, une
      accroche interchangeable (« passer au niveau supérieur » ne veut rien
      dire), et une décoration abondante — trois polygones en rotation, deux
      halos, une trame hexagonale — sans rapport avec le produit.

      Trois partis pris :

      · Un seul bouton. Une page à CTA unique convertit nettement mieux qu'une
        page qui en propose plusieurs ; « Nous contacter » descend d'un cran,
        en lien discret.

      · Le fond est le produit. Plutôt que des formes abstraites, une semaine
        qui se remplit sous les yeux du visiteur : c'est le langage du temps
        employé partout ailleurs dans l'application, et c'est ce que le
        commerçant achète. Le mouvement dit quelque chose au lieu de meubler.

      · L'accroche nomme le bénéfice, pas l'ambition. « Vos clients réservent.
        Vous travaillez. » est vérifiable ; « niveau supérieur » ne l'est pas.
    -->
    <section class="cta">
      <div class="cta__backdrop" aria-hidden="true">
        <!--
          La semaine du commerce, qui se remplit.

          Les créneaux apparaissent en cascade, décalés par un délai calculé
          depuis leur position — l'effet est celui d'un agenda qui prend, pas
          d'une animation décorative. Purement CSS : rien à charger, rien à
          exécuter en JavaScript sur un téléphone d'entrée de gamme.
        -->
        <div class="cta__week">
          <div v-for="day in ctaWeek" :key="day.label" class="cta__day">
            <span class="cta__day-label">{{ day.label }}</span>
            <div class="cta__day-track">
              <span
                v-for="slot in day.slots"
                :key="slot.id"
                class="cta__slot"
                :style="{
                  left: `${slot.left}%`,
                  width: `${slot.width}%`,
                  animationDelay: `${slot.delay}ms`,
                }"
              />
            </div>
          </div>
        </div>
      </div>

      <div class="cta__content">
        <h2 class="cta__title">
          Vos clients réservent.<br />
          <span class="cta__title-accent">Vous travaillez.</span>
        </h2>

        <p class="cta__lede">
          Pas d'appel à décrocher entre deux clients, pas d'agenda à tenir à
          jour. Votre lien s'occupe des réservations pendant que vous vous
          occupez du reste.
        </p>

        <RouterLink to="/register" class="cta__button">
          <span>Créer ma page</span>
          <svg class="cta__arrow" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M4 10h11m0 0-4-4m4 4-4 4" stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </RouterLink>

        <p class="cta__reassurance">
          <span class="numeric">14</span> jours d'essai Pro · sans carte bancaire · sans engagement
        </p>

        <p class="cta__secondary">
          Une question avant de vous lancer ?
          <RouterLink to="/contact">Écrivez-nous</RouterLink>
        </p>
      </div>
    </section>
</template>

<style scoped>

.cta {
  position: relative;
  overflow-x: clip;
  overflow-y: hidden;
  max-width: 100vw;
  padding: 128px 24px 136px;
  background:
    radial-gradient(120% 80% at 50% 0%, var(--forest-800) 0%, transparent 60%),
    var(--forest-950);
  isolation: isolate;
}

@media (max-width: 640px) {
  .cta { padding: 88px 20px 96px; }
}

/* ── Le fond : une semaine qui se remplit ── */

.cta__backdrop {
  position: absolute;
  inset: 0;
  z-index: -1;
  display: flex;
  align-items: center;
  justify-content: center;
  /*
   * Le motif s'efface vers le centre, où vit le texte. Un masque plutôt qu'une
   * simple opacité : les blocs restent nets sur les bords, là où on les lit
   * comme un agenda, et disparaissent complètement derrière le titre.
   */
  mask-image: radial-gradient(ellipse 62% 58% at 50% 50%, transparent 42%, #000 88%);
  -webkit-mask-image: radial-gradient(ellipse 62% 58% at 50% 50%, transparent 42%, #000 88%);
  opacity: 0.85;
}

.cta__week {
  display: flex;
  flex-direction: column;
  gap: clamp(10px, 2.4vh, 26px);
  width: min(1180px, 88vw);
}

.cta__day {
  display: flex;
  align-items: center;
  gap: 14px;
}

.cta__day-label {
  width: 34px;
  flex-shrink: 0;
  text-align: right;
  font-family: 'Yuzo', Roboto, monospace;
  font-size: 10px;
  letter-spacing: 0.1em;
  color: rgb(255 255 255 / 0.2);
}

.cta__day-track {
  position: relative;
  flex: 1;
  height: clamp(18px, 3.4vh, 34px);
  border-radius: 7px;
  background: rgb(255 255 255 / 0.025);
  /* Une graduation horaire discrète : le repère qui fait lire un agenda
     plutôt qu'une frise abstraite. */
  background-image: repeating-linear-gradient(
    to right,
    rgb(255 255 255 / 0.045) 0 1px,
    transparent 1px 9.09%
  );
}

.cta__slot {
  position: absolute;
  top: 3px;
  bottom: 3px;
  border-radius: 5px;
  background: linear-gradient(
    90deg,
    color-mix(in srgb, var(--forest-400) 55%, transparent),
    color-mix(in srgb, var(--forest-500) 38%, transparent)
  );
  box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.07);
  /* Le bloc se déploie depuis son heure de début : le mouvement raconte une
     durée qui se pose, pas une forme qui apparaît. */
  transform-origin: left;
  animation: cta-slot-land 0.66s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes cta-slot-land {
  from { opacity: 0; transform: scaleX(0.12); }
  to   { opacity: 1; transform: scaleX(1); }
}

/* ── Le contenu ── */

.cta__content {
  position: relative;
  width: 100%;
  max-width: 720px;
  margin: 0 auto;
  text-align: center;
}

.cta__title {
  margin: 0 0 20px;
  font-size: clamp(29px, 6.4vw, 56px);
  line-height: 1.06;
  letter-spacing: -0.028em;
  color: #fff;
}

/*
 * La seconde ligne prend la couleur claire de l'échelle plutôt qu'un dégradé.
 * Sur un fond déjà vert, un texte en dégradé vert perdait tout contraste ;
 * une teinte franche tranche mieux et reste lisible sur un écran bon marché.
 */
.cta__title-accent {
  color: var(--forest-300);
}

.cta__lede {
  margin: 0 auto 36px;
  max-width: 46ch;
  font-size: clamp(15px, 2vw, 17.5px);
  line-height: 1.62;
  color: rgb(255 255 255 / 0.62);
}

.cta__button {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 17px 34px;
  border-radius: var(--radius-control);
  background: var(--clay-50);
  color: var(--forest-900);
  font-weight: 700;
  font-size: 16px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  box-shadow: 0 10px 34px rgb(0 0 0 / 0.28);
}

.cta__button:hover {
  transform: translateY(-2px);
  box-shadow: 0 16px 44px rgb(0 0 0 / 0.36);
}

.cta__button:focus-visible {
  outline: 2px solid var(--forest-300);
  outline-offset: 4px;
}

.cta__arrow {
  width: 19px;
  height: 19px;
  transition: transform 0.2s ease;
}

.cta__button:hover .cta__arrow {
  transform: translateX(4px);
}

.cta__reassurance {
  margin: 20px 0 0;
  font-size: 13px;
  color: rgb(255 255 255 / 0.44);
}

/*
 * « Nous contacter » descend au rang de lien.
 *
 * Deux boutons de même poids se disputaient l'attention ; une page qui ne
 * propose qu'une action convertit nettement mieux. Le contact reste accessible
 * — il cesse simplement de concurrencer l'inscription.
 */
.cta__secondary {
  margin: 34px 0 0;
  font-size: 13.5px;
  color: rgb(255 255 255 / 0.38);
}

.cta__secondary a {
  color: rgb(255 255 255 / 0.78);
  text-decoration: underline;
  text-underline-offset: 3px;
  transition: color 0.15s ease;
}

.cta__secondary a:hover {
  color: #fff;
}

/*
 * Sans mouvement, la semaine s'affiche d'emblée : l'information reste, seule
 * la mise en scène disparaît.
 */
@media (prefers-reduced-motion: reduce) {
  .cta__slot {
    animation: none;
    opacity: 1;
  }

  .cta__button,
  .cta__arrow {
    transition: none;
  }
}

</style>
