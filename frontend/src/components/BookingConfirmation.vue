<template>
  <div class="booking-confirmation">
    <!-- Success State -->
    <div v-if="isSuccess" class="success-container">
      <div class="success-icon">✅</div>
      <h2>Reserva confirmada!</h2>
      <p class="success-message">
        T'has reservat correctament un slot al taller <strong>{{ workshopName }}</strong>
      </p>

      <!-- Booking Details -->
      <div class="booking-details">
        <div class="detail-card">
          <span class="detail-icon">📅</span>
          <div>
            <span class="detail-label">Data de la sessió</span>
            <span class="detail-value">{{ bookingDate }}</span>
          </div>
        </div>

        <div class="detail-card">
          <span class="detail-icon">🕐</span>
          <div>
            <span class="detail-label">Hora d'inici</span>
            <span class="detail-value">{{ slotTime }}</span>
          </div>
        </div>

        <div class="detail-card">
          <span class="detail-icon">⏱️</span>
          <div>
            <span class="detail-label">Durada</span>
            <span class="detail-value">{{ workshopDuration }}</span>
          </div>
        </div>

        <div class="detail-card">
          <span class="detail-icon">👥</span>
          <div>
            <span class="detail-label">Participants</span>
            <span class="detail-value">{{ slotCapacity }} estudiants</span>
          </div>
        </div>
      </div>

      <!-- Confirmation Code -->
      <div class="confirmation-code">
        <p class="code-label">Codi de confirmació</p>
        <div class="code-box">
          <span class="code-text">{{ confirmationCode }}</span>
          <button @click="copyCode" class="btn-copy">
            {{ copiedCode ? '✓' : '📋' }}
          </button>
        </div>
        <p class="code-help">Guarda aquest codi. El necessitaràs per accedir al taller.</p>
      </div>

      <!-- Instructions -->
      <div class="instructions">
        <h3>❓ Què fer ara?</h3>
        <ol>
          <li>Guarda o imprimeix aquest confirmació</li>
          <li>S'enviarà un correu amb els detalls de la sessió</li>
          <li>Presenta-te 10 minuts abans de l'hora indicada</li>
          <li>Porta el teu codi de confirmació</li>
        </ol>
      </div>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <button @click="printConfirmation" class="btn btn-secondary btn-lg">
          🖨️ Imprimir confirmació
        </button>
        <button @click="goToHome" class="btn btn-primary btn-lg">
          🏠 Anar a l'inici
        </button>
      </div>

      <!-- Additional Info -->
      <div class="info-box">
        <p>
          <strong>Nota:</strong> Si necessites cancel·lar, pots fer-ho des del teu 
          <router-link to="/dashboard">tauler de control</router-link> 
          amb almenys 24h d'anticipació.
        </p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-else-if="isLoading" class="loading-container">
      <div class="spinner"></div>
      <h3>Processant la confirmació...</h3>
      <p>Espera mentre guardem els teus detalls</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <div class="error-icon">❌</div>
      <h2>Error en la confirmació</h2>
      <p>{{ error }}</p>
      <button @click="retry" class="btn btn-primary btn-lg">
        🔄 Reintentar
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useWorkshopStore } from '@/stores/workshop'

const props = defineProps({
  slot: {
    type: Object,
    required: true
  },
  date: {
    type: Date,
    required: true
  },
  workshopId: {
    type: Number,
    required: true
  },
  workshopName: {
    type: String,
    required: true
  },
  workshopDuration: {
    type: Number,
    default: 2
  }
})

const emit = defineEmits(['completed'])

const router = useRouter()
const workshopStore = useWorkshopStore()

const isSuccess = ref(false)
const isLoading = ref(false)
const error = ref(null)
const confirmationCode = ref(generateConfirmationCode())
const copiedCode = ref(false)

// Computed properties
const bookingDate = computed(() => {
  const date = props.date
  const days = ['diumenge', 'dilluns', 'dimarts', 'dimecres', 'dijous', 'divendres', 'dissabte']
  const months = [
    'gener', 'febrer', 'març', 'abril', 'maig', 'juny',
    'juliol', 'agost', 'setembre', 'octubre', 'novembre', 'desembre'
  ]
  
  const dayName = days[date.getDay()]
  const day = date.getDate()
  const monthName = months[date.getMonth()]
  const year = date.getFullYear()
  
  return `${dayName}, ${day} de ${monthName} de ${year}`
})

const slotTime = computed(() => {
  return props.slot.start_time.substring(0, 5)
})

const slotCapacity = computed(() => {
  return props.slot.capacity
})

// Helper functions
function generateConfirmationCode() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
  let code = ''
  for (let i = 0; i < 12; i++) {
    if (i === 4 || i === 8) {
      code += '-'
    } else {
      code += chars.charAt(Math.floor(Math.random() * chars.length))
    }
  }
  return code
}

const copyCode = async () => {
  try {
    await navigator.clipboard.writeText(confirmationCode.value)
    copiedCode.value = true
    setTimeout(() => {
      copiedCode.value = false
    }, 2000)
  } catch (err) {
    console.error('Error copying code:', err)
  }
}

const printConfirmation = () => {
  window.print()
}

const goToHome = () => {
  emit('completed')
  router.push('/')
}

const retry = () => {
  error.value = null
  confirmBooking()
}

const confirmBooking = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    // Simulate API call - in real app, this would save the booking
    await new Promise(resolve => setTimeout(resolve, 2000))
    isSuccess.value = true
  } catch (err) {
    error.value = 'No s\'ha pogut confirmar la reserva. Intenta-ho més tard.'
    console.error(err)
  } finally {
    isLoading.value = false
  }
}

// Auto-confirm on mount
confirmBooking()
</script>

<style scoped>
.booking-confirmation {
  width: 100%;
  min-height: 400px;
}

/* Success State */
.success-container {
  background: linear-gradient(135deg, #D1FAE5 0%, #D1FAE5 100%);
  border-radius: 12px;
  padding: 40px 32px;
  text-align: center;
}

.success-icon {
  font-size: 4rem;
  margin-bottom: 16px;
  animation: bounce 0.6s ease;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.success-container h2 {
  margin: 0 0 12px 0;
  font-size: 2rem;
  color: #065F46;
  font-weight: 700;
}

.success-message {
  margin: 0 0 32px 0;
  color: #047857;
  font-size: 1.05rem;
  line-height: 1.6;
}

/* Booking Details */
.booking-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin: 32px 0;
}

.detail-card {
  background: white;
  border-radius: 8px;
  padding: 16px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.detail-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.detail-label {
  display: block;
  font-size: 0.8rem;
  color: #6B7280;
  font-weight: 500;
  margin-bottom: 4px;
}

.detail-value {
  display: block;
  font-size: 1rem;
  color: #0F172A;
  font-weight: 600;
}

/* Confirmation Code */
.confirmation-code {
  background: white;
  border-radius: 8px;
  padding: 24px;
  margin: 32px 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.code-label {
  margin: 0 0 12px 0;
  font-size: 0.9rem;
  color: #6B7280;
  font-weight: 600;
  text-transform: uppercase;
}

.code-box {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.code-text {
  flex: 1;
  font-family: 'Courier New', monospace;
  font-size: 1.3rem;
  font-weight: 700;
  color: #3B82F6;
  letter-spacing: 2px;
  user-select: all;
  padding: 12px 16px;
  background: #F0F4F8;
  border-radius: 6px;
  border: 2px solid #3B82F6;
}

.btn-copy {
  padding: 10px 16px;
  background: #3B82F6;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 1rem;
}

.btn-copy:hover {
  background: #2563EB;
  transform: translateY(-2px);
}

.code-help {
  margin: 0;
  font-size: 0.85rem;
  color: #6B7280;
  font-style: italic;
}

/* Instructions */
.instructions {
  background: white;
  border-radius: 8px;
  padding: 24px;
  margin: 32px 0;
  text-align: left;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.instructions h3 {
  margin: 0 0 16px 0;
  font-size: 1.1rem;
  color: #0F172A;
  font-weight: 600;
  text-align: center;
}

.instructions ol {
  margin: 0;
  padding-left: 24px;
  color: #475569;
  line-height: 1.8;
}

.instructions li {
  margin-bottom: 12px;
}

/* Info Box */
.info-box {
  background: white;
  border-left: 4px solid #3B82F6;
  border-radius: 8px;
  padding: 16px;
  margin: 24px 0;
  text-align: left;
}

.info-box p {
  margin: 0;
  color: #475569;
  line-height: 1.6;
  font-size: 0.95rem;
}

.info-box a {
  color: #3B82F6;
  text-decoration: none;
  font-weight: 600;
}

.info-box a:hover {
  text-decoration: underline;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 12px;
  margin-top: 32px;
  justify-content: center;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-lg {
  padding: 14px 28px;
  font-size: 1rem;
}

.btn-primary {
  background: #C5A059;
  color: white;
}

.btn-primary:hover {
  background: #b08d47;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-secondary {
  background: white;
  color: #0F172A;
  border: 2px solid #3B82F6;
}

.btn-secondary:hover {
  background: #F0F4F8;
}

/* Loading State */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 32px;
  text-align: center;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #E2E8F0;
  border-top-color: #3B82F6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 24px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-container h3 {
  margin: 0 0 8px 0;
  font-size: 1.2rem;
  color: #0F172A;
}

.loading-container p {
  margin: 0;
  color: #64748B;
  font-size: 0.95rem;
}

/* Error State */
.error-container {
  background: linear-gradient(135deg, #FEE2E2 0%, #FEE2E2 100%);
  border-radius: 12px;
  padding: 40px 32px;
  text-align: center;
}

.error-icon {
  font-size: 4rem;
  margin-bottom: 16px;
}

.error-container h2 {
  margin: 0 0 12px 0;
  font-size: 2rem;
  color: #991B1B;
  font-weight: 700;
}

.error-container p {
  margin: 0 0 32px 0;
  color: #DC2626;
  font-size: 1.05rem;
  line-height: 1.6;
}

/* Responsive */
@media print {
  .action-buttons {
    display: none;
  }

  .success-container {
    background: white;
  }
}

@media (max-width: 768px) {
  .success-container {
    padding: 24px 16px;
  }

  .booking-details {
    grid-template-columns: 1fr;
  }

  .code-box {
    flex-direction: column;
  }

  .action-buttons {
    flex-direction: column;
  }

  .btn-lg {
    width: 100%;
  }

  .success-icon,
  .error-icon {
    font-size: 3rem;
  }

  .success-container h2,
  .error-container h2 {
    font-size: 1.5rem;
  }
}
</style>


