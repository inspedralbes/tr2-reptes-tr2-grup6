<template>
  <div class="admin-centres">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-school"></i> Centres Educatius</h1>
        <p class="subtitle">Gestió de centres i coordinadors</p>
      </div>
      <div class="header-actions">
        <button class="btn-cta" @click="openModal">
          <i class="fas fa-plus"></i>
          Crear centre i coordinador
        </button>
      </div>
    </div>

    <!-- MODAL CREAR/EDITAR CENTRE -->
    <div v-if="showModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>
            <i :class="editMode ? 'fas fa-edit' : 'fas fa-building'"></i> 
            {{ editMode ? 'Editar Centre' : 'Crear Centre i Coordinador' }}
          </h2>
          <button class="btn-close" @click="closeModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="editMode ? updateCenter() : create()" class="form-create">
            <!-- Secció 1: Dades del Centre -->
            <div class="form-section">
              <h3><i class="fas fa-map-marker-alt"></i> Dades del Centre</h3>
              <div class="form-group">
                <label>Nom del Centre <span class="required">*</span></label>
                <input v-model="form.name" type="text" placeholder="Ex: IES Institut Gràfic" required />
              </div>
              <div class="form-group">
                <label>Codi del Centre <span class="required">*</span></label>
                <input v-model="form.code" type="text" placeholder="Ex: C001" required />
              </div>
              <div class="form-group">
                <label>Adreça <span class="required">*</span></label>
                <textarea v-model="form.address" placeholder="Ex: Carrer Principal 123, 08002 Barcelona" rows="2" required></textarea>
              </div>
            </div>

            <!-- Secció 2: Dades del Coordinador -->
            <div class="form-section" v-if="!editMode">
              <h3><i class="fas fa-user-tie"></i> Coordinador del Centre</h3>
              <div class="form-group">
                <label>Nom del Coordinador <span class="required">*</span></label>
                <input v-model="form.coordinator_name" type="text" placeholder="Ex: Joan Martí" required />
              </div>
              <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input v-model="form.coordinator_email" type="email" placeholder="Ex: coordinador@instituit.cat" required />
              </div>
              <div class="form-group">
                <label>Contrasenya <span class="required">*</span></label>
                <input v-model="form.coordinator_password" type="password" placeholder="Mínimo 6 caràcters" required />
              </div>
              <div class="form-group">
                <label>Repetir Contrasenya <span class="required">*</span></label>
                <input v-model="form.coordinator_password_confirm" type="password" placeholder="Repeteix la contrasenya" required />
              </div>
            </div>
            
            <!-- Info del coordinador en mode edició -->
            <div class="form-section" v-if="editMode && editingCenter">
              <h3><i class="fas fa-user-tie"></i> Coordinador Assignat</h3>
              <div class="info-readonly">
                <p><strong>Nom:</strong> {{ editingCenter.coordinator_name || '—' }}</p>
                <p><strong>Email:</strong> {{ editingCenter.coordinator_email || '—' }}</p>
              </div>
            </div>

            <p v-if="createMessage" :class="['message', createMessage.type]">
              {{ createMessage.text }}
            </p>

            <div class="modal-actions">
              <button type="button" @click="closeModal" class="btn-secondary">
                <i class="fas fa-times"></i> Cancel·lar
              </button>
              <button type="submit" :disabled="loading" class="btn-primary">
                <i class="fas fa-check"></i> 
                {{ loading ? 'Guardant...' : (editMode ? 'Actualitzar Centre' : 'Crear Centre i Coordinador') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- LLISTA DE CENTRES -->
    <div class="card">
      <h2><i class="fas fa-list"></i> Llista de Centres ({{ centers.length }})</h2>
        <table class="data-table" v-if="centers.length > 0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Codi</th>
              <th>Nom</th>
              <th>Adreça</th>
              <th>Coordinador</th>
              <th>Email</th>
              <th>Accions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in centers" :key="c.id">
              <td class="id">{{ c.id }}</td>
              <td>{{ c.code }}</td>
              <td>{{ c.name }}</td>
              <td class="address">{{ c.address }}</td>
              <td>{{ c.coordinator_name || '—' }}</td>
              <td class="email">{{ c.coordinator_email || '—' }}</td>
              <td class="actions">
                <button @click="editCenter(c)" class="btn-edit" title="Editar">
                  <i class="fas fa-pen"></i>
                </button>
                <button @click="resetPassword(c)" class="btn-warning" title="Restablir contrasenya" v-if="c.coordinator_email">
                  <i class="fas fa-key"></i>
                </button>
                <button @click="deleteCenter(c.id)" class="btn-delete" title="Eliminar">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div v-else class="empty-state">
          <p><i class="fas fa-inbox"></i> No hi ha centres registrats ancora.</p>
          <p class="hint">Afegeix el primer centre utilitzant el botó flotant inferior dret.</p>
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
            <p>Contrasenya temporal generada per a <strong>{{ currentCenter?.coordinator_name }}</strong>:</p>
            <div class="password-box">
              <code>{{ newPassword }}</code>
              <button @click="copyPassword" class="btn-copy" title="Copiar">
                <i class="fas fa-copy"></i>
              </button>
            </div>
            <p class="warning-text">
              <i class="fas fa-exclamation-triangle"></i>
              Aquesta contrasenya només es mostra una vegada. Guarda-la o comparteix-la amb el coordinador.
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
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()

const loading = ref(false)
const centers = ref([])
const createMessage = ref(null)
const editMode = ref(false)
const editingCenter = ref(null)
const showModal = ref(false)
const showPasswordModal = ref(false)
const currentCenter = ref(null)
const newPassword = ref('')

const form = ref({
  name: '',
  code: '',
  address: '',
  coordinator_name: '',
  coordinator_email: '',
  coordinator_password: '',
  coordinator_password_confirm: ''
})

const openModal = () => {
  editMode.value = false
  editingCenter.value = null
  showModal.value = true
  createMessage.value = null
  resetForm()
}

const closeModal = () => {
  showModal.value = false
  editMode.value = false
  editingCenter.value = null
  resetForm()
  createMessage.value = null
}

const resetForm = () => {
  form.value = {
    name: '',
    code: '',
    address: '',
    coordinator_name: '',
    coordinator_email: '',
    coordinator_password: '',
    coordinator_password_confirm: ''
  }
}

const validateForm = () => {
  if (!form.value.name || !form.value.code || !form.value.address) {
    createMessage.value = { type: 'error', text: 'Camp obligatori falta: Nom, Codi o Adreça' }
    return false
  }
  if (!form.value.coordinator_name || !form.value.coordinator_email) {
    createMessage.value = { type: 'error', text: 'Camp obligatori falta: Nom o Email del Coordinador' }
    return false
  }
  if (!form.value.coordinator_password) {
    createMessage.value = { type: 'error', text: 'Cal definir contrasenya' }
    return false
  }
  if (form.value.coordinator_password.length < 6) {
    createMessage.value = { type: 'error', text: 'Contrasenya ha de tenir mínim 6 caràcters' }
    return false
  }
  if (form.value.coordinator_password !== form.value.coordinator_password_confirm) {
    createMessage.value = { type: 'error', text: 'Les contrasenyes no coincideixen' }
    return false
  }
  return true
}

const create = async () => {
  createMessage.value = null
  
  if (!validateForm()) {
    return
  }

  loading.value = true
  
  try {
    const response = await fetch('http://localhost:8000/api/centers', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${auth.token}`
      },
      body: JSON.stringify({
        name: form.value.name,
        code: form.value.code,
        address: form.value.address,
        coordinator_name: form.value.coordinator_name,
        coordinator_email: form.value.coordinator_email,
        coordinator_password: form.value.coordinator_password
      })
    })

    const data = await response.json()
    
    if (data.success || response.ok) {
      createMessage.value = { type: 'success', text: '✓ Centre i coordinador creat correctament!' }
      resetForm()
      await fetchCenters()
      
      setTimeout(() => {
        createMessage.value = null
      }, 3000)
    } else {
      createMessage.value = { type: 'error', text: data.message || 'Error creant centre' }
    }
  } catch (err) {
    createMessage.value = { type: 'error', text: 'Error: ' + err.message }
  } finally {
    loading.value = false
  }
}

const fetchCenters = async () => {
  try {
    const response = await fetch('http://localhost:8000/api/centers', {
      headers: {
        'Authorization': `Bearer ${auth.token}`
      }
    })
    const data = await response.json()
    
    if (data.success && Array.isArray(data.data)) {
      centers.value = data.data
    }
  } catch (err) {
    console.error('Error fetching centers:', err)
  }
}

const editCenter = (center) => {
  editMode.value = true
  editingCenter.value = center
  form.value = {
    name: center.name,
    code: center.code,
    address: center.address,
    coordinator_name: '',
    coordinator_email: '',
    coordinator_password: '',
    coordinator_password_confirm: ''
  }
  showModal.value = true
  createMessage.value = null
}

const updateCenter = async () => {
  createMessage.value = null
  
  if (!form.value.name || !form.value.code || !form.value.address) {
    createMessage.value = { type: 'error', text: 'Camp obligatori falta: Nom, Codi o Adreça' }
    return
  }

  loading.value = true
  
  try {
    const response = await fetch(`http://localhost:8000/api/centers/${editingCenter.value.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${auth.token}`
      },
      body: JSON.stringify({
        name: form.value.name,
        code: form.value.code,
        address: form.value.address
      })
    })

    const data = await response.json()
    
    if (data.success || response.ok) {
      createMessage.value = { type: 'success', text: '✓ Centre actualitzat correctament!' }
      await fetchCenters()
      
      setTimeout(() => {
        closeModal()
      }, 1500)
    } else {
      createMessage.value = { type: 'error', text: data.message || 'Error actualitzant centre' }
    }
  } catch (err) {
    createMessage.value = { type: 'error', text: 'Error: ' + err.message }
  } finally {
    loading.value = false
  }
}

const deleteCenter = async (id) => {
  if (!confirm('Segur que vols eliminar aquest centre?')) return
  
  try {
    const response = await fetch(`http://localhost:8000/api/centers/${id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${auth.token}`
      }
    })
    
    const data = await response.json()
    if (data.success) {
      createMessage.value = { type: 'success', text: '✓ Centre eliminat' }
      await fetchCenters()
    } else {
      createMessage.value = { type: 'error', text: 'Error eliminant centre' }
    }
  } catch (err) {
    createMessage.value = { type: 'error', text: 'Error: ' + err.message }
  }
}

const resetPassword = async (center) => {
  currentCenter.value = center
  
  if (!center.coordinator_email) {
    alert('Aquest centre no té un coordinador assignat')
    return
  }
  
  try {
    // Cridar directament l'endpoint de reset per centre
    const response = await fetch(`http://localhost:8000/api/centers/${center.id}/reset-password`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token}`
      }
    })
    const data = await response.json()
    
    if (data.success) {
      newPassword.value = data.password
      showPasswordModal.value = true
    } else {
      alert(data.message || 'Error restablint contrasenya')
    }
  } catch (err) {
    alert('Error restablint contrasenya: ' + err.message)
  }
}

const copyPassword = () => {
  navigator.clipboard.writeText(newPassword.value)
  alert('Contrasenya copiada al portapapers')
}

onMounted(() => {
  fetchCenters()
})
</script>

<style scoped>
.admin-centres {
  padding: 2rem 2.5rem;
  background: #f4f6fb;
  min-height: 100vh;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-bottom: 1.2rem;
  border-bottom: 3px solid #c59d32;
  margin-bottom: 1.5rem;
}

.page-header h1 {
  margin: 0;
  color: #1f2937;
  font-size: 2.2rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.subtitle {
  margin: 0.35rem 0 0;
  color: #6b7280;
  font-size: 1rem;
  font-weight: 500;
}

.header-actions {
  display: flex;
  align-items: center;
}

.btn-cta {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  background: linear-gradient(135deg, #d6b14c 0%, #b88923 100%);
  color: #fff;
  border: none;
  padding: 0.85rem 1.4rem;
  border-radius: 12px;
  font-weight: 700;
  font-size: 0.98rem;
  cursor: pointer;
  box-shadow: 0 10px 24px rgba(184, 137, 35, 0.35);
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-cta:hover { transform: translateY(-1px); box-shadow: 0 14px 30px rgba(184,137,35,0.4); }
.btn-cta:active { transform: translateY(0); }
.btn-cta i { font-size: 1rem; }

.card {
  background: #fff;
  border-radius: 14px;
  padding: 2rem;
  border: 1px solid #e5e7eb;
  box-shadow: 0 15px 45px rgba(15, 23, 42, 0.08);
}

.card h2 {
  margin: 0 0 1.5rem;
  color: #1f2937;
  font-size: 1.4rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.7rem;
}

.card h2 i { color: #c59d32; }

.form-create { display: flex; flex-direction: column; gap: 1.4rem; }

.form-section { padding-bottom: 1.3rem; border-bottom: 1px solid #e5e7eb; }
.form-section:last-of-type { border-bottom: none; padding-bottom: 0; }
.form-section h3 { margin: 0 0 1rem; font-size: 1.02rem; color: #1f2937; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; }

.info-readonly { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.9rem 1rem; }
.info-readonly p { margin: 0.35rem 0; color: #475569; }
.info-readonly strong { color: #1f2937; margin-right: 0.35rem; }

.form-group { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1rem; }
.form-group label { font-weight: 600; color: #1f2937; font-size: 0.95rem; }
.required { color: #dc2626; font-weight: 700; }

input, textarea, select {
  padding: 0.85rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.95rem;
  background: #f9fafb;
  transition: all 0.2s ease;
}

input::placeholder, textarea::placeholder { color: #9ca3af; }
input:focus, textarea:focus, select:focus {
  outline: none;
  border-color: #c59d32;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(197, 157, 50, 0.16);
}
textarea { resize: vertical; min-height: 90px; }

.message { padding: 0.9rem 1rem; border-radius: 10px; font-weight: 600; margin-top: 0.5rem; text-align: center; }
.message.success { background: #ecfdf3; color: #166534; border: 1px solid #34d399; }
.message.error { background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; }

.modal-actions { display: flex; gap: 0.75rem; justify-content: flex-end; align-items: center; margin-top: 1rem; }

.btn-secondary, .btn-primary {
  border: none;
  border-radius: 12px;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  height: 48px;
  min-width: 160px;
  padding: 0 1.2rem;
}

.btn-secondary { background: #e5e7eb; color: #111827; }
.btn-secondary:hover { background: #d1d5db; transform: translateY(-1px); }

.btn-primary { background: linear-gradient(135deg, #d6b14c 0%, #b88923 100%); color: #fff; box-shadow: 0 10px 24px rgba(184,137,35,0.32); }
.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 14px 32px rgba(184,137,35,0.4); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

.data-table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.95rem; }
.data-table thead th {
  text-align: left;
  padding: 1rem 0.85rem;
  background: #fbf7ef;
  border-bottom: 3px solid #c59d32;
  color: #1f2937;
  font-weight: 700;
  font-size: 0.82rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.data-table td { padding: 1rem 0.85rem; border-bottom: 1px solid #e5e7eb; color: #111827; }
.data-table tbody tr:hover { background: #fdf9f2; }
.id { font-weight: 700; color: #1f2937; width: 50px; }
.address, .email { max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.email { color: #1d4ed8; }

.actions { display: flex; gap: 0.6rem; }
.btn-edit, .btn-delete, .btn-warning {
  padding: 0.55rem 0.9rem;
  border-radius: 8px;
  border: 1px solid transparent;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-edit { background: #e0ecff; color: #1d4ed8; border-color: #bfdbfe; }
.btn-edit:hover { background: #1d4ed8; color: #fff; box-shadow: 0 8px 18px rgba(29,78,216,0.25); transform: translateY(-1px); }

.btn-warning { background: #fef3c7; color: #f59e0b; border-color: #fde68a; }
.btn-warning:hover { background: #f59e0b; color: #fff; box-shadow: 0 8px 18px rgba(245,158,11,0.25); transform: translateY(-1px); }

.btn-delete { background: #ffe4e6; color: #dc2626; border-color: #fecdd3; }
.btn-delete:hover { background: #dc2626; color: #fff; box-shadow: 0 8px 18px rgba(220,38,38,0.25); transform: translateY(-1px); }

.empty-state { text-align: center; padding: 2.5rem 1.5rem; color: #6b7280; }
.empty-state p { margin: 0.6rem 0; }
.hint { color: #9ca3af; font-style: italic; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-content { background: #fff; border-radius: 14px; max-width: 620px; width: 92%; max-height: 90vh; overflow-y: auto; box-shadow: 0 18px 60px rgba(0,0,0,0.18); }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 1px solid #e5e7eb; background: #fbf7ef; }
.modal-header h2 { margin: 0; font-size: 1.25rem; color: #1f2937; display: flex; align-items: center; gap: 0.6rem; }
.modal-header h2 i { color: #c59d32; }
.btn-close { background: transparent; border: none; font-size: 1.35rem; color: #9ca3af; cursor: pointer; transition: all 0.2s ease; }
.btn-close:hover { color: #111827; transform: rotate(90deg); }
.modal-body { padding: 1.5rem; }

/* Modal Small (Password) */
.modal-small { max-width: 500px; }

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

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
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
  box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
}

.btn-primary:hover {
  transform: translateY(-3px);
  background: linear-gradient(135deg, #e0c160 0%, #d4af5e 100%);
  box-shadow: 0 8px 20px rgba(212, 175, 55, 0.5);
}

@media (max-width: 900px) {
  .admin-centres { padding: 1.25rem; }
  .page-header { flex-direction: column; align-items: flex-start; }
  .header-actions { width: 100%; }
  .btn-cta { width: 100%; justify-content: center; }
  .card { padding: 1.4rem; }
  .data-table th, .data-table td { padding: 0.75rem 0.55rem; font-size: 0.86rem; }
  .actions { flex-direction: column; }
  .btn-secondary, .btn-primary { width: 100%; }
}
</style>
