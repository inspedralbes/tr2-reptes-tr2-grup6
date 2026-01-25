<template>
  <div class="admin-container">
    <div class="admin-header">
      <h1>Gestionar Sol·licituds</h1>
      <p class="subtitle">Revisa i aprova les sol·licituds dels usuaris</p>
    </div>

    <!-- FILTERS -->
    <div class="filters-section">
      <div class="filter-group">
        <label for="status-filter">Estat:</label>
        <select v-model="filterStatus" id="status-filter">
          <option value="">Tots</option>
          <option value="pending">Pendent</option>
          <option value="approved">Aprovat</option>
          <option value="rejected">Rebutjat</option>
        </select>
      </div>
    </div>

    <!-- LOADING STATE -->
    <div v-if="adminStore.loading" class="loading">
      <div class="spinner"></div>
      <p>Carregant sol·licituds...</p>
    </div>

    <!-- ERROR STATE -->
    <div v-else-if="adminStore.error" class="error-message">
      ⚠️ Error: {{ adminStore.error }}
    </div>

    <!-- TABLE -->
    <div v-else class="table-container">
      <table class="requests-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Usuari</th>
            <th>Taller</th>
            <th>Estat</th>
            <th>Data</th>
            <th>Accions</th>
          </tr>
        </thead>
        <tbody v-if="filteredRequests.length > 0">
          <tr v-for="request in filteredRequests" :key="request.id">
            <td class="id-col">{{ request.id }}</td>
            <td>{{ request.user_name }}</td>
            <td>{{ request.workshop_name }}</td>
            <td>
              <span :class="['status-badge', `status-${request.status}`]">
                {{ getStatusLabel(request.status) }}
              </span>
            </td>
            <td>{{ formatDate(request.created_at) }}</td>
            <td class="actions-col">
              <button 
                v-if="request.status === 'pending'"
                @click="approveRequest(request.id)"
                class="btn btn-success btn-small"
                :disabled="processingId === request.id"
              >
                ✓ Aprovar
              </button>
              <button 
                v-if="request.status === 'pending'"
                @click="rejectRequest(request.id)"
                class="btn btn-danger btn-small"
                :disabled="processingId === request.id"
              >
                ✗ Rebutjar
              </button>
              <button 
                @click="viewDetails(request)"
                class="btn btn-secondary btn-small"
              >
                👁️ Veure
              </button>
            </td>
          </tr>
        </tbody>
        <tbody v-else>
          <tr>
            <td colspan="6" class="empty-state">
              📭 No hi ha sol·licituds
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- DETAIL MODAL -->
    <div v-if="selectedRequest" class="modal-overlay" @click="selectedRequest = null">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Detalls de la Sol·licitud</h3>
          <button @click="selectedRequest = null" class="btn-close">✕</button>
        </div>
        <div class="modal-body">
          <div class="detail-row">
            <strong>ID:</strong>
            <span>{{ selectedRequest.id }}</span>
          </div>
          <div class="detail-row">
            <strong>Usuari:</strong>
            <span>{{ selectedRequest.user_name }} ({{ selectedRequest.user_email }})</span>
          </div>
          <div class="detail-row">
            <strong>Taller:</strong>
            <span>{{ selectedRequest.workshop_name }}</span>
          </div>
          <div class="detail-row">
            <strong>Estat:</strong>
            <span :class="['status-badge', `status-${selectedRequest.status}`]">
              {{ getStatusLabel(selectedRequest.status) }}
            </span>
          </div>
          <div class="detail-row">
            <strong>Data de Creació:</strong>
            <span>{{ formatDateTime(selectedRequest.created_at) }}</span>
          </div>
          <div v-if="selectedRequest.notes" class="detail-row">
            <strong>Notes:</strong>
            <span>{{ selectedRequest.notes }}</span>
          </div>
        </div>
        <div class="modal-footer">
          <button 
            v-if="selectedRequest.status === 'pending'"
            @click="approveRequest(selectedRequest.id); selectedRequest = null"
            class="btn btn-success"
          >
            ✓ Aprovar
          </button>
          <button 
            v-if="selectedRequest.status === 'pending'"
            @click="rejectRequest(selectedRequest.id); selectedRequest = null"
            class="btn btn-danger"
          >
            ✗ Rebutjar
          </button>
          <button @click="selectedRequest = null" class="btn btn-secondary">
            Tancar
          </button>
        </div>
      </div>
    </div>

    <!-- STATISTICS -->
    <div class="stats-summary">
      <div class="stat-card">
        <h4>Total</h4>
        <p class="stat-number">{{ adminStore.requests.length }}</p>
      </div>
      <div class="stat-card">
        <h4>Pendents</h4>
        <p class="stat-number pending">{{ countByStatus('pending') }}</p>
      </div>
      <div class="stat-card">
        <h4>Aprovades</h4>
        <p class="stat-number approved">{{ countByStatus('approved') }}</p>
      </div>
      <div class="stat-card">
        <h4>Rebutjades</h4>
        <p class="stat-number rejected">{{ countByStatus('rejected') }}</p>
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
const selectedRequest = ref(null)
const processingId = ref(null)

const filteredRequests = computed(() => {
  if (!filterStatus.value) {
    return adminStore.requests
  }
  return adminStore.requests.filter(r => r.status === filterStatus.value)
})

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Pendent',
    approved: 'Aprovat',
    rejected: 'Rebutjat'
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

const countByStatus = (status) => {
  return adminStore.requests.filter(r => r.status === status).length
}

const viewDetails = (request) => {
  selectedRequest.value = { ...request }
}

const approveRequest = async (requestId) => {
  processingId.value = requestId
  try {
    await adminStore.updateRequest(requestId, { status: 'approved' })
    toast.success('Sol·licitud aprovada')
  } catch (error) {
    toast.error('Error en aprovar la sol·licitud')
    console.error(error)
  } finally {
    processingId.value = null
  }
}

const rejectRequest = async (requestId) => {
  processingId.value = requestId
  try {
    await adminStore.updateRequest(requestId, { status: 'rejected' })
    toast.success('Sol·licitud rebutjada')
  } catch (error) {
    toast.error('Error en rebutjar la sol·licitud')
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
  margin-bottom: 2rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.admin-header h1 {
  margin: 0;
  color: #0F172A;
  font-size: 2.2rem;
}

.subtitle {
  margin: 0.5rem 0 0;
  color: #666;
  font-size: 0.95rem;
}

/* FILTERS */
.filters-section {
  background: #fff;
  padding: 1.2rem 1.4rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  border: 1px solid #e5e7eb;
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 1rem;
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

.filter-group select:hover {
  border-color: #c59d32;
}

.filter-group select:focus {
  outline: none;
  border-color: #c59d32;
  box-shadow: 0 0 0 3px rgba(197, 157, 50, 0.16);
}

/* LOADING */
.loading {
  text-align: center;
  padding: 3rem;
  background: white;
  border-radius: 8px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-top-color: #C5A059;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ERROR */
.error-message {
  background: #fee2e2;
  color: #991b1b;
  padding: 1.5rem;
  border-radius: 8px;
  border-left: 4px solid #dc2626;
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

.requests-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
}

.requests-table thead {
  background: #fbf7ef;
  border-bottom: 3px solid #c59d32;
}

.requests-table th {
  padding: 1rem 0.85rem;
  text-align: left;
  font-weight: 700;
  color: #1f2937;
  font-size: 0.82rem;
  white-space: nowrap;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.requests-table tbody tr {
  border-bottom: 1px solid #e5e7eb;
  transition: background 0.2s;
}

.requests-table tbody tr:hover {
  background: #fdf9f2;
}

.requests-table td {
  padding: 1rem 0.85rem;
  color: #111827;
}

.id-col {
  color: #c59d32;
  font-weight: 700;
  font-size: 0.95rem;
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

.status-approved {
  background: linear-gradient(120deg, #bbf7d0, #86efac);
  color: #065f46;
}

.status-rejected {
  background: linear-gradient(120deg, #fecdd3, #fda4af);
  color: #991b1b;
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
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 600;
  transition: all 0.2s;
  margin-left: 0.5rem;
}

.btn-small {
  padding: 0.35rem 0.75rem;
  font-size: 0.8rem;
}

.btn-success {
  background: #10b981;
  color: white;
}

.btn-success:hover {
  background: #059669;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover {
  background: #dc2626;
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
  border-bottom: 1px solid #f1f5f9;
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
  border-top: 1px solid #e2e8f0;
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

.stat-number.pending {
  color: #d97706;
}

.stat-number.approved {
  color: #10b981;
}

.stat-number.rejected {
  color: #ef4444;
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .admin-container {
    padding: 1rem;
  }

  .admin-header h1 {
    font-size: 1.5rem;
  }

  .requests-table {
    font-size: 0.85rem;
  }

  .requests-table th,
  .requests-table td {
    padding: 0.5rem;
  }

  .btn {
    padding: 0.35rem 0.5rem;
    font-size: 0.75rem;
  }

  .actions-col { gap: 0.25rem; }

  .modal-content {
    width: 95%;
  }
}
</style>
