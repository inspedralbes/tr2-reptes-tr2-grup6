<template>
  <div class="admin-centres">
    <div class="page-header">
      <h1>🏫 Centres Educatius</h1>
      <p class="subtitle">Gestió de centres i coordinadors</p>
    </div>

    <div class="grid">
      <!-- FORMULARI CREACIÓ CENTRE -->
      <div class="card">
        <h2><i class="fas fa-building"></i> Afegir Centre</h2>
        <form @submit.prevent="create" class="form-create">
          <!-- Secció 1: Dades del Centre -->
          <div class="form-section">
            <h3>📍 Dades del Centre</h3>
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
          <div class="form-section">
            <h3>👤 Coordinador del Centre</h3>
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

          <button type="submit" :disabled="loading" class="btn-primary">
            <i class="fas fa-plus"></i> {{ loading ? 'Guardant...' : 'Crear Centre i Coordinador' }}
          </button>
          <p v-if="createMessage" :class="['message', createMessage.type]">
            {{ createMessage.text }}
          </p>
        </form>
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
                <button @click="deleteCenter(c.id)" class="btn-delete" title="Eliminar">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div v-else class="empty-state">
          <p>No hi ha centres registrats ancora.</p>
          <p class="hint">Afegeix el primer centre utilitzant el formulari de l'esquerra.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const loading = ref(false)
const centers = ref([])
const createMessage = ref(null)

const form = ref({
  name: '',
  code: '',
  address: '',
  coordinator_name: '',
  coordinator_email: '',
  coordinator_password: '',
  coordinator_password_confirm: ''
})

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
        'Content-Type': 'application/json'
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
    const response = await fetch('http://localhost:8000/api/centers')
    const data = await response.json()
    
    if (data.success && Array.isArray(data.data)) {
      centers.value = data.data
    }
  } catch (err) {
    console.error('Error fetching centers:', err)
  }
}

const editCenter = (center) => {
  alert('Funcionalitat de edició per implementar')
}

const deleteCenter = async (id) => {
  if (!confirm('Segur que vols eliminar aquest centre?')) return
  
  try {
    const response = await fetch(`http://localhost:8000/api/centers/${id}`, {
      method: 'DELETE'
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

onMounted(() => {
  fetchCenters()
})
</script>

<style scoped>
/* Container Principal */
.admin-centres {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  background: linear-gradient(135deg, #f8f9fa 0%, #f3f5f7 100%);
  min-height: 100vh;
}

/* Header */
.page-header {
  margin-bottom: 3rem;
  padding-bottom: 2rem;
  border-bottom: 3px solid #d4af37;
}

.page-header h1 {
  margin: 0;
  color: #1a1a1a;
  font-size: 2.5rem;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.subtitle {
  color: #666;
  margin-top: 0.75rem;
  font-size: 1.05rem;
  font-weight: 500;
}

/* Grid Layout */
.grid {
  display: grid;
  grid-template-columns: 1fr 1.5fr;
  gap: 2rem;
}

@media (max-width: 1024px) {
  .grid {
    grid-template-columns: 1fr;
  }
}

/* Card Container */
.card {
  background: white;
  border-radius: 12px;
  padding: 2.5rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  border: 1px solid #f0f0f0;
}

.card h2 {
  margin: 0 0 2rem 0;
  color: #1a1a1a;
  font-size: 1.5rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.card h2 i {
  color: #d4af37;
  font-size: 1.4rem;
}

/* Form Styles */
.form-create {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-section {
  padding-bottom: 1.5rem;
  border-bottom: 2px solid #f0f0f0;
}

.form-section:last-of-type {
  border-bottom: none;
  padding-bottom: 0;
}

.form-section h3 {
  color: #1a1a1a;
  font-size: 1.1rem;
  margin: 0 0 1.2rem 0;
  font-weight: 600;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1.2rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-group label {
  font-weight: 600;
  color: #1a1a1a;
  font-size: 0.95rem;
}

.required {
  color: #ef4444;
  font-weight: 700;
}

/* Input Fields */
input, textarea, select {
  padding: 0.85rem 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-family: inherit;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  background: #fafafa;
}

input::placeholder, textarea::placeholder {
  color: #aaa;
}

input:focus, textarea:focus, select:focus {
  outline: none;
  border-color: #d4af37;
  background: white;
  box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.15);
}

textarea {
  resize: vertical;
  min-height: 80px;
}

/* Primary Button */
.btn-primary {
  background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%);
  color: white;
  border: none;
  padding: 1rem 2rem;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  margin-top: 1rem;
  font-size: 1rem;
  box-shadow: 0 4px 12px rgba(212, 175, 55, 0.25);
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #c5a059 0%, #a6820f 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(212, 175, 55, 0.35);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Messages */
.message {
  padding: 1rem 1.2rem;
  border-radius: 8px;
  font-weight: 600;
  margin-top: 1.5rem;
  text-align: center;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.message.success {
  background: #ecfdf5;
  color: #047857;
  border: 2px solid #10b981;
}

.message.error {
  background: #fef2f2;
  color: #991b1b;
  border: 2px solid #ef4444;
}

/* Data Table */
.data-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1.5rem;
  font-size: 0.95rem;
}

.data-table th {
  text-align: left;
  padding: 1.2rem 1rem;
  background: linear-gradient(135deg, #fdf8f0 0%, #faf5f0 100%);
  border-bottom: 3px solid #d4af37;
  color: #1a1a1a;
  font-weight: 700;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.data-table td {
  padding: 1.2rem 1rem;
  border-bottom: 1px solid #e0e0e0;
}

.data-table tbody tr {
  transition: all 0.2s ease;
}

.data-table tbody tr:hover {
  background: linear-gradient(90deg, #fdf8f0 0%, transparent 100%);
}

.id {
  color: #1a1a1a;
  font-weight: 700;
  width: 50px;
}

.address {
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.email {
  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #0066cc;
}

/* Actions */
.actions {
  display: flex;
  gap: 0.75rem;
  white-space: nowrap;
}

.btn-edit, .btn-delete {
  padding: 0.65rem 1rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
  font-weight: 600;
}

.btn-edit {
  background: #dbeafe;
  color: #0066cc;
  border: 1px solid #bfdbfe;
}

.btn-edit:hover {
  background: #0066cc;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
}

.btn-delete {
  background: #fee2e2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.btn-delete:hover {
  background: #dc2626;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: #666;
}

.empty-state p {
  margin: 0.75rem 0;
  font-size: 1rem;
}

.hint {
  font-size: 0.9rem;
  color: #999;
  font-style: italic;
}

@media (max-width: 768px) {
  .admin-centres {
    padding: 1.5rem;
  }

  .page-header h1 {
    font-size: 2rem;
  }

  .grid {
    grid-template-columns: 1fr;
  }

  .card {
    padding: 1.5rem;
  }

  .data-table th, .data-table td {
    padding: 0.75rem 0.5rem;
    font-size: 0.85rem;
  }

  .address, .email {
    max-width: 120px;
  }
}
</style>
