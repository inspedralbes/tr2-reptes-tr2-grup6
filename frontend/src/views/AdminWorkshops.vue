<script setup>
import { ref, onMounted } from 'vue'
import { useAdminStore } from '@/stores/admin'

const adminStore = useAdminStore()

// Edit dialog state (modal)
const editDialogVisible = ref(false)
const editForm = ref({
  id: null,
  name: '',
  description: '',
  duration: '',
  instructor: '',
  max_capacity: 15,
  available_slots: 15,
  location: '',
  theme: '',
  course: '',
  allowed_days: [],
  time_slots: [],
  images: [],
  start_date: '',
  end_date: ''
})

const editNewSlot = ref('')
const editNewImage = ref('')
const editImageFileInput = ref(null)

const openEditWorkshop = (workshop) => {
  editForm.value = {
    id: workshop.id,
    name: workshop.name || '',
    description: workshop.description || '',
    duration: workshop.duration || '',
    instructor: workshop.instructor || '',
    max_capacity: workshop.max_capacity || 15,
    available_slots: workshop.available_slots ?? workshop.max_capacity ?? 15,
    location: workshop.location || '',
    theme: workshop.theme || '',
    course: workshop.course || '',
    allowed_days: Array.isArray(workshop.allowed_days) ? [...workshop.allowed_days] : [],
    time_slots: Array.isArray(workshop.time_slots) ? [...workshop.time_slots] : [],
    images: Array.isArray(workshop.images) ? [...workshop.images] : [],
    start_date: workshop.start_date || '',
    end_date: workshop.end_date || ''
  }
  editDialogVisible.value = true
}

const closeEditDialog = () => {
  editDialogVisible.value = false
}

const addEditTimeSlot = () => {
  const v = (editNewSlot.value || '').trim()
  if (!v) return
  editForm.value.time_slots.push(v)
  editNewSlot.value = ''
}
const removeEditTimeSlot = (idx) => {
  editForm.value.time_slots.splice(idx, 1)
}

const addEditImage = () => {
  const v = (editNewImage.value || '').trim()
  if (!v) return
  editForm.value.images.push(v)
  editNewImage.value = ''
}

const onEditImageFileSelected = (event) => {
  const file = event.target.files?.[0]
  if (file) {
    editForm.value.images.push(file)
    editImageFileInput.value.value = ''
  }
}

const removeEditImage = (idx) => {
  editForm.value.images.splice(idx, 1)
}

const saveEditWorkshop = async () => {
  const id = editForm.value.id
  const payload = { ...editForm.value }
  delete payload.id
  const result = await adminStore.updateWorkshop(id, payload)
  if (result.success) {
    editDialogVisible.value = false
  }
}

const deleteWorkshop = async (id) => {
  if (confirm('Estàs segur que vols eliminar aquest taller?')) {
    await adminStore.deleteWorkshop(id)
  }
}

// Helper functions per formatar badges
const formatTheme = (theme) => {
  const themes = {
    robotica: 'Robòtica',
    programacio: 'Programació',
    electronica: 'Electrònica',
    fabricacio: 'Fabricació',
    disseny3d: 'Disseny 3D',
    energies: 'Energies',
    mecatronica: 'Mecatrònica',
    iot: 'IoT',
    'intel·ligencia-artificial': 'IA'
  }
  return themes[theme] || theme
}

const formatCourse = (course) => {
  const courses = {
    'eso1': '1r ESO',
    'eso2': '2n ESO',
    'eso3': '3r ESO',
    'eso4': '4t ESO',
    'batx1': '1r Batx',
    'batx2': '2n Batx',
    'cfgm': 'CFGM',
    'cfgs': 'CFGS',
    'tots': 'Tots'
  }
  return courses[course] || course
}

onMounted(async () => {
  await adminStore.fetchAdminData()
})
</script>

<template>
  <div class="admin-workshops">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-book"></i> Gestionar Tallers</h1>
        <p class="subtitle">Catàleg de tallers disponibles</p>
      </div>
      <div class="header-actions">
        <button class="btn-cta" @click="$router.push('/admin/workshops/create')">
          <i class="fas fa-plus"></i>
          Nou Taller
        </button>
      </div>
    </div>

    <!-- LLISTA DE TALLERS -->
    <div class="card">
      <h2><i class="fas fa-list"></i> Tallers Actius ({{ adminStore.workshops.length }})</h2>
      
      <table class="data-table" v-if="adminStore.workshops.length > 0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Tema</th>
            <th>Curs</th>
            <th>Places</th>
            <th>Accions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="workshop in adminStore.workshops" :key="workshop.id">
            <td class="id">{{ workshop.id }}</td>
            <td class="workshop-name">{{ workshop.name }}</td>
            <td>
              <span class="theme-badge">{{ formatTheme(workshop.theme) }}</span>
            </td>
            <td>
              <span class="ambit-badge">{{ formatCourse(workshop.course) }}</span>
            </td>
            <td class="center-align">
              <span class="badge badge-success" v-if="workshop.available_slots > 0">
                {{ workshop.available_slots }} / {{ workshop.max_capacity }}
              </span>
              <span class="badge badge-danger" v-else>
                Complet
              </span>
            </td>
            <td class="actions">
              <button class="btn-edit" @click="openEditWorkshop(workshop)" title="Editar">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn-delete" @click="deleteWorkshop(workshop.id)" title="Eliminar">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="empty-state" v-else>
        <p>No hi ha tallers disponibles</p>
      </div>
    </div>

    <!-- Modal Editar Taller -->
    <div v-if="editDialogVisible" class="modal-overlay" @click.self="closeEditDialog">
      <div class="modal-content">
        <div class="modal-header">
          <h2><i class="fas fa-edit"></i> Editar Taller</h2>
          <button @click="closeEditDialog" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>Nom del Taller <span class="required">*</span></label>
              <input v-model="editForm.name" type="text" placeholder="Ex: Robòtica Educativa" required />
            </div>
          </div>

          <div class="form-group">
            <label>Descripció</label>
            <textarea v-model="editForm.description" rows="3" placeholder="Descripció del taller..."></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Instructor</label>
              <input v-model="editForm.instructor" type="text" placeholder="Nom de l'instructor" />
            </div>
            <div class="form-group">
              <label>Durada</label>
              <input v-model="editForm.duration" type="text" placeholder="Ex: 2 hores" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Tema</label>
              <input v-model="editForm.theme" type="text" placeholder="Ex: robotica" />
            </div>
            <div class="form-group">
              <label>Curs</label>
              <input v-model="editForm.course" type="text" placeholder="Ex: eso3" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Capacitat Màxima</label>
              <input v-model.number="editForm.max_capacity" type="number" min="1" />
            </div>
            <div class="form-group">
              <label>Places Disponibles</label>
              <input v-model.number="editForm.available_slots" type="number" min="0" />
            </div>
          </div>

          <div class="form-group">
            <label>Ubicació</label>
            <input v-model="editForm.location" type="text" placeholder="Sala/Aula" />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Data Inici</label>
              <input v-model="editForm.start_date" type="date" />
            </div>
            <div class="form-group">
              <label>Data Fi</label>
              <input v-model="editForm.end_date" type="date" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeEditDialog" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel·lar
          </button>
          <button @click="saveEditWorkshop" class="btn btn-primary">
            <i class="fas fa-save"></i> Guardar Canvis
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-workshops {
  max-width: 100%;
  width: 100%;
  margin: 0;
  padding: 2rem 3rem;
  background: #f7f8fb;
  min-height: 100vh;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 3rem;
  padding-bottom: 2rem;
  border-bottom: 3px solid #d4af37;
}

.page-header div h1 {
  margin: 0 0 0.5rem 0;
  font-size: 2.5rem;
  color: #1a1a1a;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-header div h1 i {
  color: #d4af37;
}

.subtitle {
  margin: 0;
  color: #666;
  font-size: 1.1rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.btn-cta {
  background: linear-gradient(135deg, #d4af37 0%, #c99c45 100%);
  color: white;
  border: none;
  padding: 0.85rem 2rem;
  border-radius: 12px;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.7rem;
  transition: all 0.3s ease;
  box-shadow: 0 8px 20px rgba(212, 175, 55, 0.35);
}

.btn-cta:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 28px rgba(212, 175, 55, 0.45);
}

.card {
  background: white;
  border: 1px solid #f0f0f0;
  border-radius: 12px;
  padding: 2rem 2.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.card h2 {
  margin: 0 0 1.5rem 0;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f0f0f0;
  font-size: 1.3rem;
  color: #1a1a1a;
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.card h2 i {
  color: #d4af37;
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
}

.data-table tbody tr:hover {
  background: #f9fafb;
}

.id {
  font-weight: 700;
  color: #1a1a1a;
}

.workshop-name {
  font-weight: 600;
  color: #1a1a1a;
}

.theme-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  background: #dbeafe;
  color: #1e40af;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
}

.ambit-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  background: #fef3c7;
  color: #92400e;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
}

.center-align {
  text-align: center;
}

.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.4rem 0.9rem;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 700;
}

.badge-success {
  background: #dcfce7;
  color: #166534;
}

.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.actions {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.btn-edit,
.btn-delete {
  background: #ffffff;
  border: 2px solid #e0e6ed;
  padding: 0.5rem 0.7rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.btn-edit {
  color: #0066cc;
  border-color: #0066cc;
}

.btn-edit:hover {
  background: #0066cc;
  border-color: #0066cc;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 102, 204, 0.4);
}

.btn-delete {
  color: #dc2626;
  border-color: #dc2626;
}

.btn-delete:hover {
  background: #dc2626;
  border-color: #dc2626;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
}

.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: #666;
}

.empty-state p {
  margin: 0.75rem 0;
  font-size: 1rem;
}

/* Modal Styles */
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
  max-width: 700px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: modalFadeIn 0.3s ease-out;
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
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
  padding: 0.5rem;
  border-radius: 8px;
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
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
textarea,
select {
  width: 100%;
  padding: 0.85rem 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 0.95rem;
  background: #fafafa;
  transition: all 0.2s ease;
  font-family: inherit;
}

input:focus,
textarea:focus,
select:focus {
  outline: none;
  border-color: #d4af37;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.15);
}

textarea {
  resize: vertical;
  min-height: 80px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.75rem 2rem;
  border-top: 2px solid #f0f0f0;
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
  box-shadow: 0 8px 20px rgba(212, 175, 55, 0.35);
}

.btn-primary:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 28px rgba(212, 175, 55, 0.45);
}

.btn-secondary {
  background: linear-gradient(135deg, #64748b 0%, #475569 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(100, 116, 139, 0.25);
}

.btn-secondary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(100, 116, 139, 0.35);
}

@media (max-width: 768px) {
  .admin-workshops {
    padding: 1.5rem;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .page-header div h1 {
    font-size: 2rem;
  }

  .header-actions {
    width: 100%;
  }

  .btn-cta {
    width: 100%;
    justify-content: center;
  }

  .modal-content {
    width: 95%;
    max-height: 85vh;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 1.25rem 1.5rem;
  }
}
</style>
