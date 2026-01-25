<template>
  <div class="admin-container">
    <div class="page-header">
      <router-link to="/admin/workshops" class="btn-back">
        <i class="fas fa-chevron-left"></i> Tornar a Tallers
      </router-link>
      <h1><i class="fas fa-pen-fancy"></i> Editar Taller</h1>
    </div>

    <!-- Toast Notification -->
    <transition name="toast-fade">
      <div v-if="notification" :class="['toast-notification', notification.type]">
        <i :class="notification.type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
        {{ notification.message }}
      </div>
    </transition>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Carregant dades del taller...</p>
    </div>

    <form v-if="workshop && !loading" @submit.prevent="saveWorkshop" class="edit-form">
      <div class="grid">
        <!-- INFORMACIÓN BÁSICA -->
        <div class="card">
          <h2><i class="fas fa-info-circle"></i> Informació General</h2>
          <div class="form-group">
            <label>Nom del Taller <span class="required">*</span></label>
            <input v-model="workshop.name" type="text" required />
          </div>
          <div class="form-group">
            <label>Descripció</label>
            <textarea v-model="workshop.description" rows="3" placeholder="Descripció detallada del taller..."></textarea>
          </div>
        </div>

        <!-- CATEGORIZACIÓN -->
        <div class="card">
          <h2><i class="fas fa-tags"></i> Categorització</h2>
          <div class="form-group">
            <label>Modalitat <span class="required">*</span></label>
            <select v-model="workshop.modality" required>
              <option value="">Selecciona modalitat</option>
              <option value="A">A - Presencial</option>
              <option value="B">B - Semipresencial</option>
              <option value="C">C - Online</option>
            </select>
          </div>
          <div class="form-group">
            <label>Àmbit <span class="required">*</span></label>
            <select v-model="workshop.ambit" required>
              <option value="">Selecciona àmbit</option>
              <option value="Artístic">Artístic</option>
              <option value="Tecnologia">Tecnologia</option>
              <option value="Industrial">Industrial</option>
              <option value="Fabricació">Fabricació</option>
            </select>
          </div>
        </div>

        <!-- DURACIÓN Y CAPACIDAD -->
        <div class="card">
          <h2><i class="fas fa-clock"></i> Duració i Capacitat</h2>
          <div class="form-row">
            <div class="form-group">
              <label>Data d'inici</label>
              <input v-model="workshop.start_date" type="date" />
            </div>
            <div class="form-group">
              <label>Data de finalització</label>
              <input v-model="workshop.end_date" type="date" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Duració (hores) <span class="required">*</span></label>
              <input v-model.number="workshop.duration_hours" type="number" required />
            </div>
            <div class="form-group">
              <label>Dies totals</label>
              <input v-model.number="workshop.duration_days" type="number" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Hores per dia</label>
              <input v-model.number="workshop.hours_per_day" type="number" step="0.5" />
            </div>
            <div class="form-group">
              <label>Capacitat Màxima <span class="required">*</span></label>
              <input v-model.number="workshop.capacity" type="number" required />
            </div>
          </div>
        </div>

        <!-- PROVEÏDOR -->
        <div class="card">
          <h2><i class="fas fa-user-tie"></i> Proveïdor</h2>
          <div class="form-group">
            <label>Nom del proveïdor</label>
            <input v-model="workshop.provider_name" type="text" />
          </div>
          <div class="form-group">
            <label>Contacte del proveïdor</label>
            <input v-model="workshop.provider_contact" type="text" />
          </div>
          <div class="form-group">
            <label>Actiu</label>
            <select v-model.number="workshop.is_active">
              <option :value="1">Sí</option>
              <option :value="0">No</option>
            </select>
          </div>
        </div>

        <!-- IMATGES -->
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
              <button type="button" @click="prevImage" class="carousel-btn prev-btn" v-if="imageFiles.length > 1">
                <i class="fas fa-chevron-left"></i>
              </button>
              
              <div class="carousel-slide">
                <img :src="imageFiles[currentImageIndex].preview" :alt="`Imatge ${currentImageIndex + 1}`" />
                <button type="button" @click="deleteImage(currentImageIndex)" class="delete-image-btn">
                  <i class="fas fa-times"></i>
                </button>
              </div>
              
              <button type="button" @click="nextImage" class="carousel-btn next-btn" v-if="imageFiles.length > 1">
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
                  <button type="button" @click.stop="deleteImage(index)" class="thumbnail-delete">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- BOTONES DE ACCIÓN -->
      <div class="form-actions">
        <button type="submit" class="btn btn-primary" :disabled="saving">
          <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
          {{ saving ? 'Guardant...' : 'Guardar Canvis' }}
        </button>
        <router-link to="/admin/workshops" class="btn btn-secondary">
          <i class="fas fa-times"></i> Cancel·lar
        </router-link>
      </div>

    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const workshop = ref(null)
const loading = ref(true)
const saving = ref(false)
const error = ref(null)
const success = ref(false)
const workshopId = route.params.id
const imageInput = ref(null)
const imageFiles = ref([])
const currentImageIndex = ref(0)
const notification = ref(null)

const showNotification = (message, type = 'info') => {
  notification.value = { message, type }
  setTimeout(() => {
    notification.value = null
  }, 4000)
}

onMounted(async () => {
  try {
    const response = await fetch(`http://localhost:8000/api/workshops/${workshopId}`)
    const data = await response.json()
    
    if (data.success || data.data) {
      workshop.value = data.data || data
      
      // Cargar la imagen existente del taller
      if (workshop.value.image) {
        const img = new Image()
        img.onload = () => {
          imageFiles.value.push({
            file: null, // No es un archivo nuevo, es una imagen existente
            preview: workshop.value.image,
            isExisting: true // Marcamos que es una imagen existente
          })
        }
        img.src = `http://localhost:8000${workshop.value.image}`
      }
    } else {
      error.value = 'No s\'han pogut carregar les dades del taller'
    }
  } catch (err) {
    error.value = 'Error carregant el taller: ' + err.message
  } finally {
    loading.value = false
  }
})

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

const saveWorkshop = async () => {
  saving.value = true
  success.value = false
  error.value = null
  
  try {
    const formData = new FormData()
    
    // Afegir tots els camps del workshop
    Object.keys(workshop.value).forEach(key => {
      if (key !== 'id' && key !== 'created_at' && key !== 'updated_at' && workshop.value[key] !== null && workshop.value[key] !== '') {
        // No incluir la imagen en el campo de workshop si ya existe
        if (key !== 'image' || !imageFiles.value.some(img => !img.file)) {
          formData.append(key, workshop.value[key])
        }
      }
    })
    
    // Afegir només les imatges noves (les que tienen file)
    let hasNewImages = false
    imageFiles.value.forEach((imageObj) => {
      if (imageObj.file) {
        formData.append('images[]', imageObj.file)
        hasNewImages = true
      }
    })

    const response = await fetch(`http://localhost:8000/api/workshops/${workshopId}`, {
      method: 'PUT',
      body: formData
    })
    
    const data = await response.json()
    if (data.success || response.ok) {
      success.value = true
      showNotification('Taller guardat correctament!', 'success')
      setTimeout(() => {
        router.push('/admin/workshops')
      }, 1500)
    } else {
      error.value = data.message || 'Error guardant el taller'
      showNotification(error.value, 'error')
    }
  } catch (err) {
    error.value = 'Error: ' + err.message
    showNotification(error.value, 'error')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.admin-container {
  max-width: 100%;
  width: 100%;
  margin: 0;
  padding: 2rem 3rem;
  background: #f7f8fb;
  min-height: 100vh;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 3rem;
  padding-bottom: 2rem;
  border-bottom: 3px solid #d4af37;
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: #6c757d;
  color: white;
  border: none;
  border-radius: 8px;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.3s;
  font-weight: 600;
}

.btn-back:hover {
  background: #545b62;
  transform: translateX(-2px);
}

.page-header h1 {
  margin: 0;
  font-size: 2.5rem;
  color: #1a1a1a;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-header i {
  color: #d4af37;
  font-size: 2rem;
}

.edit-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.card {
  background: white;
  border: 1px solid #f0f0f0;
  border-radius: 12px;
  padding: 2rem 2.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.card.full {
  grid-column: 1 / -1;
}

.card h2 {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin: 0 0 1.5rem 0;
  font-size: 1.3rem;
  color: #1a1a1a;
  font-weight: 600;
}

.card h2 i {
  color: #d4af37;
  font-size: 1.5rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.full {
  grid-column: 1 / -1;
}

.form-group label {
  font-weight: 700;
  margin-bottom: 0.8rem;
  color: #333;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.required {
  color: #ef4444;
}

input,
textarea,
select {
  padding: 0.85rem 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 1rem;
  background: #fafafa;
  transition: all 0.2s ease;
  font-family: inherit;
  width: 100%;
}

input:focus,
textarea:focus,
select:focus {
  outline: none;
  border-color: #d4af37;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.16);
}

textarea {
  resize: vertical;
  min-height: 120px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.form-control {
  padding: 1rem 1.2rem;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 1rem;
  font-family: inherit;
  transition: all 0.3s;
  background: white;
}

.form-control:focus {
  outline: none;
  border-color: #d4af37;
  box-shadow: 0 0 0 5px rgba(212, 175, 55, 0.12);
  background: #fffbf5;
}

.form-control:disabled {
  background: #f5f5f5;
  color: #999;
}

.textarea-large {
  resize: vertical;
  min-height: 180px;
  max-height: 500px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.color-input-group {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.color-picker {
  width: 80px;
  height: 50px;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  cursor: pointer;
  padding: 2px;
}

.color-picker:focus {
  outline: none;
  border-color: #d4af37;
}

.form-actions {
  display: flex;
  gap: 1.5rem;
  margin-top: 2.5rem;
  justify-content: center;
  grid-column: 1 / -1;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.8rem;
  padding: 1rem 2.5rem;
  border: none;
  border-radius: 10px;
  font-size: 1.05rem;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.3s ease;
  font-weight: 700;
  min-width: 180px;
}

.btn-lg {
  padding: 1.2rem 3rem;
  font-size: 1.1rem;
}

.btn-primary {
  background: linear-gradient(135deg, #d4af37 0%, #c99c45 100%);
  color: white;
  box-shadow: 0 8px 20px rgba(212, 175, 55, 0.35);
  border: none;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-4px);
  box-shadow: 0 12px 28px rgba(212, 175, 55, 0.45);
  background: linear-gradient(135deg, #e0bb47 0%, #d4af37 100%);
}

.btn-primary:active:not(:disabled) {
  transform: translateY(-2px);
}

.btn-primary:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary {
  background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
  color: white;
  box-shadow: 0 8px 20px rgba(108, 117, 125, 0.25);
  border: none;
}

.btn-secondary:hover {
  background: linear-gradient(135deg, #7d8a94 0%, #6c757d 100%);
  transform: translateY(-4px);
  box-shadow: 0 12px 28px rgba(108, 117, 125, 0.35);
}

.btn-secondary:active {
  transform: translateY(-2px);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 5rem;
  background: white;
  border-radius: 12px;
  gap: 1.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.loading-state p {
  font-size: 1.2rem;
  color: #666;
  margin: 0;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e0e0e0;
  border-top-color: #d4af37;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-alert {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem 1.8rem;
  background: #f8d7da;
  border: 2px solid #f5c6cb;
  border-radius: 10px;
  color: #721c24;
  font-weight: 600;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(220, 53, 69, 0.15);
}

.error-alert i {
  font-size: 1.3rem;
  flex-shrink: 0;
}

.success-message {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem 1.8rem;
  background: #d4edda;
  border: 2px solid #c3e6cb;
  border-radius: 10px;
  color: #155724;
  font-weight: 600;
  margin-top: 1.5rem;
  animation: slideIn 0.4s ease-out;
  box-shadow: 0 2px 8px rgba(40, 167, 69, 0.15);
}

.success-message i {
  font-size: 1.3rem;
  flex-shrink: 0;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-15px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 768px) {
  .admin-container {
    padding: 1rem;
  }
  
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    margin-bottom: 2rem;
  }
  
  .page-header h1 {
    font-size: 2rem;
  }
  
  .grid {
    grid-template-columns: 1fr;
  }
  
  .card {
    padding: 1.5rem;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column;
    gap: 1rem;
  }
  
  .btn {
    width: 100%;
  }
}

/* Carousel Styles */
.image-input {
  display: none;
}

.image-upload-area {
  margin: 1rem 0;
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

/* Toast Notification */
.toast-notification {
  position: fixed;
  top: 2rem;
  right: 2rem;
  padding: 1.2rem 1.5rem;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 0.8rem;
  font-weight: 700;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  z-index: 9999;
  max-width: 400px;
  animation: slideInRight 0.3s ease-out;
}

.toast-notification.success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.toast-notification.success i {
  font-size: 1.3rem;
}

.toast-notification.error {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.toast-notification.error i {
  font-size: 1.3rem;
}

.toast-notification.info {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

@keyframes slideInRight {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.toast-fade-enter-active,
.toast-fade-leave-active {
  transition: all 0.3s ease;
}

.toast-fade-enter-from {
  transform: translateX(400px);
  opacity: 0;
}

.toast-fade-leave-to {
  transform: translateX(400px);
  opacity: 0;
}

@media (max-width: 768px) {
  .toast-notification {
    top: 1rem;
    right: 1rem;
    left: 1rem;
    max-width: none;
  }
}
</style>
