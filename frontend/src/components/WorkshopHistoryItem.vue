<template>
  <div class="history-item-card">
    <div class="item-header">
      <div class="item-title">
        <h3>{{ workshop.workshop_name }}</h3>
        <span class="date-label">{{ formatDate(workshop.completed_date) }}</span>
      </div>
      <div class="rating-display">
        <div class="stars" :data-rating="workshop.rating">
          <span v-for="i in 5" :key="i" class="star" :class="{ filled: i <= workshop.rating }">
            ⭐
          </span>
        </div>
        <span class="rating-text">{{ workshop.rating }}/5</span>
      </div>
    </div>

    <div class="item-review" v-if="workshop.review">
      <p>{{ workshop.review }}</p>
    </div>

    <div class="item-actions">
      <button @click="handleRate" class="btn btn-primary btn-sm">
        ✍️ Afegir valoració
      </button>
      <button @click="handleCertificate" class="btn btn-secondary btn-sm">
        📜 Descarregar certificat
      </button>
    </div>

    <!-- Rating Modal -->
    <div v-if="showRatingModal" class="modal-overlay" @click.self="showRatingModal = false">
      <div class="modal-content">
        <h3>Valorar taller</h3>
        <p>{{ workshop.workshop_name }}</p>

        <div class="rating-selector">
          <span v-for="i in 5" :key="i" class="star-selector" @click="selectedRating = i">
            <span :class="{ active: i <= selectedRating }">⭐</span>
          </span>
        </div>

        <div class="review-form">
          <textarea
            v-model="reviewText"
            placeholder="Parteix la teva opinió sobre aquest taller..."
            class="textarea"
            rows="4"
          ></textarea>
        </div>

        <div class="modal-actions">
          <button @click="submitRating" :disabled="loading" class="btn btn-primary">
            {{ loading ? '⏳ Guardant...' : '💾 Guardar valoració' }}
          </button>
          <button @click="showRatingModal = false" class="btn btn-secondary">
            Cancelar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  workshop: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['rate'])

const showRatingModal = ref(false)
const selectedRating = ref(props.workshop.rating || 5)
const reviewText = ref(props.workshop.review || '')
const loading = ref(false)

const formatDate = (date) => {
  const options = { year: 'numeric', month: 'long', day: 'numeric' }
  return new Date(date).toLocaleDateString('ca-ES', options)
}

const handleRate = () => {
  showRatingModal.value = true
}

const handleCertificate = () => {
  alert('Descàrrega de certificat pròximament')
}

const submitRating = async () => {
  loading.value = true
  try {
    emit('rate', {
      workshopId: props.workshop.id,
      rating: selectedRating.value,
      review: reviewText.value
    })
    showRatingModal.value = false
  } catch (error) {
    console.error('Error guardant valoració:', error)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.history-item-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
  border-left: 4px solid #10B981;
}

.history-item-card:hover {
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

/* Header */
.item-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 16px;
}

.item-title {
  flex: 1;
}

.item-title h3 {
  margin: 0 0 4px 0;
  font-size: 1.1rem;
  color: #0F172A;
  font-weight: 600;
}

.date-label {
  display: inline-block;
  font-size: 0.8rem;
  color: #94A3B8;
  font-weight: 500;
}

/* Rating Display */
.rating-display {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-direction: column;
}

.stars {
  display: flex;
  gap: 4px;
}

.star {
  font-size: 1.2rem;
  opacity: 0.3;
  transition: opacity 0.2s ease;
}

.star.filled {
  opacity: 1;
}

.rating-text {
  font-size: 0.9rem;
  font-weight: 600;
  color: #0F172A;
}

/* Review */
.item-review {
  background: #F8FAFC;
  border-left: 3px solid #3B82F6;
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 16px;
}

.item-review p {
  margin: 0;
  color: #475569;
  font-size: 0.95rem;
  line-height: 1.6;
}

/* Actions */
.item-actions {
  display: flex;
  gap: 8px;
}

.btn {
  flex: 1;
  padding: 8px 12px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.85rem;
}

.btn-primary {
  background: #C5A059;
  color: white;
}

.btn-primary:hover {
  background: #2563EB;
}

.btn-secondary {
  background: #E2E8F0;
  color: #0F172A;
}

.btn-secondary:hover {
  background: #CBD5E1;
}

/* Modal */
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
}

.modal-content {
  background: white;
  border-radius: 12px;
  padding: 32px;
  max-width: 450px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-content h3 {
  margin: 0 0 8px 0;
  font-size: 1.3rem;
  color: #0F172A;
}

.modal-content p {
  margin: 0 0 24px 0;
  color: #475569;
  font-size: 0.95rem;
}

/* Rating Selector */
.rating-selector {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-bottom: 24px;
}

.star-selector {
  font-size: 2rem;
  cursor: pointer;
  transition: all 0.2s ease;
  opacity: 0.3;
}

.star-selector span {
  display: block;
  transition: all 0.2s ease;
}

.star-selector span.active {
  opacity: 1;
  transform: scale(1.2);
}

.star-selector:hover {
  opacity: 0.6;
}

/* Review Form */
.review-form {
  margin-bottom: 24px;
}

.textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #E2E8F0;
  border-radius: 6px;
  font-size: 0.95rem;
  color: #0F172A;
  font-family: inherit;
  resize: none;
  transition: all 0.2s ease;
}

.textarea:focus {
  outline: none;
  border-color: #3B82F6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Modal Actions */
.modal-actions {
  display: flex;
  gap: 12px;
}

.modal-actions .btn {
  flex: 1;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Responsive */
@media (max-width: 480px) {
  .history-item-card {
    padding: 16px;
  }

  .item-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .rating-display {
    flex-direction: row;
    width: 100%;
  }

  .item-actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }

  .modal-content {
    margin: 0 20px;
    max-width: none;
  }
}
</style>


