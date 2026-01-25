<template>
  <div class="center-dashboard">
    <!-- HEADER amb estadístiques -->
    <div class="dashboard-header">
      <div class="header-content">
        <h1><i class="fas fa-building text-gold"></i> {{ centerName }}</h1>
        <div class="user-meta">
          <p class="role-badge">Coordinador</p>
          <p class="subtitle">Benvingut, {{ userName }} ({{ userEmail }})</p>
        </div>
      </div>
      
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-book"></i></div>
          <div class="stat-content">
            <h3>Tallers Reservats</h3>
            <p class="stat-number">{{ totalBookings }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-chalkboard-user"></i></div>
          <div class="stat-content">
            <h3>Docents</h3>
            <p class="stat-number">{{ totalTeachers }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
          <div class="stat-content">
            <h3>Reservations Completades</h3>
            <p class="stat-number">{{ completedBookings }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-star"></i></div>
          <div class="stat-content">
            <h3>Valoració Mitjana</h3>
            <p class="stat-number">{{ averageRating }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN ACTIONS -->
    <div class="main-actions">
      <div class="action-section">
        <div class="action-card primary">
          <div class="action-icon"><i class="fas fa-store"></i></div>
          <h3>Explorar Marketplace</h3>
          <p>Descobreix tots els tallers disponibles</p>
          <router-link to="/marketplace" class="btn-action primary">
            Anar al Marketplace
          </router-link>
        </div>

        <div class="action-card">
          <div class="action-icon"><i class="fas fa-chalkboard-user"></i></div>
          <h3>Gestionar Docents</h3>
          <p>Administra els professors del teu centre</p>
          <router-link to="/center/teachers" class="btn-action">
            Gestionar Docents
          </router-link>
        </div>

        <div class="action-card">
          <div class="action-icon"><i class="fas fa-qrcode"></i></div>
          <h3>Registre d'Assistència</h3>
          <p>Escaneja codi QR per registrar presència</p>
          <router-link to="/qr-scanner" class="btn-action">
            Escàner QR
          </router-link>
        </div>

        <div class="action-card">
          <div class="action-icon"><i class="fas fa-camera"></i></div>
          <h3>Pujar Fotos</h3>
          <p>Comparteix imatges dels tallers realitzats</p>
          <button @click="showUploadModal = true" class="btn-action">
            Pujar Foto
          </button>
        </div>
      </div>
    </div>
    
    <UploadPhotoModal v-if="showUploadModal" @close="showUploadModal = false" />

    <!-- RECENT BOOKINGS -->
    <div class="recent-section">
      <h2><i class="fas fa-clock"></i> Últimes Reserves</h2>
      <div v-if="recentBookings.length === 0" class="empty-state">
        <p>No tens reserves actuals</p>
      </div>
      <div v-else class="bookings-list">
        <div v-for="booking in recentBookings" :key="booking.id" class="booking-item">
          <div class="booking-icon">
            <i class="fas fa-check-circle" :class="{'completed': booking.status === 'completed'}"></i>
          </div>
          <div class="booking-info">
            <h4>{{ booking.workshop_name }}</h4>
            <p class="booking-date">{{ formatDate(booking.date) }}</p>
            <p class="booking-teacher">Professor: {{ booking.teacher_name }}</p>
          </div>
          <div class="booking-status">
            <span class="status-badge" :class="`status-${booking.status}`">{{ booking.status }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';
import UploadPhotoModal from '../components/UploadPhotoModal.vue'; // IMPORT

const router = useRouter();
const authStore = useAuthStore();

// State
const centerName = ref('');
const userName = ref('');
const userEmail = ref('');
const showUploadModal = ref(false); // STATE

const totalBookings = ref(0);
const completedBookings = ref(0);
const totalTeachers = ref(0);
const averageRating = ref(0);
const recentBookings = ref([]); // Keep recentBookings for the template

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('ca-ES', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}



// Fetch Dashboard Data
const fetchDashboardData = async () => {
    try {
        if (authStore.user) {
            centerName.value = authStore.user.center_name || 'My Center'; // Fallback if not in user obj
            userName.value = authStore.user.full_name;
            userEmail.value = authStore.user.email;
            
// Use authenticated API client
            const api = authStore.getApiClient();
            
            // If we have center_id, fetch stats
            console.log('User in Dashboard:', authStore.user);

            if (authStore.user.center_id) {
                console.log('Fetching stats for center:', authStore.user.center_id);
                const response = await api.get(`/api/centers/${authStore.user.center_id}/dashboard-stats`);
                console.log('Stats Response:', response.data);

                if (response.data.success) {
                    const data = response.data.data;
                    totalBookings.value = data.total_bookings;
                    completedBookings.value = data.completed_bookings;
                    totalTeachers.value = data.total_teachers;
                    averageRating.value = data.average_rating;
                    recentBookings.value = data.recent_bookings.map(booking => ({
                      id: booking.id,
                      workshop_name: booking.workshop_name,
                      date: booking.created_at, // O updated_at segons preferència
                      teacher_name: 'Assignat',
                      status: booking.status
                    }))
                }
            }
        }
    } catch (error) {
        console.error('Error fetching dashboard stats:', error);
    }
};

onMounted(() => {
    fetchDashboardData();
});
</script>

<style scoped>
.center-dashboard {
  min-height: 100vh;
  background: #F7F8FB;
}

/* HEADER */
.dashboard-header {
  background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
  color: white;
  padding: 3rem 2.5rem;
}

.header-content {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.dashboard-header h1 {
  font-size: 2.5rem;
  margin: 0;
  color: white;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.text-gold {
  color: #C5A059;
}

.user-meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: 0.5rem;
}

.role-badge {
  background: rgba(197, 160, 89, 0.2);
  color: #C5A059;
  padding: 0.2rem 0.8rem;
  border-radius: 50px;
  font-size: 0.85rem;
  font-weight: 600;
  border: 1px solid rgba(197, 160, 89, 0.4);
  margin: 0;
}

.subtitle {
  color: #94a3b8;
  font-size: 1.1rem;
  margin: 0;
}

/* STATS GRID */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.2rem;
  margin-top: 2rem;
}

.stat-card {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(197, 160, 89, 0.3);
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.3s ease;
}

.stat-card:hover {
  background: rgba(255, 255, 255, 0.12);
  border-color: #C5A059;
  transform: translateY(-3px);
}

.stat-icon {
  width: 3rem;
  height: 3rem;
  background: linear-gradient(135deg, #C5A059 0%, #d4af37 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
}

.stat-content h3 {
  font-size: 0.95rem;
  color: #cbd5e1;
  margin: 0;
  font-weight: 500;
}

.stat-number {
  font-size: 2rem;
  font-weight: 800;
  color: white;
  margin: 0.3rem 0 0 0;
}

/* MAIN ACTIONS */
.main-actions {
  padding: 2.5rem;
}

.action-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.action-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
}

.action-card:hover {
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.1);
  border-color: #C5A059;
  transform: translateY(-5px);
}

.action-card.primary {
  background: linear-gradient(135deg, #C5A059 0%, #d4af37 100%);
  border: none;
  box-shadow: 0 8px 24px rgba(197, 160, 89, 0.3);
}

.action-card.primary h3 {
  color: white;
}

.action-card.primary p {
  color: rgba(255, 255, 255, 0.9);
}

.action-icon {
  font-size: 2.5rem;
  color: #C5A059;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 4.5rem;
  height: 4.5rem;
  background: #F7F8FB;
  border-radius: 50%;
}

.action-card.primary .action-icon {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.action-card h3 {
  font-size: 1.3rem;
  margin: 0;
  color: #0F172A;
}

.action-card p {
  color: #64748b;
  margin: 0;
  font-size: 0.95rem;
}

.btn-action {
  background: #C5A059;
  color: white;
  border: none;
  padding: 0.8rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  margin-top: auto;
}

.btn-action:hover {
  background: #d4af37;
  transform: translateY(-2px);
}

.action-card.primary .btn-action {
  background: white;
  color: #C5A059;
}

.action-card.primary .btn-action:hover {
  background: #F7F8FB;
}

/* RECENT BOOKINGS */
.recent-section {
  padding: 2.5rem;
  background: white;
  margin: 0 2.5rem 2.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
}

.recent-section h2 {
  margin: 0 0 1.5rem 0;
  font-size: 1.5rem;
  color: #0F172A;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.recent-section h2 i {
  color: #C5A059;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: #64748b;
}

.bookings-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.booking-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.2rem;
  background: #F7F8FB;
  border-radius: 8px;
  border-left: 4px solid #C5A059;
  transition: all 0.3s ease;
}

.booking-item:hover {
  background: #f1f5f9;
  transform: translateX(5px);
}

.booking-icon {
  font-size: 1.5rem;
  color: #C5A059;
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border-radius: 50%;
}

.booking-icon .fa-check-circle.completed {
  color: #10b981;
}

.booking-info {
  flex: 1;
}

.booking-info h4 {
  margin: 0;
  color: #0F172A;
  font-size: 1.1rem;
}

.booking-date {
  margin: 0.2rem 0 0 0;
  color: #64748b;
  font-size: 0.9rem;
}

.booking-teacher {
  margin: 0.3rem 0 0 0;
  color: #94a3b8;
  font-size: 0.85rem;
}

.booking-status {
  text-align: right;
}

.status-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-badge.status-active {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.status-completed {
  background: #dcfce7;
  color: #166534;
}

.status-badge.status-pending {
  background: #fef3c7;
  color: #92400e;
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .center-navbar {
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
  }

  .navbar-right {
    width: 100%;
    justify-content: space-between;
  }

  .dashboard-header {
    padding: 2rem 1.5rem;
  }

  .dashboard-header h1 {
    font-size: 2rem;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .main-actions {
    padding: 1.5rem;
  }

  .action-section {
    grid-template-columns: 1fr;
  }

  .recent-section {
    margin: 0 1.5rem 1.5rem;
  }
}
</style>
