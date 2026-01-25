<template>
  <div class="modal-overlay" @click.self="closeModal">
    <div class="modal-content">
      <button class="btn-close" @click="closeModal">✕</button>

      <!-- Loading State -->
      <div v-if="loading" class="modal-loading">
        <div class="spinner"></div>
        <p>Carregant detalls...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="modal-error">
        <p>{{ error }}</p>
        <button @click="closeModal" class="btn btn-primary">Tancar</button>
      </div>

      <!-- Workshop Details -->
      <div v-else-if="workshop" class="modal-body">
        <!-- Header with image -->
        <div class="modal-header">
          <div class="workshop-image">
            <div class="image-placeholder">
              <span class="image-emoji">{{ getModalityEmoji }}</span>
            </div>
          </div>
          <div class="header-info">
            <span class="modality-badge" :style="{ backgroundColor: modalityColor }">
              Modalitat {{ workshop.modality }}
            </span>
            <h2>{{ workshop.name }}</h2>
            <p class="category-tag">{{ workshop.category }}</p>
          </div>
        </div>

        <!-- Main Content -->
        <div class="modal-main">
          <!-- Description -->
          <section class="section">
            <h3>Descripció</h3>
            <p>{{ workshop.description }}</p>
          </section>

          <!-- Details Grid -->
          <section class="section">
            <h3>Informació</h3>
            <div class="details-grid">
              <div class="detail-item">
                <span class="detail-icon">⏱️</span>
                <div>
                  <span class="detail-label">Durada</span>
                  <span class="detail-value">{{ workshop.hours }}h</span>
                </div>
              </div>
              <div class="detail-item">
                <span class="detail-icon">👥</span>
                <div>
                  <span class="detail-label">Capacitat</span>
                  <span class="detail-value">{{ workshop.capacity }} estudiants</span>
                </div>
              </div>
              <div class="detail-item">
                <span class="detail-icon">📍</span>
                <div>
                  <span class="detail-label">Disponibles</span>
                  <span class="detail-value">{{ workshop.available_slots || 0 }} places</span>
                </div>
              </div>
              <div class="detail-item">
                <span class="detail-icon">📊</span>
                <div>
                  <span class="detail-label">Ocupació</span>
                  <span class="detail-value">{{ capacityPercent }}%</span>
                </div>
              </div>
            </div>

            <!-- Capacity Bar -->
            <div class="capacity-section">
              <div class="capacity-bar">
                <div class="bar-fill" :style="{ width: capacityPercent + '%' }"></div>
              </div>
              <p class="capacity-text">
                {{ workshop.capacity - (workshop.available_slots || 0) }} de {{ workshop.capacity }} places ocupades
              </p>
            </div>
          </section>

          <!-- Objectives -->
          <section class="section" v-if="workshop.objectives">
            <h3>Objectius</h3>
            <ul class="objectives-list">
              <li v-for="(obj, idx) in workshop.objectives.split('\n')" :key="idx" v-if="obj.trim()">
                {{ obj.trim() }}
              </li>
            </ul>
          </section>

          <!-- Requirements -->
          <section class="section" v-if="workshop.requirements">
            <h3>Requisits</h3>
            <ul class="requirements-list">
              <li v-for="(req, idx) in workshop.requirements.split('\n')" :key="idx" v-if="req.trim()">
                {{ req.trim() }}
              </li>
            </ul>
          </section>

          <!-- Availability Status -->
          <section class="section">
            <h3>Estat de disponibilitat</h3>
            <div :class="['availability-badge', availabilityClass]">
              {{ availabilityText }}
            </div>
          </section>
        </div>

        <!-- Action Buttons -->
        <div class="modal-footer">
          <button 
            @click="requestWorkshop"
            class="btn btn-primary"
            :disabled="!canRequest || requestLoading"
          >
            <span v-if="requestLoading">⏳ Processant...</span>
            <span v-else-if="!canRequest">❌ No pots sol·licitar</span>
            <span v-else>✅ Demanar accés</span>
          </button>
          <button @click="closeModal" class="btn btn-secondary">
            Tancar
          </button>
        </div>

        <!-- Success Message -->
        <div v-if="requestSuccess" class="success-message">
          ✅ Sol·licitud creada correctament!
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useWorkshopStore } from '@/stores/workshop'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  workshopId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close'])

const workshopStore = useWorkshopStore()
const authStore = useAuthStore()

const workshop = ref(null)
const loading = ref(true)
const error = ref(null)
const requestLoading = ref(false)
const requestSuccess = ref(false)

// Modality color mapping
const modalityColor = computed(() => {
  const colors = {
    A: '#3B82F6', // Blue
    B: '#C5A059', // Gold
    C: '#10B981'  // Green
  }
  return colors[workshop.value?.modality] || '#6B7280'
})

// Modality emoji
const getModalityEmoji = computed(() => {
  const emojis = {
    A: '🤖',
    B: '💻',
    C: '⚙️'
  }
  return emojis[workshop.value?.modality] || '🎓'
})

// Capacity percent
const capacityPercent = computed(() => {
  if (!workshop.value || !workshop.value.capacity) return 0
  const used = workshop.value.capacity - (workshop.value.available_slots || 0)
  return Math.round((used / workshop.value.capacity) * 100)
})

// Availability info
const availabilityClass = computed(() => {
  if (!workshop.value) return 'unavailable'
  if (workshop.value.available_slots === 0) return 'full'
  if (workshop.value.available_slots < 3) return 'limited'
  return 'available'
})

const availabilityText = computed(() => {
  if (!workshop.value) return 'No disponible'
  if (workshop.value.available_slots === 0) return '❌ Taller ple'
  if (workshop.value.available_slots < 3) {
    return `⚠️ Només ${workshop.value.available_slots} places disponibles`
  }
  return `✅ ${workshop.value.available_slots} places disponibles`
})

// Can request workshop
const canRequest = computed(() => {
  return workshop.value && workshop.value.available_slots > 0 && authStore.isAuthenticated
})

// Load workshop details
const loadWorkshop = async () => {
  loading.value = true
  error.value = null
  try {
    await workshopStore.fetchWorkshop(props.workshopId)
    workshop.value = workshopStore.workshops.find(w => w.id === props.workshopId)
    if (!workshop.value) {
      error.value = 'No s\'ha trobat el taller'
    }
  } catch (e) {
    error.value = 'Error carregant els detalls. Intenta-ho més tard.'
    console.error(e)
  } finally {
    loading.value = false
  }
}

// Request workshop
const requestWorkshop = async () => {
  if (!canRequest.value) return

  requestLoading.value = true
  try {
    await workshopStore.requestWorkshop({
      workshop_id: workshop.value.id,
      center_id: authStore.user.center_id,
      priority: 1
    })
    requestSuccess.value = true
    setTimeout(() => {
      emit('close')
    }, 2000)
  } catch (e) {
    error.value = 'Error creant la sol·licitud. Intenta-ho més tard.'
    console.error(e)
  } finally {
    requestLoading.value = false
  }
}

// Close modal
const closeModal = () => {
  emit('close')
}

// Load on mount
onMounted(() => {
  loadWorkshop()
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  position: relative;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    transform: translateY(40px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.btn-close {
  position: absolute;
  top: 16px;
  right: 16px;
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #64748B;
  z-index: 10;
  transition: color 0.2s ease;
}

.btn-close:hover {
  color: #0F172A;
}

/* Loading State */
.modal-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  gap: 16px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #E2E8F0;
  border-top-color: #3B82F6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.modal-loading p {
  color: #64748B;
  font-weight: 500;
}

/* Error State */
.modal-error {
  padding: 40px 20px;
  text-align: center;
}

.modal-error p {
  color: #EF4444;
  margin: 0 0 20px 0;
  font-weight: 500;
}

/* Main Content */
.modal-header {
  display: grid;
  grid-template-columns: 120px 1fr;
  gap: 20px;
  padding: 24px;
  background: linear-gradient(135deg, #F8FAFC 0%, #F0F4F8 100%);
  border-bottom: 1px solid #E2E8F0;
}

.workshop-image {
  display: flex;
  align-items: center;
  justify-content: center;
}

.image-placeholder {
  width: 100%;
  aspect-ratio: 1;
  background: white;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #E2E8F0;
}

.image-emoji {
  font-size: 3rem;
}

.header-info {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 8px;
}

.modality-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 20px;
  color: white;
  font-weight: 600;
  font-size: 0.85rem;
  width: fit-content;
}

.header-info h2 {
  margin: 0;
  font-size: 1.5rem;
  color: #0F172A;
  line-height: 1.3;
}

.category-tag {
  margin: 0;
  color: #64748B;
  font-size: 0.95rem;
}

/* Main Content */
.modal-main {
  padding: 24px;
}

.section {
  margin-bottom: 24px;
}

.section:last-of-type {
  margin-bottom: 0;
}

.section h3 {
  margin: 0 0 12px 0;
  font-size: 1.1rem;
  color: #0F172A;
  font-weight: 600;
}

.section p {
  margin: 0;
  color: #475569;
  line-height: 1.6;
}

/* Details Grid */
.details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.detail-item {
  background: #F8FAFC;
  padding: 12px;
  border-radius: 6px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.detail-icon {
  font-size: 1.3rem;
}

.detail-label {
  display: block;
  font-size: 0.8rem;
  color: #94A3B8;
  font-weight: 500;
  margin-bottom: 2px;
}

.detail-value {
  display: block;
  font-size: 1rem;
  color: #0F172A;
  font-weight: 600;
}

/* Capacity Section */
.capacity-section {
  margin-top: 16px;
}

.capacity-bar {
  width: 100%;
  height: 8px;
  background: #E2E8F0;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 8px;
}

.bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #C5A059, #b08d47);
  transition: width 0.3s ease;
}

.capacity-text {
  font-size: 0.85rem;
  color: #64748B;
  margin: 0;
}

/* Lists */
.objectives-list,
.requirements-list {
  margin: 0;
  padding-left: 20px;
  color: #475569;
}

.objectives-list li,
.requirements-list li {
  margin-bottom: 8px;
  line-height: 1.5;
}

/* Availability Badge */
.availability-badge {
  padding: 12px 16px;
  border-radius: 6px;
  font-weight: 600;
  text-align: center;
}

.availability-badge.available {
  background: #D1FAE5;
  color: #065F46;
}

.availability-badge.limited {
  background: #FEF3C7;
  color: #92400E;
}

.availability-badge.full {
  background: #FEE2E2;
  color: #991B1B;
}

.availability-badge.unavailable {
  background: #F3F4F6;
  color: #4B5563;
}

/* Footer */
.modal-footer {
  padding: 20px 24px;
  border-top: 1px solid #E2E8F0;
  background: #F8FAFC;
  display: flex;
  gap: 12px;
}

.btn {
  flex: 1;
  padding: 12px 16px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.95rem;
}

.btn-primary {
  background: #C5A059;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563EB;
}

.btn-primary:disabled {
  background: #CBD5E1;
  color: #94A3B8;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  color: #0F172A;
  border: 1px solid #E2E8F0;
}

.btn-secondary:hover {
  background: #F8FAFC;
  border-color: #CBD5E1;
}

/* Success Message */
.success-message {
  position: absolute;
  bottom: 20px;
  left: 20px;
  right: 20px;
  background: #D1FAE5;
  color: #065F46;
  padding: 16px;
  border-radius: 6px;
  font-weight: 600;
  text-align: center;
  animation: slideUp 0.3s ease;
}

@media (max-width: 768px) {
  .modal-header {
    grid-template-columns: 100px 1fr;
    gap: 16px;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }

  .modal-footer {
    flex-direction: column;
  }
}
</style>


