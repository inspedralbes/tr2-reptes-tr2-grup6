<template>
  <div class="feedback-page">
    <div class="feedback-container">
      
      <!-- SIMPLE HEADER -->
      <div class="page-header">
        <h1 class="page-title">Valoració del Taller</h1>
        <div class="gold-divider"></div>
        
        <div class="workshop-summary" v-if="workshop">
          <span class="summary-item highlight">{{ workshop.title }}</span>
          <span class="separator">•</span>
          <span class="summary-item">{{ workshop.center_name }}</span>
          <span class="separator">•</span>
          <span class="summary-item">{{ formatDate(workshop.scheduled_date) }}</span>
        </div>
      </div>

      <!-- MAIN CARD -->
      <div class="feedback-card">
        
        <!-- Steps -->
        <div class="steps-nav">
          <div class="step" :class="{ active: currentStep === 1, complete: currentStep > 1 }" @click="currentStep = 1">1. Valoració</div>
          <div class="step" :class="{ active: currentStep === 2, complete: currentStep > 2 }" @click="currentStep = 2">2. Detalls</div>
          <div class="step" :class="{ active: currentStep === 3, complete: currentStep > 3 }" @click="currentStep = 3">3. Finalitzar</div>
        </div>

        <form @submit.prevent="submitFeedback">
          
          <!-- STEP 1: RATINGS -->
          <div v-show="currentStep === 1" class="step-content">
            <div class="main-rating-section">
              <label>Valoració General <span class="required">*</span></label>
              <div class="star-rating-large">
                <button 
                  v-for="star in 5" 
                  :key="star" 
                  type="button" 
                  class="star-btn"
                  @click="formData.overall_rating = star"
                  @mouseenter="hoverRating = star"
                  @mouseleave="hoverRating = 0"
                >
                  <svg 
                    xmlns="http://www.w3.org/2000/svg" 
                    viewBox="0 0 24 24" 
                    class="star-svg"
                    :class="{ filled: star <= (hoverRating || formData.overall_rating) }"
                  >
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-5.82 3.25L7.36 14.14 2 9.27l6.91-1.01L12 2z"/>
                  </svg>
                </button>
              </div>
              <p class="rating-text">{{ getRatingText(formData.overall_rating) }}</p>
            </div>

            <div class="sub-ratings-grid">
              <div class="sub-rating" v-for="(label, key) in subRatings" :key="key">
                <label>{{ label }}</label>
                <div class="star-rating-small">
                  <button 
                    v-for="star in 5" 
                    :key="star" 
                    type="button" 
                    class="star-btn-small"
                    @click="formData[key] = star"
                    :class="{ active: star <= formData[key] }"
                  >★</button>
                </div>
              </div>
            </div>

            <div class="actions">
              <button type="button" class="btn-primary" @click="currentStep = 2" :disabled="!formData.overall_rating">Següent</button>
            </div>
          </div>

          <!-- STEP 2: DETAILS -->
          <div v-show="currentStep === 2" class="step-content">
            <div class="form-group">
              <label>Assistència</label>
              <div class="row">
                <div class="col">
                  <span class="input-label">Esperats</span>
                  <input type="number" v-model.number="formData.expected_attendees" class="input-clean" placeholder="0">
                </div>
                <div class="col">
                  <span class="input-label">Reals</span>
                  <input type="number" v-model.number="formData.actual_attendees" class="input-clean" placeholder="0">
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Registre d'Alumnes</label>
              <div class="student-input-row">
                <input 
                  type="text" 
                  v-model="newStudentName" 
                  class="input-clean" 
                  placeholder="Nom de l'alumne" 
                  @keyup.enter="addStudent"
                >
                <button type="button" class="btn-secondary" @click="addStudent">Afegir</button>
                <button type="button" class="btn-outline" @click="triggerFileInput" title="Pujar llista (CSV)">
                  <i class="fas fa-file-upload"></i>
                </button>
                <input 
                  type="file" 
                  ref="csvInput" 
                  accept=".csv,.txt" 
                  hidden 
                  @change="handleFileUpload" 
                />
              </div>
              
              <div class="students-list" v-if="studentList.length > 0">
                <div v-for="(student, index) in studentList" :key="index" class="student-item">
                  <span>{{ student }}</span>
                  <button type="button" class="btn-remove" @click="removeStudent(index)">×</button>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Etiquetes</label>
              <div class="tags-list">
                <button 
                  v-for="tag in availableTags" 
                  :key="tag.id"
                  type="button"
                  class="tag-pill"
                  :class="{ selected: selectedTags.includes(tag.id) }"
                  @click="toggleTag(tag.id)"
                >
                  {{ tag.name }}
                </button>
              </div>
            </div>

            <div class="form-group">
              <label>Ho recomanaries?</label>
              <div class="toggle-row">
                <button type="button" class="toggle-btn" :class="{ active: formData.would_recommend }" @click="formData.would_recommend = true">👍 Sí</button>
                <button type="button" class="toggle-btn" :class="{ active: formData.would_recommend === false }" @click="formData.would_recommend = false">👎 No</button>
              </div>
            </div>

            <div class="actions space-between">
              <button type="button" class="btn-text" @click="currentStep = 1">Enrere</button>
              <button type="button" class="btn-primary" @click="currentStep = 3">Següent</button>
            </div>
          </div>

          <!-- STEP 3: COMMENTS -->
          <div v-show="currentStep === 3" class="step-content">
            <div class="form-group">
              <label>Punts Forts</label>
              <textarea v-model="formData.positive_aspects" class="input-area" placeholder="Què ha anat bé?"></textarea>
            </div>
            
            <div class="form-group">
              <label>A millorar</label>
              <textarea v-model="formData.negative_aspects" class="input-area" placeholder="Què es pot millorar?"></textarea>
            </div>

            <div class="actions space-between">
              <button type="button" class="btn-text" @click="currentStep = 2">Enrere</button>
              <button type="submit" class="btn-primary-sc" :disabled="isSubmitting">Enviar Valoració</button>
            </div>
          </div>

        </form>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const currentStep = ref(1);
const workshop = ref(null);
const hoverRating = ref(0);
const isSubmitting = ref(false);
const selectedTags = ref([]);
const studentList = ref([]);
const newStudentName = ref('');

const formData = ref({
  overall_rating: 0,
  content_quality: 0,
  teacher_performance: 0,
  organization: 0,
  relevance: 0,
  positive_aspects: '',
  negative_aspects: '',
  suggestions: '',
  general_comments: '',
  expected_attendees: null,
  actual_attendees: null,
  would_recommend: true,
  is_public: false
});

const subRatings = {
  content_quality: 'Contingut',
  teacher_performance: 'Docent',
  organization: 'Organització',
  relevance: 'Utilitat'
};

const availableTags = ref([
  { id: 1, name: 'Excel·lent' }, { id: 2, name: 'Pràctic' }, { id: 3, name: 'Ameno' },
  { id: 4, name: 'Massa teòric' }, { id: 5, name: 'Poc temps' }, { id: 6, name: 'Ben organitzat' }
]);

// Methods
const csvInput = ref(null);

const triggerFileInput = () => {
  csvInput.value.click();
};

const handleFileUpload = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = (e) => {
    const text = e.target.result;
    const lines = text.split(/\r\n|\n/);
    
    let count = 0;
    lines.forEach(line => {
      // Simple CSV parse: take first non-empty column or just the line if it's a list
      const name = line.split(',')[0].trim();
      if (name && !studentList.value.includes(name)) { // Avoid duplicates
        studentList.value.push(name);
        count++;
      }
    });
    
    if (count > 0) {
      alert(`${count} alumnes importats correctament.`);
    } else {
      alert('No s\'han trobat noms valid al fitxer.');
    }
    
    // Reset input
    event.target.value = '';
  };
  reader.readAsText(file);
};

const addStudent = () => {
  if (newStudentName.value.trim()) {
    studentList.value.push(newStudentName.value.trim());
    newStudentName.value = '';
  }
};

const removeStudent = (index) => {
  studentList.value.splice(index, 1);
};

const getRatingText = (r) => {
  if (!r) return 'Selecciona una puntuació';
  return ['Molt dolent', 'Dolent', 'Correcte', 'Bo', 'Excel·lent'][r-1];
};

const toggleTag = (id) => {
  const idx = selectedTags.value.indexOf(id);
  if (idx === -1) selectedTags.value.push(id);
  else selectedTags.value.splice(idx, 1);
};

const formatDate = (d) => {
  if (!d) return '';
  return new Date(d).toLocaleDateString();
};

const submitFeedback = async () => {
  isSubmitting.value = true;
  try {
    const assignmentId = route.params.assignmentId || route.query.assignmentId;
    const workshopId = route.query.workshopId;
    
    const client = authStore.getApiClient();
    await client.post('/api/feedback', {
      assignment_id: assignmentId,
      workshop_id: workshopId,
      ...formData.value,
      tags: selectedTags.value,
      students: studentList.value
    });
    
    router.push('/center-dashboard');
  } catch (e) {
    console.error(e);
    const msg = e.response?.data?.message || 'Error enviant feedback';
    // Use a toast or alert, here simple alert for now as requested
    alert(msg);
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(async () => {
  try {
    const assignmentId = route.params.assignmentId || route.query.assignmentId;
    
    if (assignmentId) {
      const client = authStore.getApiClient();
      // Try to fetch allocation/assignment details
      // Note: Endpoint might be /api/allocations/:id or different depending on backend
      const response = await client.get(`/api/allocations/${assignmentId}`);
      
      if (response.data.success) {
        const data = response.data.data;
        workshop.value = {
          title: data.workshop_name || 'Taller Sense Nom',
          center_name: data.center_name || authStore.user?.center_name || 'El Teu Centre',
          scheduled_date: data.slot_date || new Date()
        };
      }
    }
  } catch (error) {
    console.error('Error loading workshop data:', error);
    // Fallback to mock if fetch fails so UI doesn't break
    workshop.value = {
      title: 'Taller de Exemple (Dades no trobades)',
      center_name: 'Centre Cívic Demo',
      scheduled_date: new Date()
    };
  }
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap');

.feedback-page {
  min-height: 100vh;
  background-color: #F8FAFC;
  font-family: 'Inter', sans-serif;
  color: #1E293B;
  display: flex;
  justify-content: center;
  padding: 40px 20px;
}

.feedback-container {
  width: 100%;
  max-width: 600px;
}

/* Header */
.page-header {
  text-align: center;
  margin-bottom: 40px;
}

.page-title {
  font-family: 'Playfair Display', serif;
  font-size: 2.5rem;
  color: #1E293B;
  margin-bottom: 12px;
}

.gold-divider {
  width: 60px;
  height: 4px;
  background: #D4AF37;
  margin: 0 auto 20px;
  border-radius: 2px;
}

.workshop-summary {
  font-size: 0.95rem;
  color: #64748B;
  display: flex;
  justify-content: center;
  gap: 12px;
  align-items: center;
}

.summary-item.highlight {
  color: #D4AF37;
  font-weight: 600;
}

/* Card */
.feedback-card {
  background: white;
  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.05);
}

/* Steps */
.steps-nav {
  display: flex;
  justify-content: space-between;
  margin-bottom: 40px;
  border-bottom: 2px solid #F1F5F9;
}

.step {
  padding-bottom: 12px;
  font-size: 0.9rem;
  color: #94A3B8;
  cursor: pointer;
  position: relative;
  font-weight: 500;
  top: 2px;
}

.step.active {
  color: #D4AF37;
  border-bottom: 2px solid #D4AF37;
}

.step.complete {
  color: #10B981;
}

/* Ratings */
.main-rating-section {
  text-align: center;
  margin-bottom: 40px;
}

.star-rating-large {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin: 16px 0;
}

.star-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
}

.star-svg {
  width: 48px;
  height: 48px;
  fill: #E2E8F0;
  transition: fill 0.2s;
}

.star-svg.filled {
  fill: #D4AF37;
}

.rating-text {
  font-weight: 600;
  height: 20px;
  color: #64748B;
}

.sub-ratings-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 40px;
}

.sub-rating label {
  display: block;
  font-size: 0.9rem;
  margin-bottom: 8px;
  color: #475569;
}

.star-btn-small {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #E2E8F0;
  cursor: pointer;
  padding: 0 2px;
  transition: color 0.1s;
}

.star-btn-small.active {
  color: #D4AF37;
}

/* Form Elements */
.form-group { margin-bottom: 24px; }
.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 8px;
  font-size: 0.95rem;
}

.input-clean {
  width: 100%;
  padding: 12px;
  border: 1px solid #E2E8F0;
  border-radius: 8px;
  background: #F8FAFC;
  font-size: 1rem;
  transition: all 0.2s;
}

.input-clean:focus {
  outline: none;
  background: white;
  border-color: #D4AF37;
  box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
}

.input-area {
  width: 100%;
  padding: 12px;
  border: 1px solid #E2E8F0;
  border-radius: 8px;
  background: #F8FAFC;
  resize: vertical;
  min-height: 100px;
  font-family: inherit;
}

.row { display: flex; gap: 16px; }
.col { flex: 1; }
.input-label { font-size: 0.8rem; color: #64748B; margin-bottom: 4px; display: block; }

/* Tags */
.tags-list { display: flex; flex-wrap: wrap; gap: 8px; }
.tag-pill {
  padding: 8px 16px;
  border-radius: 20px;
  border: 1px solid #E2E8F0;
  background: white;
  color: #64748B;
  cursor: pointer;
  transition: all 0.2s;
}

.tag-pill.selected {
  background: #D4AF37;
  color: white;
  border-color: #D4AF37;
}

/* Toggle */
.toggle-row { display: flex; gap: 12px; }
.toggle-btn {
  flex: 1;
  padding: 12px;
  border: 1px solid #E2E8F0;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.toggle-btn.active {
  background: #1E293B;
  color: white;
  border-color: #1E293B;
}

/* Student List */
.student-input-row {
  display: flex;
  gap: 10px;
  margin-bottom: 12px;
}

.btn-secondary {
  background: white;
  border: 1px solid #E2E8F0;
  color: #1E293B;
  padding: 0 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  border-color: #D4AF37;
  color: #D4AF37;
}

.btn-outline {
  background: white;
  border: 1px dashed #94A3B8;
  color: #64748B;
  width: 42px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-outline:hover {
  border-color: #0F172A;
  color: #0F172A;
  background: #F8FAFC;
}

.students-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.student-item {
  background: #F1F5F9;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-remove {
  background: none;
  border: none;
  color: #94A3B8;
  cursor: pointer;
  font-size: 1.1rem;
  line-height: 1;
  padding: 0;
}

.btn-remove:hover {
  color: #EF4444;
}

/* Buttons */
.actions { margin-top: 32px; display: flex; justify-content: flex-end; }
.actions.space-between { justify-content: space-between; }

.btn-primary, .btn-primary-sc {
  background: #1E293B;
  color: white;
  padding: 14px 32px;
  border-radius: 8px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: transform 0.2s;
}

.btn-primary:hover, .btn-primary-sc:hover {
  transform: translateY(-2px);
  background: #0F172A;
}

.btn-primary:disabled {
  background: #CBD5E1;
  cursor: not-allowed;
  transform: none;
}

.btn-text {
  background: none;
  border: none;
  color: #64748B;
  cursor: pointer;
  font-weight: 500;
  padding: 0 12px;
}

.btn-text:hover { color: #1E293B; }

/* Responsive */
@media (max-width: 600px) {
  .sub-ratings-grid { grid-template-columns: 1fr; }
  .feedback-card { padding: 24px; }
  .page-title { font-size: 2rem; }
}
</style>
