/**
 * Les jetons de design — la source unique de la palette.
 * ─────────────────────────────────────────────────────────────────────────
 *
 * CE FICHIER EST LE SEUL ENDROIT OÙ UNE COULEUR EST DÉFINIE.
 *
 * Changer l'identité visuelle du produit revient à éditer ce fichier, et rien
 * d'autre. Il alimente :
 *
 *   · tailwind.config.js  → les classes bg-*, text-*, border-*
 *   · src/style.css       → les variables CSS, pour les styles hors Tailwind
 *   · les composants      → via les classes, jamais en dur
 *
 * Une valeur hexadécimale écrite dans un fichier .vue est un bug : elle
 * échappe à ce fichier et survivra au prochain changement de palette. La seule
 * exception admise est la couleur d'accent choisie par le commerçant, qui
 * vient de la base de données et transite par useAccent().
 *
 * Les contrastes annotés ci-dessous sont mesurés, pas estimés. Toute
 * modification doit être revalidée — voir scripts/check-contrast.mjs.
 */

/**
 * Forêt — l'action et la confirmation.
 *
 * C'est le « oui, c'est réservé » du produit : boutons, liens, états confirmés,
 * éléments actifs de la navigation.
 */
export const forest = {
  50:  '#EDF7F1',
  100: '#D3EBDE',
  200: '#A8D7BF',
  300: '#74BC9B',
  400: '#419C76',
  500: '#1F7D57',
  600: '#14603C', // action par défaut — 6,2:1 sur le fond de page
  700: '#114D31',
  800: '#0E3E28',
  900: '#0B3120',
  950: '#051A11',
}

/**
 * Argile — les surfaces et le texte.
 *
 * Remplace le blanc et les gris. Le blanc ne dit rien et fatigue l'œil en
 * plein soleil, cas courant chez les utilisateurs de ce produit.
 *
 * Les nuances 400 et 500 sont plus sombres que la progression ne le voudrait :
 * elles portent du texte discret dans des centaines d'endroits et doivent
 * rester lisibles. La régularité de l'échelle compte moins que la lecture sur
 * un téléphone bon marché.
 */
export const clay = {
  50:  '#FBF8F4', // cartes et surfaces hautes
  100: '#F0E8DA', // fond de page
  200: '#E2D6C4', // bordures et séparateurs
  300: '#CBBBA2',
  400: '#836F52', // texte discret — 4,2:1 sur le fond de page
  500: '#7E6A4E',
  600: '#665640', // texte secondaire — 5,1:1
  700: '#4F4333',
  800: '#3A3128',
  900: '#2A241E',
  950: '#1F1B16', // texte principal — 14,1:1
}

/**
 * Couleurs de sens — elles disent un état, jamais une décoration.
 *
 * Chacune existe en teinte de fond, de bordure et de texte, pour que les
 * badges et les alertes se composent sans inventer de nuance au cas par cas.
 */
export const semantic = {
  /** En attente d'une décision du commerçant. Teinte propre : l'ambre n'existe
   *  dans aucune des deux échelles. */
  pending: { bg: '#FEF6E7', border: '#F7DCA8', text: '#8A5A08', solid: '#C2820C' },

  /**
   * Confirmé — dérivé de l'échelle forêt, pas recopié.
   *
   * C'est le même geste que l'action : changer le vert du produit doit changer
   * la pastille « Confirmé ». Écrites en dur, ces valeurs restaient figées
   * pendant que le reste basculait — la duplication que ce fichier existe pour
   * supprimer, à l'intérieur de ce fichier.
   */
  confirmed: { bg: forest[50], border: forest[200], text: forest[800], solid: forest[600] },

  /** Annulé. Rouge propre, hors échelles. */
  cancelled: { bg: '#FDF0EE', border: '#F5C4BC', text: '#8C2A18', solid: '#BE3A22' },

  /** Terminé — froid et neutre : c'est du passé, pas une alerte. */
  completed: { bg: '#EEF3F8', border: '#C3D5E6', text: '#1F4260', solid: '#2E6394' },

  /** Non présenté — dérivé de l'argile, sans dramatisation. */
  noShow: { bg: clay[100], border: clay[300], text: clay[700], solid: clay[500] },
}

/** La marque : dégradé du logo, du favicon et des icônes PWA. */
export const brand = {
  from: forest[500],
  to: forest[800],
  /** Barre d'adresse mobile et manifeste PWA. */
  theme: forest[600],
}

/**
 * Le nuancier proposé aux commerçants.
 *
 * Ce ne sont pas des jetons de marque mais des données : le commerçant choisit
 * la couleur de sa page publique et celle de ses services. Elles vivent tout
 * de même ici, parce qu'elles étaient recopiées à l'identique dans deux vues,
 * avec un défaut pointant encore sur l'ancien violet du produit.
 *
 * Toutes ont été retenues pour rester lisibles en pastille et en fond de
 * bandeau, avec le texte blanc ou noir que useAccent choisit selon leur
 * luminance.
 */
export const swatches = [
  forest[600], // le vert du produit, proposé en premier
  '#8B5CF6',   // violet
  '#EC4899',   // rose
  '#F59E0B',   // ambre
  '#0EA5E9',   // bleu ciel
  '#EF4444',   // rouge
  '#06B6D4',   // cyan
  '#F97316',   // orange
]

/** Couleur d'un commerce ou d'un service tant qu'il n'en a pas choisi. */
export const defaultAccent = forest[600]

/** Couleurs imposées de l'extérieur — jamais choisies, jamais modifiables. */
export const external = {
  whatsapp: '#25D366',
  whatsappHover: '#1FB855',
}

/**
 * L'échelle `gray` de Tailwind est remappée sur l'argile.
 *
 * Les templates portent des centaines de classes text-gray-*, bg-gray-* et
 * border-gray-*. Plutôt que de les réécrire — un diff illisible pour un
 * résultat identique — le remappage les teinte toutes d'un coup, et reste
 * réversible.
 */
export const colors = {
  primary: forest,
  forest,
  clay,
  gray: clay,
}

/**
 * Les jetons sous forme de déclarations CSS, posées sur :root.
 *
 * Consommé par le plugin déclaré dans tailwind.config.js. Ce qui n'est pas
 * exprimable en classe utilitaire — un dégradé, une teinte lue par color-mix,
 * la couleur d'un bloc du ruban — lit ces variables plutôt que de recopier une
 * valeur. Le fichier reste la source unique quelle que soit la manière dont la
 * couleur est consommée.
 */
export function cssDeclarations() {
  const vars = {}

  for (const [shade, value] of Object.entries(forest)) vars[`--forest-${shade}`] = value
  for (const [shade, value] of Object.entries(clay)) vars[`--clay-${shade}`] = value

  for (const [name, tones] of Object.entries(semantic)) {
    for (const [role, value] of Object.entries(tones)) {
      vars[`--${name}-${role}`] = value
    }
  }

  vars['--brand-from'] = brand.from
  vars['--brand-to'] = brand.to
  vars['--whatsapp'] = external.whatsapp
  vars['--whatsapp-hover'] = external.whatsappHover

  return vars
}

export default { forest, clay, semantic, brand, external, swatches, defaultAccent, colors }
