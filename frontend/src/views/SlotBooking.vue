<template>
  <div class="slot-booking-view">
    <!-- Header -->
    <div class="booking-header">
      <h1>🎓 Reservar una sessió</h1>
      <p class="subtitle">Completa els passos per reservar una plaça en el taller</p>
    </div>

    <!-- Progress Steps -->
    <div class="progress-steps">
      <div 
        v-for="(step, index) in steps" 
        :key="step.id"
        :class="['step', { 'active': currentStep === step.id, 'completed': isStepCompleted(step.id) }]"
      >
        <div class="step-number">
          {{ isStepCompleted(step.id) ? '✓' : index + 1 }}
        </div>
        <div class="step-label">{{ step.label }}</div>
        <div v-if="index < steps.length - 1" class="step-connector"></div>
      </div>
    </div>

    <!-- Step Container -->
    <div class="booking-container">
      <!-- Step 1: Slot Selection -->
      <transition name="slide-fade" mode="out-in">
        <div v-if="currentStep === 'selection'" key="selection" class="step-content">
          <SlotSelector 
            @proceed="handleSlotSelected"
          />
        </div>
      </transition>

      <!-- Step 2: Countdown/Lock -->
      <transition name="slide-fade" mode="out-in">
        <div v-if="currentStep === 'countdown'" key="countdown" class="step-content">
          <SlotCountdown 
            v-if="selectedSlot && selectedDate && lockToken"
            :slot="selectedSlot"
            :date="selectedDate"
            :lock-token="lockToken"
            :workshop-name="workshopName"
            @confirmed="handleBookingConfirmed"
            @cancelled="handleBookingCancelled"
            @expired="handleLockExpired"
          />
        </div>
      </transition>

      <!-- Step 3: Confirmation -->
      <transition name="slide-fade" mode="out-in">
        <div v-if="currentStep === 'confirmation'" key="confirmation" class="step-content">
          <BookingConfirmation 
            v-if="selectedSlot && selectedDate"
            :slot="selectedSlot"
            :date="selectedDate"
            :workshop-id="workshopId"
            :workshop-name="workshopName"
            :workshop-duration="workshopDuration"
            @completed="handleBookingCompleted"
          />
        </div>
      </transition>
    </div>

    <!-- Error Toast -->
    <transition name="slide-down">
      <div v-if="errorMessage" class="error-toast">
        <p>❌ {{ errorMessage }}</p>
        <button @click="clearError" class="btn-close">✕</button>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useWorkshopStore } from '@/stores/workshop'
import { useSlotStore } from '@/stores/slot'
import { useAuthStore } from '@/stores/auth'
import SlotSelector from '@/components/SlotSelector.vue'
import SlotCountdown from '@/components/SlotCountdown.vue'
import BookingConfirmation from '@/components/BookingConfirmation.vue'

const router = useRouter()
const workshopStore = useWorkshopStore()
const slotStore = useSlotStore()
const authStore = useAuthStore()

const steps = [
  { id: 'selection', label: 'Seleccionar slot' },
  { id: 'countdown', label: 'Confirmar' },
  { id: 'confirmation', label: 'Finalitzar' }
]

const currentStep = ref('selection')
const selectedSlot = ref(null)
const selectedDate = ref(null)
const lockToken = ref(null)
const workshopId = ref(null)
const workshopName = ref('')
const workshopDuration = ref(2)
const errorMessage = ref(null)

// Get workshop details from route or store
const initializeWorkshop = () => {
  const routeWorkshopId = router.currentRoute.value.params.workshopId
  if (routeWorkshopId) {
    workshopId.value = parseInt(routeWorkshopId)
    const workshop = workshopStore.workshops.find(w => w.id === workshopId.value)
    if (workshop) {
      workshopName.value = workshop.name
      workshopDuration.value = workshop.hours || 2
    }
  }
}

// Helper functions
const isStepCompleted = (stepId) => {
  const stepIndex = steps.findIndex(s => s.id === stepId)
  const currentIndex = steps.findIndex(s => s.id === currentStep.value)
  return stepIndex < currentIndex
}

const handleSlotSelected = async (data) => {
  selectedSlot.value = data.slot
  selectedDate.value = data.date
  
  try {
    // Lock the slot (get lock token)
    const response = await slotStore.lockSlot(data.slotId)
    lockToken.value = response.lock_token || response.token
    
    // Move to countdown step
    currentStep.value = 'countdown'
  } catch (error) {
    errorMessage.value = 'Error bloqueant el slot. Intenta-ho de nou.'
    console.error(error)
  }
}

const handleBookingConfirmed = () => {
  currentStep.value = 'confirmation'
}

const handleBookingCancelled = () => {
  // Reset to selection
  currentStep.value = 'selection'
  selectedSlot.value = null
  selectedDate.value = null
  lockToken.value = null
}

const handleLockExpired = () => {
  errorMessage.value = 'El temps per confirmar ha expirat. Selecciona un altre slot.'
  currentStep.value = 'selection'
  selectedSlot.value = null
  selectedDate.value = null
  lockToken.value = null
}

const handleBookingCompleted = () => {
  // Redirect to dashboard or home
  router.push('/dashboard')
}

const clearError = () => {
  errorMessage.value = null
}

onMounted(() => {
  // Check if user is authenticated
  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }

  initializeWorkshop()
})
</script>

<style scoped>
.slot-booking-view {
  min-height: 100vh;
  background: linear-gradient(135deg, #F8FAFC 0%, #F0F4F8 100%);
  padding: 40px 20px;
}

.booking-header {
  max-width: 1000px;
  margin: 0 auto 40px;
  text-align: center;
}

.booking-header h1 {
  font-size: 2.5rem;
  font-weight: 700;
  color: #0F172A;
  margin: 0 0 12px 0;
}

.subtitle {
  font-size: 1.1rem;
  color: #64748B;
  margin: 0;
}

/* Progress Steps */
.progress-steps {
  max-width: 1000px;
  margin: 0 auto 40px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  position: relative;
  flex: 0 0 auto;
}

.step-number {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: white;
  border: 2px solid #E2E8F0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: #94A3B8;
  font-size: 1.1rem;
  transition: all 0.3s ease;
}

.step.active .step-number {
  background: #3B82F6;
  border-color: #3B82F6;
  color: white;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.step.completed .step-number {
  background: #10B981;
  border-color: #10B981;
  color: white;
}

.step-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #64748B;
  text-align: center;
  min-width: 100px;
  transition: color 0.3s ease;
}

.step.active .step-label {
  color: #3B82F6;
}

.step.completed .step-label {
  color: #10B981;
}

.step-connector {
  position: absolute;
  top: 24px;
  left: 50%;
  width: 60px;
  height: 2px;
  background: #E2E8F0;
  transform: translateX(50%);
  transition: background 0.3s ease;
}

.step:last-child .step-connector {
  display: none;
}

.step.completed ~ .step .step-connector {
  background: #E2E8F0;
}

.step.completed .step-connector {
  background: #10B981;
}

/* Booking Container */
.booking-container {
  max-width: 1000px;
  margin: 0 auto;
  background: white;
  border-radius: 12px;
  padding: 40px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.step-content {
  min-height: 500px;
}

/* Transitions */
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease;
}

.slide-fade-enter-from {
  transform: translateX(30px);
  opacity: 0;
}

.slide-fade-leave-to {
  transform: translateX(-30px);
  opacity: 0;
}

.slide-down-enter-active {
  animation: slideDown 0.3s ease;
}

.slide-down-leave-active {
  animation: slideUp 0.3s ease;
}

@keyframes slideDown {
  from {
    transform: translateY(-100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    transform: translateY(0);
    opacity: 1;
  }
  to {
    transform: translateY(-100%);
    opacity: 0;
  }
}

/* Error Toast */
.error-toast {
  position: fixed;
  top: 20px;
  right: 20px;
  background: #FEE2E2;
  border-left: 4px solid #EF4444;
  border-radius: 6px;
  padding: 16px 20px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 1000;
  max-width: 400px;
}

.error-toast p {
  margin: 0;
  color: #991B1B;
  font-weight: 500;
  flex: 1;
}

.btn-close {
  background: none;
  border: none;
  color: #DC2626;
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0;
  transition: color 0.2s ease;
}

.btn-close:hover {
  color: #991B1B;
}

/* Responsive */
@media (max-width: 1024px) {
  .booking-container {
    padding: 24px;
  }
}

@media (max-width: 768px) {
  .slot-booking-view {
    padding: 24px 16px;
  }

  .booking-header h1 {
    font-size: 1.75rem;
  }

  .progress-steps {
    flex-direction: column;
    gap: 16px;
  }

  .step-connector {
    display: none;
  }

  .booking-container {
    padding: 16px;
  }

  .error-toast {
    left: 16px;
    right: 16px;
    max-width: none;
  }
}
</style>


