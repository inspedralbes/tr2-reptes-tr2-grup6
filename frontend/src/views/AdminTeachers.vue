<template>
  <div class="admin-container">
    <div class="admin-header">
      <h1><i class="fas fa-user-tie"></i> Docents</h1>
    </div>

    <!-- Toast Notification -->
    <transition name="toast-fade">
      <div v-if="notification" :class="['toast-notification', notification.type]">
        <i :class="notification.type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
        {{ notification.message }}
      </div>
    </transition>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Carregant docents...</p>
    </div>

    <div v-else>
      <!-- Lista de Docents -->
      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-list"></i> Llista de Docents ({{ teachers.length }})</h2>
          <button @click="showCreateModal = true" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nou Docent
          </button>
        </div>
        
        <table class="data-table" v-if="teachers.length > 0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nom Complet</th>
              <th>Email</th>
              <th>Centre</th>
              <th>Accions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="teacher in teachers" :key="teacher.id">
              <td>{{ teacher.id }}</td>
              <td>{{ teacher.full_name }}</td>
              <td>{{ teacher.email }}</td>
              <td>
                <span v-if="teacher.center_name" class="center-badge">
                  <i class="fas fa-building"></i> {{ teacher.center_name }} ({{ teacher.center_code }})
                </span>
                <span v-else class="no-center">
                  <i class="fas fa-minus-circle"></i> Sense centre
                </span>
              </td>
              <td class="actions">
                <button @click="openEditModal(teacher)" class="btn-icon" title="Editar">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="resetPassword(teacher)" class="btn-icon btn-warning" title="Restablir contrasenya">
                  <i class="fas fa-key"></i>
                </button>
                <button @click="deleteTeacher(teacher.id)" class="btn-icon btn-danger" title="Eliminar">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-else class="empty-state">
          <i class="fas fa-user-slash"></i>
          <p>No hi ha docents registrats</p>
        </div>
      </div>
    </div>

    <!-- Modal Crear/Editar -->
    <div v-if="showCreateModal || showEditModal" class="modal-overlay" @click.self="closeModals">
      <div class="modal-content">
        <div class="modal-header">
          <h2>
            <i :class="showCreateModal ? 'fas fa-user-plus' : 'fas fa-user-edit'"></i>
            {{ showCreateModal ? 'Crear Docent' : 'Editar Docent' }}
          </h2>
          <button @click="closeModals" class="btn-close" aria-label="Tancar">
            <i class="fas fa-xmark"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nom Complet <span class="required">*</span></label>
            <input v-model="form.full_name" type="text" placeholder="Ex: Maria García López" required />
          </div>
          <div class="form-group">
            <label>Email <span class="required">*</span></label>
            <input v-model="form.email" type="email" placeholder="maria@example.com" required />
          </div>
          <div class="form-group">
            <label>Centre</label>
            <select v-model.number="form.center_id">
              <option :value="null">Sense centre assignat</option>
              <option v-for="c in centers" :key="c.id" :value="c.id">{{ c.name }} ({{ c.code }})</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeModals" class="btn btn-secondary">
            <i class="fas fa-xmark"></i> Cancel·lar
          </button>
          <button @click="showCreateModal ? createTeacher() : updateTeacher()" class="btn btn-primary" :disabled="saving">
            <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
            {{ saving ? 'Guardant...' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Reset Password -->
    <div v-if="showPasswordModal" class="modal-overlay" @click.self="showPasswordModal = false">
      <div class="modal-content modal-small">
        <div class="modal-header">
          <h2><i class="fas fa-key"></i> Nova Contrasenya</h2>
          <button @click="showPasswordModal = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="password-display">
            <p>Contrasenya temporal generada per a <strong>{{ currentTeacher?.full_name }}</strong>:</p>
            <div class="password-box">
              <code>{{ newPassword }}</code>
              <button @click="copyPassword" class="btn-copy" title="Copiar">
                <i class="fas fa-copy"></i>
              </button>
            </div>
            <p class="warning-text">
              <i class="fas fa-exclamation-triangle"></i>
              Aquesta contrasenya només es mostra una vegada. Guarda-la o comparteix-la amb el docent.
            </p>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showPasswordModal = false" class="btn btn-primary">
            <i class="fas fa-check"></i> Entès
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useTeacherStore } from '@/stores/teacher'
import { useCenterStore } from '@/stores/center'

const teacherStore = useTeacherStore()
const centerStore = useCenterStore()

const centers = computed(() => centerStore.centers)
const teachers = computed(() => teacherStore.teachers)
const loading = ref(false)
const saving = ref(false)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showPasswordModal = ref(false)
const currentTeacher = ref(null)
const newPassword = ref('')
const notification = ref(null)

const form = ref({
  full_name: '',
  email: '',
  center_id: null
})

const showNotification = (message, type = 'info') => {
  notification.value = { message, type }
  setTimeout(() => {
    notification.value = null
  }, 4000)
}

const closeModals = () => {
  showCreateModal.value = false
  showEditModal.value = false
  form.value = { full_name: '', email: '', center_id: null }
}

const openEditModal = (teacher) => {
  currentTeacher.value = teacher
  form.value = {
    full_name: teacher.full_name,
    email: teacher.email,
    center_id: teacher.center_id
  }
  showEditModal.value = true
}

const createTeacher = async () => {
  if (!form.value.full_name || !form.value.email) {
    showNotification('Nom i email són obligatoris', 'error')
    return
  }
  saving.value = true
  const res = await teacherStore.createTeacher({ ...form.value })
  saving.value = false
  if (res.success) {
    showNotification('Docent creat correctament', 'success')
    closeModals()
  } else {
    showNotification(res.message || 'Error creant docent', 'error')
  }
}

const updateTeacher = async () => {
  saving.value = true
  const res = await teacherStore.updateTeacher(currentTeacher.value.id, { ...form.value })
  saving.value = false
  if (res.success) {
    showNotification('Docent actualitzat correctament', 'success')
    closeModals()
  } else {
    showNotification(res.message || 'Error actualitzant docent', 'error')
  }
}

const deleteTeacher = async (id) => {
  if (!confirm('Estàs segur que vols eliminar aquest docent?')) return
  const res = await teacherStore.deleteTeacher(id)
  if (res.success) {
    showNotification('Docent eliminat', 'success')
  } else {
    showNotification(res.message || 'Error eliminant docent', 'error')
  }
}

const resetPassword = async (teacher) => {
  currentTeacher.value = teacher
  try {
    const response = await fetch(`http://localhost:8000/api/teachers/${teacher.id}/reset-password`, {
      method: 'POST'
    })
    const data = await response.json()
    if (data.success) {
      newPassword.value = data.password
      showPasswordModal.value = true
      showNotification('Contrasenya restablerta', 'success')
    } else {
      showNotification(data.message || 'Error restablint contrasenya', 'error')
    }
  } catch (err) {
    showNotification('Error restablint contrasenya: ' + err.message, 'error')
  }
}

const copyPassword = () => {
  navigator.clipboard.writeText(newPassword.value)
  showNotification('Contrasenya copiada al portapapers', 'success')
}

onMounted(async () => {
  loading.value = true
  await Promise.all([
    centerStore.fetchCenters(),
    teacherStore.fetchTeachers()
  ])
  loading.value = false
})
</script>

<style scoped>
.admin-container {
  width: 100%;
  max-width: 100%;
  margin: 0;
  padding: 2rem 3rem;
  background: #f7f8fb;
  min-height: 100vh;
}

.admin-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 3rem;
  padding-bottom: 2rem;
  border-bottom: 3px solid #d4af37;
}

.admin-header h1 {
  margin: 0;
  font-size: 2.5rem;
  color: #1a1a1a;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.admin-header h1 i {
  font-size: 2.5rem;
  color: #d4af37;
}

.card {
  background: white;
  border: 1px solid #f0f0f0;
  border-radius: 12px;
  padding: 2rem 2.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f0f0f0;
  background: transparent;
}

.card-header h2 {
  margin: 0;
  font-size: 1.3rem;
  color: #1a1a1a;
  display: flex;
  align-items: center;
  gap: 0.8rem;
  font-weight: 700;
}

.card-header i { 
  color: #d4af37;
  font-size: 1.3rem;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: linear-gradient(135deg, #d4af37 0%, #c99c45 100%);
  color: white;
}

.data-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 700;
  font-size: 0.95rem;
  color: white;
}

.data-table th:first-child {
  border-radius: 8px 0 0 0;
}

.data-table th:last-child {
  border-radius: 0 8px 0 0;
}

.data-table td {
  padding: 1rem;
  border-bottom: 1px solid #f0f0f0;
  color: #1a1a1a;
}

.data-table td:first-child {
  font-weight: 700;
  color: #1a1a1a;
}

.data-table tbody tr:hover {
  background: #f9fafb;
}

.center-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 0.8rem;
  background: #dbeafe;
  color: #1e40af;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
}

.no-center {
  color: #666;
  font-style: italic;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.actions {
  display: flex;
  gap: 0.6rem;
  justify-content: center;
}

.btn-icon {
  background: #ffffff;
  border: 2px solid;
  padding: 0.6rem 0.8rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.25s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  color: #0066cc;
  border-color: #0066cc;
}

.btn-icon:hover {
  transform: translateY(-3px);
  background: #0066cc;
  color: white;
  box-shadow: 0 6px 16px rgba(0, 102, 204, 0.5);
}

.btn-icon.btn-warning {
  color: #f59e0b;
  border-color: #f59e0b;
}

.btn-icon.btn-warning:hover {
  background: #f59e0b;
  color: white;
  box-shadow: 0 6px 16px rgba(245, 158, 11, 0.5);
}

.btn-icon.btn-danger {
  color: #dc2626;
  border-color: #dc2626;
}

.btn-icon.btn-danger:hover {
  background: #dc2626;
  color: white;
  box-shadow: 0 6px 16px rgba(220, 38, 38, 0.5);
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  padding: 0.85rem 1.5rem;
  border: none;
  border-radius: 10px;
  font-size: 0.95rem;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.25s ease;
  font-weight: 700;
}

.btn-primary {
  background: linear-gradient(135deg, #d4af37 0%, #c99c45 100%);
  color: white;
  border: none;
  box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-3px);
  background: linear-gradient(135deg, #e0c160 0%, #d4af5e 100%);
  box-shadow: 0 8px 20px rgba(212, 175, 55, 0.5);
}

.btn-primary:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.6), 0 0 0 6px rgba(197, 160, 89, 0.4);
}

.btn-primary:active {
  transform: translateY(0);
  filter: brightness(0.95);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary {
  background: linear-gradient(135deg, #64748b 0%, #475569 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(100, 116, 139, 0.25);
}

.btn-secondary:hover {
  transform: translateY(-2px);
  background: linear-gradient(135deg, #78879a 0%, #5a6878 100%);
  box-shadow: 0 8px 20px rgba(100, 116, 139, 0.35);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 5rem;
  background: white;
  border-radius: 12px;
  gap: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f0f0f0;
  border-top-color: #d4af37;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 5rem 2rem;
  color: #94a3b8;
}

.empty-state i {
  font-size: 5rem;
  margin-bottom: 1.5rem;
  color: #cbd5e1;
  opacity: 0.6;
}

.empty-state p {
  font-size: 1.15rem;
  font-weight: 500;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: modalFadeIn 0.3s ease-out;
}

.modal-content.modal-small {
  max-width: 500px;
}

@keyframes modalFadeIn {
  from {
    opacity: 0;
    transform: translateY(-50px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.75rem 2rem;
  border-bottom: 2px solid #f0f0f0;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.4rem;
  display: flex;
  align-items: center;
  gap: 0.8rem;
  color: #1a1a1a;
  font-weight: 700;
}

.modal-header i {
  color: #d4af37;
  font-size: 1.5rem;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.35rem;
  cursor: pointer;
  color: #6b7280;
  padding: 0.45rem;
  border-radius: 10px;
  transition: all 0.2s;
  line-height: 1;
}

.btn-close:hover {
  background: #f3f4f6;
  color: #111827;
}

.modal-body {
  padding: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: #333;
}

.required {
  color: #ef4444;
}

input,
select {
  width: 100%;
  padding: 0.85rem 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 0.95rem;
  background: #fafafa;
  transition: all 0.2s ease;
}

input:focus,
select:focus {
  outline: none;
  border-color: #d4af37;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.15);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.75rem 2rem;
  border-top: 2px solid #f0f0f0;
}

/* Password Modal */
.password-display {
  text-align: center;
}

.password-box {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin: 1.5rem 0;
  padding: 1.75rem;
  background: #f9fafb;
  border: 2px dashed #d4af37;
  border-radius: 12px;
}

.password-box code {
  font-size: 1.4rem;
  font-weight: 700;
  color: #d4af37;
  letter-spacing: 2px;
}

.btn-copy {
  background: #d4af37;
  color: white;
  border: none;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 600;
}

.btn-copy:hover {
  background: #c99c45;
  transform: scale(1.05);
}

.warning-text {
  color: #f59e0b;
  font-size: 0.9rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

/* Toast */
.toast-notification {
  position: fixed;
  top: 2rem;
  right: 2rem;
  padding: 1.2rem 1.5rem;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 0.8rem;
  font-weight: 700;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  z-index: 9999;
  max-width: 400px;
  animation: slideInRight 0.3s ease-out;
}

.toast-notification.success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.toast-notification.error {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

@keyframes slideInRight {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.toast-fade-enter-active,
.toast-fade-leave-active {
  transition: all 0.3s ease;
}

.toast-fade-enter-from {
  transform: translateX(400px);
  opacity: 0;
}

.toast-fade-leave-to {
  transform: translateX(400px);
  opacity: 0;
}

@media (max-width: 768px) {
  .admin-container { 
    padding: 1.5rem;
  }
  
  .admin-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .admin-header h1 { 
    font-size: 2rem; 
  }
  
  .card-header h2 {
    font-size: 1.1rem;
  }
  
  .toast-notification {
    top: 1rem;
    right: 1rem;
    left: 1rem;
    max-width: none;
  }
  
  .modal-content {
    width: 95%;
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 1.25rem 1.5rem;
  }
}
</style>
