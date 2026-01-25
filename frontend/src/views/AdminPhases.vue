<template>
  <div class="phases-light-theme">
    
    <!-- Top Bar / Header Content -->
    <div class="page-header">
      <div class="header-icon">
        <i class="fas fa-layer-group"></i>
      </div>
      <div class="header-text">
        <h1>Fases Temporals</h1>
        <p>Gestió del cicle de vida del curs escolar</p>
      </div>
      
      <div class="header-actions">
        <button 
          v-if="isAdmin" 
          @click="refreshPhases" 
          class="btn-gold-solid"
          :disabled="isRefreshing"
          :class="{ 'is-loading': isRefreshing }"
        >
          <span class="icon">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': isRefreshing }"></i>
          </span> 
          {{ isRefreshing ? 'Refrescant...' : 'Refrescar Dades' }}
        </button>
      </div>
    </div>

    <div class="divider-gold"></div>

    <div class="main-content">
      
      <!-- Top Section: Active Phase Highlights -->
      <section v-if="currentPhase" class="active-phase-section">
        <div class="section-card featured-card">
          <!-- Gold Header for Active Phase -->
          <div class="card-header-gold">
            <h3><span class="icon-clock"><i class="fas fa-bolt"></i></span> FASE ACTUAL: {{ currentPhase.name }}</h3>
            <div class="countdown-badge">
              <span class="days">{{ daysRemaining }}</span> dies restants
            </div>
          </div>
          
          <div class="card-body-featured">
            <div class="phase-info-grid">
              <div class="info-col main-desc">
                <h4>Descripció</h4>
                <p>{{ currentPhase.description }}</p>
              </div>
              
              <div class="info-col dates-col">
                <h4>Període</h4>
                <div class="date-display">
                  <span class="date-value">{{ formatDate(currentPhase.startDate) }}</span>
                  <span class="arrow"><i class="fas fa-arrow-right"></i></span>
                  <span class="date-value">{{ formatDate(currentPhase.endDate) }}</span>
                </div>
              </div>
              
              <div class="info-col features-col">
                <h4>Funcions Disponibles</h4>
                <div class="features-list">
                  <span 
                    v-for="(available, feature) in currentPhase.features"
                    :key="feature"
                    class="feature-tag"
                    :class="{ active: available }"
                  >
                    <span class="check" v-if="available"><i class="fas fa-check"></i></span>
                    <span class="cross" v-else><i class="fas fa-times"></i></span>
                    {{ formatFeatureName(feature) }}
                  </span>
                </div>
              </div>
            </div>
            
            <!-- Progress Bar Visual -->
            <div class="phase-progress-container">
              <div class="progress-labels">
                <span>Progrés de la fase</span>
                <span>{{ progressPercentage }}%</span>
              </div>
              <div class="progress-track">
                <div class="progress-fill" :style="{ width: progressPercentage + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Roadmap / Timeline as a Clean List -->
      <section class="roadmap-section">
        <div class="section-title-row">
          <span class="icon-list"><i class="far fa-calendar-alt"></i></span>
          <h3>Itinerari de Fases</h3>
          <span class="count-badge">{{ allPhases.length }} Fases</span>
        </div>

        <div class="phases-table-card">
          <table class="phases-table">
            <thead>
              <tr>
                <th width="5%">ID</th>
                <th width="25%">Nom de la Fase</th>
                <th width="30%">Descripció</th>
                <th width="20%">Dates</th>
                <th width="10%">Estat</th>
                <th width="10%" class="text-right">Accions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="phase in allPhases" 
                :key="phase.id"
                :class="{ 'is-active': phase.status === 'active' }"
              >
                <td class="id-cell">0{{ phase.id }}</td>
                <td class="name-cell">
                  <strong>{{ phase.name }}</strong>
                </td>
                <td class="desc-cell">{{ phase.description }}</td>
                <td class="dates-cell">
                  <div v-if="!editingPhase[phase.id]">
                    {{ formatDate(phase.startDate) }} - {{ formatDate(phase.endDate) }}
                  </div>
                  <div v-else class="edit-dates-container">
                    <input 
                      type="text" 
                      :id="'picker-phase-' + phase.id" 
                      class="flatpickr-input-custom" 
                      placeholder="Selecciona dates..."
                      readonly
                    >
                  </div>
                </td>
                <td>
                  <span class="status-pill" :class="phase.status">
                    {{ getStatusLabel(phase.status) }}
                  </span>
                </td>
                <td class="actions-cell text-right">
                  <template v-if="isAdmin">
                    <button v-if="!editingPhase[phase.id]" @click="startEditPhase(phase)" class="btn-icon" title="Editar dates">
                      <i class="fas fa-pencil-alt"></i>
                    </button>
                    <div v-else class="edit-actions">
                      <button @click="savePhaseDates(phase)" class="btn-icon save" title="Guardar"><i class="fas fa-check"></i></button>
                      <button @click="cancelEditPhase(phase)" class="btn-icon cancel" title="Cancel·lar"><i class="fas fa-times"></i></button>
                    </div>
                  </template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useAuthStore } from '../stores/auth';
import { usePhaseStore } from '../stores/phase';
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.css";

const auth = useAuthStore();
const phaseStore = usePhaseStore();

// State
const currentPhase = ref(null);
const allPhases = ref([]);
const roadmap = ref([]);
const editingPhase = ref({});
const editingDates = ref({});
const pickerInstances = {}; // Store instances
const isRefreshing = ref(false); // Loading state for refresh button
const isAdmin = computed(() => auth.user?.role === 'admin');

const daysRemaining = computed(() => {
  if (!currentPhase.value) return 0;
  const today = new Date();
  const endDate = new Date(currentPhase.value.endDate);
  const diff = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));
  return diff > 0 ? diff : 0;
});

const progressPercentage = computed(() => {
  if (!currentPhase.value) return 0;
  const start = new Date(currentPhase.value.startDate).getTime();
  const end = new Date(currentPhase.value.endDate).getTime();
  const now = new Date().getTime();
  
  if (now < start) return 0;
  if (now > end) return 100;
  
  const total = end - start;
  const current = now - start;
  return Math.round((current / total) * 100);
});

// Logic
const calculateDuration = (dates) => {
  if (!dates || !dates.startDate || !dates.endDate) return 0;
  const start = new Date(dates.startDate);
  const end = new Date(dates.endDate);
  const diffTime = Math.abs(end - start);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
  return diffDays;
};

const startEditPhase = (phase) => {
  editingPhase.value[phase.id] = true;
  
  // Set initial dates state
  editingDates.value[phase.id] = {
    startDate: phase.startDate,
    endDate: phase.endDate
  };

  // Init Flatpickr after DOM update matches the new input
  nextTick(() => {
    const inputId = `picker-phase-${phase.id}`;
    const el = document.getElementById(inputId);
    if (el) {
        // Destroy prev instance if exists (cleanup)
        if (pickerInstances[phase.id]) pickerInstances[phase.id].destroy();

        pickerInstances[phase.id] = flatpickr(el, {
            mode: "range",
            dateFormat: "Y-m-d",
            defaultDate: [phase.startDate, phase.endDate],
            minDate: "2020-01-01",
            locale: {
                firstDayOfWeek: 1
            },
            onChange: (selectedDates, dateStr, instance) => {
                if (selectedDates.length === 2) {
                    // Update Vue state when range is full
                    const start = instance.formatDate(selectedDates[0], "Y-m-d");
                    const end = instance.formatDate(selectedDates[1], "Y-m-d");
                    editingDates.value[phase.id].startDate = start;
                    editingDates.value[phase.id].endDate = end;
                }
            }
        });
    }
  });
};

const cancelEditPhase = (phase) => {
  editingPhase.value[phase.id] = false;
  if(pickerInstances[phase.id]) {
      pickerInstances[phase.id].destroy();
      delete pickerInstances[phase.id];
  }
};

const fetchPhases = async () => {
  // 1. Fallback Data
  const dbPhases = [
    { id: 1, name: 'Fase 1: Exploració', description: 'Els centres poden explorar el catàleg de tallers.', startDate: '2025-09-01', endDate: '2025-10-15', status: 'completed', features: { marketplace_view: true } },
    { id: 2, name: 'Fase 2: Llista de Desitjos', description: 'Els centres poden crear el seu carret i enviar sol·licituds.', startDate: '2025-10-16', endDate: '2025-11-30', status: 'completed', features: { add_to_cart: true, submit_requests: true } },
    { id: 3, name: 'Fase 3: La Concordança', description: 'Els coordinadors d\'entre tallers i professors assignen...', startDate: '2025-12-01', endDate: '2026-01-15', status: 'completed', features: { view_allocations: true } },
    { id: 4, name: 'Fase 4: Calendari', description: 'Docents poden agafar els tallers assignats.', startDate: '2026-01-16', endDate: '2026-03-31', status: 'active', features: { schedule_teachers: true } },
    { id: 5, name: 'Fase 5: Execució', description: 'Execució dels tallers programats.', startDate: '2026-04-01', endDate: '2026-05-31', status: 'upcoming', features: {} },
    { id: 6, name: 'Fase 6: Avaluació', description: 'Recollida de feedback i avaluació dels tallers.', startDate: '2026-06-01', endDate: '2026-06-30', status: 'upcoming', features: { feedback: true } }
  ];

  try {
    const api = auth.getApiClient();
    const [allRes, currentRes] = await Promise.all([
        api.get('/api/phases/all'),
        api.get('/api/phases/current')
    ]);
    const all = allRes.data;
    const cur = currentRes.data;

    if (all.success && all.data.length > 0) {
        allPhases.value = all.data;
        if(cur.success) currentPhase.value = cur.data.phase;
        else currentPhase.value = all.data.find(p => p.status === 'active') || all.data[0];
    } else {
        allPhases.value = dbPhases;
        currentPhase.value = dbPhases.find(p => p.status === 'active');
    }
  } catch (e) {
      console.error("API Error fetching phases:", e);
      if(e.response) console.error("Response data:", e.response.data);
      allPhases.value = dbPhases;
      currentPhase.value = dbPhases.find(p => p.status === 'active');
  }
};

const refreshPhases = async () => {
    if (isRefreshing.value) return; // Prevent multiple simultaneous refreshes
    
    isRefreshing.value = true;
    try {
        // Re-fetch phases from the API (local component state)
        await fetchPhases();
        
        // Also refresh the global phase store (used by Marketplace)
        await phaseStore.fetchCurrentPhase();
        
        console.log('Fases refrescades correctament (local i global)');
        
        // Show success message with current phase info
        const phaseName = phaseStore.currentPhase?.name || 'Desconeguda';
        const phaseId = phaseStore.currentPhase?.id || '?';
        alert(`✅ Dades actualitzades correctament!\n\nFase actual: Fase ${phaseId}: ${phaseName}\n\nSi estàs al Marketplace, fes un hard refresh (Ctrl+Shift+R) per veure els canvis.`);
    } catch(e) { 
        console.error('Error refrescant fases:', e);
        alert('❌ Error refrescant les dades. Si us plau, torna-ho a intentar.');
    } finally {
        isRefreshing.value = false;
    }
};



const savePhaseDates = async (phase) => {
  const dates = editingDates.value[phase.id];
  const originalDates = { startDate: phase.startDate, endDate: phase.endDate };
  
  // 1. Optimistic Update
  phase.startDate = dates.startDate;
  phase.endDate = dates.endDate;
  
  try {
      // 2. Try to persist to API using Axios
      const api = auth.getApiClient();
      const res = await api.put(`/api/phases/${phase.id}/dates`, dates);
      
      if(res.data.success) {
          console.log('Fase actualitzada a la BD');
      } else {
        throw new Error(res.data.message || 'Error desconegut');
      }
  } catch(e) { 
      console.error('API Error:', e);
      alert('Error guardant a la base de dades: ' + (e.response?.data?.message || e.message || e));
      
      // Revert optimization on error
      phase.startDate = originalDates.startDate;
      phase.endDate = originalDates.endDate;
  } finally {
      cancelEditPhase(phase);
  }
};

const getStatusLabel = (status) => {
    const labels = {
        completed: 'Completada',
        active: 'ACTIVA',
        upcoming: 'Pendent'
    };
    return labels[status] || status;
};

const formatDate = (d) => new Date(d).toLocaleDateString('ca-ES', { day: '2-digit', month: 'short', year: 'numeric' });

const formatFeatureName = (f) => {
    const map = {
        marketplace_view: 'Explorar Catàleg',
        add_to_cart: 'Carret de Compra',
        submit_requests: 'Enviar Sol·licituds',
        view_allocations: 'Veure Assignacions',
        schedule_teachers: 'Agendar Docents',
        feedback: 'Sistema de Feedback'
    };
    return map[f] || f;
};

onMounted(() => {
    fetchPhases();
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap');

:root {
  --gold-primary: #C5A059;
  --gold-hover: #b08d4b;
  --bg-page: #f8f9fa;
  --text-dark: #333333;
  --text-muted: #666666;
  --white: #ffffff;
  --border-light: #e0e0e0;
}

.phases-light-theme {
  padding: 2rem 5%;
  background-color: #fcfcfc;
  min-height: 100vh;
  font-family: 'Lato', sans-serif;
  color: #333;
}

/* Header Styles */
.page-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.header-text h1 {
  font-family: 'Playfair Display', serif;
  font-size: 2.5rem;
  font-weight: 700;
  color: #333;
  margin: 0;
  line-height: 1.2;
}

.header-text p {
  color: #888;
  margin: 0.2rem 0 0;
  font-size: 1rem;
}

.header-actions {
  margin-left: auto;
}

.btn-gold-solid {
  background-color: #C5A059;
  color: white;
  border: none;
  padding: 0.8rem 1.5rem;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 6px rgba(197, 160, 89, 0.2);
  transition: all 0.2s;
}

.btn-gold-solid:hover {
  background-color: #b08d4b;
  transform: translateY(-1px);
}

.btn-gold-solid:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-gold-solid:disabled:hover {
  background-color: #C5A059;
  transform: none;
}

.divider-gold {
  height: 2px;
  background: linear-gradient(90deg, #C5A059 0%, rgba(197,160,89,0.2) 100%);
  margin-bottom: 3rem;
  width: 100%;
}

/* Featured Card (Active Phase) */
.featured-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0,0,0,0.05);
  margin-bottom: 3rem;
  border: 1px solid #eee;
}

.card-header-gold {
  background: #C5A059;
  padding: 1.5rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
}

.card-header-gold h3 {
  font-family: 'Playfair Display', serif;
  font-size: 1.5rem;
  margin: 0;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.countdown-badge {
  background: rgba(255,255,255,0.2);
  padding: 0.4rem 1rem;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 400;
}

.countdown-badge .days {
  font-weight: 700;
  font-size: 1.2rem;
}

.card-body-featured {
  padding: 2rem;
}

.phase-info-grid {
  display: grid;
  grid-template-columns: 2fr 1.5fr 1.5fr;
  gap: 3rem;
  margin-bottom: 2rem;
}

.info-col h4 {
  font-family: 'Playfair Display', serif;
  color: #C5A059;
  border-bottom: 1px solid #eee;
  padding-bottom: 0.5rem;
  margin-bottom: 1rem;
  font-size: 1.1rem;
}

.main-desc p {
  line-height: 1.6;
  color: #555;
  font-size: 1.05rem;
}

/* Date Display */
.date-display {
  display: flex;
  align-items: center;
  gap: 1rem;
  font-weight: 600;
  font-size: 1.1rem;
  color: #444;
  background: #fcfcfc;
  padding: 0.8rem;
  border-radius: 6px;
  border: 1px solid #f0f0f0;
}

.arrow { color: #C5A059; }

/* Features List */
.features-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.feature-tag {
  display: flex;
  align-items: center;
  gap: 0.8rem;
  font-size: 0.95rem;
  color: #999;
}

.feature-tag.active {
  color: #333;
  font-weight: 600;
}

.check { color: #10b981; font-weight: bold; }
.cross { color: #ccc; }

/* Progress Bar */
.phase-progress-container {
  margin-top: 1rem;
}

.progress-labels {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  color: #666;
  font-weight: 600;
}

.progress-track {
  height: 8px;
  background: #eee;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #C5A059 0%, #E6C888 100%);
  border-radius: 4px;
  transition: width 1s ease;
}

/* Roadmap List Section */
.roadmap-section {
  margin-top: 2rem;
}

.section-title-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.section-title-row h3 {
  font-family: 'Playfair Display', serif;
  font-size: 1.8rem;
  color: #333;
  margin: 0;
}

.icon-list { font-size: 1.5rem; }

.count-badge {
  background: #f0f0f0;
  color: #666;
  padding: 0.2rem 0.8rem;
  border-radius: 10px;
  font-size: 0.8rem;
  font-weight: 600;
}

/* Table Style (Like Gestionar Tallers) */
.phases-table-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.03);
  overflow: hidden;
  border: 1px solid #f0f0f0;
}

.phases-table {
  width: 100%;
  border-collapse: collapse;
}

.phases-table th {
  background: #C5A059;
  color: white;
  padding: 1rem 1.5rem;
  text-align: left;
  font-weight: 600;
  font-size: 0.95rem;
  letter-spacing: 0.5px;
}

.phases-table td {
  padding: 1.2rem 1.5rem;
  border-bottom: 1px solid #f2f2f2;
  color: #555;
  font-size: 0.95rem;
  vertical-align: middle;
}

.phases-table tr:last-child td {
  border-bottom: none;
}

.phases-table tr:hover {
  background-color: #fafafa;
}

.phases-table tr.is-active {
  background-color: rgba(197, 160, 89, 0.05);
}

.phases-table tr.is-active td {
  color: #333;
}

.id-cell { 
  color: #C5A059 !important; 
  font-weight: 700; 
}

.name-cell strong {
  font-size: 1.05rem;
  color: #333;
}

/* Status Pills */
.status-pill {
  display: inline-block;
  padding: 0.3rem 0.8rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-pill.active {
  background: #C5A059;
  color: white;
}

.status-pill.completed {
  background: #e6f7ed;
  color: #10b981;
}

.status-pill.upcoming {
  background: #fdfdfd;
  color: #ccc;
  border: 1px solid #eee;
}

/* Edit Inputs & Container */
.edit-dates-container {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.date-edit-pill {
  display: flex;
  align-items: center;
  background: #fff;
  border: 1px solid #C5A059;
  border-radius: 8px;
  padding: 4px 8px;
  box-shadow: 0 2px 8px rgba(197, 160, 89, 0.1);
  width: fit-content;
}

.date-input-clean {
  border: none;
  background: transparent;
  color: #333;
  font-family: inherit;
  font-size: 0.9rem;
  width: 105px;
  cursor: pointer;
  padding: 4px 0;
}

.date-input-clean:focus {
  outline: none;
  background: rgba(197, 160, 89, 0.05);
  border-radius: 4px;
}

.date-separator {
  color: #C5A059;
  margin: 0 8px;
  font-size: 0.8rem;
}

/* Buttons */
.btn-icon {
  background: none;
  border: 1px solid #eee;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #C5A059;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #C5A059;
  color: white;
  border-color: #C5A059;
}

.edit-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.btn-icon.save { color: #10b981; border-color: #d1fae5; }
.btn-icon.save:hover { background: #10b981; color: white; }

.btn-icon.cancel { color: #ef4444; border-color: #fee2e2; }
.btn-icon.cancel:hover { background: #ef4444; color: white; }

.text-right { text-align: right; }

/* Responsive */
@media (max-width: 900px) {
  .phase-info-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
}
</style>

<!-- Global Styles for Flatpickr (Must be outside scoped) -->
<style>
/* Flatpickr Gold Theme Overrides */
.flatpickr-calendar {
    font-family: 'Lato', sans-serif;
    border: none !important;
    box-shadow: 0 5px 25px rgba(0,0,0,0.15) !important;
}

/* Header Background */
.flatpickr-month {
    background: #C5A059 !important;
    color: white !important;
    fill: white !important;
    padding-top: 10px;
    border-top-left-radius: 5px;
    border-top-right-radius: 5px;
}

.flatpickr-current-month {
    color: white !important;
    padding-top: 0 !important;
}

/* Month Dropdown */
.flatpickr-current-month .flatpickr-monthDropdown-months {
    background: #C5A059 !important;
    color: white !important;
    font-weight: bold;
}

.flatpickr-current-month .flatpickr-monthDropdown-months:hover {
    background: #b08d4b !important;
}

/* Year Input */
.flatpickr-current-month input.cur-year {
    color: white !important;
    font-weight: bold;
}

/* Arrows */
.flatpickr-current-month .numInputWrapper span.arrowUp:after {
    border-bottom-color: white !important;
}
.flatpickr-current-month .numInputWrapper span.arrowDown:after {
    border-top-color: white !important;
}

.flatpickr-prev-month, .flatpickr-next-month {
    color: white !important;
    fill: white !important;
}

.flatpickr-prev-month:hover, .flatpickr-next-month:hover {
    color: #f0f0f0 !important;
}

/* Weekdays */
.flatpickr-weekdays {
    background: #C5A059 !important;
}

span.flatpickr-weekday {
    background: #C5A059 !important;
    color: white !important;
    font-weight: bold;
}

/* Days */
.flatpickr-day.selected, 
.flatpickr-day.startRange, 
.flatpickr-day.endRange, 
.flatpickr-day.selected.inRange, 
.flatpickr-day.startRange.inRange, 
.flatpickr-day.endRange.inRange, 
.flatpickr-day.selected:focus, 
.flatpickr-day.startRange:focus, 
.flatpickr-day.endRange:focus, 
.flatpickr-day.selected:hover, 
.flatpickr-day.startRange:hover, 
.flatpickr-day.endRange:hover, 
.flatpickr-day.selected.prevMonthDay, 
.flatpickr-day.startRange.prevMonthDay, 
.flatpickr-day.endRange.prevMonthDay, 
.flatpickr-day.selected.nextMonthDay, 
.flatpickr-day.startRange.nextMonthDay, 
.flatpickr-day.endRange.nextMonthDay {
    background: #C5A059 !important;
    border-color: #C5A059 !important;
    color: white !important;
}

.flatpickr-day.inRange {
    box-shadow: -5px 0 0 #f3e9d2, 5px 0 0 #f3e9d2 !important;
    background: #f3e9d2 !important; /* Gold light */
    border-color: #f3e9d2 !important;
    color: #333 !important;
}

.flatpickr-day.today {
    border-color: #C5A059 !important;
}

.flatpickr-day:hover {
    background: #f0f0f0 !important;
}

/* Custom Input Style (scoped ok here but global is easier for override) */
.flatpickr-input-custom {
    background: white;
    border: 1px solid #C5A059;
    padding: 8px 12px;
    border-radius: 8px;
    width: 220px;
    font-family: 'Lato', sans-serif;
    color: #333;
    cursor: pointer;
    font-size: 0.9rem;
    box-shadow: 0 2px 5px rgba(197, 160, 89, 0.1);
}

.flatpickr-input-custom:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(197, 160, 89, 0.2);
}
</style>
