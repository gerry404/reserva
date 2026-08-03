<script setup>
import { ref, onMounted, onUnmounted, reactive, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { servicesApi } from '@/api'
import { defaultAccent, swatches } from '@/design/tokens'
import {
  PlusIcon,
  PencilIcon,
  TrashIcon,
  Squares2X2Icon,
  ClockIcon,
  BanknotesIcon,
  PhotoIcon,
  XMarkIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'

const auth    = useAuthStore()
const services = ref([])
const loading  = ref(true)
const modal    = ref(false)
const editing  = ref(null)
const deleting = ref(null)
const errors   = ref({})
const saving   = ref(false)

// Gallery state
const gallery = ref(null)  // { images: [], index: 0 }

function openGallery(svc, startIndex = 0) {
  gallery.value = { images: svc.images, name: svc.name, index: startIndex }
}

function galleryNext() {
  if (!gallery.value) return
  gallery.value.index = (gallery.value.index + 1) % gallery.value.images.length
}

function galleryPrev() {
  if (!gallery.value) return
  gallery.value.index = (gallery.value.index - 1 + gallery.value.images.length) % gallery.value.images.length
}

function onGalleryKey(e) {
  if (!gallery.value) return
  if (e.key === 'ArrowRight') galleryNext()
  else if (e.key === 'ArrowLeft') galleryPrev()
  else if (e.key === 'Escape') gallery.value = null
}

const form = reactive({
  name: '', description: '', duration: 30, price: 0, category: '', color: defaultAccent,
})

// Images state
const newImages = ref([])        // File objects to upload
const existingImages = ref([])   // paths already saved on server
const imagePreviews = ref([])    // preview URLs for display

const MAX_IMAGES = 5
const totalImages = computed(() => existingImages.value.length + newImages.value.length)
const canAddMore = computed(() => totalImages.value < MAX_IMAGES)

const colors = swatches

const durations = [
  { v: 15, l: '15 min' }, { v: 30, l: '30 min' }, { v: 45, l: '45 min' },
  { v: 60, l: '1h' },     { v: 90, l: '1h 30' },  { v: 120, l: '2h' },
  { v: 180, l: '3h' },    { v: 240, l: '4h' },
]

// Images arrive as { path, url }: `url` renders, `path` is what the API wants
// back in existing_images. Building URLs here is the server's job, not ours.


async function loadServices() {
  loading.value = true
  try {
    const data = await servicesApi.list()
    services.value = data
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadServices()
  document.addEventListener('keydown', onGalleryKey)
})

onUnmounted(() => {
  document.removeEventListener('keydown', onGalleryKey)
})

function resetImageState() {
  newImages.value = []
  existingImages.value = []
  imagePreviews.value = []
}

function openCreate() {
  editing.value = null
  Object.assign(form, { name: '', description: '', duration: 30, price: 0, category: '', color: defaultAccent })
  errors.value = {}
  resetImageState()
  modal.value  = true
}

function openEdit(svc) {
  editing.value = svc.id
  Object.assign(form, {
    name: svc.name, description: svc.description ?? '', duration: svc.duration,
    price: svc.price, category: svc.category ?? '', color: svc.color ?? defaultAccent,
  })
  errors.value = {}
  resetImageState()
  existingImages.value = (svc.images ?? []).map(image => image.path)
  imagePreviews.value  = (svc.images ?? []).map(image => ({
    url: image.url, isExisting: true, path: image.path,
  }))
  modal.value  = true
}

function onFilesSelected(e) {
  const files = Array.from(e.target.files)
  const remaining = MAX_IMAGES - totalImages.value
  const toAdd = files.slice(0, remaining)

  for (const file of toAdd) {
    if (!file.type.startsWith('image/')) continue
    if (file.size > 2 * 1024 * 1024) continue // 2MB max
    newImages.value.push(file)
    imagePreviews.value.push({ url: URL.createObjectURL(file), isExisting: false, file })
  }
  e.target.value = ''
}

function removeImage(index) {
  const preview = imagePreviews.value[index]
  if (preview.isExisting) {
    existingImages.value = existingImages.value.filter(p => p !== preview.path)
  } else {
    newImages.value = newImages.value.filter(f => f !== preview.file)
    URL.revokeObjectURL(preview.url)
  }
  imagePreviews.value.splice(index, 1)
}

function buildFormData() {
  const fd = new FormData()
  fd.append('name', form.name)
  fd.append('description', form.description || '')
  fd.append('duration', form.duration)
  fd.append('price', form.price)
  fd.append('category', form.category || '')
  fd.append('color', form.color)

  for (const file of newImages.value) {
    fd.append('images[]', file)
  }

  if (editing.value) {
    // Always sent, even when empty: an absent key means "no opinion", while an
    // empty list means "the merchant removed every picture".
    for (const path of existingImages.value) {
      fd.append('existing_images[]', path)
    }
    if (existingImages.value.length === 0) fd.append('existing_images', '')
  }

  return fd
}

async function save() {
  errors.value = {}
  saving.value = true
  try {
    const fd = buildFormData()
    if (editing.value) {
      const data = await servicesApi.update(editing.value, fd)
      const idx = services.value.findIndex(s => s.id === editing.value)
      if (idx !== -1) services.value[idx] = data
    } else {
      const data = await servicesApi.create(fd)
      services.value.unshift(data)
    }
    modal.value = false
  } catch (e) {
    errors.value = e.fieldErrors
  } finally {
    saving.value = false
  }
}

async function toggleService(svc) {
  const data = await servicesApi.toggle(svc.id)
  const idx = services.value.findIndex(s => s.id === svc.id)
  if (idx !== -1) services.value[idx] = data
}

async function deleteService(id) {
  try {
    await servicesApi.delete(id)
    services.value = services.value.filter(s => s.id !== id)
  } catch (e) {
    alert(e.message ?? 'Impossible de supprimer ce service.')
  } finally {
    deleting.value = null
  }
}

function formatPrice(price) {
  if (price == 0) return 'Gratuit'
  return Number(price).toLocaleString('fr-FR') + ' F'
}

function formatDuration(min) {
  if (min < 60) return min + ' min'
  const h = Math.floor(min / 60), m = min % 60
  return m ? `${h}h${m}` : `${h}h`
}
</script>

<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-black text-gray-900">Services</h2>
        <p class="text-sm text-gray-500 mt-0.5">{{ services.length }} service(s) configuré(s)</p>
      </div>
      <button @click="openCreate" class="btn-primary">
        <PlusIcon class="w-4 h-4" />
        Nouveau service
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 3" :key="i" class="card p-5 animate-pulse">
        <div class="h-5 bg-gray-100 rounded w-2/3 mb-3" />
        <div class="h-3 bg-gray-100 rounded w-full mb-2" />
        <div class="h-3 bg-gray-100 rounded w-1/2" />
      </div>
    </div>

    <!-- Empty state -->
    <div v-else-if="services.length === 0" class="card p-16 text-center">
      <Squares2X2Icon class="w-16 h-16 text-gray-200 mx-auto mb-4" />
      <h3 class="font-bold text-gray-900 mb-2">Aucun service encore</h3>
      <p class="text-gray-400 text-sm mb-6">Ajoutez vos services pour que vos clients puissent réserver.</p>
      <button @click="openCreate" class="btn-primary mx-auto">
        <PlusIcon class="w-4 h-4" /> Créer mon premier service
      </button>
    </div>

    <!-- Services grid -->
    <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="svc in services"
        :key="svc.id"
        :class="['card overflow-hidden flex flex-col transition-all', !svc.is_active && 'opacity-60']"
      >
        <!-- Image banner (click to open gallery) -->
        <div v-if="svc.images && svc.images.length" class="relative h-36 overflow-hidden bg-gray-100 cursor-pointer group/img" @click.stop="openGallery(svc)">
          <img :src="svc.images[0].url" :alt="svc.name" class="w-full h-full object-cover group-hover/img:scale-105 transition-transform duration-300" />
          <div class="absolute inset-0 bg-black/0 group-hover/img:bg-black/20 transition-colors flex items-center justify-center">
            <PhotoIcon class="w-6 h-6 text-white opacity-0 group-hover/img:opacity-100 transition-opacity drop-shadow-lg" />
          </div>
          <span v-if="svc.images.length > 1" class="absolute bottom-2 right-2 bg-black/60 text-white text-[10px] font-bold px-2 py-0.5 rounded-full backdrop-blur-sm">
            +{{ svc.images.length - 1 }}
          </span>
        </div>

        <div class="p-5 flex flex-col gap-4 flex-1">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-lg shrink-0"
                :style="{ backgroundColor: svc.color ?? defaultAccent }">
                ✦
              </div>
              <div>
                <h3 class="font-bold text-gray-900 text-sm">{{ svc.name }}</h3>
                <p v-if="svc.category" class="text-xs text-gray-400">{{ svc.category }}</p>
              </div>
            </div>
            <!-- Active toggle -->
            <button
              @click="toggleService(svc)"
              :class="['relative inline-flex h-5 w-9 shrink-0 rounded-full transition-colors duration-200', svc.is_active ? 'bg-primary-600' : 'bg-gray-200']"
            >
              <span :class="['absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-clay-50 shadow transition-transform duration-200', svc.is_active ? 'translate-x-4' : 'translate-x-0']" />
            </button>
          </div>

          <p v-if="svc.description" class="text-xs text-gray-500 leading-relaxed">{{ svc.description }}</p>

          <div class="flex items-center gap-4 text-sm">
            <div class="flex items-center gap-1.5 text-gray-600">
              <ClockIcon class="w-4 h-4 text-gray-400" />
              {{ formatDuration(svc.duration) }}
            </div>
            <div class="flex items-center gap-1.5 font-semibold text-gray-900">
              <BanknotesIcon class="w-4 h-4 text-gray-400" />
              {{ formatPrice(svc.price) }}
            </div>
          </div>

          <div class="flex gap-2 pt-1 border-t border-gray-50 mt-auto">
            <button @click="openEdit(svc)" class="btn-ghost text-xs flex-1 py-1.5">
              <PencilIcon class="w-3.5 h-3.5" /> Modifier
            </button>
            <button @click="deleting = svc.id" class="btn-ghost text-xs text-red-500 hover:text-red-700 hover:bg-red-50 flex-1 py-1.5">
              <TrashIcon class="w-3.5 h-3.5" /> Supprimer
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Create/Edit Modal -->
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="bg-clay-50 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto animate-slide-up">
          <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-900 text-lg">{{ editing ? 'Modifier le service' : 'Nouveau service' }}</h2>
            <button @click="modal = false" class="btn-ghost p-2">✕</button>
          </div>

          <form @submit.prevent="save" class="p-6 space-y-5">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nom du service *</label>
              <input v-model="form.name" type="text" class="input-field" placeholder="ex: Coiffure naturelle" required />
              <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Description</label>
              <textarea v-model="form.description" class="input-field resize-none" rows="2" placeholder="Description optionnelle…" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Durée *</label>
                <select v-model="form.duration" class="input-field">
                  <option v-for="d in durations" :key="d.v" :value="d.v">{{ d.l }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Prix (F CFA)</label>
                <input v-model.number="form.price" type="number" min="0" step="100" class="input-field" placeholder="0" />
                <p v-if="errors.price" class="text-red-500 text-xs mt-1">{{ errors.price[0] }}</p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Catégorie</label>
              <input v-model="form.category" type="text" class="input-field" placeholder="ex: Soin capillaire" />
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Couleur</label>
              <div class="flex gap-2 flex-wrap">
                <button
                  v-for="c in colors"
                  :key="c"
                  type="button"
                  @click="form.color = c"
                  class="w-8 h-8 rounded-control transition-transform hover:scale-110 ring-offset-2"
                  :class="form.color === c ? 'ring-2 ring-gray-900 scale-110' : ''"
                  :style="{ backgroundColor: c }"
                />
              </div>
            </div>

            <!-- Images upload -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Photos <span class="text-gray-400 font-normal">({{ totalImages }}/{{ MAX_IMAGES }})</span>
              </label>

              <div class="grid grid-cols-5 gap-2">
                <!-- Existing + New previews -->
                <div v-for="(preview, idx) in imagePreviews" :key="idx" class="relative aspect-square rounded-xl overflow-hidden group bg-gray-100">
                  <img :src="preview.url" class="w-full h-full object-cover" />
                  <button
                    type="button"
                    @click="removeImage(idx)"
                    class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity"
                  >
                    <XMarkIcon class="w-5 h-5 text-white" />
                  </button>
                </div>

                <!-- Add button -->
                <label v-if="canAddMore"
                  class="aspect-square rounded-xl border-2 border-dashed border-gray-200 hover:border-primary-400 hover:bg-primary-50/50 flex flex-col items-center justify-center cursor-pointer transition-colors">
                  <PhotoIcon class="w-5 h-5 text-gray-300" />
                  <span class="text-[10px] text-gray-400 mt-1">Ajouter</span>
                  <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    class="hidden"
                    @change="onFilesSelected"
                  />
                </label>
              </div>

              <p v-if="errors.images || errors['images.0']" class="text-red-500 text-xs mt-1">
                {{ (errors.images || errors['images.0'])?.[0] }}
              </p>
              <p class="text-[11px] text-gray-400 mt-1.5">JPG, PNG ou WebP. Max 2 Mo par image.</p>
            </div>

            <div class="flex gap-3 pt-2">
              <button type="button" @click="modal = false" class="btn-secondary flex-1">Annuler</button>
              <button type="submit" class="btn-primary flex-1" :disabled="saving">
                <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                <span v-else>{{ editing ? 'Enregistrer' : 'Créer le service' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- Image gallery lightbox -->
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="gallery" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 backdrop-blur-sm" @click.self="gallery = null">
        <button @click="gallery = null" class="absolute top-4 right-4 p-2 text-white/70 hover:text-white transition-colors z-10">
          <XMarkIcon class="w-7 h-7" />
        </button>

        <!-- Counter -->
        <div class="absolute top-4 left-4 text-white/70 text-sm font-medium">
          {{ gallery.index + 1 }} / {{ gallery.images.length }}
        </div>

        <!-- Title -->
        <div class="absolute top-4 left-1/2 -translate-x-1/2 text-white font-bold text-sm">
          {{ gallery.name }}
        </div>

        <!-- Prev -->
        <button v-if="gallery.images.length > 1" @click="galleryPrev" class="absolute left-3 top-1/2 -translate-y-1/2 p-2 bg-white/10 hover:bg-white/20 rounded-full text-white transition-colors">
          <ChevronLeftIcon class="w-6 h-6" />
        </button>

        <!-- Image -->
        <img :src="gallery.images[gallery.index].url" :alt="gallery.name" class="max-w-[90vw] max-h-[85vh] object-contain rounded-lg shadow-2xl" />

        <!-- Next -->
        <button v-if="gallery.images.length > 1" @click="galleryNext" class="absolute right-3 top-1/2 -translate-y-1/2 p-2 bg-white/10 hover:bg-white/20 rounded-full text-white transition-colors">
          <ChevronRightIcon class="w-6 h-6" />
        </button>

        <!-- Thumbnails -->
        <div v-if="gallery.images.length > 1" class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
          <button
            v-for="(img, i) in gallery.images"
            :key="i"
            @click="gallery.index = i"
            :class="['w-14 h-14 rounded-lg overflow-hidden border-2 transition-all', gallery.index === i ? 'border-white scale-110' : 'border-transparent opacity-60 hover:opacity-100']"
          >
            <img :src="img.url" class="w-full h-full object-cover" />
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- Delete confirm -->
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="deleting" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="bg-clay-50 rounded-2xl shadow-xl p-6 w-full max-w-sm animate-slide-up">
          <h3 class="font-bold text-gray-900 text-lg mb-2">Supprimer ce service ?</h3>
          <p class="text-sm text-gray-500 mb-6">Cette action est irréversible. Les réservations passées seront conservées.</p>
          <div class="flex gap-3">
            <button @click="deleting = null" class="btn-secondary flex-1">Annuler</button>
            <button @click="deleteService(deleting)" class="btn-danger flex-1">Supprimer</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
