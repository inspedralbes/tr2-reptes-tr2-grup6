<template>
  <div class="admin-container">
    <div class="admin-header">
      <div class="page-title">
        <div>
          <h1><i class="fas fa-sitemap"></i> Gestionar Assignacions</h1>
          <p class="subtitle">Visualitza i gestiona les assignacions dels usuaris als tallers</p>
        </div>
      </div>
      <div class="header-chip">Algoritme actiu</div>
    </div>

    <div class="filters-section">
      <div class="filter-group">
        <label for="status-filter">Estat</label>
        <select v-model="filterStatus" id="status-filter">
          <option value="">Tots</option>
          <option value="pending">Pendent</option>
          <option value="assigned">Assignat</option>
          <option value="completed">Completat</option>
          <option value="cancelled">Cancel·lat</option>
        </select>
      </div>
      <div class="filter-hint">
        <i class="fas fa-lightbulb"></i>
        Filtra per revisar ràpidament pendents i confirmar-les.
      </div>
    </div>

    <div v-if="adminStore.loading" class="loading">
      <div class="spinner"></div>
      <p>Carregant assignacions...</p>
    </div>

    <div v-else-if="adminStore.error" class="error-message">
      ⚠️ Error: {{ adminStore.error }}
    </div>

    <div v-else class="table-container">
      <table class="allocations-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Usuari</th>
            <th>Taller</th>
            <th>Slot</th>
            <th>Estat</th>
            <th>Data Assignació</th>
            <th>Accions</th>
          </tr>
        </thead>
        <tbody v-if="filteredAllocations.length > 0">
          <tr v-for="allocation in filteredAllocations" :key="allocation.id">
            <td class="id-col">{{ allocation.id }}</td>
            <td>{{ allocation.user_name }}</td>
            <td>{{ allocation.workshop_name }}</td>
            <td class="slot-col">{{ formatSlot(allocation.slot_time) }}</td>
            <td>
              <span :class="['status-badge', `status-${allocation.status}`]">
                {{ getStatusLabel(allocation.status) }}
              </span>
            </td>
            <td>{{ formatDate(allocation.allocation_date) }}</td>
            <td class="actions-col">
              <select
                :value="allocation.status"
                @change="(e) => updateAllocationStatus(allocation.id, e.target.value)"
                class="status-select"
                :disabled="processingId === allocation.id"
              >
                <option value="pending">Pendent</option>
                <option value="assigned">Assignat</option>
                <option value="completed">Completat</option>
                <option value="cancelled">Cancel·lat</option>
              </select>
              <button
                @click="viewDetails(allocation)"
                class="btn btn-secondary btn-small"
              >
                👁️ Veure
              </button>
            </td>
          </tr>
        </tbody>
        <tbody v-else>
          <tr>
            <td colspan="7" class="empty-state">
              📭 No hi ha assignacions
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="selectedAllocation" class="modal-overlay" @click="selectedAllocation = null">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Detalls de l'Assignació</h3>
          <button @click="selectedAllocation = null" class="btn-close" aria-label="Tancar">
            <i class="fas fa-xmark"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="detail-row">
            <strong>ID Assignació:</strong>
            <span>{{ selectedAllocation.id }}</span>
          </div>
          <div class="detail-row">
            <strong>Usuari:</strong>
            <span>{{ selectedAllocation.user_name }}</span>
          </div>
          <div class="detail-row">
            <strong>Email:</strong>
            <span>{{ selectedAllocation.user_email }}</span>
          </div>
          <div class="detail-row">
            <strong>Taller:</strong>
            <span>{{ selectedAllocation.workshop_name }}</span>
          </div>
          <div class="detail-row">
            <strong>Slot de Temps:</strong>
            <span>{{ formatSlot(selectedAllocation.slot_time) }}</span>
          </div>
          <div class="detail-row">
            <strong>Instructor:</strong>
            <span>{{ selectedAllocation.instructor_name }}</span>
          </div>
          <div class="detail-row">
            <strong>Estat:</strong>
            <span :class="['status-badge', `status-${selectedAllocation.status}`]">
              {{ getStatusLabel(selectedAllocation.status) }}
            </span>
          </div>
          <div class="detail-row">
            <strong>Data d'Assignació:</strong>
            <span>{{ formatDateTime(selectedAllocation.allocation_date) }}</span>
          </div>
          <div v-if="selectedAllocation.notes" class="detail-row">
            <strong>Notes:</strong>
            <span>{{ selectedAllocation.notes }}</span>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="selectedAllocation = null" class="btn btn-secondary">
            Tancar
          </button>
        </div>
      </div>
    </div>

    <div class="stats-summary">
      <div class="stat-card">
        <h4>Total</h4>
        <p class="stat-number">{{ adminStore.allocations.length }}</p>
      </div>
      <div class="stat-card">
        <h4>Pendents</h4>
        <p class="stat-number pending">{{ countByStatus('pending') }}</p>
      </div>
      <div class="stat-card">
        <h4>Assignades</h4>
        <p class="stat-number assigned">{{ countByStatus('assigned') }}</p>
      </div>
      <div class="stat-card">
        <h4>Completades</h4>
        <p class="stat-number completed">{{ countByStatus('completed') }}</p>
      </div>
      <div class="stat-card">
        <h4>Cancel·lades</h4>
        <p class="stat-number cancelled">{{ countByStatus('cancelled') }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { useToast } from '@/composables/useToast'

const adminStore = useAdminStore()
const toast = useToast()

const filterStatus = ref('')
const selectedAllocation = ref(null)
const processingId = ref(null)

const filteredAllocations = computed(() => {
  if (!filterStatus.value) {
    return adminStore.allocations
  }
  return adminStore.allocations.filter(a => a.status === filterStatus.value)
})

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Pendent',
    assigned: 'Assignat',
    completed: 'Completat',
    cancelled: 'Cancel·lat'
  }
  return labels[status] || status
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('ca-ES')
}

const formatDateTime = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('ca-ES')
}

const formatSlot = (slotTime) => {
  if (!slotTime) return '-'
  try {
    return new Date(slotTime).toLocaleString('ca-ES', {
      hour: '2-digit',
      minute: '2-digit',
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch {
    return slotTime
  }
}

const countByStatus = (status) => {
  return adminStore.allocations.filter(a => a.status === status).length
}

const viewDetails = (allocation) => {
  selectedAllocation.value = { ...allocation }
}

const updateAllocationStatus = async (allocationId, newStatus) => {
  processingId.value = allocationId
  try {
    await adminStore.updateAllocation(allocationId, { status: newStatus })
    toast.success(`Assignació actualitzada a ${getStatusLabel(newStatus)}`)
  } catch (error) {
    toast.error('Error en actualitzar l\'assignació')
    console.error(error)
  } finally {
    processingId.value = null
  }
}

onMounted(() => {
  adminStore.fetchAdminData()
})
</script>

<style scoped>
.admin-container {
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
  padding: 2.5rem 3rem 3rem;
}

.admin-header {
  margin-bottom: 2.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-bottom: 1.2rem;
  border-bottom: 3px solid #c59d32;
}

.page-title h1 {
  margin: 0;
  color: #1f2937;
  font-size: 2.2rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  display: flex;
  align-items: center;
  gap: 0.7rem;
}

.page-title h1 i { color: #c59d32; }

.subtitle {
  margin: 0.35rem 0 0;
  color: #6b7280;
  font-size: 1rem;
  font-weight: 500;
}

.header-chip {
  background: linear-gradient(135deg, #d6b14c 0%, #b88923 100%);
  color: #fff;
  padding: 0.5rem 1rem;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 700;
  letter-spacing: 0.01em;
}

/* FILTERS */
.filters-section {
  background: #fff;
  padding: 1.2rem 1.4rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  border: 1px solid #e5e7eb;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 1rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.filter-group label {
  font-weight: 600;
  color: #1f2937;
}

.filter-group select {
  padding: 0.6rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #f9fafb;
  color: #1f2937;
  cursor: pointer;
  min-width: 180px;
  font-size: 0.95rem;
}

.filter-group select:hover { border-color: #c59d32; }
.filter-group select:focus {
  outline: none;
  border-color: #c59d32;
  box-shadow: 0 0 0 3px rgba(197, 157, 50, 0.16);
}

.filter-hint {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #6b7280;
  font-size: 0.9rem;
  background: #f9fafb;
  border-radius: 10px;
  padding: 0.6rem 0.9rem;
  border: 1px solid #e5e7eb;
}

.filter-hint i { color: #c59d32; }

/* LOADING */
.loading {
  text-align: center;
  padding: 3rem;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  color: #6b7280;
  box-shadow: 0 4px 15px rgba(0,0,0,0.06);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-top-color: #c59d32;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ERROR */
.error-message {
  background: #fef2f2;
  color: #b91c1c;
  padding: 1.2rem;
  border-radius: 12px;
  border: 1px solid #fca5a5;
  box-shadow: 0 4px 15px rgba(220,38,38,0.12);
}

/* TABLE */
.table-container {
  background: #fff;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
  margin-bottom: 1.5rem;
  overflow-x: auto;
  box-shadow: 0 15px 45px rgba(15, 23, 42, 0.08);
}

.allocations-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
}

.allocations-table thead {
  background: #fbf7ef;
  border-bottom: 3px solid #c59d32;
}

.allocations-table th {
  padding: 1rem 0.85rem;
  text-align: left;
  font-weight: 700;
  color: #1f2937;
  font-size: 0.82rem;
  white-space: nowrap;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.allocations-table tbody tr {
  border-bottom: 1px solid #e5e7eb;
  transition: background 0.2s;
}

.allocations-table tbody tr:hover {
  background: #fdf9f2;
}

.allocations-table td {
  padding: 1rem 0.85rem;
  color: #111827;
}

.id-col {
  color: #c59d32;
  font-weight: 700;
  font-size: 0.95rem;
}

.slot-col {
  font-size: 0.85rem;
  color: #1f2937;
  font-weight: 500;
}

/* STATUS BADGES */
.status-badge {
  display: inline-block;
  padding: 0.35rem 0.85rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0.01em;
  border: 1px solid rgba(0,0,0,0.08);
}

.status-pending {
  background: linear-gradient(120deg, #fef3c7, #fde68a);
  color: #78350f;
}

.status-assigned {
  background: linear-gradient(120deg, #c7d2fe, #a5b4fc);
  color: #1d4ed8;
}

.status-completed {
  background: linear-gradient(120deg, #bbf7d0, #86efac);
  color: #065f46;
}

.status-cancelled {
  background: linear-gradient(120deg, #fecdd3, #fda4af);
  color: #991b1b;
}

/* STATUS SELECT */
.status-select {
  padding: 0.5rem 0.7rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f9fafb;
  color: #1f2937;
  cursor: pointer;
  font-size: 0.85rem;
  margin-right: 0.5rem;
  font-weight: 600;
}

.status-select:hover { border-color: #c59d32; }
.status-select:focus {
  outline: none;
  border-color: #c59d32;
  box-shadow: 0 0 0 2px rgba(197, 157, 50, 0.14);
}

.status-select:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ACTIONS */
.actions-col {
  display: flex;
  gap: 0.5rem;
  white-space: nowrap;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 600;
  transition: all 0.2s;
}

.btn-small {
  padding: 0.4rem 0.75rem;
  font-size: 0.8rem;
}

.btn-secondary {
  background: linear-gradient(135deg, #d6b14c, #b88923);
  color: #fff;
  border: 1px solid rgba(0,0,0,0.08);
}

.btn-secondary:hover {
  filter: brightness(0.95);
  transform: translateY(-1px);
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem !important;
  color: #6b7280;
  font-size: 1.1rem;
  background: #f9fafb;
}

/* MODAL */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: #fff;
  border-radius: 14px;
  max-width: 560px;
  width: 90%;
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.25);
  border: 1px solid #e5e7eb;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  background: #fbf7ef;
}

.modal-header h3 {
  margin: 0;
  color: #1f2937;
  font-size: 1.25rem;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.35rem;
  cursor: pointer;
  color: #9ca3af;
  transition: all 0.2s;
}

.btn-close:hover {
  color: #111827;
  transform: rotate(90deg);
}

.modal-body {
  padding: 1.5rem;
  max-height: 500px;
  overflow-y: auto;
}

.detail-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.detail-row strong {
  color: #1f2937;
  min-width: 140px;
  font-weight: 600;
}

.detail-row span {
  color: #6b7280;
  word-break: break-word;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

/* STATISTICS */
.stats-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.stat-card {
  background: #fff;
  padding: 1.3rem 1.5rem;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  text-align: left;
  box-shadow: 0 15px 45px rgba(15, 23, 42, 0.08);
}

.stat-card h4 {
  margin: 0 0 0.4rem;
  color: #6b7280;
  font-size: 0.9rem;
  font-weight: 600;
}

.stat-number {
  margin: 0;
  font-size: 2rem;
  font-weight: 800;
  color: #c59d32;
  letter-spacing: -0.01em;
}

.stat-number.pending { color: #d97706; }
.stat-number.assigned { color: #3b82f6; }
.stat-number.completed { color: #10b981; }
.stat-number.cancelled { color: #ef4444; }

/* RESPONSIVE */
@media (max-width: 900px) {
  .admin-container { padding: 1.25rem; }
  .admin-header { flex-direction: column; align-items: flex-start; gap: 0.8rem; }
  .page-title h1 { font-size: 1.5rem; }
  .filters-section { grid-template-columns: 1fr; }
  .allocations-table { font-size: 0.8rem; }
  .allocations-table th, .allocations-table td { padding: 0.5rem; }
  .actions-col { flex-direction: column; gap: 0.25rem; }
  .modal-content { width: 95%; }
  .detail-row { flex-direction: column; }
  .detail-row strong { min-width: auto; }
}
</style>
