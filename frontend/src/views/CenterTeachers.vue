<template>
  <div class="center-teachers-container">
    <!-- Header Premium -->
    <div class="header-section">
      <div class="header-content">
        <router-link to="/center-dashboard" class="btn-back-dash mb-2">
          <i class="fas fa-arrow-left"></i> Tornar al Dashboard
        </router-link>
        <h1><i class="fas fa-chalkboard-teacher"></i> Gestió de Docents</h1>
        <p class="subtitle">Administra l'equip docent i els seus permisos al centre</p>
      </div>
      <div class="header-actions">
        <button @click="openCreateModal" class="btn-cta primary">
          <i class="fas fa-plus"></i> Afegir Docent
        </button>
      </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="filters-bar">
      <div class="search-group">
        <i class="fas fa-search search-icon"></i>
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Cercar per nom, correu o especialitat..."
          class="form-search"
        >
      </div>
      <div class="stats-badge">
        <i class="fas fa-users"></i>
        <span>{{ filteredTeachers.length }} docents</span>
      </div>
    </div>

    <!-- Teachers Table Card -->
    <div class="content-card">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Carregant dades...</p>
      </div>

      <div v-else-if="filteredTeachers.length === 0" class="empty-state">
        <div class="empty-icon">
          <i class="fas fa-folder-open"></i>
        </div>
        <p>No s'han trobat docents</p>
        <button @click="showCreateModal = true" class="btn-cta secondary">
          <i class="fas fa-plus"></i> Afegir el primer docent
        </button>
      </div>

      <div v-else class="table-responsive">
        <table class="kairos-table">
          <thead>
            <tr>
              <th>Docent</th>
              <th>Contacte</th>
              <th>Especialitat</th>
              <th>Estat</th>
              <th class="text-end">Accions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="teacher in filteredTeachers" :key="teacher.id">
              <td>
                <div class="user-cell">
                  <div class="avatar-circle">{{ getInitials(teacher.full_name) }}</div>
                  <div class="user-info">
                    <span class="user-name">{{ teacher.full_name }}</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="contact-cell">
                  <div class="contact-item">
                    <i class="fas fa-envelope"></i> {{ teacher.email }}
                  </div>
                  <div v-if="teacher.phone" class="contact-item">
                    <i class="fas fa-phone"></i> {{ teacher.phone }}
                  </div>
                </div>
              </td>
              <td>
                <span class="badge-specialty">
                  <i class="fas fa-tag"></i> {{ teacher.specialty || 'General' }}
                </span>
              </td>
              <td>
                <span :class="['status-badge', teacher.is_active ? 'active' : 'inactive']">
                  <i :class="teacher.is_active ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                  {{ teacher.is_active ? 'Actiu' : 'Inactiu' }}
                </span>
              </td>
              <td class="text-end">
                <div class="action-buttons">
                  <button @click="editTeacher(teacher)" class="btn-icon" title="Editar">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button 
                    @click="confirmResetPassword(teacher)" 
                    class="btn-icon"
                    title="Restablir Contrasenya"
                  >
                    <i class="fas fa-key"></i>
                  </button>
                  <button 
                    @click="confirmDelete(teacher)" 
                    class="btn-icon delete" 
                    title="Eliminar"
                  >
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CREATE/EDIT MODAL -->
    <div v-if="showCreateModal || showEditModal" class="modal-overlay">
      <div class="modal-card">
        <div class="modal-header">
          <h3>
            <i :class="showEditModal ? 'fas fa-edit' : 'fas fa-user-plus'"></i>
            {{ showEditModal ? 'Editar Docent' : 'Nou Docent' }}
          </h3>
          <button @click="closeModals" class="btn-close"><i class="fas fa-times"></i></button>
        </div>
        
        <form @submit.prevent="handleSubmit">
          <div class="modal-body">
            <div class="form-group">
              <label>Nom Complet <span class="required">*</span></label>
              <div class="input-wrapper">
                <i class="fas fa-user icon-left"></i>
                <input 
                  v-model="form.full_name" 
                  type="text" 
                  required
                  placeholder="Ex: Maria Garcia"
                >
              </div>
            </div>

            <div class="form-group">
              <label>Correu Electrònic <span class="required">*</span></label>
              <div class="input-wrapper">
                <i class="fas fa-envelope icon-left"></i>
                <input 
                  v-model="form.email" 
                  type="email" 
                  required
                  placeholder="maria@exemple.com"
                >
              </div>
            </div>

            <!-- Password Field (Only for Create) -->
            <div v-if="!showEditModal" class="form-group">
              <label>Contrasenya <span class="required">*</span></label>
              <div class="input-wrapper">
                <i class="fas fa-lock icon-left"></i>
                <input 
                  v-model="form.password" 
                  :type="showPassword ? 'text' : 'password'" 
                  required
                  placeholder="••••••••"
                >
                <!-- Magic Generator Button -->
                <button type="button" class="btn-generate-password" @click="fillRandomPassword" title="Generar automàtica">
                  <i class="fas fa-magic"></i>
                </button>
                
                <button type="button" class="btn-toggle-password" @click="showPassword = !showPassword">
                  <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Especialitat</label>
                <div class="input-wrapper">
                  <i class="fas fa-briefcase icon-left"></i>
                  <input 
                    v-model="form.specialty" 
                    type="text" 
                    placeholder="Ex: Tecnologia"
                  >
                </div>
              </div>

              <div class="form-group">
                <label>Telèfon</label>
                <div class="input-wrapper">
                  <i class="fas fa-phone icon-left"></i>
                  <input 
                    v-model="form.phone" 
                    type="tel" 
                    placeholder="600 000 000"
                  >
                </div>
              </div>
            </div>

            <div class="form-group checkbox-group">
              <label class="toggle-switch">
                <div class="toggle-content">
                  <span class="toggle-label">Compte Actiu</span>
                  <span class="toggle-hint">Permetre l'accés d'aquest docent a la plataforma</span>
                </div>
                <input 
                  v-model="form.is_active" 
                  type="checkbox" 
                  class="toggle-input"
                >
                <div class="toggle-slider"></div>
              </label>
            </div>

            <div v-if="formError" class="error-message">
              <i class="fas fa-exclamation-circle"></i> {{ formError }}
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModals" class="btn-text">Cancel·lar</button>
            <button type="submit" class="btn-cta primary" :disabled="submitting">
              <i class="fas fa-save"></i>
              {{ submitting ? 'Guardant...' : 'Guardar Canvis' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal-card small">
        <div class="modal-header warning">
          <h3><i class="fas fa-exclamation-triangle"></i> Eliminar Docent</h3>
        </div>
        <div class="modal-body text-center">
          <p>Estàs a punt d'eliminar permanentment a <strong>{{ teacherToDelete?.full_name }}</strong>.</p>
          <p class="text-sm">Aquesta acció no es pot desfer.</p>
        </div>
        <div class="modal-footer centered">
          <button @click="showDeleteModal = false" class="btn-text">Cancel·lar</button>
          <button @click="handleDelete" class="btn-cta danger" :disabled="deleting">
            <i class="fas fa-trash-alt"></i> {{ deleting ? 'Eliminant...' : 'Confirmar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- RESET PASSWORD MODAL (Replicated from Admin) -->
    <div v-if="showPasswordModal" class="modal-overlay" @click.self="closePasswordModal">
      <div class="modal-card small">
        <div class="modal-header">
          <h3><i class="fas fa-key"></i> Nova Contrasenya</h3>
          <button @click="closePasswordModal" class="btn-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body text-center">
          <p class="mb-4">Contrasenya temporal generada per a<br><strong>{{ teacherToReset?.full_name }}</strong>:</p>
          
          <div class="password-display-box">
            <code>{{ newGeneratedPassword }}</code>
            <button @click="copyPassword" class="btn-icon" title="Copiar">
              <i class="fas fa-copy"></i>
            </button>
          </div>

          <p class="text-xs text-warning mt-4">
            <i class="fas fa-exclamation-triangle"></i>
            Aquesta contrasenya només es mostra una vegada.
          </p>
        </div>
        <div class="modal-footer centered">
          <button @click="closePasswordModal" class="btn-cta primary">
            <i class="fas fa-check"></i> Entès
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'

const authStore = useAuthStore()
const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000'

const teachers = ref([])
const loading = ref(false)
const searchQuery = ref('')

// Modals
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)


// Forms
const form = ref({
  full_name: '',
  email: '',
  password: '',
  specialty: '',
  phone: '',
  is_active: true
})

const showPassword = ref(false)
const formError = ref(null)
const submitting = ref(false)

const teacherToEdit = ref(null)
const teacherToDelete = ref(null)
const deleting = ref(false)

// Computed
const filteredTeachers = computed(() => {
  if (!searchQuery.value) return teachers.value
  
  const query = searchQuery.value.toLowerCase()
  return teachers.value.filter(t => 
    t.full_name.toLowerCase().includes(query) ||
    t.email.toLowerCase().includes(query) ||
    (t.specialty && t.specialty.toLowerCase().includes(query))
  )
})

const teacherToReset = ref(null)
const newGeneratedPassword = ref('')
const showPasswordModal = ref(false)

// Methods
function generatePassword() {
  const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
  let suffix = ""
  for (let i = 0; i < 10; i++) {
    suffix += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  return 'kairos-' + suffix
}

function fillRandomPassword() {
  form.value.password = generatePassword()
  showPassword.value = true // Show the password so they can see it
}

async function confirmResetPassword(teacher) {
  teacherToReset.value = teacher
  try {
    // Try to call API to reset password
    // NOTE: Assuming this endpoint exists based on Admin pattern
    const response = await axios.post(
      `${API_BASE}/api/center/teachers/${teacher.id}/reset-password`,
      {},
      { headers: { Authorization: `Bearer ${authStore.token}` } }
    )
    
    if (response.data.success) {
      newGeneratedPassword.value = response.data.password
      showPasswordModal.value = true
    } else {
      // Fallback or error handling
      console.error('Reset password failed', response.data)
      alert(response.data.message || 'Error al restablir la contrasenya')
    }
  } catch (error) {
    console.error('Error resetting password:', error)
    // Fallback: Client side generation + Update? 
    // For now, let's stick to the API attempt as requested "replicate admin function"
    alert('Error al connectar amb el servidor per restablir la contrasenya.')
  }
}

function copyPassword() {
  navigator.clipboard.writeText(newGeneratedPassword.value)
  // Optional: toast notification
}

function closePasswordModal() {
  showPasswordModal.value = false
  newGeneratedPassword.value = ''
  teacherToReset.value = null
}

function getInitials(name) {
  if (!name) return '??'
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
}

async function fetchTeachers() {
  loading.value = true
  try {
    const response = await axios.get(
      `${API_BASE}/api/center/teachers`,
      { 
        params: { center_id: authStore.user?.center_id },
        headers: { Authorization: `Bearer ${authStore.token}` } 
      }
    )
    
    if (response.data.success) {
      teachers.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching teachers:', error)
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  formError.value = null
  submitting.value = true

  try {
    if (!authStore.user?.center_id) {
        formError.value = "Sessió caducada o incompleta. Si us plau, tanca sessió i torna a entrar."
        submitting.value = false
        return
    }

    const url = showEditModal.value 
      ? `${API_BASE}/api/center/teachers/${teacherToEdit.value.id}`
      : `${API_BASE}/api/center/teachers`
    
    const method = showEditModal.value ? 'put' : 'post'
    
    const response = await axios[method](
      url,
      { 
        ...form.value, 
        center_id: authStore.user?.center_id 
      },
      { headers: { Authorization: `Bearer ${authStore.token}` } }
    )
    
    if (response.data.success) {
      await fetchTeachers()
      closeModals()
      
      // Show generated password if available (creation only)
      if (response.data.generated_password) {
        newGeneratedPassword.value = response.data.generated_password
        teacherToReset.value = response.data.data
        showPasswordModal.value = true
      }
    } else {
      formError.value = response.data.message
    }
  } catch (error) {
    formError.value = error.response?.data?.message || 'Error al guardar el docent'
  } finally {
    submitting.value = false
  }
}

function editTeacher(teacher) {
  teacherToEdit.value = teacher
  form.value = { ...teacher }
  showEditModal.value = true
}

function confirmDelete(teacher) {
  teacherToDelete.value = teacher
  showDeleteModal.value = true
}

async function handleDelete() {
  deleting.value = true
  try {
    const response = await axios.delete(
      `${API_BASE}/api/center/teachers/${teacherToDelete.value.id}`,
      { headers: { Authorization: `Bearer ${authStore.token}` } }
    )
    
    if (response.data.success) {
      await fetchTeachers()
      showDeleteModal.value = false
    }
  } catch (error) {
    console.error('Error deleting teacher:', error)
  } finally {
    deleting.value = false
  }
}

function openCreateModal() {
  form.value = {
    full_name: '',
    email: '',
    password: '',
    specialty: '',
    phone: '',
    is_active: true
  }
  teacherToEdit.value = null
  showEditModal.value = false
  showCreateModal.value = true
  formError.value = null
  
  // Auto-generate password matching user request screenshot behavior
  fillRandomPassword()
}

function closeModals() {
  showCreateModal.value = false
  showEditModal.value = false
  teacherToEdit.value = null
  form.value = {
    full_name: '',
    email: '',
    password: '',
    specialty: '',
    phone: '',
    is_active: true
  }
  formError.value = null
  showPassword.value = false
}

// Watch for user changes to fetch when ready
import { watch } from 'vue'

onMounted(() => {
  if (authStore.user?.center_id) {
    fetchTeachers()
  }
})

watch(() => authStore.user, (newUser) => {
  if (newUser?.center_id) {
    fetchTeachers()
  }
})
</script>

<style scoped>
.center-teachers-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

.btn-back-dash {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9rem;
  transition: color 0.2s;
  display: block;
  width: fit-content;
}

.btn-back-dash:hover {
  color: #C5A059;
}

.mb-2 { margin-bottom: 0.5rem; }

/* HEADER PREMIUM */
.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 3px solid #C5A059;
}

.header-content h1 {
  font-size: 2.2rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  letter-spacing: -0.02em;
}

.header-content h1 i {
  background: linear-gradient(135deg, #C5A059 0%, #B8905F 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.subtitle {
  color: #64748b;
  font-size: 1.1rem;
  margin: 0.5rem 0 0 0;
}

/* FILTERS & SEARCH */
.filters-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.search-group {
  position: relative;
  flex: 1;
  max-width: 450px;
}

.search-icon {
  position: absolute;
  left: 1.2rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 1.1rem;
}

.form-search {
  width: 100%;
  padding: 0.9rem 1rem 0.9rem 3rem;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: white;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
}

.form-search:focus {
  outline: none;
  border-color: #C5A059;
  box-shadow: 0 0 0 3px rgba(197, 160, 89, 0.15);
}

.stats-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1.2rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 50px;
  color: #64748b;
  font-weight: 600;
  font-size: 0.9rem;
}

/* CONTENT CARD */
.content-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
  overflow: hidden;
  border: 1px solid #f1f5f9;
}

/* TABLE STYLES */
.table-responsive {
  overflow-x: auto;
}

.kairos-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.kairos-table th {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.05em;
  padding: 1.2rem 1.5rem;
  border-bottom: 2px solid #e2e8f0;
}

.kairos-table td {
  padding: 1.2rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
  color: #334155;
}

.kairos-table tr:hover {
  background-color: #fcfcfc;
}

.kairos-table tr:last-child td {
  border-bottom: none;
}

/* CELL STYLES */
.user-cell {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.avatar-circle {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  color: #C5A059;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1rem;
  border: 2px solid #C5A059;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.user-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-weight: 600;
  color: #0f172a;
  font-size: 1rem;
}

.contact-cell {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  font-size: 0.9rem;
  color: #64748b;
}

.contact-item i {
  width: 18px;
  color: #94a3b8;
}

.badge-specialty {
  background: #eff6ff;
  color: #2563eb;
  padding: 0.4rem 0.8rem;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 0.8rem;
  border-radius: 50px;
  font-size: 0.85rem;
  font-weight: 600;
  border: 1px solid transparent;
}

.status-badge.active {
  background: #f0fdf4;
  color: #166534;
  border-color: #bbf7d0;
}

.status-badge.inactive {
  background: #fef2f2;
  color: #991b1b;
  border-color: #fecaca;
}

/* ACTIONS */
.action-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-icon:hover {
  background: #f8fafc;
  color: #C5A059;
  border-color: #C5A059;
  transform: translateY(-2px);
}

.btn-icon.delete:hover {
  color: #ef4444;
  border-color: #ef4444;
  background: #fef2f2;
}

/* BUTTONS CTA */
.btn-cta {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.8rem 1.6rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
}

.btn-cta.primary {
  background: linear-gradient(135deg, #C5A059 0%, #B8905F 100%);
  color: white;
  box-shadow: 0 4px 6px -1px rgba(197, 160, 89, 0.3);
}

.btn-cta.primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(197, 160, 89, 0.4);
}

.btn-cta.primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.btn-cta.secondary {
  background: white;
  border: 2px solid #C5A059;
  color: #C5A059;
}

.btn-cta.secondary:hover {
  background: #fffbf0;
}

.btn-cta.danger {
  background: #ef4444;
  color: white;
}

.btn-cta.danger:hover {
  background: #dc2626;
}

.btn-text {
  background: none;
  border: none;
  color: #64748b;
  font-weight: 600;
  cursor: pointer;
  padding: 0.8rem;
}

.btn-text:hover {
  color: #1e293b;
}

/* EMPTY & LOADING STATES */
.empty-state, .loading-state {
  text-align: center;
  padding: 4rem 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
}

.empty-icon {
  font-size: 3rem;
  color: #cbd5e1;
  background: #f8fafc;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-state p {
  color: #64748b;
  font-size: 1.1rem;
  margin: 0;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f1f5f9;
  border-top-color: #C5A059;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* MODAL STYLES */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(5px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-card {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 500px;
  max-height: 85vh; /* Reduced max-height */
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  animation: modal-slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  display: flex;
  flex-direction: column;
}

.modal-card form {
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow: hidden;
  min-height: 0;
}

.modal-header {
  padding: 1rem 2rem; /* Reduced padding */
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
  flex-shrink: 0;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 700;
}

.btn-close {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #64748b;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-close:hover {
  background: #f1f5f9;
  color: #ef4444;
  border-color: #ef4444;
}

.modal-body {
  padding: 1.5rem 2rem; /* Reduced padding */
  overflow-y: auto;
  flex: 1; /* Allow body to take available space */
}

/* INPUT FLOATING LABEL STYLE ALTERNATIVE - CLEANER */
.form-group {
  margin-bottom: 1rem; /* Reduced margin */
}

.form-group label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569; /* Slightly darker for better contrast */
  margin-bottom: 0.5rem;
  letter-spacing: 0.01em; /* Subtle tracking */
}

.input-wrapper {
  position: relative;
  transition: all 0.2s ease-in-out;
}

.input-wrapper i.icon-left {
  position: absolute;
  left: 1.1rem; /* Slightly more spacing */
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  transition: color 0.3s ease;
  pointer-events: none;
  font-size: 1rem;
}

.input-wrapper .btn-toggle-password {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  cursor: pointer;
  background: none;
  border: none;
  padding: 0.25rem;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.3s ease;
}

.input-wrapper .btn-toggle-password:hover {
  color: #C5A059; /* Gold hover effect */
  transform: translateY(-50%) scale(1.1); /* Subtle grow */
}

.input-wrapper input {
  width: 100%;
  padding: 0.9rem 1rem 0.9rem 3rem; /* Adjusted left padding */
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  font-size: 0.95rem;
  background: #f8fafc;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  color: #0f172a;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05); /* Subtle depth */
}

.input-wrapper input:hover {
  background: white;
  border-color: #cbd5e1;
}

.input-wrapper input:focus {
  outline: none;
  background: white;
  border-color: #C5A059;
  box-shadow: 0 0 0 4px rgba(197, 160, 89, 0.15); /* Premium gold glow */
}

/* Gold accent for icon when input is focused */
.input-wrapper input:focus + i.icon-left,
.input-wrapper input:focus ~ i.icon-left {
  color: #C5A059;
}

/* TOGGLE SWITCH - ROBUST FIX */
.checkbox-group {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px dashed #e2e8f0;
}

.toggle-switch {
  display: flex !important;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  padding: 0.25rem 0;
  width: 100%;
}

.toggle-content {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  padding-right: 1.5rem;
  flex: 1; 
}

.toggle-label {
  font-weight: 600;
  color: #0f172a;
  font-size: 0.9rem;
}

.toggle-hint {
  font-size: 0.8rem;
  color: #64748b;
  line-height: 1.3;
}

/* Password Generation & Display */
.btn-generate-password {
  position: absolute;
  right: 3rem; /* Positioned to the left of the eye icon */
  top: 50%;
  transform: translateY(-50%);
  color: #C5A059;
  cursor: pointer;
  background: none;
  border: none;
  padding: 0.25rem;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.btn-generate-password:hover {
  color: #B8905F;
  transform: translateY(-50%) scale(1.1);
}

.input-wrapper input {
  padding-right: 5rem !important; /* Space for both buttons */
}

/* Password Modal Styles */
.password-display-box {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin: 1.5rem 0;
  padding: 1.5rem;
  background: #f8fafc;
  border: 2px dashed #C5A059;
  border-radius: 12px;
}

.password-display-box code {
  font-size: 1.5rem;
  font-weight: 700;
  color: #C5A059;
  letter-spacing: 0.1em;
  font-family: monospace;
}

.text-warning {
  color: #f59e0b;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-weight: 600;
}

.centered {
  justify-content: center !important;
}

.mb-4 { margin-bottom: 1.5rem; }
.mt-4 { margin-top: 1.5rem; }
.text-xs { font-size: 0.85rem; }

.toggle-input {
  display: none;
}

/* Updated Slider Dimensions & Colors */
.toggle-slider {
  display: block; 
  width: 44px; /* Slightly smaller */
  min-width: 44px; 
  height: 24px;
  background-color: #cbd5e1;
  border-radius: 34px;
  position: relative;
  transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
  flex-shrink: 0;
}

.toggle-slider::before {
  content: "";
  position: absolute;
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  border-radius: 50%;
  transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  z-index: 2;
}

.toggle-input:checked + .toggle-slider {
  background-color: #C5A059;
}

.toggle-input:checked + .toggle-slider::before {
  transform: translateX(20px);
}

/* MODAL FOOTER */
.modal-footer {
  padding: 1.25rem 2rem; /* Reduced padding */
  background: #f8fafc;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  flex-shrink: 0;
}

.btn-text {
  padding: 0.8rem 1.5rem;
  color: #64748b;
  font-weight: 600;
  border-radius: 10px;
}

.btn-text:hover {
  background: #f1f5f9;
  color: #0f172a;
}

@media (max-width: 768px) {
  .header-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .filters-bar {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-group {
    max-width: 100%;
  }
  
  .form-row {
    flex-direction: column;
    gap: 0;
  }
}
</style>
