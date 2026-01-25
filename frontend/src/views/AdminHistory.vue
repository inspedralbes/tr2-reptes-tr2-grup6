<template>
  <div class="admin-container">
    <!-- Header -->
    <div class="admin-header">
      <h1><i class="fas fa-history"></i> Historial i Prioritat</h1>
      <p class="subtitle">Gestió de l'historial de participació i càlcul de prioritats</p>
    </div>

    <div class="header-controls">
      <select v-model="selectedYear" class="year-selector">
        <option value="">Tots els anys</option>
        <option v-for="year in academicYears" :key="year" :value="year">
          {{ year }}
        </option>
      </select>
      <button @click="recalculateAllPriorities" class="btn-primary" :disabled="calculating">
        <i v-if="calculating" class="fas fa-spinner fa-spin"></i>
        <i v-else class="fas fa-rotate-right"></i>
        <span style="margin-left:0.5rem;">{{ calculating ? 'Calculant...' : 'Recalcular Prioritats' }}</span>
      </button>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button 
        @click="activeTab = 'ranking'" 
        :class="{ active: activeTab === 'ranking' }"
        class="tab-btn"
      >
        <i class="fas fa-trophy"></i> Ranking de Centres
      </button>
      <button 
        @click="activeTab = 'history'" 
        :class="{ active: activeTab === 'history' }"
        class="tab-btn"
      >
        <i class="fas fa-list"></i> Historial Participació
      </button>
      <button 
        @click="activeTab = 'stats'" 
        :class="{ active: activeTab === 'stats' }"
        class="tab-btn"
      >
        <i class="fas fa-chart-line"></i> Estadístiques
      </button>
    </div>

    <!-- Ranking Tab -->
    <div v-if="activeTab === 'ranking'" class="tab-content">
      <div class="ranking-grid">
        <div 
          v-for="(item, index) in ranking" 
          :key="item.id"
          class="ranking-card"
          :class="'rank-' + (index + 1)"
        >
          <!-- Medal -->
          <div class="rank-medal">
            <span v-if="index === 0" class="medal gold"><i class="fas fa-medal"></i></span>
            <span v-else-if="index === 1" class="medal silver"><i class="fas fa-medal"></i></span>
            <span v-else-if="index === 2" class="medal bronze"><i class="fas fa-medal"></i></span>
            <span v-else class="rank-number">#{{ index + 1 }}</span>
          </div>

          <!-- Center Info -->
          <div class="center-info">
            <h3>{{ item.center_name }}</h3>
            <p class="center-code">{{ item.center_code }}</p>
          </div>

          <!-- Priority Score -->
          <div class="priority-display">
            <div class="score-circle" :style="{ background: getScoreColor(item.priority_score) }">
              <span class="score-value">{{ item.priority_score.toFixed(1) }}</span>
              <span class="score-label">punts</span>
            </div>
          </div>

          <!-- Metrics -->
          <div class="metrics-grid">
            <div class="metric">
              <div class="metric-icon"><i class="fas fa-file-alt"></i></div>
              <div class="metric-value">{{ item.total_requests }}</div>
              <div class="metric-label">Sol·licituds</div>
            </div>
            <div class="metric">
              <div class="metric-icon"><i class="fas fa-check"></i></div>
              <div class="metric-value">{{ item.total_assignments }}</div>
              <div class="metric-label">Assignacions</div>
            </div>
            <div class="metric">
              <div class="metric-icon"><i class="fas fa-bullseye"></i></div>
              <div class="metric-value">{{ item.total_participations }}</div>
              <div class="metric-label">Participacions</div>
            </div>
            <div class="metric">
              <div class="metric-icon"><i class="fas fa-users"></i></div>
              <div class="metric-value">{{ item.avg_attendance_rate.toFixed(1) }}%</div>
              <div class="metric-label">Assistència</div>
            </div>
            <div class="metric">
              <div class="metric-icon">⭐</div>
              <div class="metric-value">{{ item.avg_satisfaction.toFixed(2) }}</div>
              <div class="metric-label">Satisfacció</div>
            </div>
          </div>

          <!-- Bonuses and Penalties -->
          <div class="modifiers">
            <div v-if="item.bonus_attendance > 0" class="bonus">
              +{{ item.bonus_attendance }} (Assistència)
            </div>
            <div v-if="item.bonus_satisfaction > 0" class="bonus">
              +{{ item.bonus_satisfaction }} (Satisfacció)
            </div>
            <div v-if="item.penalty_low_participation > 0" class="penalty">
              -{{ item.penalty_low_participation }} (Baixa participació)
            </div>
          </div>

          <!-- Actions -->
          <div class="card-actions">
            <button @click="viewCenterDetails(item.center_id)" class="btn-view">
              📊 Veure Detall
            </button>
            <button @click="recalculatePriority(item.center_id)" class="btn-recalc">
              🔄 Recalcular
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- History Tab -->
    <div v-if="activeTab === 'history'" class="tab-content">
      <!-- Filters -->
      <div class="filters-bar">
        <div class="filter-group">
          <label>Centre:</label>
          <select v-model="filterCenter" class="filter-select">
            <option value="">Tots els centres</option>
            <option v-for="center in centers" :key="center.id" :value="center.id">
              {{ center.name }}
            </option>
          </select>
        </div>
        <div class="filter-group">
          <label>Any Acadèmic:</label>
          <select v-model="filterYear" class="filter-select">
            <option value="">Tots els anys</option>
            <option v-for="year in academicYears" :key="year" :value="year">
              {{ year }}
            </option>
          </select>
        </div>
      </div>

      <!-- History Table -->
      <div class="table-container">
        <table class="history-table">
          <thead>
            <tr>
              <th>Centre</th>
              <th>Taller</th>
              <th>Any</th>
              <th>Sol·licitat</th>
              <th>Assignat</th>
              <th>Participat</th>
              <th>Assistència</th>
              <th>Satisfacció</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in filteredHistory" :key="record.id">
              <td>{{ record.center_name }}</td>
              <td>{{ record.workshop_name }}</td>
              <td>{{ record.academic_year }}</td>
              <td>
                <span v-if="record.requested" class="badge success"><i class="fas fa-check"></i> Sí</span>
                <span v-else class="badge secondary"><i class="fas fa-minus"></i> No</span>
              </td>
              <td>
                <span v-if="record.assigned" class="badge success"><i class="fas fa-check"></i> Sí</span>
                <span v-else class="badge secondary"><i class="fas fa-minus"></i> No</span>
              </td>
              <td>
                <span v-if="record.participated" class="badge success"><i class="fas fa-check"></i> Sí</span>
                <span v-else class="badge warning"><i class="fas fa-times"></i> No</span>
              </td>
              <td>
                <span v-if="record.attendance_rate !== null" class="attendance-badge" :class="getAttendanceClass(record.attendance_rate)">
                  {{ record.attendance_rate.toFixed(1) }}%
                </span>
                <span v-else class="badge secondary">-</span>
              </td>
              <td>
                <span v-if="record.satisfaction_score !== null" class="rating-stars">
                  <i v-for="n in Math.round(record.satisfaction_score)" :key="n" class="fas fa-star"></i>
                  {{ record.satisfaction_score.toFixed(1) }}
                </span>
                <span v-else class="badge secondary">-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Stats Tab -->
    <div v-if="activeTab === 'stats'" class="tab-content">
      <div class="stats-grid">
        <!-- Overall Stats -->
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-university"></i></div>
          <div class="stat-value">{{ centers.length }}</div>
          <div class="stat-label">Centres Totals</div>
        </div>

        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
          <div class="stat-value">{{ participationHistory.length }}</div>
          <div class="stat-label">Registres Històrics</div>
        </div>

        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
          <div class="stat-value">{{ participationHistory.filter(h => h.participated).length }}</div>
          <div class="stat-label">Participacions Reals</div>
        </div>

        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-users"></i></div>
          <div class="stat-value">{{ calculateAvgAttendance().toFixed(1) }}%</div>
          <div class="stat-label">Assistència Mitjana</div>
        </div>

        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-star"></i></div>
          <div class="stat-value">{{ calculateAvgSatisfaction().toFixed(2) }}</div>
          <div class="stat-label">Satisfacció Mitjana</div>
        </div>

        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
          <div class="stat-value">{{ calculateParticipationRate().toFixed(1) }}%</div>
          <div class="stat-label">Taxa Participació</div>
        </div>
      </div>

      <!-- Charts Placeholder -->
      <div class="charts-section">
        <div class="chart-card">
          <h3>📊 Distribució de Prioritats</h3>
          <div class="priority-bars">
            <div v-for="item in ranking" :key="item.id" class="priority-bar">
              <div class="bar-label">{{ item.center_name }}</div>
              <div class="bar-container">
                <div 
                  class="bar-fill" 
                  :style="{ width: item.priority_score + '%', background: getScoreColor(item.priority_score) }"
                ></div>
              </div>
              <div class="bar-value">{{ item.priority_score.toFixed(1) }}</div>
            </div>
          </div>
        </div>

        <div class="chart-card">
          <h3>📈 Evolució Participació per Any</h3>
          <div class="year-evolution">
            <div v-for="year in academicYears" :key="year" class="year-stat">
              <div class="year-label">{{ year }}</div>
              <div class="year-count">{{ getParticipationsByYear(year) }} participacions</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <div v-if="showDetailModal" class="modal-overlay" @click.self="showDetailModal = false">
      <div class="modal-content detail-modal">
        <div class="modal-header">
          <h2>📊 Detall del Centre</h2>
          <button @click="showDetailModal = false" class="btn-close">✕</button>
        </div>
        <div class="modal-body">
          <div v-if="selectedCenterDetail" class="center-detail">
            <h3>{{ selectedCenterDetail.center_name }}</h3>
            <div class="detail-stats">
              <div class="detail-stat">
                <strong>Prioritat Actual:</strong>
                <span class="priority-badge" :style="{ background: getScoreColor(selectedCenterDetail.priority_score) }">
                  {{ selectedCenterDetail.priority_score.toFixed(1) }} punts
                </span>
              </div>
              <div class="detail-stat">
                <strong>Sol·licituds Totals:</strong> {{ selectedCenterDetail.total_requests }}
              </div>
              <div class="detail-stat">
                <strong>Assignacions:</strong> {{ selectedCenterDetail.total_assignments }}
              </div>
              <div class="detail-stat">
                <strong>Participacions:</strong> {{ selectedCenterDetail.total_participations }}
              </div>
              <div class="detail-stat">
                <strong>Assistència Mitjana:</strong> {{ selectedCenterDetail.avg_attendance_rate.toFixed(1) }}%
              </div>
              <div class="detail-stat">
                <strong>Satisfacció Mitjana:</strong> {{ selectedCenterDetail.avg_satisfaction.toFixed(2) }} ⭐
              </div>
            </div>

            <h4>Modifiers de Prioritat:</h4>
            <div class="modifiers-detail">
              <div class="modifier bonus">
                <strong>+{{ selectedCenterDetail.bonus_attendance }}</strong> Bonus Assistència
              </div>
              <div class="modifier bonus">
                <strong>+{{ selectedCenterDetail.bonus_satisfaction }}</strong> Bonus Satisfacció
              </div>
              <div class="modifier penalty">
                <strong>-{{ selectedCenterDetail.penalty_low_participation }}</strong> Penalització Participació
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const authStore = useAuthStore()
const toast = useToast()

// State
const activeTab = ref('ranking')
const selectedYear = ref('2025-2026')
const filterCenter = ref('')
const filterYear = ref('')
const calculating = ref(false)
const showDetailModal = ref(false)
const selectedCenterDetail = ref(null)

const ranking = ref([])
const participationHistory = ref([])
const centers = ref([])

const academicYears = ['2023-2024', '2024-2025', '2025-2026']

// Computed
const filteredHistory = computed(() => {
  let result = [...participationHistory.value]
  
  if (filterCenter.value) {
    result = result.filter(h => h.center_id === parseInt(filterCenter.value))
  }
  
  if (filterYear.value) {
    result = result.filter(h => h.academic_year === filterYear.value)
  }
  
  return result
})

// Methods
const fetchRanking = async () => {
  try {
    const client = authStore.getApiClient()
    const response = await client.get('/api/priority/ranking', {
      params: { academic_year: selectedYear.value }
    })
    
    if (response.data.success) {
      ranking.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching ranking:', error)
    toast.error('Error carregant el ranking')
  }
}

const fetchHistory = async () => {
  try {
    const client = authStore.getApiClient()
    const response = await client.get('/api/history/participation')
    
    if (response.data.success) {
      participationHistory.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching history:', error)
    toast.error('Error carregant l\'historial')
  }
}

const fetchCenters = async () => {
  try {
    const client = authStore.getApiClient()
    const response = await client.get('/api/centers')
    
    if (response.data.success) {
      centers.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching centers:', error)
  }
}

const recalculateAllPriorities = async () => {
  calculating.value = true
  
  try {
    const client = authStore.getApiClient()
    
    for (const center of centers.value) {
      await client.post('/api/priority/calculate', {
        center_id: center.id,
        academic_year: selectedYear.value
      })
    }
    
    toast.success('Prioritats recalculades correctament!')
    await fetchRanking()
  } catch (error) {
    console.error('Error recalculating priorities:', error)
    toast.error('Error recalculant prioritats')
  } finally {
    calculating.value = false
  }
}

const recalculatePriority = async (centerId) => {
  try {
    const client = authStore.getApiClient()
    await client.post('/api/priority/calculate', {
      center_id: centerId,
      academic_year: selectedYear.value
    })
    
    toast.success('Prioritat recalculada!')
    await fetchRanking()
  } catch (error) {
    console.error('Error recalculating priority:', error)
    toast.error('Error recalculant prioritat')
  }
}

const viewCenterDetails = (centerId) => {
  selectedCenterDetail.value = ranking.value.find(r => r.center_id === centerId)
  showDetailModal.value = true
}

const getScoreColor = (score) => {
  if (score >= 80) return 'linear-gradient(135deg, #10b981 0%, #059669 100%)'
  if (score >= 60) return 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)'
  if (score >= 40) return 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'
  return 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'
}

const getAttendanceClass = (rate) => {
  if (rate >= 90) return 'excellent'
  if (rate >= 75) return 'good'
  if (rate >= 60) return 'average'
  return 'poor'
}

const calculateAvgAttendance = () => {
  const participated = participationHistory.value.filter(h => h.participated && h.attendance_rate !== null)
  if (participated.length === 0) return 0
  return participated.reduce((sum, h) => sum + h.attendance_rate, 0) / participated.length
}

const calculateAvgSatisfaction = () => {
  const rated = participationHistory.value.filter(h => h.satisfaction_score !== null)
  if (rated.length === 0) return 0
  return rated.reduce((sum, h) => sum + h.satisfaction_score, 0) / rated.length
}

const calculateParticipationRate = () => {
  const assigned = participationHistory.value.filter(h => h.assigned).length
  const participated = participationHistory.value.filter(h => h.participated).length
  if (assigned === 0) return 0
  return (participated / assigned) * 100
}

const getParticipationsByYear = (year) => {
  return participationHistory.value.filter(h => h.academic_year === year && h.participated).length
}

// Lifecycle
onMounted(async () => {
  await fetchCenters()
  await fetchRanking()
  await fetchHistory()
})
</script>

<style scoped>
.admin-container {
  max-width: 100%;
  margin: 0;
  padding: 2.5rem 3rem 3rem;
  min-height: 100vh;
  background: #F8FAFC;
}

.admin-header {
  margin-bottom: 2rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.admin-header h1 {
  font-size: 2.2rem;
  color: #0F172A;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.admin-header h1 i {
  font-size: 2.3rem;
  color: #C5A059;
}

.subtitle {
  color: #64748b;
  margin: 0;
  font-size: 1rem;
}

.header-controls {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.year-selector {
  padding: 0.75rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  font-size: 1rem;
}

.btn-primary {
  background: #C5A059;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary:hover:not(:disabled) {
  background: #b08d47;
  transform: translateY(-2px);
}

.btn-primary:active:not(:disabled) {
  background: #9a7a3a;
  transform: translateY(0);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 2rem;
  border-bottom: 2px solid #e2e8f0;
}

.tab-btn {
  background: none;
  border: none;
  padding: 1rem 2rem;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 600;
  color: #64748b;
  border-bottom: 3px solid transparent;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.tab-btn.active {
  color: #C5A059;
  border-bottom-color: #C5A059;
}

.tab-content {
  animation: fadeIn 0.3s;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Ranking Grid */
.ranking-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 2rem;
  width: 100%;
}

.ranking-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 2px 12px rgba(0,0,0,0.08);
  position: relative;
  overflow: hidden;
}

.ranking-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #C5A059 0%, #b08d47 100%);
}

.ranking-card.rank-1::before {
  background: linear-gradient(90deg, #FFD700 0%, #FFA500 100%);
}

.ranking-card.rank-2::before {
  background: linear-gradient(90deg, #C0C0C0 0%, #A8A8A8 100%);
}

.ranking-card.rank-3::before {
  background: linear-gradient(90deg, #CD7F32 0%, #B87333 100%);
}

.rank-medal {
  position: absolute;
  top: 1rem;
  right: 1rem;
}

.medal {
  font-size: 2rem;
  color: #C5A059;
}

.rank-number {
  background: #e2e8f0;
  color: #0F172A;
  font-weight: 700;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 1.2rem;
}

.center-info {
  margin-bottom: 1.5rem;
}

.center-info h3 {
  margin: 0 0 0.5rem;
  color: #0F172A;
  font-size: 1.5rem;
}

.center-code {
  color: #64748b;
  margin: 0;
  font-size: 0.95rem;
}

.priority-display {
  display: flex;
  justify-content: center;
  margin: 1.5rem 0;
}

.score-circle {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.score-value {
  font-size: 2rem;
  font-weight: 700;
}

.score-label {
  font-size: 0.85rem;
  opacity: 0.9;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin: 1.5rem 0;
}

.metric {
  text-align: center;
}

.metric-icon {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
  color: #C5A059;
}

.metric-value {
  font-size: 1.3rem;
  font-weight: 700;
  color: #0F172A;
}

.metric-label {
  font-size: 0.85rem;
  color: #64748b;
}

.modifiers {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin: 1rem 0;
}

.bonus {
  background: #dcfce7;
  color: #166534;
  padding: 0.4rem 0.8rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
}

.penalty {
  background: #fee2e2;
  color: #991b1b;
  padding: 0.4rem 0.8rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
}

.card-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.btn-view, .btn-recalc {
  flex: 1;
  padding: 0.75rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-view {
  background: #C5A059;
  color: white;
}

.btn-view:hover {
  background: #b08d47;
  transform: translateY(-2px);
}

.btn-view:active {
  transform: translateY(0);
}

.btn-recalc {
  background: #f3f4f6;
  color: #0F172A;
}

.btn-recalc:hover {
  background: #e5e7eb;
  transform: translateY(-2px);
}

.btn-recalc:active {
  transform: translateY(0);
}

/* History Table */
.filters-bar {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  display: flex;
  gap: 2rem;
  margin-bottom: 2rem;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.filter-select {
  padding: 0.5rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  min-width: 200px;
}

.table-container {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0,0,0,0.08);
  margin-bottom: 2rem;
}

.history-table {
  width: 100%;
  border-collapse: collapse;
}

.history-table th {
  background: #1e293b;
  color: #ffffff;
  font-weight: 600;
  text-align: left;
  padding: 1rem 0.75rem;
  border-bottom: 2px solid #C5A059;
}

.history-table td {
  padding: 1rem 0.75rem;
  border-bottom: 1px solid #f1f5f9;
}

.badge {
  padding: 0.3rem 0.7rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.badge.success {
  background: #dcfce7;
  color: #166534;
}

.badge.warning {
  background: #fef3c7;
  color: #92400e;
}

.badge.secondary {
  background: #f1f5f9;
  color: #64748b;
}

.attendance-badge {
  padding: 0.3rem 0.7rem;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.85rem;
}

.attendance-badge.excellent {
  background: #dcfce7;
  color: #166534;
}

.attendance-badge.good {
  background: #dbeafe;
  color: #1e40af;
}

.attendance-badge.average {
  background: #fef3c7;
  color: #92400e;
}

.attendance-badge.poor {
  background: #fee2e2;
  color: #991b1b;
}

.rating-stars {
  color: #f59e0b;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  justify-content: center;
}

.rating-stars i {
  color: #fbbf24;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
  margin-bottom: 2rem;
  width: 100%;
}

@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 2.5rem 2rem;
  text-align: center;
  box-shadow: 0 2px 12px rgba(0,0,0,0.08);
  border-top: 4px solid #C5A059;
  transition: all 0.3s ease;
}

.stat-card:hover {
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  transform: translateY(-4px);
}

.stat-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  color: #C5A059;
}

.stat-value {
  font-size: 3rem;
  font-weight: 700;
  color: #0F172A;
  display: block;
  margin-bottom: 0.5rem;
}

.stat-label {
  font-size: 1rem;
  color: #64748b;
  font-weight: 600;
}

.stat-label {
  color: #64748b;
  font-size: 0.95rem;
}

/* Charts */
.charts-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 2rem;
}

.chart-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.chart-card h3 {
  margin: 0 0 1.5rem;
  color: #0F172A;
}

.priority-bars {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.priority-bar {
  display: grid;
  grid-template-columns: 150px 1fr 60px;
  align-items: center;
  gap: 1rem;
}

.bar-label {
  font-weight: 600;
  color: #0F172A;
  font-size: 0.9rem;
}

.bar-container {
  height: 30px;
  background: #f1f5f9;
  border-radius: 15px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  transition: width 0.5s ease;
  border-radius: 15px;
}

.bar-value {
  text-align: right;
  font-weight: 600;
  color: #0F172A;
}

.year-evolution {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.year-stat {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.year-label {
  font-weight: 600;
  color: #0F172A;
}

.year-count {
  color: #64748b;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 16px;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modal-header {
  padding: 2rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  color: #0F172A;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #94a3b8;
}

.modal-body {
  padding: 2rem;
}

.center-detail h3 {
  margin: 0 0 1.5rem;
  color: #0F172A;
}

.detail-stats {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 2rem;
}

.detail-stat {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 8px;
}

.priority-badge {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  color: white;
  font-weight: 700;
}

.modifiers-detail {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.modifier {
  padding: 1rem;
  border-radius: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

@media (max-width: 768px) {
  .ranking-grid {
    grid-template-columns: 1fr;
  }

  .charts-section {
    grid-template-columns: 1fr;
  }
}
</style>
