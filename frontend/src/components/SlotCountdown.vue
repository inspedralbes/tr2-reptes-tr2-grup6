<template>
  <div class="slot-countdown">
    <!-- Header -->
    <div class="countdown-header">
      <h3>⏱️ Confirmació de reserva</h3>
      <p class="subtitle">Has bloquejar aquest slot durant {{ lockDurationMinutes }} minuts</p>
    </div>

    <!-- Countdown Timer -->
    <div :class="['countdown-box', { 'warning': isWarning, 'critical': isCritical }]">
      <div class="timer-display">
        <span class="timer-icon">⏳</span>
        <div class="timer-numbers">
          <span class="minutes">{{ paddedMinutes }}</span>
          <span class="separator">:</span>
          <span class="seconds">{{ paddedSeconds }}</span>
        </div>
      </div>

      <!-- Progress Bar -->
      <div class="progress-container">
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: progressPercent + '%' }"></div>
        </div>
        <p class="progress-text">
          {{ timeRemainingText }}
        </p>
      </div>
    </div>

    <!-- Slot Details -->
    <div class="slot-details-box">
      <div class="detail-row">
        <span class="detail-label">📅 Data i hora:</span>
        <span class="detail-value">{{ slotDateTime }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">👥 Places disponibles:</span>
        <span class="detail-value">{{ slot.available_spots }}/{{ slot.capacity }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">🎓 Taller:</span>
        <span class="detail-value">{{ workshopName }}</span>
      </div>
    </div>

    <!-- Status Messages -->
    <div v-if="isWarning && !isCritical" class="status-box warning">
      <p>⚠️ <strong>Avís:</strong> El temps s'està acabant. Completa la confirmació!</p>
    </div>

    <div v-if="isCritical" class="status-box critical">
      <p>🚨 <strong>URGENT:</strong> El temps s'ha esgotat! El slot ha estat alliberat.</p>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
      <button 
        @click="confirmBooking"
        class="btn btn-primary btn-lg"
        :disabled="isCritical || isConfirming"
      >
        <span v-if="isConfirming">⏳ Confirmant...</span>
        <span v-else>✅ Confirmar reserva</span>
      </button>
      <button 
        @click="cancelBooking"
        class="btn btn-secondary btn-lg"
        :disabled="isConfirming"
      >
        ❌ Alliberar slot
      </button>
    </div>

    <!-- Success Message -->
    <div v-if="bookingSuccess" class="success-box">
      <p>✅ <strong>Reserva confirmada!</strong> El teu slot ha estat guardat.</p>
    </div>

    <!-- Error Message -->
    <div v-if="bookingError" class="error-box">
      <p>❌ {{ bookingError }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useSlotStore } from '@/stores/slot'

const props = defineProps({
  slot: {
    type: Object,
    required: true
  },
  date: {
    type: Date,
    required: true
  },
  lockToken: {
    type: String,
    required: true
  },
  workshopName: {
    type: String,
    required: true
  },
  lockDurationMinutes: {
    type: Number,
    default: 5
  }
})

const emit = defineEmits(['confirmed', 'cancelled', 'expired'])

const slotStore = useSlotStore()

const timeRemaining = ref(props.lockDurationMinutes * 60)
const isConfirming = ref(false)
const bookingSuccess = ref(false)
const bookingError = ref(null)
let timerInterval = null

// Computed properties
const paddedMinutes = computed(() => {
  const minutes = Math.floor(timeRemaining.value / 60)
  return String(minutes).padStart(2, '0')
})

const paddedSeconds = computed(() => {
  const seconds = timeRemaining.value % 60
  return String(seconds).padStart(2, '0')
})

const progressPercent = computed(() => {
  const total = props.lockDurationMinutes * 60
  return (timeRemaining.value / total) * 100
})

const isWarning = computed(() => {
  return timeRemaining.value <= 60 && timeRemaining.value > 0
})

const isCritical = computed(() => {
  return timeRemaining.value <= 0
})

const timeRemainingText = computed(() => {
  if (timeRemaining.value > 60) {
    return `Temps restant: ${paddedMinutes.value}:${paddedSeconds.value}`
  } else if (timeRemaining.value > 0) {
    return `Pressa! ${timeRemaining.value}s restants`
  } else {
    return 'Temps esgotat'
  }
})

const slotDateTime = computed(() => {
  const hours = props.slot.start_time.substring(0, 5)
  const date = props.date
  const days = ['diumenge', 'dilluns', 'dimarts', 'dimecres', 'dijous', 'divendres', 'dissabte']
  const months = [
    'gener', 'febrer', 'març', 'abril', 'maig', 'juny',
    'juliol', 'agost', 'setembre', 'octubre', 'novembre', 'desembre'
  ]
  
  const dayName = days[date.getDay()]
  const day = date.getDate()
  const monthName = months[date.getMonth()]
  
  return `${dayName}, ${day} de ${monthName} a les ${hours}`
})

// Methods
const startTimer = () => {
  timerInterval = setInterval(() => {
    timeRemaining.value--
    
    if (timeRemaining.value <= 0) {
      clearInterval(timerInterval)
      emit('expired')
      handleExpiry()
    }
  }, 1000)
}

const handleExpiry = async () => {
  await slotStore.unlockSlot(props.lockToken)
}

const confirmBooking = async () => {
  if (isCritical.value || isConfirming.value) return
  
  isConfirming.value = true
  bookingError.value = null
  
  try {
    await slotStore.bookSlot({
      slot_id: props.slot.id,
      lock_token: props.lockToken
    })
    
    bookingSuccess.value = true
    clearInterval(timerInterval)
    
    setTimeout(() => {
      emit('confirmed')
    }, 2000)
  } catch (error) {
    bookingError.value = error.message || 'Error confirmant la reserva. Intenta-ho de nou.'
    console.error(error)
  } finally {
    isConfirming.value = false
  }
}

const cancelBooking = async () => {
  if (isConfirming.value) return
  
  isConfirming.value = true
  
  try {
    await slotStore.unlockSlot(props.lockToken)
    clearInterval(timerInterval)
    emit('cancelled')
  } catch (error) {
    bookingError.value = 'Error alliberant el slot. Intenta-ho de nou.'
    console.error(error)
  } finally {
    isConfirming.value = false
  }
}

// Lifecycle
onMounted(() => {
  startTimer()
})

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval)
  }
})
</script>

<style scoped>
.slot-countdown {
  width: 100%;
}

.countdown-header {
  margin-bottom: 24px;
}

.countdown-header h3 {
  margin: 0 0 8px 0;
  font-size: 1.2rem;
  color: #0F172A;
  font-weight: 600;
}

.subtitle {
  margin: 0;
  color: #64748B;
  font-size: 0.95rem;
}

/* Countdown Box */
.countdown-box {
  background: white;
  border: 2px solid #E2E8F0;
  border-radius: 12px;
  padding: 32px 24px;
  text-align: center;
  margin-bottom: 24px;
  transition: all 0.3s ease;
}

.countdown-box.warning {
  border-color: #F59E0B;
  background: #FFFBEB;
}

.countdown-box.critical {
  border-color: #EF4444;
  background: #FEE2E2;
}

.timer-display {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-bottom: 24px;
}

.timer-icon {
  font-size: 3rem;
  animation: pulse-icon 1s ease-in-out infinite;
}

.countdown-box.critical .timer-icon {
  animation: shake 0.5s ease-in-out infinite;
}

@keyframes pulse-icon {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-8px); }
  75% { transform: translateX(8px); }
}

.timer-numbers {
  display: flex;
  align-items: center;
  gap: 4px;
}

.minutes,
.seconds {
  font-size: 3.5rem;
  font-weight: 700;
  color: #3B82F6;
  font-variant-numeric: tabular-nums;
}

.countdown-box.warning .minutes,
.countdown-box.warning .seconds {
  color: #F59E0B;
}

.countdown-box.critical .minutes,
.countdown-box.critical .seconds {
  color: #EF4444;
}

.separator {
  font-size: 2rem;
  color: #CBD5E1;
  opacity: 0.5;
}

/* Progress */
.progress-container {
  margin-top: 24px;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #E2E8F0;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #C5A059, #b08d47);
  transition: width 0.3s ease;
}

.countdown-box.warning .progress-fill {
  background: linear-gradient(90deg, #F59E0B, #FBBF24);
}

.countdown-box.critical .progress-fill {
  background: linear-gradient(90deg, #EF4444, #DC2626);
}

.progress-text {
  margin: 0;
  font-size: 0.85rem;
  color: #64748B;
  font-weight: 500;
}

.countdown-box.warning .progress-text {
  color: #D97706;
}

.countdown-box.critical .progress-text {
  color: #DC2626;
}

/* Slot Details */
.slot-details-box {
  background: #F8FAFC;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 20px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #E2E8F0;
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-label {
  color: #64748B;
  font-weight: 500;
  font-size: 0.9rem;
}

.detail-value {
  color: #0F172A;
  font-weight: 600;
  font-size: 0.95rem;
}

/* Status Messages */
.status-box {
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-weight: 500;
}

.status-box p {
  margin: 0;
  font-size: 0.95rem;
}

.status-box.warning {
  background: #FFFBEB;
  border-left: 4px solid #F59E0B;
  color: #92400E;
}

.status-box.critical {
  background: #FEE2E2;
  border-left: 4px solid #EF4444;
  color: #991B1B;
  animation: pulse-warning 1s ease-in-out infinite;
}

@keyframes pulse-warning {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.8; }
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.btn {
  flex: 1;
  padding: 12px 20px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-lg {
  padding: 14px 24px;
}

.btn-primary {
  background: #C5A059;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563EB;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-primary:disabled {
  background: #CBD5E1;
  color: #94A3B8;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  color: #0F172A;
  border: 2px solid #E2E8F0;
}

.btn-secondary:hover:not(:disabled) {
  border-color: #DC2626;
  color: #DC2626;
}

.btn-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Success & Error Messages */
.success-box,
.error-box {
  padding: 16px;
  border-radius: 8px;
  font-weight: 500;
  margin-bottom: 20px;
}

.success-box p,
.error-box p {
  margin: 0;
  font-size: 0.95rem;
}

.success-box {
  background: #D1FAE5;
  border-left: 4px solid #10B981;
  color: #065F46;
  animation: slideDown 0.3s ease;
}

.error-box {
  background: #FEE2E2;
  border-left: 4px solid #EF4444;
  color: #991B1B;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .timer-numbers {
    flex-direction: column;
    gap: 0;
  }

  .minutes,
  .seconds {
    font-size: 2.5rem;
  }

  .separator {
    display: none;
  }

  .action-buttons {
    flex-direction: column;
  }
}
</style>


