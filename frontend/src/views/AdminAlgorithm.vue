<template>
  <div class="admin-container">
    <div class="admin-header">
      <h1><i class="fas fa-microchip"></i> Algoritme d'Assignació</h1>
      <p class="subtitle">Assigna automàticament tallers als docents segons prioritats intel·ligents</p>
    </div>

    <div class="grid">
      <!-- Panel de Control -->
      <div class="card">
        <h3>Configuració</h3>
        <div class="form">
          <div class="form-group">
            <label>Estratègia d'assignació</label>
            <select v-model="selectedStrategy">
              <option value="balanced">Equilibrat (balanceig de càrrega)</option>
              <option value="priority">Prioritat (historial)</option>
              <option value="proximity">Proximitat (ubicació)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Sol·licituds pendents</label>
            <div class="stat-box">
              <span class="stat-number">{{ pendingRequests }}</span>
              <span class="stat-label">sol·licituds</span>
            </div>
          </div>
          <div class="form-group">
            <label>Docents disponibles</label>
            <div class="stat-box">
              <span class="stat-number">{{ availableTeachers }}</span>
              <span class="stat-label">docents</span>
            </div>
          </div>
        </div>
        <button 
          @click="executeAlgorithm" 
          :disabled="loading || pendingRequests === 0"
          class="btn-primary btn-large"
        >
          <i v-if="loading" class="fas fa-spinner fa-spin"></i>
          <i v-else class="fas fa-play"></i>
          <span style="margin-left:0.5rem;">{{ loading ? 'Executant...' : 'Executar Assignació' }}</span>
        </button>
        <p class="hint">L'algoritme assignarà tallers basant-se en disponibilitat i historial.</p>
      </div>

      <!-- Resultats -->
      <div class="card">
        <h3>Resultats</h3>
        
        <div v-if="!executionResult" class="empty-state">
          <div class="empty-icon"><i class="fas fa-chart-bar"></i></div>
          <p>Executa l'algoritme per veure resultats</p>
        </div>

        <div v-else class="results">
          <div class="result-summary">
            <div class="summary-item success">
              <span class="icon"><i class="fas fa-check-circle"></i></span>
              <div>
                <strong>{{ executionResult.assigned }}</strong>
                <p>Assignades</p>
              </div>
            </div>
            <div class="summary-item warning">
              <span class="icon"><i class="fas fa-exclamation-triangle"></i></span>
              <div>
                <strong>{{ executionResult.unassigned }}</strong>
                <p>No assignades</p>
              </div>
            </div>
            <div class="summary-item info">
              <span class="icon"><i class="fas fa-chart-line"></i></span>
              <div>
                <strong>{{ executionResult.successRate }}%</strong>
                <p>Èxit</p>
              </div>
            </div>
            <div class="summary-item info">
              <span class="icon"><i class="fas fa-clock"></i></span>
              <div>
                <strong>{{ executionTime }}</strong>
                <p>Temps</p>
              </div>
            </div>
          </div>

          <!-- Estadístiques avançades -->
          <div v-if="executionResult.stats" class="section">
            <h4>Estadístiques Detallades</h4>
            <div class="stats-grid">
              <div class="stat-card">
                <span class="stat-label">Sol·licituds processades</span>
                <span class="stat-value">{{ executionResult.processed }}</span>
              </div>
              <div class="stat-card">
                <span class="stat-label">Taxa d'èxit</span>
                <span class="stat-value">{{ executionResult.successRate }}%</span>
              </div>
              <div class="stat-card">
                <span class="stat-label">Estratègia usada</span>
                <span class="stat-value">{{ executionResult.strategy_used }}</span>
              </div>
              <div class="stat-card">
                <span class="stat-label">Temps execució</span>
                <span class="stat-value">{{ executionResult.execution_time_ms }}ms</span>
              </div>
            </div>

            <!-- Raons de no assignació -->
            <div v-if="executionResult.stats.byReason && Object.keys(executionResult.stats.byReason).length > 0" class="reasons-breakdown">
              <h5>Raons de No Assignació</h5>
              <div class="reason-items">
                <div v-for="(count, reason) in executionResult.stats.byReason" :key="reason" class="reason-item">
                  <span class="reason-label">{{ reason }}</span>
                  <span class="reason-count">{{ count }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Taula d'assignacions exitoses -->
          <div class="section" v-if="executionResult.allocations && executionResult.allocations.length > 0">
            <h4>Assignacions Exitoses ({{ executionResult.allocations.length }})</h4>
            <table class="data-table">
              <thead>
                <tr>
                  <th>Taller</th>
                  <th>Docent</th>
                  <th>Centre</th>
                  <th>Dia i Hora</th>
                  <th>Puntuació</th>
                  <th>Estratègia</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="alloc in executionResult.allocations" :key="alloc.id">
                  <td class="workshop">{{ alloc.workshop_name }}</td>
                  <td class="teacher">{{ alloc.instructor_name }}</td>
                  <td class="center">{{ getCenterName(alloc.assigned_center_id) }}</td>
                  <td class="date">
                    <div v-if="alloc.slot">
                        <i class="fas fa-calendar-alt"></i> {{ formatSlot(alloc.slot) }}
                    </div>
                    <span v-else class="warning-text">Pendent d'horari</span>
                  </td>
                  <td class="score">
                    <span class="score-badge" :class="{ 'score-high': alloc.score >= 80, 'score-medium': alloc.score >= 60, 'score-low': alloc.score < 60 }">
                      {{ alloc.score ? alloc.score.toFixed(1) : '—' }}
                    </span>
                  </td>
                  <td class="strategy">{{ alloc.strategy_used || executionResult.strategy_used }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Taula de no assignades -->
          <div class="section" v-if="executionResult.unassignedReasons && executionResult.unassignedReasons.length > 0">
            <h4>Sol·licituds No Assignades ({{ executionResult.unassignedReasons.length }})</h4>
            <table class="data-table">
              <thead>
                <tr>
                  <th>Usuari</th>
                  <th>Taller</th>
                  <th>Raó</th>
                  <th>Severitat</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="unassigned in executionResult.unassignedReasons" :key="unassigned.request_id" class="row-warning">
                  <td class="user">{{ unassigned.user_name || '?' }}</td>
                  <td class="workshop">{{ unassigned.workshop_name || 'N/A' }}</td>
                  <td class="reason">{{ unassigned.reason }}</td>
                  <td class="severity">
                    <span class="severity-badge" :class="{ 'sev-error': unassigned.severity === 'error', 'sev-warning': unassigned.severity === 'warning' }">
                      {{ unassigned.severity || 'info' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="actions">
            <button @click="exportResults" class="btn-secondary"><i class="fas fa-download"></i> Descarregar Resultats</button>
            <button @click="resetResults" class="btn-secondary"><i class="fas fa-rotate-right"></i> Nova Execució</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Historial d'execucions -->
    <div class="card">
      <h3>Historial d'Execucions</h3>
      <table v-if="executionHistory.length > 0" class="data-table">
        <thead>
          <tr>
            <th>Data</th>
            <th>Estratègia</th>
            <th>Assignades</th>
            <th>No Assignades</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="exec in executionHistory" :key="exec.id">
            <td>{{ formatDate(exec.timestamp) }}</td>
            <td>{{ exec.strategy }}</td>
            <td class="success">{{ exec.assigned }}</td>
            <td class="warning">{{ exec.unassigned }}</td>
          </tr>
        </tbody>
      </table>
      <div v-else class="empty-state">
        <p>Sense historial d'execucions</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const adminStore = useAdminStore()
const authStore = useAuthStore()
const toast = useToast()

const selectedStrategy = ref('balanced')
const loading = ref(false)
const executionResult = ref(null)
const executionTime = ref('—')
const executionHistory = ref([])

const pendingRequests = computed(() => {
  return adminStore.requests.filter(r => r.status === 'pending').length
})

const availableTeachers = computed(() => {
  return adminStore.teachers?.length || 0
})

const executeAlgorithm = async () => {
  loading.value = true
  const startTime = Date.now()
  
  try {
    const client = authStore.getApiClient()
    const res = await client.post('/api/allocations/execute', {
      strategy: selectedStrategy.value,
      period_id: 1 // Default active period
    })

    if (res.data?.success) {
      executionResult.value = res.data.data
      const elapsed = ((Date.now() - startTime) / 1000).toFixed(2)
      executionTime.value = `${elapsed}s`

      // Afegir al historial
      executionHistory.value.unshift({
        id: Date.now(),
        timestamp: new Date().toISOString(),
        strategy: selectedStrategy.value,
        assigned: res.data.data.assigned,
        unassigned: res.data.data.unassigned
      })

      toast.success(`Assignació completada: ${res.data.data.assigned} exitoses`)
    } else {
      toast.error(res.data?.message || 'Error executant l\'algoritme')
    }
  } catch (err) {
    toast.error('Error de connexió amb l\'algoritme')
    console.error(err)
  } finally {
    loading.value = false
  }
}

const resetResults = () => {
  executionResult.value = null
  executionTime.value = '—'
}

const exportResults = () => {
  if (!executionResult.value) return
  const json = JSON.stringify(executionResult.value, null, 2)
  const blob = new Blob([json], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `assignacio_${new Date().toISOString().split('T')[0]}.json`
  a.click()
  URL.revokeObjectURL(url)
}

const getCenterName = (centerId) => {
  const center = adminStore.centers?.find(c => c.id === centerId)
  return center?.name || `Center #${centerId}`
}

const formatDate = (iso) => new Date(iso).toLocaleString('ca-ES')

const formatSlot = (slot) => {
    if (!slot || !slot.start) return '—'
    const date = new Date(slot.start)
    const end = new Date(slot.end)
    const options = { weekday: 'short', day: 'numeric', month: 'numeric' }
    const timeOpts = { hour: '2-digit', minute: '2-digit' }
    return `${date.toLocaleDateString('ca-ES', options)} ${date.toLocaleTimeString('ca-ES', timeOpts)}-${end.toLocaleTimeString('ca-ES', timeOpts)}`
}

onMounted(async () => {
  await Promise.all([
    adminStore.fetchAdminData(),
    adminStore.fetchRequests?.() || Promise.resolve()
  ])
})
</script>

<style scoped>
.admin-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2.5rem 3rem 3rem;
}

.admin-header {
  margin-bottom: 2.25rem;
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
  margin-top: 0.5rem;
  font-size: 1rem;
}

.grid {
  display: grid;
  grid-template-columns: 1fr 1.5fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}

.card h3 {
  margin: 0 0 1rem;
  color: #0F172A;
}

.card h4 {
  margin: 0.75rem 0 0.5rem;
  color: #1e293b;
  font-size: 0.95rem;
}

.form {
  display: grid;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.form-group {
  display: grid;
  gap: 0.35rem;
}

.form-group label {
  font-weight: 600;
  color: #0F172A;
  font-size: 0.9rem;
}

select {
  padding: 0.6rem 0.8rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font: inherit;
}

.stat-box {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 8px;
}

.stat-number {
  font-size: 1.75rem;
  font-weight: 700;
  color: #C5A059;
}

.stat-label {
  color: #64748b;
  font-size: 0.85rem;
}

.btn-primary {
  background: #C5A059;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  width: 100%;
}

.btn-primary:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
}

.btn-primary:not(:disabled):hover {
  background: #d6af66; /* Un tono un poco más claro u oscuro del gold */
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(197, 160, 89, 0.4);
}

.btn-large {
  padding: 1rem 1.5rem;
  font-size: 1rem;
}

.btn-secondary {
  background: #0F172A;
  color: white;
  border: none;
  padding: 0.6rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}

.hint {
  color: #64748b;
  font-size: 0.85rem;
  margin-top: 0.5rem;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: #64748b;
}

.empty-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  color: #C5A059;
}

.results {
  display: grid;
  gap: 1.5rem;
}

.result-summary {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 1rem;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: 8px;
}

.summary-item.success {
  background: #f0fdf4;
  border-left: 4px solid #10b981;
}

.summary-item.warning {
  background: #fffbeb;
  border-left: 4px solid #f59e0b;
}

.summary-item.info {
  background: #eff6ff;
  border-left: 4px solid #3b82f6;
}

.summary-item .icon {
  font-size: 1.5rem;
}

.summary-item strong {
  display: block;
  font-size: 1.5rem;
  color: #0F172A;
}

.summary-item p {
  margin: 0;
  color: #64748b;
  font-size: 0.85rem;
}

.section {
  display: grid;
  gap: 0.75rem;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.data-table th {
  background: #f1f5f9;
  padding: 0.75rem;
  text-align: left;
  font-weight: 600;
  color: #0F172A;
  border-bottom: 2px solid #e2e8f0;
}

.data-table td {
  padding: 0.75rem;
  border-bottom: 1px solid #e2e8f0;
}

.score-badge {
  display: inline-block;
  padding: 0.35rem 0.7rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.85rem;
}

.score-high {
  background: #d1fae5;
  color: #065f46;
}

.score-medium {
  background: #fef3c7;
  color: #92400e;
}

.score-low {
  background: #fee2e2;
  color: #991b1b;
}

.severity-badge {
  display: inline-block;
  padding: 0.35rem 0.7rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
}

.sev-error {
  background: #fee2e2;
  color: #991b1b;
}

.sev-warning {
  background: #fef3c7;
  color: #92400e;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stat-label {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
}

.stat-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0F172A;
}

.reasons-breakdown {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
  border-left: 3px solid #f59e0b;
}

.reasons-breakdown h5 {
  margin: 0 0 0.75rem;
  color: #0F172A;
  font-size: 0.95rem;
}

.reason-items {
  display: grid;
  gap: 0.5rem;
}

.reason-item {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0.75rem;
  background: white;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.reason-label {
  font-size: 0.85rem;
  color: #0F172A;
}

.reason-count {
  font-weight: 700;
  color: #f59e0b;
  background: #fffbeb;
  padding: 0.2rem 0.6rem;
  border-radius: 4px;
  font-size: 0.85rem;
}

.data-table .workshop {
  font-weight: 600;
  color: #0F172A;
}

.data-table .teacher {
  color: #475569;
}

.data-table .center {
  color: #64748b;
  font-size: 0.85rem;
}

.data-table .date {
  color: #64748b;
  font-size: 0.85rem;
}

.data-table .id {
  color: #0F172A;
  font-weight: 600;
}

.data-table .reason {
  color: #ef4444;
}

.row-warning {
  background: #fef2f2;
}

.success {
  color: #10b981;
  font-weight: 600;
}

.warning {
  color: #f59e0b;
  font-weight: 600;
}

.actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1rem;
}

.actions button {
  flex: 1;
}
</style>
