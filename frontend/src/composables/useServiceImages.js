import { computed, ref } from 'vue'

/** Cinq images par prestation, et deux mégaoctets chacune. */
export const MAX_IMAGES = 5
export const MAX_BYTES = 2 * 1024 * 1024

/**
 * La galerie d'une prestation, en cours d'édition.
 *
 * Trois listes doivent rester d'accord : les fichiers à téléverser, les chemins
 * déjà enregistrés côté serveur, et les vignettes affichées. Elles vivaient
 * dans la vue au milieu du reste, et retirer une image demandait de penser aux
 * trois à la main.
 *
 * Le composable les tient ensemble et expose ce dont le formulaire a besoin :
 * ajouter, retirer, remplir le FormData.
 */
export function useServiceImages() {
  /** Fichiers choisis dans cette session d'édition. */
  const nouveaux = ref([])

  /** Chemins déjà stockés, tels que l'API les attend en retour. */
  const existants = ref([])

  /** Ce que l'écran montre : `{ url, existante, chemin?, fichier? }`. */
  const vignettes = ref([])

  /** Motif du dernier fichier écarté, à montrer plutôt qu'à taire. */
  const refus = ref('')

  const total = computed(() => existants.value.length + nouveaux.value.length)
  const placeLibre = computed(() => total.value < MAX_IMAGES)

  function reinitialiser() {
    // Les URL d'objet non révoquées gardent le fichier en mémoire tant que
    // l'onglet vit. Ouvrir puis fermer le formulaire dix fois les accumulait.
    vignettes.value.filter((v) => !v.existante).forEach((v) => URL.revokeObjectURL(v.url))

    nouveaux.value = []
    existants.value = []
    vignettes.value = []
    refus.value = ''
  }

  /** Repart des images déjà enregistrées sur une prestation. */
  function charger(images = []) {
    reinitialiser()
    existants.value = images.map((image) => image.path)
    vignettes.value = images.map((image) => ({
      url: image.url,
      existante: true,
      chemin: image.path,
    }))
  }

  /**
   * Ajoute les fichiers retenus et dit lesquels ont été écartés.
   *
   * Les fichiers refusés l'étaient en silence : un commerçant qui déposait une
   * photo de 4 Mo la voyait simplement ne pas apparaître, sans savoir pourquoi.
   */
  function ajouter(fichiers) {
    const ecartes = []

    for (const fichier of Array.from(fichiers)) {
      if (!placeLibre.value) {
        ecartes.push(`${fichier.name} (maximum ${MAX_IMAGES} images)`)
        continue
      }
      if (!fichier.type.startsWith('image/')) {
        ecartes.push(`${fichier.name} (ce n'est pas une image)`)
        continue
      }
      if (fichier.size > MAX_BYTES) {
        ecartes.push(`${fichier.name} (plus de 2 Mo)`)
        continue
      }

      nouveaux.value.push(fichier)
      vignettes.value.push({
        url: URL.createObjectURL(fichier),
        existante: false,
        fichier,
      })
    }

    refus.value = ecartes.length ? `Non ajouté : ${ecartes.join(', ')}.` : ''
  }

  function retirer(index) {
    const vignette = vignettes.value[index]
    if (!vignette) return

    if (vignette.existante) {
      existants.value = existants.value.filter((chemin) => chemin !== vignette.chemin)
    } else {
      nouveaux.value = nouveaux.value.filter((f) => f !== vignette.fichier)
      URL.revokeObjectURL(vignette.url)
    }

    vignettes.value.splice(index, 1)
  }

  /**
   * Verse les images dans le FormData.
   *
   * `existing_images` n'est envoyé qu'en modification, et toujours, même vide :
   * une clé absente veut dire « pas d'avis », une liste vide veut dire « le
   * commerçant a tout retiré ». Les confondre effaçait ses photos ou les
   * ressuscitait.
   */
  function remplir(formData, { modification }) {
    for (const fichier of nouveaux.value) {
      formData.append('images[]', fichier)
    }

    if (!modification) return

    for (const chemin of existants.value) {
      formData.append('existing_images[]', chemin)
    }
    if (existants.value.length === 0) formData.append('existing_images', '')
  }

  return {
    vignettes, refus, total, placeLibre,
    charger, ajouter, retirer, reinitialiser, remplir,
  }
}
