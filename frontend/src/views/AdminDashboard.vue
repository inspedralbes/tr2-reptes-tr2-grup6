<template>
  <div class="admin-container">
    <div class="admin-header">
      <h1><i class="fas fa-sliders-h"></i> Panell d'Administrador</h1>
      <p>Gestiona tallers, sol·licituds i assignacions</p>
    </div>

    <!-- ESTADISTICS CARDS -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-book"></i></div>
        <div class="stat-content">
          <h3>Tallers</h3>
          <p class="stat-number">{{ adminStore.stats.totalWorkshops }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-clipboard"></i></div>
        <div class="stat-content">
          <h3>Sol·licituds</h3>
          <p class="stat-number">{{ adminStore.stats.totalRequests }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
          <h3>Assignacions</h3>
          <p class="stat-number">{{ adminStore.stats.totalAllocations }}</p>
        </div>
      </div>

      <div class="stat-card pending">
        <div class="stat-icon"><i class="fas fa-hourglass-end"></i></div>
        <div class="stat-content">
          <h3>Pendents</h3>
          <p class="stat-number">{{ adminStore.stats.pendingAllocations }}</p>
        </div>
      </div>
    </div>

    <!-- ACTIONS -->
    <div class="admin-actions">
      <div class="action-card">
        <div class="action-icon"><i class="fas fa-book"></i></div>
        <h3>Tallers</h3>
        <p>Crear, editar i gestionar tallers</p>
        <router-link to="/admin/workshops" class="btn-action">
          Gestionar Tallers
        </router-link>
      </div>

      <div class="action-card">
        <div class="action-icon"><i class="fas fa-calendar-alt"></i></div>
        <h3>Fases Temporals</h3>
        <p>Controlar el cicle del programa</p>
        <router-link to="/admin/phases" class="btn-action">
          Gestionar Fases
        </router-link>
      </div>

      <div class="action-card">
        <div class="action-icon"><i class="fas fa-building"></i></div>
        <h3>Centres</h3>
        <p>Gestionar instituts participants</p>
        <router-link to="/admin/centers" class="btn-action">
          Gestionar Centres
        </router-link>
      </div>

      <div class="action-card">
        <div class="action-icon"><i class="fas fa-envelope"></i></div>
        <h3>Sol·licituds Centres</h3>
        <p>Aprovar o rebutjar peticions d'accés</p>
        <router-link to="/admin/center-requests" class="btn-action">
          Gestionar Sol·licituds
        </router-link>
      </div>

      <div class="action-card">
        <div class="action-icon"><i class="fas fa-user-tie"></i></div>
        <h3>Docents</h3>
        <p>Alta i gestió de professors</p>
        <router-link to="/admin/teachers" class="btn-action">
          Gestionar Docents
        </router-link>
      </div>

      <div class="action-card">
        <div class="action-icon"><i class="fas fa-clipboard"></i></div>
        <h3>Sol·licituds</h3>
        <p>Veure i prioritzar les peticions</p>
        <router-link to="/admin/requests" class="btn-action">
          Gestionar Sol·licituds
        </router-link>
      </div>

      <div class="action-card">
        <div class="action-icon"><i class="fas fa-check-circle"></i></div>
        <h3>Assignacions</h3>
        <p>Revisar i confirmar assignacions</p>
        <router-link to="/admin/allocations" class="btn-action">
          Gestionar Assignacions
        </router-link>
      </div>

      <div class="action-card action-primary">
        <div class="action-icon primary"><i class="fas fa-rocket"></i></div>
        <h3>Executar Algoritme</h3>
        <p>Generar les assignacions pendents</p>
        <button @click="executeAllocation" class="btn-action primary" :disabled="adminStore.loading">
          Executar Ara
        </button>
      </div>
    </div>

    <!-- LATEST REQUESTS -->
    <div class="admin-section">
      <h2>Últimes Sol·licituds</h2>
      <div v-if="adminStore.requests.length === 0" class="empty-state">
        <p>No hi ha sol·licituds</p>
      </div>
      <div v-else class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Taller</th>
              <th>Centre</th>
              <th>Prioritat</th>
              <th>Estat</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="req in adminStore.requests.slice(0, 5)" :key="req.id">
              <td>{{ req.id }}</td>
              <td>{{ req.workshop_name || 'N/A' }}</td>
              <td>{{ req.center_name || 'N/A' }}</td>
              <td><span class="badge" :class="`priority-${req.priority}`">{{ req.priority }}</span></td>
              <td><span class="badge" :class="`status-${req.status}`">{{ req.status }}</span></td>
              <td>{{ new Date(req.created_at).toLocaleDateString('ca-ES') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- PENDING ALLOCATIONS -->
    <div class="admin-section">
      <h2>Assignacions Pendents</h2>
      <div v-if="adminStore.allocations.filter(a => a.status === 'pending').length === 0" class="empty-state">
        <p>No hi ha assignacions pendents</p>
      </div>
      <div v-else class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Usuari</th>
              <th>Taller</th>
              <th>Estat</th>
              <th>Accions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="alloc in adminStore.allocations.filter(a => a.status === 'pending').slice(0, 5)" :key="alloc.id">
              <td>{{ alloc.id }}</td>
              <td>{{ alloc.user_name || 'N/A' }}</td>
              <td>{{ alloc.workshop_name || 'N/A' }}</td>
              <td><span class="badge status-pending">{{ alloc.status }}</span></td>
              <td>
                <router-link :to="`/admin/allocations/${alloc.id}`" class="btn-small">
                  Editar
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- LOADING & ERROR -->
    <div v-if="adminStore.loading" class="loading-spinner">
      <div class="spinner"></div>
      <p>Carregant dades...</p>
    </div>
    <div v-if="adminStore.error" class="alert alert-danger">
      {{ adminStore.error }}
    </div>
  </div>
</template>

<script setup>
import { onMounted, inject } from 'vue'
import { useAdminStore } from '@/stores/admin'

const adminStore = useAdminStore()
const toast = inject('toast')

const executeAllocation = async () => {
  const result = await adminStore.executeAllocation()
  if (result.success) {
    toast({
      title: '✅ Algoritme executat',
      message: 'Les assignacions s\'han creat correctament',
      type: 'success'
    })
  } else {
    toast({
      title: '❌ Error',
      message: result.error,
      type: 'error'
    })
  }
}

onMounted(async () => {
  await adminStore.fetchAdminData()
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
  line-height: 1.2;
}

.admin-header h1 i {
  font-size: 2.3rem;
  color: #C5A059;
}

.admin-header p {
  color: #4B5563;
  font-size: 1.05rem;
  margin: 0;
}

/* STATISTICS GRID */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
  gap: 1.25rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.4rem 1.6rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.25s ease;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.stat-card:hover {
  box-shadow: 0 14px 38px rgba(15, 23, 42, 0.08);
  border-color: #C5A059;
}

.stat-card.pending {
  background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
  border-color: #FCD34D;
}

.stat-icon {
  font-size: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 3.25rem;
  height: 3.25rem;
  background: linear-gradient(135deg, #3B82F6 0%, #1E40AF 100%);
  border-radius: 50%;
  color: white;
  box-shadow: 0 10px 25px rgba(59, 130, 246, 0.25);
}

.stat-content h3 {
  font-size: 0.95rem;
  color: #475569;
  margin: 0;
  font-weight: 600;
}

.stat-number {
  font-size: 1.9rem;
  font-weight: 800;
  color: #0F172A;
  margin: 0.4rem 0 0 0;
}

/* ADMIN ACTIONS */

.admin-actions {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
  margin-bottom: 2.5rem;
}

@media (max-width: 1200px) {
  .admin-actions {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 900px) {
  .admin-actions {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .admin-actions {
    grid-template-columns: 1fr;
  }
}

.btn-admin,
.btn-admin-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 1.1rem 1.5rem;
  border: none;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s ease;
  font-size: 1rem;
  box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
}

.btn-admin {
  background: white;
  color: #0F172A;
  border: 2px solid #C5A059;
}

.btn-admin:hover {
  background: #C5A059;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 14px 32px rgba(197, 160, 89, 0.28);
}

.btn-admin-primary {
  background: #C5A059;
  color: white;
  border: 2px solid #C5A059;
}

.btn-admin-primary:hover:not(:disabled) {
  background: #B8905F;
  border-color: #B8905F;
  transform: translateY(-2px);
  box-shadow: 0 14px 32px rgba(184, 144, 95, 0.3);
}

.btn-admin-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ADMIN SECTIONS */
.admin-section {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 2.2rem;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
}

.admin-section h2 {
  color: #0F172A;
  margin-bottom: 1.5rem;
  font-size: 1.4rem;
  letter-spacing: -0.01em;
}

/* TABLE */
.table-responsive {
  overflow-x: auto;
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
}

.admin-table thead tr {
  background: #F8FAFC;
  border-bottom: 2px solid #e2e8f0;
}

.admin-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 700;
  color: #0F172A;
  text-transform: uppercase;
  font-size: 0.85rem;
  letter-spacing: 0.02em;
}

.admin-table td {
  padding: 1rem;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: middle;
}

.admin-table tbody tr:hover {
  background: #F8FAFC;
}

.admin-table tbody tr:nth-child(even) {
  background: #FBFCFE;
}

/* BADGES */
.badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
}

.priority-1 {
  background: #FEE2E2;
  color: #991B1B;
}

.priority-2 {
  background: #FEF08A;
  color: #713F12;
}

.priority-3 {
  background: #DCFCE7;
  color: #166534;
}

.status-pending {
  background: #FEF3C7;
  color: #92400E;
}

.status-assigned {
  background: #DCFCE7;
  color: #166534;
}

.status-completed {
  background: #DBEAFE;
  color: #0C4A6E;
}

/* EMPTY STATE */
.empty-state {
  text-align: center;
  padding: 3rem;
  color: #999;
}

/* LOADING & ERROR */
.loading-spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  gap: 1rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-top-color: #C5A059;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.alert {
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.alert-danger {
  background: #FEE2E2;
  color: #991B1B;
  border: 1px solid #FECACA;
}

.btn-small {
  padding: 0.5rem 1rem;
  background: #C5A059;
  color: white;
  border: none;
  border-radius: 4px;
  text-decoration: none;
  font-size: 0.85rem;
}

.action-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.6rem;
  transition: all 0.25s ease;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  box-shadow: 0 10px 26px rgba(15, 23, 42, 0.07);
  min-height: 260px;
  justify-content: space-between;
}

.action-card:hover {
  border-color: #C5A059;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.1);
  transform: translateY(-2px);
}

.action-card h3 {
  margin: 0.15rem 0 0;
  color: #0F172A;
  letter-spacing: -0.01em;
}

.action-card p {
  color: #475569;
  margin: 0;
}

.action-card p {
  color: #475569;
  margin: 0;
}

.action-icon {
  font-size: 2.4rem;
  color: #C5A059;
  margin-bottom: 0.25rem;
}

.action-icon i {
  font-size: 2.4rem;
}

.btn-action {
  margin-top: auto;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 700;
  color: #1E40AF;
  text-decoration: none;
  padding: 0.65rem 1rem;
  border-radius: 10px;
  background: rgba(59, 130, 246, 0.08);
  transition: all 0.2s ease;
}

.btn-action:hover {
  background: rgba(59, 130, 246, 0.16);
  color: #0F172A;
}

.action-card.action-primary {
  background: linear-gradient(135deg, #D9B16F 0%, #C5A059 100%);
  color: #0F172A;
  border: none;
  box-shadow: 0 16px 36px rgba(197, 160, 89, 0.3);
}

.action-card.action-primary h3,
.action-card.action-primary p {
  color: #0F172A;
}

.action-icon.primary {
  background: rgba(255, 255, 255, 0.16);
  border-radius: 12px;
  padding: 0.4rem;
  color: #0F172A;
  box-shadow: none;
}

.btn-action.primary {
  background: rgba(255, 255, 255, 0.16);
  color: #0F172A;
  border: 1px solid rgba(255, 255, 255, 0.35);
}

.btn-action.primary:hover {
  background: rgba(255, 255, 255, 0.25);
  color: #0F172A;
}
@media (max-width: 768px) {
  .admin-container {
    padding: 1rem;
  }

  .admin-header h1 {
    font-size: 1.5rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .admin-actions {
    grid-template-columns: 1fr;
  }
}
</style>
