<template>
  <div class="teacher-scheduler">
    <!-- Header -->
    <div class="scheduler-header">
      <div class="header-content">
        <h1>📅 Els Meus Tallers Assignats</h1>
        <p class="subtitle">Selecciona les dates i franges horàries per als teus tallers</p>
      </div>
      
      <!-- Phase Indicator -->
      <div 
        class="phase-indicator" 
        :class="'phase-' + phaseStore.currentPhaseCode?.toLowerCase()"
      >
        <div class="phase-icon">{{ getPhaseIcon() }}</div>
        <div class="phase-text">
          <strong>{{ phaseStore.currentPhaseName || 'Carregant...' }}</strong>
          <span class="phase-hint">{{ getPhaseHint() }}</span>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Carregant tallers assignats...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="assignedWorkshops.length === 0" class="empty-state">
      <div class="empty-icon">📭</div>
      <h3>No tens tallers assignats</h3>
      <p>Quan se t'assignin tallers, apareixeran aquí per poder-los agendar.</p>
    </div>

    <!-- Workshops List -->
    <div v-else class="workshops-container">
      <div 
        v-for="workshop in assignedWorkshops" 
        :key="workshop.id"
        class="workshop-card"
      >
        <!-- Workshop Header -->
        <div class="workshop-header">
          <div class="workshop-title">
            <h3>{{ workshop.name }}</h3>
            <div class="workshop-badges">
              <span class="badge theme-badge">{{ formatTheme(workshop.theme) }}</span>
              <span class="badge course-badge">{{ formatCourse(workshop.course) }}</span>
              <span 
                v-if="workshop.scheduled_date" 
                class="badge scheduled-badge"
              >
                ✓ Agendat
              </span>
              <span v-else class="badge pending-badge">⏳ Pendent</span>
            </div>
          </div>
          <div class="workshop-meta">
            <span>⏱️ {{ workshop.duration }}</span>
            <span>👥 {{ workshop.max_capacity }} estudiants</span>
            <span v-if="workshop.center_name">🏫 {{ workshop.center_name }}</span>
          </div>
        </div>

        <!-- Workshop Info -->
        <div class="workshop-info">
          <div class="info-section">
            <strong>Descripció:</strong>
            <p>{{ workshop.description }}</p>
          </div>

          <!-- Available Days and Time Slots -->
          <div class="availability-info">
            <div class="availability-col">
              <strong>Dies permesos:</strong>
              <div class="chips">
                <span 
                  v-for="day in workshop.allowed_days" 
                  :key="day" 
                  class="chip"
                >
                  {{ day }}
                </span>
              </div>
            </div>
            <div class="availability-col">
              <strong>Franges horàries:</strong>
              <div class="slots">
                <span 
                  v-for="slot in workshop.time_slots" 
                  :key="slot" 
                  class="slot-item"
                >
                  {{ slot }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Scheduling Section -->
        <div class="scheduling-section">
          <div v-if="workshop.scheduled_date" class="scheduled-info">
            <div class="scheduled-card">
              <div class="scheduled-icon">✅</div>
              <div class="scheduled-details">
                <strong>Taller Agendat</strong>
                <p>📅 {{ formatDate(workshop.scheduled_date) }}</p>
                <p>🕐 {{ workshop.scheduled_time_slot }}</p>
              </div>
              <button 
                v-if="canSchedule"
                @click="editSchedule(workshop)" 
                class="btn-edit"
              >
                ✏️ Editar
              </button>
            </div>
          </div>

          <div v-else class="scheduling-form">
            <h4>Agendar Taller</h4>
            
            <!-- Date Picker -->
            <div class="form-group">
              <label>Selecciona una data:</label>
              <input 
                type="date"
                v-model="schedulingData[workshop.id].date"
                :min="getMinDate()"
                :max="getMaxDate()"
                class="date-input"
                :disabled="!canSchedule"
              />
              <small class="hint">Només pots seleccionar els dies permesos pel taller</small>
            </div>

            <!-- Time Slot Picker -->
            <div class="form-group">
              <label>Selecciona una franja horària:</label>
              <select 
                v-model="schedulingData[workshop.id].timeSlot"
                class="time-slot-select"
                :disabled="!canSchedule || !(schedulingData[workshop.id] && schedulingData[workshop.id].date)"
              >
                <option value="">-- Selecciona una franja --</option>
                <option 
                  v-for="slot in getAvailableSlots(workshop)" 
                  :key="slot.value"
                  :value="slot.value"
                  :disabled="slot.taken"
                >
                  {{ slot.label }} {{ slot.taken ? '(Ocupada)' : '' }}
                </option>
              </select>
            </div>

            <!-- Live Availability Indicator -->
            <div v-if="liveUpdates[workshop.id]" class="live-indicator">
              <span class="pulse-dot"></span>
              <small>{{ liveUpdates[workshop.id] }}</small>
            </div>

            <!-- Action Buttons -->
            <div class="form-actions">
              <button 
                @click="scheduleWorkshop(workshop)"
                :disabled="!canScheduleWorkshop(workshop) || scheduling"
                class="btn-primary"
              >
                {{ scheduling ? '⏳ Agendant...' : '✓ Confirmar Agenda' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Connection Status (Socket.io) -->
    <div class="connection-status" :class="{ connected: socketConnected }">
      <span class="status-dot"></span>
      <small>{{ socketConnected ? 'Connectat en temps real' : 'Desconnectat' }}</small>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { usePhaseStore } from '@/stores/phase'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import io from 'socket.io-client'

const phaseStore = usePhaseStore()
const authStore = useAuthStore()
const toast = useToast()

// State
const loading = ref(true)
const scheduling = ref(false)
const assignedWorkshops = ref([])
const schedulingData = reactive({})
const liveUpdates = reactive({})
const socketConnected = ref(false)
let socket = null

// Theme and Course formatters (same as AdminWorkshops)
const themeLabels = {
  robotica: '🤖 Robòtica',
  programacio: '💻 Programació',
  electronica: '⚡ Electrònica',
  fabricacio: '🔧 Fabricació Digital',
  disseny3d: '🎨 Disseny 3D',
  energies: '🔋 Energies Renovables',
  mecatronica: '⚙️ Mecatrònica',
  iot: '📡 Internet de les Coses',
  ia: '🧠 Intel·ligència Artificial'
}

const courseLabels = {
  eso1: '1r ESO', eso2: '2n ESO', eso3: '3r ESO', eso4: '4t ESO',
  batx1: '1r Batx', batx2: '2n Batx',
  cfgm: 'CFGM', cfgs: 'CFGS', tots: 'Tots'
}

const formatTheme = (theme) => themeLabels[theme] || theme
const formatCourse = (course) => courseLabels[course] || course

// Can schedule (only in SCHEDULING phase)
const canSchedule = computed(() => {
  return phaseStore.currentPhaseCode === 'SCHEDULING'
})

// Phase helpers
const getPhaseIcon = () => {
  const icons = {
    PUBLICATION: '📖',
    DEMAND: '🛒',
    ASSIGNMENT: '⚙️',
    SCHEDULING: '📅',
    EXECUTION: '✅'
  }
  return icons[phaseStore.currentPhaseCode] || '📋'
}

const getPhaseHint = () => {
  const hints = {
    PUBLICATION: 'Els tallers encara no estan assignats',
    DEMAND: 'Els centres estan sol·licitant tallers',
    ASSIGNMENT: 'S\'estan processant les assignacions',
    SCHEDULING: 'Pots agendar les dates dels teus tallers assignats',
    EXECUTION: 'Tallers en execució'
  }
  return hints[phaseStore.currentPhaseCode] || ''
}

// Date helpers
const getMinDate = () => {
  const today = new Date()
  return today.toISOString().split('T')[0]
}

const getMaxDate = () => {
  // Max date: 6 months from now
  const maxDate = new Date()
  maxDate.setMonth(maxDate.getMonth() + 6)
  return maxDate.toISOString().split('T')[0]
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('ca-ES', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

// Get available time slots for a workshop
const getAvailableSlots = (workshop) => {
  if (!workshop.time_slots) return []
  
  return workshop.time_slots.map(slot => ({
    value: slot,
    label: slot,
    taken: false // TODO: Check with real-time data from socket
  }))
}

// Check if workshop can be scheduled
const canScheduleWorkshop = (workshop) => {
  const data = schedulingData[workshop.id]
  return data?.date && data?.timeSlot && canSchedule.value
}

// Initialize scheduling data for a workshop
const initSchedulingData = (workshopId) => {
  if (!schedulingData[workshopId]) {
    schedulingData[workshopId] = {
      date: '',
      timeSlot: ''
    }
  }
}

// Fetch assigned workshops
const fetchAssignedWorkshops = async () => {
  loading.value = true
  try {
    const client = authStore.getApiClient()
    const response = await client.get('/api/teachers/my-assignments')
    
    if (response.data.success) {
      assignedWorkshops.value = response.data.data || []
      
      // Initialize scheduling data
      assignedWorkshops.value.forEach(workshop => {
        initSchedulingData(workshop.id)
      })
    }
  } catch (error) {
    console.error('Error fetching assignments:', error)
    toast.error('Error carregant tallers assignats')
  } finally {
    loading.value = false
  }
}

// Schedule a workshop
const scheduleWorkshop = async (workshop) => {
  const data = schedulingData[workshop.id]
  
  if (!data?.date || !data?.timeSlot) {
    toast.warning('Selecciona una data i una franja horària')
    return
  }

  scheduling.value = true

  try {
    const client = authStore.getApiClient()
    const response = await client.post(`/api/assignments/${workshop.assignment_id}/schedule`, {
      scheduled_date: data.date,
      scheduled_time_slot: data.timeSlot
    })

    if (response.data.success) {
      toast.success('Taller agendat correctament!')
      
      // Update local state
      workshop.scheduled_date = data.date
      workshop.scheduled_time_slot = data.timeSlot
      
      // Emit socket event
      if (socket && socketConnected.value) {
        socket.emit('workshop-scheduled', {
          workshop_id: workshop.id,
          date: data.date,
          time_slot: data.timeSlot
        })
      }
      
      // Clear scheduling data
      schedulingData[workshop.id] = { date: '', timeSlot: '' }
    } else {
      toast.error(response.data.message || 'Error agendant taller')
    }
  } catch (error) {
    console.error('Error scheduling workshop:', error)
    toast.error('Error agendant taller')
  } finally {
    scheduling.value = false
  }
}

// Edit existing schedule
const editSchedule = (workshop) => {
  if (!canSchedule.value) {
    toast.warning('Només pots editar l\'agenda durant la fase de calendarització')
    return
  }
  
  // Populate form with existing data
  schedulingData[workshop.id] = {
    date: workshop.scheduled_date,
    timeSlot: workshop.scheduled_time_slot
  }
  
  // Clear scheduled state to show form
  workshop.scheduled_date = null
  workshop.scheduled_time_slot = null
  
  toast.info('Pots editar la teva agenda')
}

// Socket.io setup
const setupSocket = () => {
  socket = io('http://localhost:3000', {
    auth: {
      token: authStore.token
    }
  })

  socket.on('connect', () => {
    socketConnected.value = true
    console.log('Socket.io connected')
  })

  socket.on('disconnect', () => {
    socketConnected.value = false
    console.log('Socket.io disconnected')
  })

  // Listen for real-time updates
  socket.on('slot-taken', (data) => {
    liveUpdates[data.workshop_id] = `⚠️ La franja ${data.time_slot} del ${data.date} acaba de ser reservada per un altre docent`
    
    setTimeout(() => {
      delete liveUpdates[data.workshop_id]
    }, 5000)
  })

  socket.on('slot-freed', (data) => {
    liveUpdates[data.workshop_id] = `✓ La franja ${data.time_slot} del ${data.date} està ara disponible`
    
    setTimeout(() => {
      delete liveUpdates[data.workshop_id]
    }, 5000)
  })
}

// Lifecycle
onMounted(async () => {
  await phaseStore.fetchCurrentPhase()
  await fetchAssignedWorkshops()
  setupSocket()
})

onUnmounted(() => {
  if (socket) {
    socket.disconnect()
  }
})
</script>

<style scoped>
.teacher-scheduler {
  min-height: 100vh;
  background: #F8FAFC;
  padding: 2rem;
}

.scheduler-header {
  max-width: 1200px;
  margin: 0 auto 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
  flex-wrap: wrap;
}

.header-content h1 {
  margin: 0;
  color: #0F172A;
  font-size: 2.5rem;
}

.subtitle {
  margin: 0.5rem 0 0;
  color: #64748b;
  font-size: 1.1rem;
}

.phase-indicator {
  background: white;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  border-left: 4px solid #cbd5e1;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.phase-indicator.phase-scheduling {
  border-left-color: #8b5cf6;
  background: #faf5ff;
}

.phase-icon {
  font-size: 1.5rem;
}

.phase-text {
  display: flex;
  flex-direction: column;
}

.phase-text strong {
  color: #0F172A;
  font-size: 1rem;
}

.phase-hint {
  color: #64748b;
  font-size: 0.85rem;
}

.loading-state {
  text-align: center;
  padding: 4rem;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e2e8f0;
  border-top-color: #C5A059;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  max-width: 600px;
  margin: 4rem auto;
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-state h3 {
  color: #0F172A;
  margin: 0 0 0.5rem;
}

.empty-state p {
  color: #64748b;
}

.workshops-container {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.workshop-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.08);
  overflow: hidden;
}

.workshop-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1.5rem;
}

.workshop-title {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.workshop-title h3 {
  margin: 0;
  font-size: 1.5rem;
}

.workshop-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.badge {
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
}

.theme-badge {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.course-badge {
  background: rgba(255, 255, 255, 0.3);
  color: white;
}

.scheduled-badge {
  background: #10b981;
  color: white;
}

.pending-badge {
  background: #f59e0b;
  color: white;
}

.workshop-meta {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
  font-size: 0.95rem;
  opacity: 0.95;
}

.workshop-info {
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.info-section {
  margin-bottom: 1.5rem;
}

.info-section strong {
  display: block;
  color: #0F172A;
  margin-bottom: 0.5rem;
}

.info-section p {
  color: #64748b;
  margin: 0;
  line-height: 1.6;
}

.availability-info {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.availability-col strong {
  display: block;
  color: #0F172A;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.chips {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.chip {
  background: #eef2ff;
  color: #1e3a8a;
  padding: 0.4rem 0.7rem;
  border-radius: 14px;
  font-weight: 600;
  font-size: 0.9rem;
}

.slots {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.slot-item {
  background: #ecfeff;
  color: #0f172a;
  padding: 0.4rem 0.7rem;
  border-radius: 14px;
  font-weight: 600;
  font-size: 0.9rem;
  border: 1px solid #bae6fd;
}

.scheduling-section {
  padding: 1.5rem;
}

.scheduled-info {
  margin-bottom: 1rem;
}

.scheduled-card {
  background: #f0fdf4;
  border: 2px solid #10b981;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.scheduled-icon {
  font-size: 2rem;
}

.scheduled-details {
  flex: 1;
}

.scheduled-details strong {
  display: block;
  color: #0F172A;
  margin-bottom: 0.5rem;
}

.scheduled-details p {
  margin: 0.25rem 0;
  color: #059669;
  font-weight: 600;
}

.btn-edit {
  background: white;
  border: 2px solid #10b981;
  color: #059669;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-edit:hover {
  background: #10b981;
  color: white;
}

.scheduling-form h4 {
  color: #0F172A;
  margin: 0 0 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  color: #0F172A;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.date-input,
.time-slot-select {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.date-input:focus,
.time-slot-select:focus {
  outline: none;
  border-color: #8b5cf6;
}

.date-input:disabled,
.time-slot-select:disabled {
  background: #f8fafc;
  cursor: not-allowed;
}

.hint {
  display: block;
  color: #94a3b8;
  font-size: 0.85rem;
  margin-top: 0.5rem;
}

.live-indicator {
  background: #fef3c7;
  border-left: 3px solid #f59e0b;
  padding: 0.75rem;
  border-radius: 6px;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.pulse-dot {
  width: 8px;
  height: 8px;
  background: #f59e0b;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

.form-actions {
  display: flex;
  gap: 1rem;
}

.btn-primary {
  flex: 1;
  background: #8b5cf6;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background: #7c3aed;
}

.btn-primary:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
}

.connection-status {
  position: fixed;
  bottom: 1rem;
  right: 1rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.connection-status.connected {
  background: #f0fdf4;
  border-color: #bbf7d0;
  color: #166534;
}

.status-dot {
  width: 8px;
  height: 8px;
  background: #dc2626;
  border-radius: 50%;
}

.connection-status.connected .status-dot {
  background: #16a34a;
}

@media (max-width: 768px) {
  .teacher-scheduler {
    padding: 1rem;
  }

  .scheduler-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .header-content h1 {
    font-size: 1.8rem;
  }

  .availability-info {
    grid-template-columns: 1fr;
  }

  .scheduled-card {
    flex-direction: column;
    align-items: flex-start;
  }

  .btn-edit {
    width: 100%;
  }
}
</style>
