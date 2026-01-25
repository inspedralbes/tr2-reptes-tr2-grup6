<template>
  <div class="teacher-schedule">
    <div class="page-header">
      <div class="header-left">
        <h1>Agendament del Docent</h1>
        <p class="subtitle">Gestiona les teves sessions de manera visual</p>
      </div>
      <div class="header-actions">
        <button class="btn-secondary" @click="viewMode = viewMode === 'calendar' ? 'list' : 'calendar'">
          <i :class="viewMode === 'calendar' ? 'fas fa-list' : 'fas fa-calendar'"></i>
          {{ viewMode === 'calendar' ? 'Vista Llista' : 'Vista Calendari' }}
        </button>
        <router-link to="/qr-scanner" class="btn-qr">
          <i class="fas fa-qrcode"></i> Escanejar QR
        </router-link>
        <button class="btn-primary" @click="showUploadModal = true">
          <i class="fas fa-camera"></i> Pujar Foto
        </button>
      </div>
    </div>

    <!-- CALENDAR VIEW -->
    <div v-if="viewMode === 'calendar'" class="calendar-container">
      <div class="calendar-controls">
        <div class="month-nav">
          <button @click="changeMonth(-1)" class="btn-icon"><i class="fas fa-chevron-left"></i></button>
          <h2>{{ currentMonthName }} {{ currentYear }}</h2>
          <button @click="changeMonth(1)" class="btn-icon"><i class="fas fa-chevron-right"></i></button>
        </div>
        <button class="btn-today" @click="goToToday">Avui</button>
      </div>

      <div class="calendar-grid">
        <!-- Weekdays -->
        <div class="weekday" v-for="day in weekDays" :key="day">{{ day }}</div>
        
        <!-- Days -->
        <div 
          v-for="(day, index) in calendarDays" 
          :key="index"
          class="day-cell"
          :class="{ 
            'other-month': !day.isCurrentMonth, 
            'is-today': day.isToday,
            'has-events': day.events.length > 0
          }"
          @click="handleDayClick(day)"
        >
          <div class="day-number">{{ day.number }}</div>
          
          <div class="events-stack">
            <div 
              v-for="event in day.events" 
              :key="event.id" 
              class="event-chip"
              :class="event.status"
              @click.stop="openEditSession(event)"
            >
              <div class="event-time">{{ formatTime(event.datetime) }}</div>
              <div class="event-title">{{ findWorkshopName(event.allocation_id) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- LIST VIEW (Premium Design) -->
    <div v-else class="list-view-container">
      <div class="list-header-row">
         <h3>Totes les sessions</h3>
         <button class="btn-text" @click="refresh">Actualitzar <i class="fas fa-sync-alt"></i></button>
      </div>
      
      <div v-if="sortedSessions.length === 0" class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <p>No tens cap sessió programada.</p>
      </div>

      <div class="sessions-list">
        <div 
          v-for="s in sortedSessions" 
          :key="s.id" 
          class="session-card"
          @click="openEditSession(s)"
        >
          <div class="session-date-box">
             <span class="day-number">{{ new Date(s.datetime).getDate() }}</span>
             <span class="month-name">{{ new Date(s.datetime).toLocaleString('ca-ES', { month: 'short' }) }}</span>
          </div>
          
          <div class="session-info">
             <h4>{{ findWorkshopName(s.allocation_id) }}</h4>
             <div class="session-meta">
               <span><i class="far fa-clock"></i> {{ formatTime(s.datetime) }}</span>
               <span v-if="s.center_name"><i class="fas fa-map-marker-alt"></i> {{ s.center_name }}</span>
             </div>
          </div>

          <div class="session-status">
             <span class="status-badge" :class="s.status">{{ getStatusLabel(s.status) }}</span>
             <i class="fas fa-chevron-right arrow-icon"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- CREATE/EDIT MODAL -->
    <div v-if="showSessionModal" class="modal-overlay" @click.self="closeSessionModal">
      <div class="modal-card">
        <div class="modal-header">
          <h3>{{ editingSession ? 'Detall de la Sessió' : 'Nova Sessió' }}</h3>
          <button @click="closeSessionModal" class="btn-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
             <label>Taller</label>
             <input type="text" disabled :value="findWorkshopName(form.allocation_id)" class="disabled-input">
          </div>

          <div class="grid-2">
            <div class="form-group">
              <label>Data</label>
              <input type="date" v-model="form.date" disabled class="disabled-input">
            </div>
            <div class="form-group">
              <label>Hora d'inici</label>
              <input type="time" v-model="form.time" disabled class="disabled-input">
            </div>
          </div>

          <!-- Assignment Status -->
          <div class="assignment-status" v-if="editingSession">
             <div v-if="isAssignedToMe" class="status-box my-session">
                <i class="fas fa-check-circle"></i> Aquest taller t'està assignat.
             </div>
             <div v-else-if="isAssignedToOther" class="status-box other-session">
                <i class="fas fa-lock"></i> Assignat a: <strong>{{ editingSession.user_name }}</strong>
             </div>
             <div v-else class="status-box free-session">
                <i class="fas fa-exclamation-circle"></i> Taller disponible.
             </div>
          </div>

        </div>
        <div class="modal-footer">
          <button @click="closeSessionModal" class="btn-text">Tancar</button>
          
          <!-- Action Buttons -->
          <button 
            v-if="!isAssignedToMe" 
            @click="assignToMe" 
            class="btn-primary" 
            :disabled="scheduleStore.loading"
          >
            {{ scheduleStore.loading ? 'Assignant...' : (isAssignedToOther ? 'Reassignar-me al taller' : 'Assignar-me al taller') }}
          </button>
          
          <button 
             v-if="isAssignedToMe"
             class="btn-primary" 
             disabled
          >
             Ja assignat
          </button>
        </div>
      </div>
    </div>

    <UploadPhotoModal v-if="showUploadModal" @close="showUploadModal = false" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useScheduleStore } from '@/stores/schedule'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import UploadPhotoModal from '../components/UploadPhotoModal.vue'

const scheduleStore = useScheduleStore()
const authStore = useAuthStore()
const toast = useToast()

const viewMode = ref('calendar')
const showUploadModal = ref(false)
const showSessionModal = ref(false)
const editingSession = ref(null)

const allocations = ref([])
const currentDate = ref(new Date())
const weekDays = ['Dil', 'Dim', 'Dmc', 'Dij', 'Div', 'Dis', 'Diu']

const form = reactive({
  allocation_id: '',
  date: '',
  time: '',
  status: 'active'
})

// Helper Computed
const isAssignedToMe = computed(() => {
    return editingSession.value?.assigned_teacher_id == authStore.user?.id
})
const isAssignedToOther = computed(() => {
    return editingSession.value?.assigned_teacher_id && !isAssignedToMe.value
})

// ... (Calendar Logic stays same)
const currentMonthName = computed(() => {
  return currentDate.value.toLocaleString('ca-ES', { month: 'long' }).replace(/^\w/, c => c.toUpperCase())
})
const currentYear = computed(() => currentDate.value.getFullYear())

const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  
  const firstDayOfMonth = new Date(year, month, 1)
  const lastDayOfMonth = new Date(year, month + 1, 0)
  
  // Adjust for Monday start (0=Sun, 1=Mon... we want Mon=0)
  let startDay = firstDayOfMonth.getDay() - 1
  if (startDay === -1) startDay = 6 // Sunday becomes 6
  
  const days = []
  
  // Previous month padding
  const prevMonthLastDay = new Date(year, month, 0).getDate()
  for (let i = startDay - 1; i >= 0; i--) {
    days.push({
      number: prevMonthLastDay - i,
      isCurrentMonth: false,
      date: new Date(year, month - 1, prevMonthLastDay - i),
      events: []
    })
  }
  
  // Current month days
  for (let i = 1; i <= lastDayOfMonth.getDate(); i++) {
    const d = new Date(year, month, i)
    days.push({
      number: i,
      isCurrentMonth: true,
      isToday: isSameDay(d, new Date()),
      date: d,
      events: getEventsForDate(d)
    })
  }
  
  // Next month padding
  const remainingCells = 42 - days.length // 6 rows of 7
  for (let i = 1; i <= remainingCells; i++) {
    days.push({
      number: i,
      isCurrentMonth: false,
      date: new Date(year, month + 1, i),
      events: []
    })
  }
  
  return days
})

function isSameDay(d1, d2) {
  return d1 && d2 && d1.toDateString() === d2.toDateString()
}

// Computed Events Source (Allocations with dates)
const allEvents = computed(() => {
    return allocations.value
        .filter(a => a.slot_date && a.slot_time)
        .map(a => ({
            id: a.id,
            allocation_id: a.id,
            datetime: `${a.slot_date}T${a.slot_time}`, // Combine date & time
            status: a.status,
            workshop_name: a.workshop_name,
            assigned_teacher_id: a.assigned_teacher_id, // Fix for reassignment
            user_name: a.user_name,
            center_name: a.center_name // Ensure center name is available
        }))
})

// Use computed events instead of store sessions if possible, or merge
function getEventsForDate(date) {
  const dateStr = toLocalDateString(date); // YYYY-MM-DD
  return allEvents.value.filter(s => s.datetime.startsWith(dateStr))
}

function changeMonth(delta) {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + delta, 1)
}

function goToToday() {
  currentDate.value = new Date()
}

function toLocalDateString(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function handleDayClick(day) {
    // Disable create from empty cell for now if logic is allocation-based
     toast.info('Selecciona un taller existent per editar-lo.');
}

function openEditSession(session) {
  editingSession.value = session
  const d = new Date(session.datetime)
  form.date = toLocalDateString(d)
  form.time = d.toTimeString().slice(0, 5)
  form.allocation_id = session.allocation_id
  form.status = session.status || 'active'
  
  showSessionModal.value = true
}

function closeSessionModal() {
  showSessionModal.value = false
  editingSession.value = null
}

async function assignToMe() {
    if (!editingSession.value) return;
    
    // Debug log in console
    console.log('Sending reassign request for ID:', editingSession.value.id, 'User:', authStore.user.id);

    const client = authStore.getApiClient();
    try {
        const res = await client.put(`/api/allocations/${editingSession.value.id}`, {
            assigned_teacher_id: Number(authStore.user.id), // Ensure INT
            status: 'active' 
        });
        
        if (res.data?.success) {
            toast.success('Taller assignat correctament!');
            closeSessionModal();
            loadAllocations();
        } else {
            console.error('Assign response fail:', res.data);
            toast.error('Error assignant taller.');
        }
    } catch (e) {
        console.error('Assign request fail:', e);
        toast.error('Error de connexió.');
    }
}

// Removed saveSession in favor of assignToMe specific action


// Data loading
const loadAllocations = async () => {
    try {
        const client = authStore.getApiClient()
        const params = {};
        // Watcher logic ensures we have user data, but safeguard
        if (authStore.user?.center_id) params.assigned_center_id = authStore.user.center_id;
        
        // Fetch allocations 
        const res = await client.get('/api/allocations', { params })
        if (res.data?.success) allocations.value = res.data.data || []
    } catch(e) { console.error(e) }
}

const allocationOptions = computed(() => {
    return allocations.value.map(a => ({ 
        value: a.id, 
        label: `${a.workshop_name} (${a.center_name})` 
    }))
})

const findAllocation = (id) => allocations.value.find(a => a.id === id)
const findWorkshopName = (id) => findAllocation(id)?.workshop_name || `Workshop #${id}`

const formatTime = (iso) => {
    return new Date(iso).toLocaleTimeString('ca-ES', { hour: '2-digit', minute:'2-digit' })
}

const formatDateTime = (iso) => new Date(iso).toLocaleString('ca-ES')

// FIXED STATUS LOGIC
const getStatusLabel = (s) => {
    if (s === 'active' || s === 'scheduled') return 'Programada';
    if (s === 'completed') return 'Completada';
    if (s === 'canceled' || s === 'cancelled') return 'Cancel·lada';
    return 'Programada'; // Default fallback
}

const sortedSessions = computed(() => {
    return [...allEvents.value].sort((a,b) => new Date(b.datetime) - new Date(a.datetime))
})

// Reactivity to auth user loading
const init = async () => {
    if (authStore.user?.id) { await loadAllocations() }
}

watch(() => authStore.user, async (newUser) => {
    if (newUser?.id) { await loadAllocations() }
}, { immediate: true })

onMounted(() => {
    // Watcher handles initial load if user is ready, or waits for it.
    // Init call just in case (e.g. if user is null but later becomes null again? unlikely)
})

</script>

<style scoped>
/* Keeping existing styles and adding new ones */
.teacher-schedule {
  padding: 2rem;
  font-family: 'Inter', sans-serif;
  max-width: 1400px;
  margin: 0 auto;
}

/* Header */
.page-header {
  display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;
}
.header-left h1 {
  font-size: 2rem; font-weight: 800; color: #0F172A; margin: 0;
  background: linear-gradient(135deg, #C5A059 0%, #B8905F 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.subtitle { color: #64748b; margin-top: 0.5rem; }
.header-actions { display: flex; gap: 1rem; }

/* Buttons */
.btn-primary, .btn-secondary, .btn-today, .btn-icon {
    cursor: pointer; border: none; border-radius: 8px; font-weight: 600; transition: all 0.2s;
}
.btn-primary { background: #C5A059; color: white; padding: 0.6rem 1.2rem; }
.btn-primary:hover { background: #b08d4d; }
.btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }

.btn-secondary { background: white; color: #64748b; border: 1px solid #e2e8f0; padding: 0.6rem 1.2rem; display: flex; gap: 0.5rem; align-items: center; }
.btn-secondary:hover { background: #f8fafc; color: #0F172A; }

.btn-icon { background: transparent; color: #64748b; padding: 0.5rem; font-size: 1.1rem; }
.btn-icon:hover { color: #0F172A; background: #f1f5f9; }
.btn-today { background: #f1f5f9; color: #0F172A; padding: 0.4rem 1rem; font-size: 0.9rem; }

/* Calendar Main */
.calendar-container {
    background: white; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    overflow: hidden; border: 1px solid #e2e8f0;
}
.calendar-controls {
    display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 1px solid #e2e8f0;
}
.month-nav { display: flex; align-items: center; gap: 1rem; }
.month-nav h2 { margin: 0; font-size: 1.5rem; color: #0F172A; min-width: 200px; text-align: center; }

.calendar-grid {
    display: grid; grid-template-columns: repeat(7, 1fr); background: white;
    border-top: 1px solid #e2e8f0; border-left: 1px solid #e2e8f0;
}
.weekday {
    background: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.8rem;
    padding: 1rem; text-align: center; letter-spacing: 0.05em;
    border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;
}
.day-cell {
    background: white; min-height: 120px; padding: 0.5rem; position: relative; cursor: pointer;
    transition: background 0.2s; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;
    display: flex; flex-direction: column;
}
.day-cell:hover { background: #fcfcfc; }
.other-month { background: #fcfcfc; color: #94a3b8; }
.is-today { background: #fffbeb; }
.day-number { font-weight: 600; margin-bottom: 0.5rem; color: #334155; }
.is-today .day-number { color: #C5A059; font-weight: 800; }

.events-stack { display: flex; flex-direction: column; gap: 0.25rem; }
.event-chip {
    padding: 0.25rem 0.5rem; background: #e0f2fe; color: #0369a1; border-left: 3px solid #0ea5e9;
    font-size: 0.75rem; border-radius: 4px; cursor: pointer; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;
}
.event-chip:hover { filter: brightness(0.95); }
.event-chip.completed { background: #dcfce7; color: #15803d; border-color: #22c55e; }
.event-chip.canceled { background: #fee2e2; color: #b91c1c; border-color: #ef4444; }

.event-time { font-weight: 700; display: inline-block; margin-right: 0.3rem; }
.event-title { display: inline; }

/* Premium List View styles */
.list-view-container {
    max-width: 800px; margin: 0 auto;
}
.list-header-row {
    display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;
}
.sessions-list {
    display: flex; flex-direction: column; gap: 1rem;
}
.session-card {
    background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid #e2e8f0;
    display: flex; align-items: center; gap: 1.5rem; transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}
.session-card:hover {
    transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(0,0,0,0.05); border-color: #C5A059;
}
.session-date-box {
    background: #f8fafc; padding: 0.8rem 1.2rem; border-radius: 12px;
    display: flex; flex-direction: column; align-items: center; min-width: 80px;
    border: 1px solid #e2e8f0;
}
.session-date-box .day-number { font-size: 1.8rem; font-weight: 800; color: #0F172A; line-height: 1; }
.session-date-box .month-name { text-transform: uppercase; font-size: 0.75rem; font-weight: 600; color: #C5A059; margin-top: 0.2rem; }

.session-info { flex: 1; }
.session-info h4 { margin: 0 0 0.5rem 0; font-size: 1.1rem; color: #0F172A; }
.session-meta { display: flex; gap: 1rem; font-size: 0.9rem; color: #64748b; }
.session-meta i { color: #94a3b8; }

.session-status { display: flex; align-items: center; gap: 1rem; }
.arrow-icon { color: #cbd5e1; }

.empty-state {
    text-align: center; padding: 3rem; color: #94a3b8;
}
.empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }

/* Status Badges */
.status-badge { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.status-badge.active, .status-badge.scheduled { background: #e0f2fe; color: #0369a1; }
.status-badge.completed { background: #dcfce7; color: #166534; }
.status-badge.canceled, .status-badge.cancelled { background: #fee2e2; color: #991b1b; }

/* Modal */
.modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100;
    display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);
}
.modal-card {
    background: white; width: 100%; max-width: 500px; border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); animation: slideUp 0.3s ease;
}
@keyframes slideUp { from {opacity: 0; transform: translateY(20px);} to {opacity: 1; transform: translateY(0);} }
.modal-header { padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { margin: 0; color: #0F172A; }
.btn-close { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b; }

.modal-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.modal-footer { padding: 1.5rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 1rem; }
.btn-text { background: transparent; border: none; color: #64748b; font-weight: 500; cursor: pointer; }

.form-group label { display: block; font-weight: 500; color: #334155; margin-bottom: 0.4rem; font-size: 0.9rem; }
input, select {
    width: 100%; padding: 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; transition: border-color 0.2s;
}
input:focus, select:focus { outline: none; border-color: #C5A059; ring: 2px solid rgba(197, 160, 89, 0.1); }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.disabled-input { background: #f1f5f9; color: #64748b; }

.assignment-status { margin-top: 1rem; }
.status-box { padding: 1rem; border-radius: 8px; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; }
.my-session { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
.other-session { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
.free-session { background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; }

@media (max-width: 768px) {
    .calendar-grid { grid-template-columns: 1fr; gap: 0.5rem; background: transparent; }
    .day-cell { min-height: auto; border: 1px solid #e2e8f0; border-radius: 8px; }
    .weekday { display: none; }
}

.btn-qr {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1.2rem;
  background: #C5A059;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s;
}
.btn-qr:hover {
  background: #b08d4d;
  transform: translateY(-1px);
}

</style>
