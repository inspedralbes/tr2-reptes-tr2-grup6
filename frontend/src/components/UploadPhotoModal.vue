<template>
  <div class="modal-backdrop" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h3><i class="fas fa-camera"></i> Pujar Foto a la Galeria</h3>
        <button class="btn-close" @click="$emit('close')"><i class="fas fa-times"></i></button>
      </div>

      <div class="modal-body">
        <form @submit.prevent="submitUpload">
          
          <!-- Image Selection -->
          <div class="form-group upload-area" 
               :class="{'has-files': previewUrls.length > 0}"
               @dragover.prevent 
               @drop.prevent="handleDrop"
               @click="triggerFileInput"
          >
            <input 
              type="file" 
              ref="fileInput" 
              @change="handleFileSelect" 
              accept="image/*" 
              multiple
              style="display: none" 
            />
            
            <div v-if="previewUrls.length > 0" class="previews-grid">
               <div v-for="(url, index) in previewUrls" :key="index" class="preview-item">
                  <img :src="url" class="preview-thumb" />
                  <button type="button" class="btn-remove-preview" @click.stop="removeFile(index)">
                    <i class="fas fa-times"></i>
                  </button>
               </div>
               <div class="add-more-placeholder">
                  <i class="fas fa-plus"></i>
                  <span>Afegir</span>
               </div>
            </div>
            
            <div v-else class="upload-placeholder">
              <i class="fas fa-cloud-upload-alt fa-3x"></i>
              <p>Arrossega imatges o fes clic per seleccionar</p>
              <small>Pots seleccionar-ne vàries a la vegada</small>
            </div>
          </div>

          <!-- Fields -->
          <div class="form-row">
            <div class="form-group">
              <label>Títol de la foto</label>
              <input type="text" v-model="form.title" required placeholder="Ex: Projecte Robot mBot" />
            </div>
            <div class="form-group">
              <label>Fotògraf/a (Opcional)</label>
              <input type="text" v-model="form.photographer_name" placeholder="Nom de l'autor/a" />
            </div>
          </div>

          <div class="form-group">
            <label>Descripció</label>
            <textarea v-model="form.description" rows="3" placeholder="Descriu què es veu a la imatge..."></textarea>
          </div>

          <div class="form-group" v-if="workshops.length > 0">
            <label>Taller associat</label>
            <select v-model="form.workshop_id" required>
              <option value="" disabled>Selecciona un taller</option>
              <option v-for="ws in workshops" :key="ws.id" :value="ws.id">
                {{ ws.name }}
              </option>
            </select>
          </div>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="$emit('close')">Cancel·lar</button>
            <button type="submit" class="btn-primary" :disabled="uploading || selectedFiles.length === 0">
              <span v-if="uploading"><i class="fas fa-spinner fa-spin"></i> Pujant {{ uploadProgress }}%...</span>
              <span v-else><i class="fas fa-upload"></i> Pujar {{ selectedFiles.length > 1 ? selectedFiles.length + ' Fotos' : 'Foto' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, inject } from 'vue';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';

const props = defineProps({
  preselectedWorkshopId: {
    type: Number,
    default: null
  }
});

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000';
const emit = defineEmits(['close', 'uploaded']); 

const authStore = useAuthStore();
const notify = inject('notify'); 

const fileInput = ref(null);
const selectedFiles = ref([]);
const previewUrls = ref([]);
const uploading = ref(false);
const workshops = ref([]);
const uploadProgress = ref(0);

// Helper to safely notify
const safeNotify = (title, message, type) => {
    if (typeof notify === 'function') {
        notify(title, message, type);
    } else {
        console.error('Notify function not found!', { title, message, type });
        // Fallback alert if notify is broken
        if (type === 'error') console.error(message);
    }
};

const form = reactive({
  title: '', 
  description: '',
  photographer_name: '',
  workshop_id: props.preselectedWorkshopId || ''
});

onMounted(async () => {
  try {
    const response = await axios.get(`${API_BASE}/api/workshops`, {
      headers: { 'Authorization': `Bearer ${authStore.token}` }
    });
    if (response.data.success) {
      workshops.value = response.data.data;
    }
  } catch (error) {
    console.error("Error loading workshops", error);
  }
  
  if (authStore.user) {
      form.photographer_name = authStore.user.full_name;
  }
});

const triggerFileInput = () => fileInput.value.click();

const handleFileSelect = (event) => {
  processFiles(event.target.files);
};

const handleDrop = (event) => {
  processFiles(event.dataTransfer.files);
};

const processFiles = (files) => {
  if (!files || files.length === 0) return;
  
  const validFiles = Array.from(files).filter(file => file.type.startsWith('image/'));
  
  if (validFiles.length === 0) {
    safeNotify('Format Invàlid', 'Si us plau, selecciona fitxers d\'imatge.', 'warning');
    return;
  }
  
  selectedFiles.value = [...selectedFiles.value, ...validFiles];
  
  validFiles.forEach(file => {
      const reader = new FileReader();
      reader.onload = (e) => previewUrls.value.push(e.target.result);
      reader.readAsDataURL(file);
  });
};

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
    previewUrls.value.splice(index, 1);
};

const submitUpload = async () => {
  if (selectedFiles.value.length === 0) return;
  
  uploading.value = true;
  uploadProgress.value = 0;
  
  let successCount = 0;
  let failCount = 0;
  let errors = [];
  const total = selectedFiles.value.length;

  for (let i = 0; i < total; i++) {
      try {
        const file = selectedFiles.value[i];
        const formData = new FormData();
        formData.append('image', file);
        formData.append('title', total > 1 ? `${form.title} (${i+1})` : form.title); 
        formData.append('description', form.description);
        formData.append('workshop_id', form.workshop_id);
        formData.append('center_id', authStore.user.center_id || 1);
        formData.append('user_id', authStore.user.id);
        formData.append('photographer_name', form.photographer_name);

        await axios.post(`${API_BASE}/api/gallery/upload`, formData, {
            headers: { 
              'Content-Type': 'multipart/form-data',
              'Authorization': `Bearer ${authStore.token}`
            }
        });
        
        successCount++;
      } catch (error) {
         console.error(`Error uploading file ${i+1}:`, error);
         failCount++;
         errors.push(error.response?.data?.message || error.message);
      }
      
      uploadProgress.value = Math.round(((i + 1) / total) * 100);
  }
  
  uploading.value = false;

  if (successCount > 0) {
      if (failCount > 0) {
          safeNotify('Pujada Parcial', `S'han pujat ${successCount} fotos. ${failCount} han fallat.`, 'warning');
      } else {
          safeNotify('Foto Pujada', `S'han pujat ${successCount} fotos correctament!`, 'success');
      }
      
      emit('uploaded');
      emit('close');
  } else {
      safeNotify('Error Pujada', 'No s\'ha pogut pujar cap foto.', 'error');
  }
};
</script>

<style scoped>
.modal-backdrop {
  position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
  background: rgba(0,0,0,0.5); z-index: 1000;
  display: flex; align-items: center; justify-content: center;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white; width: 90%; max-width: 500px;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
  overflow: hidden;
  animation: slideUp 0.3s ease-out;
  /* Ensure it fits in viewport */
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.modal-header {
  padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0;
  display: flex; justify-content: space-between; align-items: center;
  background: #f8fafc;
  flex-shrink: 0; /* Header stays fixed */
}

.modal-header h3 { margin: 0; color: #1e293b; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem; }
.btn-close { border: none; background: transparent; color: #64748b; cursor: pointer; font-size: 1.2rem; }

.modal-body { 
    padding: 1.5rem; 
    overflow-y: auto; /* Scrollable content */
    flex: 1;
}

.upload-area {
  border: 2px dashed #cbd5e1; border-radius: 8px;
  padding: 1.5rem;  /* Reduced padding */
  cursor: pointer; transition: all 0.2s;
  background: #f8fafc;
  margin-bottom: 1rem;
  position: relative; overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 150px; /* Reduced original min-height */
}

.upload-area:hover { border-color: #C5A059; background: #fffbf0; }

/* When files are selected */
.upload-area.has-files {
    padding: 0.8rem;
    border: 2px dashed #C5A059;
    background: #fdfdfd;
    height: auto;
    min-height: 100px; /* Much smaller min height when populated */
    max-height: 220px; /* Restrict max height */
    overflow-y: auto; 
    justify-content: flex-start;
    align-items: flex-start;
}

/* Custom Scrollbar for the upload area */
.upload-area.has-files::-webkit-scrollbar {
  width: 8px;
}
.upload-area.has-files::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}
.upload-area.has-files::-webkit-scrollbar-thumb {
  background: #C5A059;
  border-radius: 4px;
}

.previews-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.8rem;
    justify-content: center;
}

.preview-item {
    position: relative;
    width: 100px;
    height: 100px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.preview-thumb {
    width: 100%; height: 100%; object-fit: cover;
}

.btn-remove-preview {
    position: absolute; top: 2px; right: 2px;
    background: rgba(0,0,0,0.6); color: white;
    border: none; border-radius: 50%;
    width: 20px; height: 20px;
    font-size: 0.7rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
}
.btn-remove-preview:hover { background: red; }

.add-more-placeholder {
    width: 100px; height: 100px;
    border: 2px dashed #cbd5e1; border-radius: 8px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    color: #94a3b8; font-size: 0.8rem;
    cursor: pointer;
}
.add-more-placeholder:hover { border-color: #C5A059; color: #C5A059; }

.preview-overlay { display: none; } /* Remove old overlay code */

.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-size: 0.9rem; font-weight: 500; color: #475569; margin-bottom: 0.4rem; }
.form-group input, .form-group textarea, .form-group select {
    width: 100%; padding: 0.6rem 0.8rem;
    border: 1px solid #e2e8f0; border-radius: 6px;
    font-size: 0.95rem; font-family: inherit;
}
.form-group input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

.form-actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; }

.btn-primary {
    background: #C5A059; color: white; border: none;
    padding: 0.6rem 1.25rem; border-radius: 6px; font-weight: 600;
    cursor: pointer; display: flex; align-items: center; gap: 0.5rem;
    transition: background 0.2s;
}
.btn-primary:hover { background: #b08d4b; }
.btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }

.btn-secondary {
    background: white; border: 1px solid #e2e8f0; color: #64748b;
    padding: 0.6rem 1.25rem; border-radius: 6px; font-weight: 600;
    cursor: pointer;
}

@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>
