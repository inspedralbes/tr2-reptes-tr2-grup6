<template>
  <div id="app" class="kairos-app">
    <!-- Navbar solo si está autenticado -->
    <nav v-if="authStore.isAuthenticated" class="navbar navbar-expand-lg navbar-dark kairos-navbar">
      <div class="container-fluid">
        <router-link to="/" class="navbar-brand">
          <span class="kairos-logo"><i class="fas fa-hourglass-end"></i> KAIROS</span>
        </router-link>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto align-items-center">
            <!-- Navegación según el rol del usuario -->
            <li class="nav-item" v-if="authStore.user?.role !== 'teacher'">
              <router-link 
                :to="authStore.user?.role === 'admin' ? '/admin' : (authStore.user?.role === 'center_coord' ? '/center-dashboard' : '/dashboard')" 
                class="nav-link"
              >
                <i class="fas fa-home"></i> Dashboard
              </router-link>
            </li>
            <li v-if="authStore.user?.role === 'center_coord'" class="nav-item">
              <router-link to="/marketplace" class="nav-link">
                <i class="fas fa-store"></i> Marketplace
              </router-link>
            </li>
            <li v-if="authStore.user?.role === 'center_coord'" class="nav-item">
              <router-link to="/center/teachers" class="nav-link">
                <i class="fas fa-chalkboard-teacher"></i> Docents
              </router-link>
            </li>
            <li v-if="authStore.user?.role === 'teacher'" class="nav-item">
              <router-link to="/teacher/schedule" class="nav-link">
                <i class="fas fa-calendar-alt"></i> Horari
              </router-link>
            </li>
          <li class="nav-item">
              <NotificationCenter ref="notificationCenter" />
            </li>
            <li class="nav-item">
              <div class="user-badge">
                <i class="fas fa-user-circle"></i>
                <span>{{ authStore.user?.full_name || authStore.user?.email }}</span>
              </div>
            </li>
            <li class="nav-item">
              <button class="btn-logout" @click="logout">
                <i class="fas fa-sign-out-alt"></i> Sortir
              </button>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    
    <main class="kairos-main">
      <router-view />
    </main>
    
    <Toast />
    <ConnectionStatus />
  </div>
</template>

<script setup>
import Toast from './components/Toast.vue'
import { ref, onMounted, provide } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useRealtimeStore } from './stores/realtime'
import NotificationCenter from './components/NotificationCenter.vue'
import ConnectionStatus from './components/ConnectionStatus.vue'

const router = useRouter()
const authStore = useAuthStore()
const realtimeStore = useRealtimeStore()
const notificationCenter = ref(null)

// Provide notification methods globally
provide('notify', (title, message, type = 'info') => {
  notificationCenter.value?.addNotification(title, message, type)
})

provide('toast', (options) => {
  notificationCenter.value?.showToast(options)
})

const logout = () => {
  authStore.logout()
  realtimeStore.disconnect()
  router.push('/login')
}

onMounted(() => {
  // Connectar realtime si l'usuari està autenticat
  if (authStore.isAuthenticated) {
    // Socket server not currently active - disabling to prevent console errors
    // realtimeStore.connect()
  }
})
</script>

<style scoped>
.kairos-app {
  min-height: 100vh;
  margin: 0;
  padding: 0;
}

.kairos-navbar {
  background: linear-gradient(135deg, var(--kairos-deep-navy) 0%, #1E293B 100%) !important;
  border-bottom: 3px solid var(--kairos-gold);
  box-shadow: var(--shadow-md);
  padding: var(--space-4) 0;
}

.kairos-logo {
  font-size: 1.5rem;
  font-weight: var(--font-bold);
  color: var(--kairos-gold);
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  letter-spacing: 0.05em;
  transition: all var(--transition-fast);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.kairos-logo i {
  font-size: 1.4rem;
  color: var(--kairos-gold);
}

.kairos-logo:hover {
  color: #D4B06B;
  transform: scale(1.05);
}

.navbar-nav {
  gap: 0.5rem;
}

.nav-link {
  color: rgba(255, 255, 255, 0.9) !important;
  font-weight: 500;
  padding: 0.5rem 1rem !important;
  border-radius: 8px;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  white-space: nowrap;
}

.nav-link i {
  font-size: 1rem;
}

.nav-link:hover {
  color: white !important;
  background-color: rgba(255, 255, 255, 0.12);
  transform: translateY(-1px);
}

.nav-link.router-link-active {
  color: #C5A059 !important;
  background-color: rgba(197, 160, 89, 0.15);
  font-weight: 600;
}

.user-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 1rem;
  background: rgba(197, 160, 89, 0.15);
  border-radius: 50px;
  color: #C5A059;
  font-weight: 500;
  font-size: 0.95rem;
  border: 1px solid rgba(197, 160, 89, 0.3);
}

.user-badge i {
  font-size: 1.2rem;
}

.btn-logout {
  background: transparent;
  border: 2px solid #C5A059;
  color: #C5A059;
  padding: 0.5rem 1.2rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.95rem;
}

.btn-logout:hover {
  background: #C5A059;
  color: #1E293B;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(197, 160, 89, 0.3);
}

.btn-logout i {
  font-size: 1rem;
}

.kairos-main {
  min-height: 100vh;
  margin: 0;
  padding: 0;
}

@media (max-width: 768px) {
  .kairos-navbar {
    padding: var(--space-3) 0;
  }
  
  .kairos-logo {
    font-size: 1.25rem;
  }
  
  .kairos-main {
    padding: 0;
  }
}
</style>



