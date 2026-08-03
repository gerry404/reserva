import {
  Bath, Brush, ConciergeBell, Droplet, Eye, Feather, Flower2, Footprints,
  Gem, Hand, HandHeart, Leaf, Palette, Ribbon, Scissors, SprayCan, Sun, Waves, Wind,
} from 'lucide-vue-next'

/**
 * Les icônes de prestation : la source unique.
 *
 * Chaque service affichait le même glyphe `✦` sur fond de couleur, écrit en dur
 * dans la carte. Un salon qui propose une coupe, un massage et une manucure
 * voyait trois fois la même étoile : le repère visuel ne distinguait rien, et
 * n'avait aucun rapport avec le métier.
 *
 * Deux mécanismes se complètent ici :
 *
 *   · le commerçant choisit une icône, et elle est stockée avec le service ;
 *   · s'il n'en choisit pas, `iconForService` la déduit du nom, ce qui donne
 *     un repère juste dès la création sans rien demander.
 *
 * La déduction ne remplace jamais un choix explicite : c'est un défaut, pas une
 * correction. Un institut qui range « Rituel signature » sous une fleur doit
 * pouvoir garder sa fleur même si le nom évoque autre chose.
 */

/**
 * Le catalogue proposé.
 *
 * `match` liste les racines de mots qui désignent la prestation. Elles sont
 * comparées sans accent ni casse, parce qu'un commerçant saisit « defrisage »
 * aussi souvent que « défrisage ».
 */
export const SERVICE_ICONS = [
  { key: 'scissors',  label: 'Coupe',        component: Scissors,      match: ['coupe', 'coiffure', 'cheveu', 'ciseau', 'frange', 'degrade'] },
  { key: 'razor',     label: 'Barbe',        component: Feather,       match: ['barbe', 'rasage', 'moustache', 'taille'] },
  { key: 'braid',     label: 'Tresses',      component: Ribbon,        match: ['tresse', 'natte', 'locks', 'vanille', 'twist'] },
  { key: 'color',     label: 'Coloration',   component: Palette,       match: ['coloration', 'couleur', 'meche', 'balayage', 'teinture', 'defrisage'] },
  { key: 'blowdry',   label: 'Brushing',     component: Wind,          match: ['brushing', 'sechage', 'lissage', 'boucle'] },
  { key: 'care',      label: 'Soin',         component: Droplet,       match: ['soin', 'shampoing', 'masque', 'hydrat', 'traitement'] },
  { key: 'face',      label: 'Visage',       component: Flower2,       match: ['visage', 'facial', 'peau', 'nettoyage'] },
  { key: 'makeup',    label: 'Maquillage',   component: Brush,         match: ['maquillage', 'make', 'teint', 'levre'] },
  { key: 'brows',     label: 'Cils, sourcils', component: Eye,         match: ['cil', 'sourcil', 'regard', 'extension'] },
  { key: 'nails',     label: 'Ongles',       component: Hand,          match: ['manucure', 'ongle', 'vernis', 'gel', 'capsule'] },
  { key: 'feet',      label: 'Pieds',        component: Footprints,    match: ['pedicure', 'pied', 'callus'] },
  { key: 'massage',   label: 'Massage',      component: HandHeart,     match: ['massage', 'modelage', 'relax', 'detente', 'dos'] },
  { key: 'spa',       label: 'Spa',          component: Waves,         match: ['spa', 'hammam', 'sauna', 'rituel', 'bien'] },
  { key: 'bath',      label: 'Gommage',      component: Bath,          match: ['gommage', 'bain', 'exfoli', 'enveloppement'] },
  { key: 'wax',       label: 'Épilation',    component: SprayCan,      match: ['epilation', 'cire', 'laser', 'jambe', 'aisselle', 'maillot'] },
  { key: 'tan',       label: 'Bronzage',     component: Sun,           match: ['bronzage', 'uv', 'solaire'] },
  { key: 'natural',   label: 'Naturel',      component: Leaf,          match: ['naturel', 'bio', 'vegetal', 'huile'] },
  { key: 'premium',   label: 'Premium',      component: Gem,           match: ['premium', 'luxe', 'signature', 'prestige', 'vip'] },
  { key: 'default',   label: 'Prestation',   component: ConciergeBell, match: [] },
]

const PAR_CLE = Object.fromEntries(SERVICE_ICONS.map((i) => [i.key, i]))

/** L'icône employée quand rien ne correspond : une sonnette de comptoir. */
export const DEFAULT_SERVICE_ICON = PAR_CLE.default

/** Les seules clés que le serveur accepte. */
export const SERVICE_ICON_KEYS = SERVICE_ICONS.map((i) => i.key)

/** Retire les accents et la casse, pour que « défrisage » et « defrisage » se rejoignent. */
function normaliser(texte) {
  return String(texte ?? '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
}

/**
 * L'icône d'un service.
 *
 * Le choix explicite l'emporte toujours. À défaut, le nom puis la catégorie
 * sont comparés au catalogue, et la sonnette sert de repli.
 */
export function iconForService(service) {
  if (!service) return DEFAULT_SERVICE_ICON.component

  const choisi = PAR_CLE[service.icon]
  if (choisi) return choisi.component

  const texte = normaliser(`${service.name ?? ''} ${service.category ?? ''}`)

  for (const entree of SERVICE_ICONS) {
    if (entree.match.some((racine) => texte.includes(racine))) {
      return entree.component
    }
  }

  return DEFAULT_SERVICE_ICON.component
}

/** La clé déduite, pour préselectionner le choix dans le formulaire. */
export function guessIconKey(name, category = '') {
  const texte = normaliser(`${name} ${category}`)

  for (const entree of SERVICE_ICONS) {
    if (entree.match.some((racine) => texte.includes(racine))) return entree.key
  }

  return DEFAULT_SERVICE_ICON.key
}
