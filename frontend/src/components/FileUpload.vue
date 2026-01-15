<template>
  <div class="file-upload">
    <div 
      class="upload-area"
      :class="{ 'dragging': isDragging, 'has-file': file }"
      @drop.prevent="handleDrop"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @click="triggerFileInput"
    >
      <input 
        ref="fileInput"
        type="file"
        :accept="accept"
        @change="handleFileSelect"
        style="display: none"
      />
      
      <div v-if="!file && !uploading" class="upload-prompt">
        <span class="upload-icon">📎</span>
        <p><strong>Clica per seleccionar</strong> o arrossega un fitxer aquí</p>
        <p class="upload-hint">PDF, JPG, PNG o DOCX (màx. 5MB)</p>
      </div>
      
      <div v-if="uploading" class="uploading-state">
        <div class="spinner"></div>
        <p>Pujant fitxer...</p>
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: progress + '%' }"></div>
        </div>
      </div>
      
      <div v-if="file && !uploading" class="file-preview">
        <span class="file-icon">📄</span>
        <div class="file-info">
          <p class="file-name">{{ file.name }}</p>
          <p class="file-size">{{ formatFileSize(file.size) }}</p>
        </div>
        <button @click.stop="removeFile" class="remove-btn">✕</button>
      </div>
    </div>
    
    <p v-if="error" class="error-message">{{ error }}</p>
    <p v-if="success" class="success-message">✓ {{ success }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  checklistItemId: {
    type: Number,
    required: true
  },
  accept: {
    type: String,
    default: '.pdf,.jpg,.jpeg,.png,.docx'
  },
  maxSize: {
    type: Number,
    default: 5 * 1024 * 1024 // 5MB
  }
})

const emit = defineEmits(['upload-success', 'upload-error'])

const fileInput = ref(null)
const file = ref(null)
const isDragging = ref(false)
const uploading = ref(false)
const progress = ref(0)
const error = ref('')
const success = ref('')

function triggerFileInput() {
  fileInput.value.click()
}

function handleFileSelect(event) {
  const selectedFile = event.target.files[0]
  if (selectedFile) {
    validateAndSetFile(selectedFile)
  }
}

function handleDrop(event) {
  isDragging.value = false
  const droppedFile = event.dataTransfer.files[0]
  if (droppedFile) {
    validateAndSetFile(droppedFile)
  }
}

function validateAndSetFile(selectedFile) {
  error.value = ''
  success.value = ''
  
  // Validar tamaño
  if (selectedFile.size > props.maxSize) {
    error.value = 'El fitxer és massa gran (màxim 5MB)'
    return
  }
  
  // Validar tipo
  const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
  if (!allowedTypes.includes(selectedFile.type)) {
    error.value = 'Tipus de fitxer no permès. Només PDF, JPG, PNG i DOCX'
    return
  }
  
  file.value = selectedFile
  uploadFile()
}

async function uploadFile() {
  if (!file.value) return
  
  uploading.value = true
  progress.value = 0
  error.value = ''
  success.value = ''
  
  const formData = new FormData()
  formData.append('file', file.value)
  formData.append('checklist_item_id', props.checklistItemId)
  
  try {
    // Simular progreso
    const progressInterval = setInterval(() => {
      if (progress.value < 90) {
        progress.value += 10
      }
    }, 200)
    
    const response = await fetch('http://localhost:8000/api/upload_evidencia.php', {
      method: 'POST',
      body: formData,
      credentials: 'include'
    })
    
    clearInterval(progressInterval)
    progress.value = 100
    
    const data = await response.json()
    
    if (!response.ok) {
      throw new Error(data.error || 'Error al pujar el fitxer')
    }
    
    success.value = 'Fitxer pujat correctament!'
    emit('upload-success', data)
    
    setTimeout(() => {
      success.value = ''
    }, 3000)
    
  } catch (err) {
    error.value = err.message
    emit('upload-error', err)
    file.value = null
  } finally {
    uploading.value = false
    progress.value = 0
  }
}

function removeFile() {
  file.value = null
  error.value = ''
  success.value = ''
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}
</script>

<script>
export default {
  name: 'FileUpload'
}
</script>

<style scoped>
.file-upload {
  width: 100%;
}

.upload-area {
  border: 2px dashed #D1D5DB;
  border-radius: 0.75rem;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s;
  background-color: #F9FAFB;
}

.upload-area:hover {
  border-color: #3B82F6;
  background-color: #EFF6FF;
}

.upload-area.dragging {
  border-color: #3B82F6;
  background-color: #DBEAFE;
  transform: scale(1.02);
}

.upload-area.has-file {
  border-color: #10B981;
  background-color: #ECFDF5;
}

.upload-prompt {
  color: #6B7280;
}

.upload-icon {
  font-size: 3rem;
  display: block;
  margin-bottom: 1rem;
}

.upload-prompt p {
  margin: 0.5rem 0;
}

.upload-hint {
  font-size: 0.875rem;
  color: #9CA3AF;
}

.uploading-state {
  padding: 1rem 0;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background-color: #E5E7EB;
  border-radius: 4px;
  overflow: hidden;
  margin-top: 1rem;
}

.progress-fill {
  height: 100%;
  background-color: #3B82F6;
  transition: width 0.3s;
}

.file-preview {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background-color: white;
  border-radius: 0.5rem;
}

.file-icon {
  font-size: 2rem;
}

.file-info {
  flex: 1;
  text-align: left;
}

.file-name {
  font-weight: 600;
  color: #0F172A;
  margin: 0;
}

.file-size {
  font-size: 0.875rem;
  color: #6B7280;
  margin: 0.25rem 0 0 0;
}

.remove-btn {
  background: none;
  border: none;
  color: #EF4444;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 0.375rem;
  transition: background-color 0.2s;
}

.remove-btn:hover {
  background-color: #FEE2E2;
}

.error-message {
  color: #EF4444;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.success-message {
  color: #10B981;
  font-size: 0.875rem;
  margin-top: 0.5rem;
  font-weight: 600;
}
</style>
