<template>
  <div class="slot-selector">
    <!-- Header -->
    <div class="selector-header">
      <h3>⏰ Selecciona el dia i hora</h3>
      <p class="subtitle">Tria una data i hora disponible per al taller</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Carregant slots disponibles...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <p>⚠️ {{ error }}</p>
      <button @click="loadSlots" class="btn btn-sm btn-primary">
        Reintentar
      </button>
    </div>

    <!-- Main Content -->
    <div v-else class="selector-content">
      <!-- Calendar -->
      <div class="calendar-section">
        <div class="calendar-header">
          <button @click="previousMonth" class="btn-nav">←</button>
          <h4>{{ monthYear }}</h4>
          <button @click="nextMonth" class="btn-nav">→</button>
        </div>

        <div class="calendar-grid">
          <!-- Days of week header -->
          <div class="dow-header">
            <div v-for="day in daysOfWeek" :key="day" class="dow-cell">
              {{ day }}
            </div>
          </div>

          <!-- Calendar days -->
          <div v-for="day in calendarDays" :key="day.date" class="calendar-day">
            <button
              v-if="day.date"
              @click="selectDate(day.date)"
              :class="[
                'day-button',
                {
                  'today': isToday(day.date),
                  'selected': isSelectedDate(day.date),
                  'disabled': !day.hasSlots,
                  'has-slots': day.hasSlots
                }
              ]"
              :disabled="!day.hasSlots"
            >
              <span class="day-number">{{ day.number }}</span>
              <span v-if="day.hasSlots" class="slot-indicator">●</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Time Slots -->
      <div v-if="selectedDateObj" class="slots-section">
        <h4>{{ formatDateLong(selectedDateObj) }}</h4>
        <p class="slots-count">{{ availableSlots.length }} hores disponibles</p>

        <!-- Slots Grid -->
        <div class="slots-grid">
          <button
            v-for="slot in availableSlots"
            :key="slot.id"
            @click="selectSlot(slot)"
            :class="[
              'slot-button',
              {
                'selected': isSelectedSlot(slot.id),
                'almost-full': slot.occupancy > 70,
                'full': slot.occupancy === 100
              }
            ]"
            :disabled="slot.occupancy === 100"
          >
            <span class="slot-time">{{ slot.start_time.substring(0, 5) }}</span>
            <span class="slot-capacity">
              {{ slot.available_spots }}/{{ slot.capacity }}
            </span>
            <span class="slot-occupancy" :style="{ width: slot.occupancy + '%' }"></span>
          </button>
        </div>

        <!-- Empty slots message -->
        <div v-if="availableSlots.length === 0" class="no-slots">
          <p>❌ No hi ha slots disponibles per a aquesta data</p>
        </div>
      </div>

      <!-- No date selected -->
      <div v-else class="no-selection">
        <p>👈 Selecciona una data per veure els horaris disponibles</p>
      </div>
    </div>

    <!-- Selected Info -->
    <div v-if="selectedSlot" class="selection-info">
      <div class="info-box">
        <div class="info-item">
          <span class="info-label">📅 Data seleccionada:</span>
          <span class="info-value">{{ formatDateLong(selectedDateObj) }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">🕐 Hora seleccionada:</span>
          <span class="info-value">{{ selectedSlot.start_time.substring(0, 5) }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">👥 Places disponibles:</span>
          <span class="info-value">{{ selectedSlot.available_spots }}/{{ selectedSlot.capacity }}</span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <button @click="proceedToBooking" class="btn btn-primary btn-lg">
          ✅ Continuar amb la reserva
        </button>
        <button @click="clearSelection" class="btn btn-secondary btn-sm">
          🔄 Canviar selecció
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSlotStore } from '@/stores/slot'

const slotStore = useSlotStore()

const emit = defineEmits(['proceed'])

const loading = ref(true)
const error = ref(null)
const currentMonth = ref(new Date())
const selectedDateObj = ref(null)
const selectedSlot = ref(null)

const daysOfWeek = ['Dl', 'Dm', 'Dc', 'Dj', 'Dv', 'Ds', 'Dg']

// Format month and year
const monthYear = computed(() => {
  const months = [
    'Gener', 'Febrer', 'Març', 'Abril', 'Maig', 'Juny',
    'Juliol', 'Agost', 'Setembre', 'Octubre', 'Novembre', 'Desembre'
  ]
  return `${months[currentMonth.value.getMonth()]} ${currentMonth.value.getFullYear()}`
})

// Generate calendar days
const calendarDays = computed(() => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()
  
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  
  const startDate = new Date(firstDay)
  startDate.setDate(startDate.getDate() - firstDay.getDay())
  
  const days = []
  const current = new Date(startDate)
  
  while (current <= lastDay) {
    const dateStr = current.toISOString().split('T')[0]
    const slots = slotStore.getSlotsByDate(dateStr) || []
    
    days.push({
      date: dateStr,
      number: current.getDate(),
      hasSlots: slots.length > 0,
      isCurrentMonth: current.getMonth() === month
    })
    
    current.setDate(current.getDate() + 1)
  }
  
  return days
})

// Get available slots for selected date
const availableSlots = computed(() => {
  if (!selectedDateObj.value) return []
  const dateStr = selectedDateObj.value.toISOString().split('T')[0]
  const slots = slotStore.getSlotsByDate(dateStr) || []
  return slots.filter(s => s.occupancy < 100).sort((a, b) => {
    return a.start_time.localeCompare(b.start_time)
  })
})

// Helper functions
const isToday = (dateStr) => {
  const today = new Date().toISOString().split('T')[0]
  return dateStr === today
}

const isSelectedDate = (dateStr) => {
  if (!selectedDateObj.value) return false
  const selected = selectedDateObj.value.toISOString().split('T')[0]
  return dateStr === selected
}

const isSelectedSlot = (slotId) => {
  return selectedSlot.value && selectedSlot.value.id === slotId
}

const formatDateLong = (date) => {
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
}

// Navigation
const previousMonth = () => {
  currentMonth.value = new Date(
    currentMonth.value.getFullYear(),
    currentMonth.value.getMonth() - 1
  )
}

const nextMonth = () => {
  currentMonth.value = new Date(
    currentMonth.value.getFullYear(),
    currentMonth.value.getMonth() + 1
  )
}

// Selection
const selectDate = (dateStr) => {
  selectedDateObj.value = new Date(dateStr + 'T00:00:00')
  selectedSlot.value = null
}

const selectSlot = (slot) => {
  selectedSlot.value = slot
}

const clearSelection = () => {
  selectedSlot.value = null
  selectedDateObj.value = null
}

const proceedToBooking = async () => {
  if (!selectedSlot.value) return
  
  emit('proceed', {
    slotId: selectedSlot.value.id,
    date: selectedDateObj.value,
    slot: selectedSlot.value
  })
}

// Load slots
const loadSlots = async () => {
  loading.value = true
  error.value = null
  try {
    // Slots already loaded in store
    loading.value = false
  } catch (e) {
    error.value = 'Error carregant els slots. Intenta-ho més tard.'
    console.error(e)
    loading.value = false
  }
}

onMounted(() => {
  loadSlots()
})
</script>

<style scoped>
.slot-selector {
  width: 100%;
}

.selector-header {
  margin-bottom: 24px;
}

.selector-header h3 {
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

/* Loading & Error */
.loading-container,
.error-container {
  padding: 40px 20px;
  text-align: center;
  background: #F8FAFC;
  border-radius: 8px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #E2E8F0;
  border-top-color: #3B82F6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-container p,
.error-container p {
  color: #475569;
  font-weight: 500;
  margin: 0;
}

.error-container p {
  color: #DC2626;
}

/* Calendar */
.selector-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 32px;
}

.calendar-section {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.calendar-header h4 {
  margin: 0;
  font-size: 1.1rem;
  color: #0F172A;
  min-width: 150px;
  text-align: center;
}

.btn-nav {
  background: none;
  border: none;
  font-size: 1.3rem;
  color: #3B82F6;
  cursor: pointer;
  padding: 4px 8px;
  transition: color 0.2s ease;
}

.btn-nav:hover {
  color: #2563EB;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
}

.dow-header {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  margin-bottom: 8px;
}

.dow-cell {
  text-align: center;
  font-weight: 600;
  color: #94A3B8;
  font-size: 0.85rem;
  padding: 8px 0;
}

.calendar-day {
  aspect-ratio: 1;
}

.day-button {
  width: 100%;
  height: 100%;
  border: 1px solid #E2E8F0;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
  font-weight: 500;
  color: #475569;
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
}

.day-button:hover:not(:disabled) {
  border-color: #3B82F6;
  background: #F0F4F8;
}

.day-button.today {
  background: #DBEAFE;
  border-color: #3B82F6;
  color: #1E40AF;
  font-weight: 600;
}

.day-button.selected {
  background: #C5A059;
  border-color: #C5A059;
  color: white;
  font-weight: 600;
}

.day-button.disabled {
  color: #CBD5E1;
  cursor: not-allowed;
  background: #F8FAFC;
}

.day-number {
  font-size: 0.9rem;
}

.slot-indicator {
  font-size: 0.6rem;
  color: #10B981;
}

/* Time Slots */
.slots-section {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.slots-section h4 {
  margin: 0 0 8px 0;
  font-size: 1.1rem;
  color: #0F172A;
  text-transform: capitalize;
}

.slots-count {
  margin: 0 0 16px 0;
  color: #64748B;
  font-size: 0.9rem;
}

.slots-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
  gap: 8px;
}

.slot-button {
  padding: 12px 8px;
  background: white;
  border: 2px solid #E2E8F0;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.slot-button:hover:not(:disabled) {
  border-color: #C5A059;
  background: #F0F4F8;
}

.slot-button.selected {
  background: #C5A059;
  border-color: #C5A059;
  color: white;
}

.slot-button.selected .slot-time {
  color: white;
  font-weight: 600;
}

.slot-button.selected .slot-capacity {
  color: #DBEAFE;
}

.slot-button.almost-full {
  border-color: #F59E0B;
}

.slot-button.full {
  background: #F8FAFC;
  border-color: #CBD5E1;
  color: #CBD5E1;
  cursor: not-allowed;
}

.slot-time {
  font-weight: 600;
  color: #0F172A;
  font-size: 0.95rem;
}

.slot-capacity {
  font-size: 0.8rem;
  color: #64748B;
}

.slot-occupancy {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 2px;
  background: #C5A059;
  transition: width 0.3s ease;
}

.no-slots,
.no-selection {
  grid-column: span 2;
  padding: 40px 20px;
  text-align: center;
  background: #F8FAFC;
  border-radius: 8px;
  color: #64748B;
}

/* Selection Info */
.selection-info {
  margin-top: 24px;
  padding: 20px;
  background: linear-gradient(135deg, #DBEAFE 0%, #F0F4F8 100%);
  border-radius: 8px;
  border-left: 4px solid #3B82F6;
}

.info-box {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-label {
  font-size: 0.85rem;
  color: #475569;
  font-weight: 500;
}

.info-value {
  font-size: 1rem;
  color: #0F172A;
  font-weight: 600;
}

.action-buttons {
  display: flex;
  gap: 12px;
}

.btn {
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.95rem;
}

.btn-lg {
  padding: 12px 24px;
  flex: 1;
}

.btn-sm {
  padding: 8px 16px;
}

.btn-primary {
  background: #C5A059;
  color: white;
}

.btn-primary:hover {
  background: #b08d47;
}

.btn-secondary {
  background: white;
  color: #0F172A;
  border: 1px solid #E2E8F0;
}

.btn-secondary:hover {
  background: #F8FAFC;
}

/* Responsive */
@media (max-width: 1024px) {
  .selector-content {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .calendar-section,
  .slots-section {
    padding: 16px;
  }

  .slots-grid {
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  }

  .info-box {
    grid-template-columns: 1fr;
  }

  .action-buttons {
    flex-direction: column;
  }
}
</style>


