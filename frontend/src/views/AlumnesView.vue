<template>
  <MainLayout>
    <div class="alumnes-view container-main">
      <div class="page-header">
        <h1 class="page-title">Gestió d'Alumnes</h1>
        <p class="page-subtitle">Gestiona la llista d'alumnes per a les teves sol·licituds de tallers</p>
      </div>
      
      <!-- Selector de Sol·licitud -->
      <div class="card mb-6">
        <div class="card-header">Selecciona una Sol·licitud</div>
        <div class="card-body">
          <select v-model="selectedSollicitudId" @change="loadAlumnes" class="input">
            <option value="">-- Selecciona una sol·licitud --</option>
            <option 
              v-for="sol in sollicituds" 
              :key="sol.id" 
              :value="sol.id"
            >
              {{ sol.taller_nom }} - {{ sol.estat }}
            </option>
          </select>
        </div>
      </div>
      
      <div v-if="selectedSollicitudId">
        <!-- Accions -->
        <div class="actions-bar">
          <button @click="showAddForm = true" class="btn btn-primary">
            + Afegir Alumne
          </button>
          <button @click="showImportCSV = true" class="btn btn-secondary">
            📥 Importar CSV
          </button>
          <button @click="exportAlumnes" class="btn btn-secondary">
            📤 Exportar
          </button>
        </div>
        
        <!-- Formulari d'Alta Individual -->
        <div v-if="showAddForm" class="card mb-6 fade-in">
          <div class="card-header">
            Afegir Alumne
            <button @click="showAddForm = false" class="close-btn">✕</button>
          </div>
          <div class="card-body">
            <form @submit.prevent="addAlumne" class="alumne-form">
              <div class="form-row">
                <div class="form-group">
                  <label class="label">Nom *</label>
                  <input v-model="newAlumne.nom" type="text" class="input" required />
                </div>
                <div class="form-group">
                  <label class="label">Cognoms *</label>
                  <input v-model="newAlumne.cognoms" type="text" class="input" required />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="label">Curs</label>
                  <input v-model="newAlumne.curs" type="text" class="input" placeholder="1r ESO" />
                </div>
                <div class="form-group">
                  <label class="label">Grup</label>
                  <input v-model="newAlumne.grup" type="text" class="input" placeholder="A" />
                </div>
              </div>
              <div class="form-group">
                <label class="label">Email</label>
                <input v-model="newAlumne.email" type="email" class="input" />
              </div>
              <div class="form-actions">
                <button type="submit" class="btn btn-primary" :disabled="saving">
                  {{ saving ? 'Guardant...' : 'Afegir Alumne' }}
                </button>
                <button type="button" @click="cancelAdd" class="btn btn-secondary">
                  Cancel·lar
                </button>
              </div>
            </form>
          </div>
        </div>
        
        <!-- Importació CSV -->
        <div v-if="showImportCSV" class="card mb-6 fade-in">
          <div class="card-header">
            Importar CSV
            <button @click="showImportCSV = false" class="close-btn">✕</button>
          </div>
          <div class="card-body">
            <p class="mb-4">Format CSV: <code>nom,cognoms,curs,grup,email</code></p>
            <textarea 
              v-model="csvData" 
              class="input" 
              rows="10" 
              placeholder="Marc,García López,1r ESO,A,marc@exemple.cat&#10;Laura,Martínez Sánchez,1r ESO,A,laura@exemple.cat"
            ></textarea>
            <div class="form-actions mt-4">
              <button @click="importCSV" class="btn btn-primary" :disabled="importing">
                {{ importing ? 'Important...' : 'Importar Alumnes' }}
              </button>
              <button @click="showImportCSV = false" class="btn btn-secondary">
                Cancel·lar
              </button>
            </div>
          </div>
        </div>
        
        <!-- Llista d'Alumnes -->
        <div class="card">
          <div class="card-header">
            Llista d'Alumnes ({{ alumnes.length }})
          </div>
          <div class="card-body">
            <div v-if="loading" class="loading-state">
              <div class="spinner"></div>
              <p>Carregant alumnes...</p>
            </div>
            
            <div v-else-if="alumnes.length === 0" class="empty-state">
              <p>No hi ha alumnes afegits encara</p>
            </div>
            
            <table v-else class="table">
              <thead>
                <tr>
                  <th>Nom</th>
                  <th>Cognoms</th>
                  <th>Curs</th>
                  <th>Grup</th>
                  <th>Email</th>
                  <th>Accions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="alumne in alumnes" :key="alumne.id">
                  <td>{{ alumne.nom }}</td>
                  <td>{{ alumne.cognoms }}</td>
                  <td>{{ alumne.curs || '-' }}</td>
                  <td>{{ alumne.grup || '-' }}</td>
                  <td>{{ alumne.email || '-' }}</td>
                  <td>
                    <button @click="deleteAlumne(alumne.id)" class="btn-icon btn-danger" title="Eliminar">
                      🗑️
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import MainLayout from '../layouts/MainLayout.vue'


const sollicituds = ref([])
const selectedSollicitudId = ref('')
const alumnes = ref([])
const loading = ref(false)
const saving = ref(false)
const importing = ref(false)
const showAddForm = ref(false)
const showImportCSV = ref(false)
const csvData = ref('')

const newAlumne = ref({
  nom: '',
  cognoms: '',
  curs: '',
  grup: '',
  email: ''
})

onMounted(() => {
  loadSollicituds()
})

async function loadSollicituds() {
  try {
    const response = await fetch('http://localhost:8000/api/mis_solicitudes.php', {
      credentials: 'include'
    })
    const data = await response.json()
    if (data.success) {
      sollicituds.value = data.sollicituds || []
    }
  } catch (error) {
    console.error('Error loading sollicituds:', error)
  }
}

async function loadAlumnes() {
  if (!selectedSollicitudId.value) return
  
  loading.value = true
  try {
    const response = await fetch(
      `http://localhost:8000/api/alumnes.php?sollicitud_id=${selectedSollicitudId.value}`,
      { credentials: 'include' }
    )
    const data = await response.json()
    if (data.success) {
      alumnes.value = data.alumnes || []
    }
  } catch (error) {
    console.error('Error loading alumnes:', error)
  } finally {
    loading.value = false
  }
}

async function addAlumne() {
  saving.value = true
  try {
    const response = await fetch('http://localhost:8000/api/alumnes.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        sollicitud_id: selectedSollicitudId.value,
        ...newAlumne.value
      })
    })
    
    const data = await response.json()
    if (data.success) {
      await loadAlumnes()
      cancelAdd()
      alert('Alumne afegit correctament!')
    } else {
      alert('Error: ' + (data.error || 'No s\'ha pogut afegir l\'alumne'))
    }
  } catch (error) {
    console.error('Error adding alumne:', error)
    alert('Error al afegir l\'alumne')
  } finally {
    saving.value = false
  }
}

function cancelAdd() {
  showAddForm.value = false
  newAlumne.value = { nom: '', cognoms: '', curs: '', grup: '', email: '' }
}

async function importCSV() {
  if (!csvData.value.trim()) {
    alert('Si us plau, introdueix dades CSV')
    return
  }
  
  importing.value = true
  try {
    const lines = csvData.value.trim().split('\n')
    const alumnesArray = lines.map(line => {
      const [nom, cognoms, curs, grup, email] = line.split(',').map(s => s.trim())
      return { nom, cognoms, curs, grup, email }
    })
    
    const response = await fetch('http://localhost:8000/api/alumnes.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        sollicitud_id: selectedSollicitudId.value,
        alumnes: alumnesArray
      })
    })
    
    const data = await response.json()
    if (data.success) {
      await loadAlumnes()
      showImportCSV.value = false
      csvData.value = ''
      alert(`${data.inserted} alumnes importats correctament!`)
    } else {
      alert('Error: ' + (data.error || 'No s\'ha pogut importar'))
    }
  } catch (error) {
    console.error('Error importing CSV:', error)
    alert('Error al importar el CSV')
  } finally {
    importing.value = false
  }
}

async function deleteAlumne(id) {
  if (!confirm('Estàs segur que vols eliminar aquest alumne?')) return
  
  try {
    const response = await fetch(`http://localhost:8000/api/alumnes.php?id=${id}`, {
      method: 'DELETE',
      credentials: 'include'
    })
    
    const data = await response.json()
    if (data.success) {
      await loadAlumnes()
      alert('Alumne eliminat correctament')
    } else {
      alert('Error: ' + (data.error || 'No s\'ha pogut eliminar'))
    }
  } catch (error) {
    console.error('Error deleting alumne:', error)
    alert('Error al eliminar l\'alumne')
  }
}

function exportAlumnes() {
  // TODO: Implementar exportació a CSV/PDF
  alert('Funcionalitat d\'exportació en desenvolupament')
}
</script>

<script>
export default {
  name: 'AlumnesView'
}
</script>

<style scoped>
.mb-6 {
  margin-bottom: 1.5rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

.mt-4 {
  margin-top: 1rem;
}

.actions-bar {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.close-btn {
  background: none;
  border: none;
  color: #6B7280;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0;
  margin-left: auto;
}

.close-btn:hover {
  color: #EF4444;
}

.alumne-form {
  max-width: 800px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.btn-icon {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 0.375rem;
  transition: background-color 0.2s;
}

.btn-danger:hover {
  background-color: #FEE2E2;
}

.loading-state,
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: #6B7280;
}

code {
  background-color: #F3F4F6;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-family: monospace;
  font-size: 0.875rem;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .actions-bar {
    flex-direction: column;
  }
}
</style>
