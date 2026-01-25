<template>
  <div class="admin-container">
    <div class="admin-header">
      <h1><i class="fas fa-chart-bar"></i> Estadístiques de l'Algoritme</h1>
      <p class="subtitle">Anàlisi del rendiment i històric d'execucions</p>
    </div>

    <div class="grid-2">
      <!-- Resum global -->
      <div class="card">
        <h3>Resum Global</h3>
        <div class="summary-cards">
          <div class="summary-card success">
            <span class="label">Assignacions totals</span>
            <span class="value">{{ globalStats.totalAssigned }}</span>
          </div>
          <div class="summary-card info">
            <span class="label">Sol·licituds processades</span>
            <span class="value">{{ globalStats.totalProcessed }}</span>
          </div>
          <div class="summary-card warning">
            <span class="label">No assignades</span>
            <span class="value">{{ globalStats.totalFailed }}</span>
          </div>
          <div class="summary-card primary">
            <span class="label">Taxa d'èxit global</span>
            <span class="value">{{ globalStats.successRate }}%</span>
          </div>
        </div>

        <div class="metric-section">
          <h4>Estratègia més efectiva</h4>
          <p class="best-strategy">
            <strong>{{ globalStats.bestStrategy }}</strong>
          </p>
        </div>
      </div>

      <!-- Comparativa d'estratègies -->
      <div class="card">
        <h3>Comparativa d'Estratègies</h3>
        <div v-if="strategyComparison.length > 0" class="strategy-bars">
          <div v-for="strategy in strategyComparison" :key="strategy.name" class="strategy-item">
            <div class="strategy-header">
              <span class="name">{{ strategy.name }}</span>
              <span class="rate" :class="getRateClass(parseFloat(strategy.successRate))">
                {{ strategy.successRate }}%
              </span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: strategy.successRate + '%' }"></div>
            </div>
            <div class="strategy-details">
              <span><i class="fas fa-check-circle"></i> {{ strategy.assigned }} assignades</span>
              <span><i class="fas fa-exclamation-triangle"></i> {{ strategy.failed }} no assignades</span>
              <span><i class="fas fa-clock"></i> {{ strategy.avgTimeMs }}ms</span>
            </div>
          </div>
        </div>
        <div v-else class="empty-state">
          <p>Sense dades de comparació</p>
        </div>
      </div>
    </div>

    <!-- Historial d'execucions recent -->
    <div class="card">
      <h3>Últimes 10 Execucions</h3>
      <div v-if="recentExecutions.length > 0" class="execution-table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Data i Hora</th>
              <th>Estratègia</th>
              <th>Processades</th>
              <th>Assignades</th>
              <th>Taxa d'Èxit</th>
              <th>Temps (ms)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="exec in recentExecutions" :key="exec.id" :class="{ 'row-success': exec.successRate >= 80, 'row-warning': exec.successRate < 80 }">
              <td class="id">#{{ exec.id }}</td>
              <td class="date">{{ formatDate(exec.timestamp) }}</td>
              <td class="strategy">
                <span class="badge" :class="'badge-' + exec.strategy">{{ exec.strategy }}</span>
              </td>
              <td class="number">{{ exec.processed }}</td>
              <td class="number success">{{ exec.assigned }}</td>
              <td class="number">
                <span class="rate-badge" :class="getRateClass(exec.successRate)">
                  {{ exec.successRate.toFixed(1) }}%
                </span>
              </td>
              <td class="number">{{ exec.executionTime }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <p>Sense historial d'execucions</p>
      </div>
    </div>

    <!-- Gràfica de tendència -->
    <div class="card">
      <h3>Tendència de Taxa d'Èxit</h3>
      <div v-if="executionHistory.length > 0" class="trend-chart">
        <div class="chart-info">
          <p>Taxa d'èxit en les últimes {{ Math.min(executionHistory.length, 30) }} execucions</p>
        </div>
        <div class="mini-chart">
          <div 
            v-for="(exec, idx) in executionHistory.slice(-30)" 
            :key="idx"
            class="chart-bar"
            :style="{ height: (exec.successRate / 100) * 100 + '%' }"
            :title="`Exec #${exec.id}: ${exec.successRate.toFixed(1)}%`"
            :class="{ 'bar-high': exec.successRate >= 80, 'bar-low': exec.successRate < 80 }"
          ></div>
        </div>
        <div class="chart-labels">
          <span class="label-min">0%</span>
          <span class="label-max">100%</span>
        </div>
      </div>
      <div v-else class="empty-state">
        <p>Sense dades de tendència</p>
      </div>
    </div>

    <!-- Controls de refresh -->
    <div class="card actions">
      <button @click="refreshData" :disabled="loading" class="btn-primary">
        <i v-if="loading" class="fas fa-spinner fa-spin"></i>
        <i v-else class="fas fa-rotate-right"></i>
        <span style="margin-left:0.5rem;">{{ loading ? 'Actualitzant...' : 'Actualitzar Dades' }}</span>
      </button>
      <button @click="exportAnalytics" class="btn-secondary"><i class="fas fa-download"></i> Descarregar Informe</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const authStore = useAuthStore()
const toast = useToast()

const loading = ref(false)
const recentExecutions = ref([])
const executionHistory = ref([])
const strategyComparison = ref([])

const globalStats = computed(() => ({
  totalAssigned: executionHistory.value.reduce((sum, e) => sum + e.assigned, 0),
  totalProcessed: executionHistory.value.reduce((sum, e) => sum + e.processed, 0),
  totalFailed: executionHistory.value.reduce((sum, e) => sum + e.failed, 0),
  successRate: executionHistory.value.length > 0
    ? ((executionHistory.value.reduce((sum, e) => sum + e.assigned, 0) / executionHistory.value.reduce((sum, e) => sum + e.processed, 0)) * 100).toFixed(1)
    : 0,
  bestStrategy: strategyComparison.value.length > 0 ? strategyComparison.value[0].name : '—'
}))

const getRateClass = (rate) => {
  if (rate >= 80) return 'rate-high'
  if (rate >= 60) return 'rate-medium'
  return 'rate-low'
}

const formatDate = (iso) => new Date(iso).toLocaleString('ca-ES')

const refreshData = async () => {
  loading.value = true
  try {
    const client = authStore.getApiClient()
    
    // Fetch recent executions
    const res1 = await client.get('/api/allocations/analytics')
    if (res1.data?.success) {
      recentExecutions.value = res1.data.data.recentExecutions
    }

    // Fetch full history
    const res2 = await client.get('/api/allocations/execution-history?limit=50')
    if (res2.data?.success) {
      executionHistory.value = res2.data.data.executions
    }

    // Fetch strategy comparison
    const res3 = await client.get('/api/allocations/strategy-comparison')
    if (res3.data?.success) {
      strategyComparison.value = res3.data.data.strategies
    }

    toast.success('Dades actualitzades correctament')
  } catch (err) {
    toast.error('Error actualitzant les dades')
    console.error(err)
  } finally {
    loading.value = false
  }
}

const exportAnalytics = () => {
  const data = {
    exportDate: new Date().toISOString(),
    globalStats: globalStats.value,
    recentExecutions: recentExecutions.value,
    strategyComparison: strategyComparison.value,
    fullHistory: executionHistory.value
  }
  
  const json = JSON.stringify(data, null, 2)
  const blob = new Blob([json], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `algoritme-estadistiques-${new Date().toISOString().split('T')[0]}.json`
  a.click()
  URL.revokeObjectURL(url)
}

onMounted(() => {
  refreshData()
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
  grid-template-columns: 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
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

.summary-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.summary-card {
  padding: 1rem;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.summary-card.success {
  background: #f0fdf4;
  border-left: 4px solid #10b981;
}

.summary-card.info {
  background: #eff6ff;
  border-left: 4px solid #3b82f6;
}

.summary-card.warning {
  background: #fffbeb;
  border-left: 4px solid #f59e0b;
}

.summary-card.primary {
  background: #f3f0ff;
  border-left: 4px solid #8b5cf6;
}

.summary-card .label {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
}

.summary-card .value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0F172A;
}

.metric-section {
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.best-strategy {
  margin: 0.75rem 0 0;
  color: #0F172A;
  font-size: 1.1rem;
}

.strategy-bars {
  display: grid;
  gap: 1.25rem;
}

.strategy-item {
  display: grid;
  gap: 0.5rem;
}

.strategy-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.strategy-header .name {
  font-weight: 600;
  color: #0F172A;
  text-transform: capitalize;
}

.strategy-header .rate {
  font-weight: 700;
  font-size: 0.9rem;
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
}

.rate {
  &.rate-high {
    background: #d1fae5;
    color: #065f46;
  }
  &.rate-medium {
    background: #fef3c7;
    color: #92400e;
  }
  &.rate-low {
    background: #fee2e2;
    color: #991b1b;
  }
}

.progress-bar {
  height: 8px;
  background: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(to right, #C5A059, #b08d47);
  transition: width 0.3s ease;
}

.strategy-details {
  display: flex;
  gap: 1rem;
  font-size: 0.85rem;
  color: #64748b;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: #64748b;
}

.execution-table-container {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.data-table th {
  background: #1e293b;
  padding: 1rem 0.75rem;
  text-align: left;
  font-weight: 600;
  color: #ffffff;
  border-bottom: 2px solid #C5A059;
}

.data-table td {
  padding: 1rem 0.75rem;
  border-bottom: 1px solid #e2e8f0;
}

.data-table .id {
  font-family: monospace;
  color: #64748b;
}

.data-table .number {
  text-align: right;
  font-weight: 600;
}

.data-table .number.success {
  color: #10b981;
}

.data-table .row-success {
  background: #f0fdf4;
}

.data-table .row-warning {
  background: #fffbeb;
}

.badge {
  display: inline-block;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.badge-balanced {
  background: #dbeafe;
  color: #1e40af;
}

.badge-priority {
  background: #ddd6fe;
  color: #4338ca;
}

.badge-proximity {
  background: #fce7f3;
  color: #831843;
}

.rate-badge {
  display: inline-block;
  padding: 0.35rem 0.7rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.8rem;
}

.rate-badge.rate-high {
  background: #d1fae5;
  color: #065f46;
}

.rate-badge.rate-medium {
  background: #fef3c7;
  color: #92400e;
}

.rate-badge.rate-low {
  background: #fee2e2;
  color: #991b1b;
}

.trend-chart {
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
}

.chart-info {
  margin-bottom: 1rem;
}

.chart-info p {
  margin: 0;
  color: #64748b;
  font-size: 0.9rem;
}

.mini-chart {
  display: flex;
  align-items: flex-end;
  justify-content: space-around;
  height: 200px;
  gap: 2px;
  margin-bottom: 0.75rem;
  padding: 0 0.5rem;
}

.chart-bar {
  flex: 1;
  background: #3b82f6;
  border-radius: 3px 3px 0 0;
  min-height: 2px;
  transition: background 0.2s;
  cursor: pointer;
}

.chart-bar.bar-high {
  background: #10b981;
}

.chart-bar.bar-low {
  background: #f59e0b;
}

.chart-bar:hover {
  opacity: 0.8;
}

.chart-labels {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: #64748b;
}

.actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn-primary, .btn-secondary {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  flex: 1;
  min-width: 200px;
}

.btn-primary {
  background: #C5A059;
  color: white;
  transition: background 0.2s ease, transform 0.1s ease;
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
  background: #cbd5e1;
  cursor: not-allowed;
}

.btn-secondary {
  background: #0F172A;
  color: white;
  transition: background 0.2s ease, transform 0.1s ease;
}

.btn-secondary:hover {
  background: #1e293b;
  transform: translateY(-2px);
}

.btn-secondary:active {
  background: #0F172A;
  transform: translateY(0);
}

@media (max-width: 1024px) {
  .grid-2 {
    grid-template-columns: 1fr;
  }

  .summary-cards {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .summary-cards {
    grid-template-columns: 1fr;
  }

  .actions {
    flex-direction: column;
  }

  .mini-chart {
    height: 100px;
  }

  .grid-2 {
    grid-template-columns: 1fr;
  }
}
</style>
