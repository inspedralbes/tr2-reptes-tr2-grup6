<template>
  <div class="admin-center-requests">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-envelope"></i> Sol·licituds de Centres</h1>
        <p class="subtitle">Gestiona les peticions d'accés de nous centres educatius</p>
      </div>
    </div>

    <!-- LLISTA DE SOL·LICITUDS -->
    <div class="card">
      <div class="card-header">
        <h2><i class="fas fa-list"></i> Sol·licituds Rebudes ({{ requests.length }})</h2>
        <div class="filters">
          <select v-model="filterStatus" class="form-select">
            <option value="">Tots els estats</option>
            <option value="pending">Pendents</option>
            <option value="approved">Aprovades</option>
            <option value="rejected">Rebutjades</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="loading">
        <i class="fas fa-spinner fa-spin"></i> Carregant...
      </div>

      <table class="data-table" v-else-if="filteredRequests.length > 0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Centre</th>
            <th>Codi</th>
            <th>Contacte</th>
            <th>Email</th>
            <th>Telèfon</th>
            <th>Data</th>
            <th>Estat</th>
            <th>Accions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="req in filteredRequests" :key="req.id">
            <td class="id">{{ req.id }}</td>
            <td><strong>{{ req.center_name }}</strong></td>
            <td>{{ req.center_code }}</td>
            <td>{{ req.contact_name }}</td>
            <td class="email">{{ req.contact_email }}</td>
            <td>{{ req.contact_phone || '—' }}</td>
            <td>{{ formatDate(req.created_at) }}</td>
            <td>
              <span :class="['badge', `badge-${req.status}`]">
                {{ getStatusLabel(req.status) }}
              </span>
            </td>
            <td class="actions">
              <button @click="viewDetails(req)" class="btn-view" title="Veure detalls">
                <i class="fas fa-eye"></i>
              </button>
              <button v-if="req.status === 'pending'" @click="approveRequest(req)" class="btn-approve" title="Aprovar">
                <i class="fas fa-check"></i>
              </button>
              <button v-if="req.status === 'pending'" @click="rejectRequest(req)" class="btn-reject" title="Rebutjar">
                <i class="fas fa-times"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty-state">
        <i class="fas fa-inbox"></i>
        <p>No hi ha sol·licituds amb aquest estat</p>
      </div>
    </div>

    <!-- MODAL DETALLS -->
    <div v-if="showDetailsModal" class="modal-overlay" @click.self="showDetailsModal = false">
      <div class="modal-content modal-large">
        <div class="modal-header">
          <h2><i class="fas fa-info-circle"></i> Detalls de la Sol·licitud</h2>
          <button @click="showDetailsModal = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body" v-if="selectedRequest">
          <div class="details-grid">
            <div class="detail-section">
              <h3><i class="fas fa-school"></i> Dades del Centre</h3>
              <p><strong>Nom:</strong> {{ selectedRequest.center_name }}</p>
              <p><strong>Codi:</strong> {{ selectedRequest.center_code }}</p>
              <p><strong>Adreça:</strong> {{ selectedRequest.address }}</p>
              <p><strong>Ciutat:</strong> {{ selectedRequest.city }}</p>
              <p><strong>Codi Postal:</strong> {{ selectedRequest.postal_code }}</p>
            </div>
            <div class="detail-section">
              <h3><i class="fas fa-user"></i> Persona de Contacte</h3>
              <p><strong>Nom:</strong> {{ selectedRequest.contact_name }}</p>
              <p><strong>Email:</strong> {{ selectedRequest.contact_email }}</p>
              <p><strong>Telèfon:</strong> {{ selectedRequest.contact_phone }}</p>
              <p><strong>Càrrec:</strong> {{ selectedRequest.contact_position || '—' }}</p>
              <p><strong>Alumnes ESO:</strong> {{ selectedRequest.student_count || '—' }}</p>
            </div>
          </div>
          <div v-if="selectedRequest.notes" class="detail-section">
            <h3><i class="fas fa-comment"></i> Observacions</h3>
            <p>{{ selectedRequest.notes }}</p>
          </div>
        </div>
        <div class="modal-footer">
          <button v-if="selectedRequest?.status === 'pending'" @click="approveRequest(selectedRequest)" class="btn btn-success">
            <i class="fas fa-check"></i> Aprovar
          </button>
          <button v-if="selectedRequest?.status === 'pending'" @click="rejectRequest(selectedRequest)" class="btn btn-danger">
            <i class="fas fa-times"></i> Rebutjar
          </button>
          <button @click="showDetailsModal = false" class="btn btn-secondary">
            Tancar
          </button>
        </div>
      </div>
    </div>

    <!-- CONFIRMATION MODAL -->
    <div v-if="confirmModal.show" class="modal-overlay" @click.self="confirmModal.show = false">
      <div class="modal-content modal-confirm">
        <div class="modal-header" :class="confirmModal.type">
          <h2 v-if="confirmModal.type === 'success'"><i class="fas fa-check-circle"></i> {{ confirmModal.title }}</h2>
          <h2 v-else-if="confirmModal.type === 'danger'"><i class="fas fa-exclamation-triangle"></i> {{ confirmModal.title }}</h2>
          <h2 v-else><i class="fas fa-info-circle"></i> {{ confirmModal.title }}</h2>
        </div>
        
        <div class="modal-body text-center">
          <div class="confirm-icon" :class="confirmModal.type">
             <i class="fas fa-question" v-if="confirmModal.type === 'info'"></i>
             <i class="fas fa-check" v-else-if="confirmModal.type === 'success'"></i>
             <i class="fas fa-times" v-else-if="confirmModal.type === 'danger'"></i>
          </div>
          <h3 class="confirm-message">{{ confirmModal.message }}</h3>
          <p class="confirm-details">{{ confirmModal.details }}</p>
        </div>
        
        <div class="modal-footer">
          <button @click="confirmModal.show = false" class="btn btn-secondary">
            Cancel·lar
          </button>
          <button @click="executeConfirm" class="btn" :class="`btn-${confirmModal.type}`">
            {{ confirmModal.confirmText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const requests = ref([])
const loading = ref(false)
const filterStatus = ref('')
const showDetailsModal = ref(false)
const selectedRequest = ref(null)

// Confirm Modal State
const confirmModal = ref({
  show: false,
  title: '',
  message: '',
  details: '',
  type: 'info', // info, success, warning, danger
  action: null,
  confirmText: 'Confirmar'
})

const filteredRequests = computed(() => {
  if (!filterStatus.value) return requests.value
  return requests.value.filter(r => r.status === filterStatus.value)
})

const fetchRequests = async () => {
  loading.value = true
  try {
    const response = await fetch('http://localhost:8000/api/center-requests', {
        headers: {
            'Authorization': `Bearer ${auth.token}`,
            'Content-Type': 'application/json'
        }
    })
    const data = await response.json()
    if (data.success) {
      requests.value = data.data
    }
  } catch (error) {
    console.error('Error carregant sol·licituds:', error)
  } finally {
    loading.value = false
  }
}

const viewDetails = (request) => {
  selectedRequest.value = request
  showDetailsModal.value = true
}

// Open Approve Confirmation
const approveRequest = (request) => {
  confirmModal.value = {
    show: true,
    title: 'Aprovar Sol·licitud',
    message: `Estàs segur que vols aprovar la sol·licitud de "${request.center_name}"?`,
    details: 'Es crearà automàticament el centre i un usuari coordinador. Rebràs una contrasenya temporal per enviar.',
    type: 'success',
    confirmText: 'Aprovar i Crear',
    action: async () => {
      try {
        const response = await fetch(`http://localhost:8000/api/center-requests/${request.id}/approve`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${auth.token}`,
                'Content-Type': 'application/json'
            }
        })
        const data = await response.json()
        
        if (data.success) {
            // Close confirm modal
            confirmModal.value.show = false
            
            alert(`✅ Sol·licitud aprovada i Centre creat.\n\n🔑 Credencials del Coordinador:\nContrasenya: ${data.password}\n\n⚠️ IMPORTANT: Copia i envia aquesta contrasenya ara mateix.`)
            
            showDetailsModal.value = false
            await fetchRequests()
        } else {
            alert('❌ Error: ' + data.message)
        }
      } catch (error) {
        alert('❌ Error aprovant la sol·licitud: ' + error.message)
      }
    }
  }
}

// Open Reject Confirmation
const rejectRequest = (request) => {
  confirmModal.value = {
    show: true,
    title: 'Rebutjar Sol·licitud',
    message: `Estàs segur que vols rebutjar la sol·licitud de "${request.center_name}"?`,
    details: 'Aquesta acció no es pot desfer. La sol·licitud quedarà marcada com a rebutjada.',
    type: 'danger',
    confirmText: 'Rebutjar',
    action: async () => {
      try {
        const response = await fetch(`http://localhost:8000/api/center-requests/${request.id}/reject`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${auth.token}`,
                'Content-Type': 'application/json'
            }
        })
        const data = await response.json()
        
        if (data.success) {
            confirmModal.value.show = false
            
            // Simple notification
            const notification = document.createElement('div')
            notification.className = 'toast-notification'
            notification.textContent = 'Sol·licitud rebutjada correctament'
            document.body.appendChild(notification)
            setTimeout(() => notification.remove(), 3000)

            showDetailsModal.value = false
            await fetchRequests()
        } else {
            alert('❌ Error: ' + data.message)
        }
      } catch (error) {
        alert('❌ Error rebutjant la sol·licitud: ' + error.message)
      }
    }
  }
}

const executeConfirm = async () => {
    if (confirmModal.value.action) {
        await confirmModal.value.action()
    }
}

const formatDate = (dateString) => {
  if (!dateString) return '—'
  return new Date(dateString).toLocaleDateString('ca-ES')
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Pendent',
    approved: 'Aprovada',
    rejected: 'Rebutjada'
  }
  return labels[status] || status
}

onMounted(() => {
  fetchRequests()
})
</script>

<style scoped>
.admin-center-requests {
  padding: 2rem 2.5rem;
  background: #f7f8fb;
  min-height: 100vh;
}

.page-header {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 3px solid #C5A059;
}

.page-header h1 {
  margin: 0;
  color: #1f2937;
  font-size: 2rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.subtitle {
  margin: 0.5rem 0 0;
  color: #6b7280;
  font-size: 1rem;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.card-header {
  padding: 1.5rem;
  background: linear-gradient(135deg, #d4af37 0%, #c99c45 100%);
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h2 {
  margin: 0;
  font-size: 1.3rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filters {
  display: flex;
  gap: 1rem;
}

.form-select {
  padding: 0.5rem 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  font-weight: 600;
}

.form-select option {
  color: #1f2937;
}

.loading {
  text-align: center;
  padding: 3rem;
  color: #6b7280;
  font-size: 1.1rem;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: #f7f8fb;
}

.data-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 700;
  color: #374151;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.data-table td {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.data-table tbody tr:hover {
  background: #f9fafb;
}

.id {
  font-weight: 700;
  color: #6b7280;
}

.email {
  font-size: 0.875rem;
  color: #6b7280;
}

.badge {
  padding: 0.35rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.badge-pending {
  background: #fef3c7;
  color: #92400e;
}

.badge-approved {
  background: #d1fae5;
  color: #065f46;
}

.badge-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.actions {
  display: flex;
  gap: 0.5rem;
}

.btn-view,
.btn-approve,
.btn-reject {
  padding: 0.5rem 0.75rem;
  border: 2px solid;
  border-radius: 6px;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.btn-view {
  border-color: #3b82f6;
  color: #3b82f6;
}

.btn-view:hover {
  background: #3b82f6;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
}

.btn-approve {
  border-color: #10b981;
  color: #10b981;
}

.btn-approve:hover {
  background: #10b981;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
}

.btn-reject {
  border-color: #ef4444;
  color: #ef4444;
}

.btn-reject:hover {
  background: #ef4444;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #9ca3af;
}

.empty-state i {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

/* Modal Styles */
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
  z-index: 9999;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-large {
  max-width: 900px;
}

.modal-header {
  padding: 1.5rem;
  background: linear-gradient(135deg, #d4af37 0%, #c99c45 100%);
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 12px 12px 0 0;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-close {
  background: transparent;
  border: none;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0.5rem;
  transition: transform 0.2s;
}

.btn-close:hover {
  transform: scale(1.2);
}

.modal-body {
  padding: 2rem;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

.detail-section {
  background: #f7f8fb;
  padding: 1.5rem;
  border-radius: 8px;
}

.detail-section h3 {
  margin: 0 0 1rem;
  color: #1f2937;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.detail-section p {
  margin: 0.5rem 0;
  color: #4b5563;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-success {
  background: #10b981;
  color: white;
}

.btn-success:hover {
  background: #059669;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover {
  background: #dc2626;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background: #4b5563;
}

/* Confirm Modal Specifics */
.modal-confirm {
    max-width: 450px;
}

.modal-header.success {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
}

.modal-header.danger {
    background: linear-gradient(135deg, #EF4444 0%, #B91C1C 100%);
}

.confirm-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2.5rem;
}

.confirm-icon.success {
    background: #D1FAE5;
    color: #10B981;
}

.confirm-icon.danger {
    background: #FEE2E2;
    color: #EF4444;
}

.confirm-message {
    font-size: 1.25rem;
    color: #1f2937;
    margin-bottom: 0.75rem;
    font-weight: 700;
}

.confirm-details {
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.text-center {
    text-align: center;
}

.toast-notification {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #333;
    color: white;
    padding: 1rem 2rem;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    animation: slideIn 0.3s ease-out;
    z-index: 10000;
}

@keyframes slideIn {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>
