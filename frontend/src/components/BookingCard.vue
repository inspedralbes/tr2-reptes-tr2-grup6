<template>
  <div class="booking-card">
    <div class="booking-header">
      <div class="booking-title">
        <h3>{{ booking.workshop_name }}</h3>
        <span class="modality-badge" :class="`modality-${booking.modality}`">
          {{ getModalityLabel(booking.modality) }}
        </span>
      </div>
      <div class="booking-status">
        <span :class="`status-badge status-${booking.status}`">
          {{ getStatusLabel(booking.status) }}
        </span>
      </div>
    </div>

    <div class="booking-details">
      <div class="detail-row">
        <span class="detail-icon">📅</span>
        <div>
          <span class="detail-label">Data</span>
          <span class="detail-value">{{ formatDate(booking.date) }}</span>
        </div>
      </div>

      <div class="detail-row">
        <span class="detail-icon">⏰</span>
        <div>
          <span class="detail-label">Hora</span>
          <span class="detail-value">{{ booking.start_time }} ({{ booking.duration }}h)</span>
        </div>
      </div>

      <div class="detail-row">
        <span class="detail-icon">👥</span>
        <div>
          <span class="detail-label">Capacitat</span>
          <span class="detail-value">{{ booking.capacity }} participants</span>
        </div>
      </div>
    </div>

    <div class="booking-countdown" v-if="daysUntil <= 3 && daysUntil > 0">
      <div class="countdown-warning">
        ⏳ {{ daysUntil === 1 ? 'Demà!' : `Comença en ${daysUntil} dies` }}
      </div>
    </div>

    <div class="booking-actions">
      <button @click="handleReschedule" class="btn btn-secondary btn-sm">
        🔄 Reprogramar
      </button>
      <button @click="handleCancel" class="btn btn-danger btn-sm">
        ❌ Cancel·lar
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  booking: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['cancel', 'reschedule'])

const daysUntil = computed(() => {
  const bookingDate = new Date(props.booking.date)
  const today = new Date()
  const diffTime = bookingDate - today
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
})

const formatDate = (date) => {
  const options = { weekday: 'short', month: 'short', day: 'numeric' }
  return new Date(date).toLocaleDateString('ca-ES', options)
}

const getModalityLabel = (modality) => {
  const labels = {
    'A': '🎓 Presencial',
    'B': '💻 Híbrida',
    'C': '🌐 Virtual'
  }
  return labels[modality] || modality
}

const getStatusLabel = (status) => {
  const labels = {
    'active': '✅ Activa',
    'pending': '⏳ Pendent',
    'completed': '✅ Completada',
    'cancelled': '❌ Cancel·lada'
  }
  return labels[status] || status
}

const handleCancel = () => {
  emit('cancel', props.booking.id)
}

const handleReschedule = () => {
  emit('reschedule', props.booking.id)
}
</script>

<style scoped>
.booking-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
  border-left: 4px solid #3B82F6;
}

.booking-card:hover {
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

/* Header */
.booking-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
  gap: 12px;
}

.booking-title {
  flex: 1;
}

.booking-title h3 {
  margin: 0 0 8px 0;
  font-size: 1.1rem;
  color: #0F172A;
  font-weight: 600;
}

.modality-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

.modality-A {
  background: #D1E7FF;
  color: #1E40AF;
}

.modality-B {
  background: #D1FAE5;
  color: #065F46;
}

.modality-C {
  background: #FEF3C7;
  color: #92400E;
}

.booking-status {
  display: flex;
  gap: 8px;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
}

.status-active {
  background: #D1FAE5;
  color: #065F46;
}

.status-pending {
  background: #FEF3C7;
  color: #92400E;
}

.status-completed {
  background: #D1E7FF;
  color: #1E40AF;
}

.status-cancelled {
  background: #FEE2E2;
  color: #991B1B;
}

/* Details */
.booking-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #E2E8F0;
}

.detail-row {
  display: flex;
  gap: 12px;
  align-items: center;
}

.detail-icon {
  font-size: 1.2rem;
  width: 24px;
}

.detail-label {
  display: block;
  font-size: 0.8rem;
  color: #94A3B8;
  font-weight: 500;
  text-transform: uppercase;
}

.detail-value {
  display: block;
  font-size: 0.95rem;
  color: #0F172A;
  font-weight: 500;
}

/* Countdown */
.booking-countdown {
  margin-bottom: 16px;
}

.countdown-warning {
  background: linear-gradient(135deg, #FEF3C7 0%, #FCD34D 100%);
  color: #92400E;
  padding: 12px;
  border-radius: 6px;
  text-align: center;
  font-weight: 600;
  font-size: 0.9rem;
}

/* Actions */
.booking-actions {
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

.btn-sm {
  padding: 8px 12px;
}

.btn-secondary {
  background: #E2E8F0;
  color: #0F172A;
  border: 1px solid #CBD5E1;
}

.btn-secondary:hover {
  background: #CBD5E1;
}

.btn-danger {
  background: #FEE2E2;
  color: #DC2626;
  border: 1px solid #FECACA;
}

.btn-danger:hover {
  background: #FCA5A5;
  color: #991B1B;
}

/* Responsive */
@media (max-width: 480px) {
  .booking-card {
    padding: 16px;
  }

  .booking-header {
    flex-direction: column;
  }

  .booking-title h3 {
    font-size: 1rem;
  }

  .booking-actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }
}
</style>


