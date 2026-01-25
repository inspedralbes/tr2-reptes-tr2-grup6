<template>
  <div class="create-workshop">
    <div class="page-header">
      <div class="title-group">
        <button class="btn-secondary" @click="goBack">
          <i class="fas fa-arrow-left"></i> Tornar
        </button>
        <div>
          <p class="eyebrow">Nou taller</p>
          <h1><i class="fas fa-book"></i> Crear Taller</h1>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn-primary" :disabled="loading" @click="create">
          <i class="fas fa-save"></i>
          {{ loading ? 'Guardant...' : 'Crear Taller' }}
        </button>
      </div>
    </div>

    <div class="grid">
      <!-- Secció 1: Informació General -->
      <div class="card">
        <h2><i class="fas fa-info-circle"></i> Informació General</h2>
        <div class="form-group">
          <label>Nom del Taller <span class="required">*</span></label>
          <input v-model="form.name" type="text" placeholder="Ex: Soldadura Bàsica" required />
        </div>
        <div class="form-group">
          <label>Descripció</label>
          <textarea v-model="form.description" rows="3" placeholder="Descripció detallada del taller"></textarea>
        </div>
      </div>

      <!-- Secció 2: Categorització -->
      <div class="card">
        <h2><i class="fas fa-tags"></i> Categorització</h2>
        <div class="form-group">
          <label>Modalitat <span class="required">*</span></label>
          <select v-model="form.modality" required>
            <option value="">Selecciona modalitat</option>
            <option value="A">A - Presencial</option>
            <option value="B">B - Semipresencial</option>
            <option value="C">C - Online</option>
          </select>
        </div>
        <div class="form-group">
          <label>Àmbit <span class="required">*</span></label>
          <select v-model="form.ambit" required>
            <option value="">Selecciona àmbit</option>
            <option value="Artístic">Artístic</option>
            <option value="Tecnologia">Tecnologia</option>
            <option value="Industrial">Industrial</option>
            <option value="Fabricació">Fabricació</option>
          </select>
        </div>
      </div>

      <!-- Secció 3: Duració i Capacitat -->
      <div class="card">
        <h2><i class="fas fa-clock"></i> Duració i Capacitat</h2>
        <div class="form-row">
          <div class="form-group">
            <label>Data d'inici</label>
            <input v-model="form.start_date" type="date" />
          </div>
          <div class="form-group">
            <label>Data de finalització</label>
            <input v-model="form.end_date" type="date" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Duració (hores) <span class="required">*</span></label>
            <input v-model.number="form.duration_hours" type="number" placeholder="Ex: 20" required />
          </div>
          <div class="form-group">
            <label>Dies totals</label>
            <input v-model.number="form.duration_days" type="number" placeholder="Ex: 5" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Hores per dia</label>
            <input v-model.number="form.hours_per_day" type="number" placeholder="Ex: 4" />
          </div>
          <div class="form-group">
            <label>Capacitat màxima <span class="required">*</span></label>
            <input v-model.number="form.capacity" type="number" placeholder="Ex: 16" required />
          </div>
        </div>
      </div>

      <!-- Secció 4: Provider -->
      <div class="card">
        <h2><i class="fas fa-user-tie"></i> Proveïdor</h2>
        <div class="form-group">
          <label>Nom del proveïdor</label>
          <input v-model="form.provider_name" type="text" placeholder="Ex: Empresa Circ XYZ" />
        </div>
        <div class="form-group">
          <label>Contacte del proveïdor</label>
          <input v-model="form.provider_contact" type="text" placeholder="Ex: proveidor@empresa.cat" />
        </div>
        <div class="form-group">
          <label>Actiu</label>
          <select v-model.number="form.is_active">
            <option :value="1">Sí</option>
            <option :value="0">No</option>
          </select>
        </div>
      </div>

      <!-- Secció de Imatges amb Carrusel -->
      <div class="card" style="grid-column: 1 / -1;">
        <h2><i class="fas fa-images"></i> Imatges del Taller</h2>
        
        <!-- Zona de carga -->
        <div class="form-group">
          <div class="image-upload-area">
            <input 
              type="file" 
              @change="handleImageChange" 
              accept="image/*" 
              multiple
              ref="imageInput"
              id="imageInput"
              class="image-input"
            />
            <label for="imageInput" class="image-upload-label">
              <i class="fas fa-cloud-upload-alt"></i>
              <span>Clica o arrossega imatges aquí</span>
            </label>
          </div>
        </div>
        
        <!-- Carrusel de imatges -->
        <div v-if="imageFiles.length > 0" class="carousel-container">
          <div class="carousel">
            <button @click="prevImage" class="carousel-btn prev-btn" v-if="imageFiles.length > 1">
              <i class="fas fa-chevron-left"></i>
            </button>
            
            <div class="carousel-slide">
              <img :src="imageFiles[currentImageIndex].preview" :alt="`Imatge ${currentImageIndex + 1}`" />
              <button @click="deleteImage(currentImageIndex)" class="delete-image-btn">
                <i class="fas fa-times"></i>
              </button>
            </div>
            
            <button @click="nextImage" class="carousel-btn next-btn" v-if="imageFiles.length > 1">
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
          
          <!-- Indicadores -->
          <div class="carousel-indicators" v-if="imageFiles.length > 1">
            <span 
              v-for="(_, index) in imageFiles" 
              :key="index"
              :class="['indicator', { active: index === currentImageIndex }]"
              @click="currentImageIndex = index"
            ></span>
          </div>
          
          <!-- Lista de imágenes -->
          <div class="images-list">
            <p class="images-count">{{ imageFiles.length }} {{ imageFiles.length === 1 ? 'imatge' : 'imatges' }} pujada{{ imageFiles.length !== 1 ? 's' : '' }}</p>
            <div class="image-thumbnails">
              <div 
                v-for="(file, index) in imageFiles" 
                :key="index"
                :class="['thumbnail', { active: index === currentImageIndex }]"
                @click="currentImageIndex = index"
              >
                <img :src="file.preview" :alt="`Imatge ${index + 1}`" />
                <button @click.stop="deleteImage(index)" class="thumbnail-delete">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="message" :class="['message', message.type]">
      {{ message.text }}
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const loading = ref(false)
const message = ref(null)
const imageInput = ref(null)
const imageFiles = ref([])
const currentImageIndex = ref(0)

const form = ref({
  name: '',
  description: '',
  modality: '',
  ambit: '',
  capacity: null,
  duration_hours: null,
  duration_days: null,
  hours_per_day: null,
  start_date: '',
  end_date: '',
  provider_name: '',
  provider_contact: '',
  is_active: 1
})

const showMessage = (type, text) => {
  message.value = { type, text }
  setTimeout(() => { message.value = null }, 3000)
}

const handleImageChange = (event) => {
  const files = event.target.files
  if (files) {
    for (let i = 0; i < files.length; i++) {
      const file = files[i]
      const reader = new FileReader()
      reader.onload = (e) => {
        imageFiles.value.push({
          file: file,
          preview: e.target.result
        })
      }
      reader.readAsDataURL(file)
    }
  }
}

const deleteImage = (index) => {
  imageFiles.value.splice(index, 1)
  if (currentImageIndex.value >= imageFiles.value.length && imageFiles.value.length > 0) {
    currentImageIndex.value = imageFiles.value.length - 1
  }
  if (imageInput.value) {
    imageInput.value.value = ''
  }
}

const nextImage = () => {
  if (currentImageIndex.value < imageFiles.value.length - 1) {
    currentImageIndex.value++
  } else {
    currentImageIndex.value = 0
  }
}

const prevImage = () => {
  if (currentImageIndex.value > 0) {
    currentImageIndex.value--
  } else {
    currentImageIndex.value = imageFiles.value.length - 1
  }
}

const validate = () => {
  if (!form.value.name || !form.value.modality || !form.value.ambit) {
    showMessage('error', 'Nom, modalitat i àmbit són obligatoris')
    return false
  }
  if (!form.value.duration_hours || form.value.duration_hours <= 0) {
    showMessage('error', 'Duració en hores és obligatòria')
    return false
  }
  if (!form.value.capacity || form.value.capacity <= 0) {
    showMessage('error', 'Capacitat màxima és obligatòria')
    return false
  }
  return true
}

const create = async () => {
  message.value = null
  if (!validate()) return
  loading.value = true

  try {
    const formData = new FormData()
    
    // Afegir tots els camps del formulari
    Object.keys(form.value).forEach(key => {
      if (form.value[key] !== null && form.value[key] !== '') {
        formData.append(key, form.value[key])
      }
    })
    
    // Afegir les imatges
    imageFiles.value.forEach((imageObj, index) => {
      formData.append(`images[]`, imageObj.file)
    })

    const response = await fetch('http://localhost:8000/api/workshops', {
      method: 'POST',
      body: formData
    })

    const data = await response.json()

    if (data.success || response.ok) {
      showMessage('success', 'Taller creat correctament!')
      setTimeout(() => {
        router.push('/admin/workshops')
      }, 1500)
    } else {
      showMessage('error', data.message || 'Error creant taller')
    }
  } catch (err) {
    showMessage('error', 'Error: ' + err.message)
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push('/admin/workshops')
}
</script>

<style scoped>
.create-workshop {
  padding: 2rem 3rem;
  background: #f7f8fb;
  min-height: 100vh;
  width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 3px solid #d4af37;
  padding-bottom: 1.5rem;
  margin-bottom: 2rem;
  gap: 1rem;
}

.title-group {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.eyebrow {
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #8c8c8c;
  font-weight: 700;
  font-size: 0.9rem;
}

.page-header h1 {
  margin: 0.3rem 0 0;
  font-size: 2.1rem;
  color: #111;
  font-weight: 800;
  letter-spacing: -0.5px;
  display: flex;
  align-items: center;
  gap: 0.7rem;
}

.page-header h1 i {
  color: #d4af37;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

@media (max-width: 960px) {
  .grid {
    grid-template-columns: 1fr;
  }
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }
  .header-actions {
    width: 100%;
  }
}

.card {
  background: #fff;
  border: 1px solid #f0f0f0;
  border-radius: 12px;
  padding: 1.8rem;
  box-shadow: 0 4px 14px rgba(0,0,0,0.08);
}

.card h2 {
  margin: 0 0 1.4rem;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  color: #222;
}

.card h2 i { color: #d4af37; }

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

@media (max-width: 720px) {
  .form-row { grid-template-columns: 1fr; }
}

label {
  font-weight: 700;
  color: #222;
}

.form-label {
  font-weight: 700;
  color: #222;
  display: block;
  margin-bottom: 0.5rem;
}

.required { color: #ef4444; }

input, textarea, select {
  padding: 0.85rem 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 1rem;
  background: #fafafa;
  transition: all 0.2s ease;
}

input:focus, textarea:focus, select:focus {
  outline: none;
  border-color: #d4af37;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(212,175,55,0.16);
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #c99c45 0%, #b28533 100%);
  color: #fff;
  border: none;
  padding: 0.85rem 1.6rem;
  border-radius: 12px;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 10px 22px rgba(180, 140, 60, 0.35);
  transition: all 0.2s ease;
}

.btn-primary:hover:not(:disabled) { transform: translateY(-1px); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: #eef1f6;
  color: #1f2937;
  border: 1px solid #d8dde5;
  padding: 0.7rem 1.2rem;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary:hover { background: #e3e8f0; }

.message {
  margin-top: 1.5rem;
  padding: 1rem 1.2rem;
  border-radius: 10px;
  font-weight: 700;
}

.message.success {
  background: #ecfdf5;
  color: #065f46;
  border: 2px solid #34d399;
}

.message.error {
  background: #fef2f2;
  color: #991b1b;
  border: 2px solid #fca5a5;
}

/* Image Upload Styles */
.image-upload-area {
  margin-top: 0.5rem;
}

.image-input {
  display: none;
}

.image-upload-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  border: 2px dashed #d4af37;
  border-radius: 12px;
  background: #fafafa;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;
}

.image-upload-label:hover {
  background: #fff;
  border-color: #c99c45;
  box-shadow: 0 4px 12px rgba(212, 175, 55, 0.15);
}

.image-upload-label i {
  font-size: 2.5rem;
  color: #d4af37;
  margin-bottom: 0.5rem;
}

.image-upload-label span {
  color: #666;
  font-weight: 600;
  font-size: 0.95rem;
}

.image-preview {
  margin-top: 1rem;
  position: relative;
  display: inline-block;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
}

.image-preview img {
  max-width: 100%;
  max-height: 300px;
  display: block;
  border-radius: 12px;
}

.remove-image {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  background: rgba(239, 68, 68, 0.9);
  color: white;
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  transition: all 0.2s ease;
}

.remove-image:hover {
  background: rgba(220, 38, 38, 1);
  transform: scale(1.1);
}

/* Carousel Styles */
.carousel-container {
  margin-top: 1.5rem;
}

.carousel {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  position: relative;
}

.carousel-slide {
  position: relative;
  width: 100%;
  max-width: 400px;
  aspect-ratio: 4 / 3;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
}

.carousel-slide img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.carousel-btn {
  background: rgba(212, 175, 55, 0.9);
  color: white;
  border: none;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.carousel-btn:hover {
  background: rgba(212, 175, 55, 1);
  transform: scale(1.1);
}

.delete-image-btn {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  background: rgba(239, 68, 68, 0.9);
  color: white;
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  transition: all 0.2s ease;
}

.delete-image-btn:hover {
  background: rgba(220, 38, 38, 1);
  transform: scale(1.1);
}

.carousel-indicators {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.indicator {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #ddd;
  cursor: pointer;
  transition: all 0.2s ease;
}

.indicator.active {
  background: #d4af37;
  transform: scale(1.2);
}

.images-list {
  padding-top: 1.5rem;
  border-top: 1px solid #e0e0e0;
}

.images-count {
  font-weight: 700;
  color: #666;
  margin: 0 0 1rem;
  font-size: 0.95rem;
}

.image-thumbnails {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  gap: 0.75rem;
}

.thumbnail {
  position: relative;
  width: 100%;
  aspect-ratio: 1;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s ease;
}

.thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.thumbnail.active {
  border-color: #d4af37;
  box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
}

.thumbnail:hover {
  transform: scale(1.05);
}

.thumbnail-delete {
  position: absolute;
  top: 4px;
  right: 4px;
  background: rgba(239, 68, 68, 0.9);
  color: white;
  border: none;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  transition: all 0.2s ease;
  opacity: 0;
}

.thumbnail:hover .thumbnail-delete {
  opacity: 1;
}

.thumbnail-delete:hover {
  background: rgba(220, 38, 38, 1);
  transform: scale(1.2);
}

@media (max-width: 768px) {
  .carousel {
    gap: 0.5rem;
  }
  
  .carousel-btn {
    width: 36px;
    height: 36px;
    font-size: 1rem;
  }
  
  .carousel-slide {
    max-width: 100%;
  }
}
</style>
