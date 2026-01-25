<template>
  <div class="admin-layout">
    <nav class="admin-sidebar">
      <div class="sidebar-header">
        <h2><i class="fas fa-cog"></i> Admin</h2>
      </div>

      <ul class="nav-menu">
        <li>
          <router-link to="/admin" :class="{ active: $route.path === '/admin' }">
            <i class="fas fa-chart-line"></i> Dashboard
          </router-link>
        </li>
        
        <li class="nav-divider">Continguts</li>
        <li>
          <router-link to="/admin/workshops" :class="{ active: $route.path === '/admin/workshops' }">
            <i class="fas fa-book"></i> Tallers
          </router-link>
        </li>
        
        <li class="nav-divider">Gestió</li>
        <li>
          <router-link to="/admin/centers" :class="{ active: $route.path === '/admin/centers' }">
            <i class="fas fa-building"></i> Centres
          </router-link>
        </li>
        <li>
          <router-link to="/admin/center-requests" :class="{ active: $route.path === '/admin/center-requests' }">
            <i class="fas fa-envelope"></i> Sol·licituds Centres
          </router-link>
        </li>
        <li>
          <router-link to="/admin/teachers" :class="{ active: $route.path === '/admin/teachers' }">
            <i class="fas fa-user-tie"></i> Docents
          </router-link>
        </li>
        
        <li class="nav-divider">Operacions</li>
        <li>
          <router-link to="/admin/requests" :class="{ active: $route.path === '/admin/requests' }">
            <i class="fas fa-clipboard"></i> Sol·licituds
          </router-link>
        </li>
        <li>
          <router-link to="/admin/gallery" :class="{ active: $route.path === '/admin/gallery' }">
            <i class="fas fa-camera-retro"></i> Galeria (Revisió)
          </router-link>
        </li>
        <li>
          <router-link to="/admin/feedback" :class="{ active: $route.path === '/admin/feedback' }">
            <i class="fas fa-comments"></i> Feedback
          </router-link>
        </li>
        <li>
          <router-link to="/admin/allocations" :class="{ active: $route.path === '/admin/allocations' }">
            <i class="fas fa-check-circle"></i> Assignacions
          </router-link>
        </li>
        <li>
          <router-link to="/admin/phases" :class="{ active: $route.path === '/admin/phases' }">
            <i class="fas fa-clock"></i> Fases Temporals
          </router-link>
        </li>
        
        <li class="nav-divider">Algoritme</li>
        <li>
          <router-link to="/admin/algorithm" :class="{ active: $route.path === '/admin/algorithm' }">
            <i class="fas fa-microchip"></i> Executar
          </router-link>
        </li>
        <li>
          <router-link to="/admin/algorithm/analytics" :class="{ active: $route.path === '/admin/algorithm/analytics' }">
            <i class="fas fa-chart-bar"></i> Estadístiques
          </router-link>
        </li>
        <li>
          <router-link to="/admin/history" :class="{ active: $route.path === '/admin/history' }">
            <i class="fas fa-history"></i> Historial i Prioritat
          </router-link>
        </li>
      </ul>

      <div class="sidebar-footer">
        <router-link to="/admin" class="btn-back">
          <i class="fas fa-arrow-left"></i> Tornar al Dashboard
        </router-link>
        <router-link to="/marketplace" class="btn-back" style="margin-top:0.5rem;">
          <i class="fas fa-shopping-cart"></i> Anar al Marketplace
        </router-link>
      </div>
    </nav>

    <div class="admin-main">
      <router-view />
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
</script>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
  background: #F8FAFC;
}

/* SIDEBAR */
.admin-sidebar {
  width: 250px;
  background: #0F172A;
  color: white;
  padding: 1.5rem 0 0 0;
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  overflow: hidden;
  border-right: 3px solid #C5A059;
  display: flex;
  flex-direction: column;
  scrollbar-width: none;
  -ms-overflow-style: none;
  z-index: 100;
}

.admin-sidebar::-webkit-scrollbar {
  display: none;
}

.sidebar-header {
  padding: 0 1.5rem 1rem 1.5rem;
  margin-bottom: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-header h2 {
  margin: 0;
  font-size: 1.3rem;
  color: #C5A059;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.sidebar-header h2 i {
  font-size: 1.4rem;
}

.nav-menu {
  list-style: none;
  padding: 0;
  margin: 0;
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.nav-menu::-webkit-scrollbar {
  display: none;
}

.nav-divider {
  padding: 0.75rem 1.5rem;
  color: rgba(255, 255, 255, 0.4);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 1rem;
}

.nav-menu li {
  margin: 0.5rem 0;
}

.nav-menu a {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
}

.nav-menu a i {
  font-size: 1.1rem;
  width: 1.2rem;
  text-align: center;
}

.nav-menu a:hover,
.nav-menu a.active {
  background: rgba(197, 160, 89, 0.1);
  color: #C5A059;
  border-left-color: #C5A059;
}

.sidebar-footer {
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding: 1.5rem;
  margin-top: auto;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.btn-back {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: rgba(197, 160, 89, 0.2);
  color: #C5A059;
  text-decoration: none;
  border-radius: 4px;
  text-align: center;
  transition: all 0.3s ease;
  font-size: 0.9rem;
  white-space: nowrap;
}

.btn-back:hover {
  background: rgba(197, 160, 89, 0.3);
}

/* MAIN CONTENT */
.admin-main {
  margin-left: 250px;
  width: calc(100% - 250px);
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 1.5rem 2rem;
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .admin-sidebar {
    width: 200px;
  }

  .admin-main {
    margin-left: 200px;
  }

  .sidebar-header h2 {
    font-size: 1rem;
  }

  .nav-menu a {
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
  }

  .admin-top-bar {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
}

@media (max-width: 600px) {
  .admin-sidebar {
    width: 100%;
    height: auto;
    position: static;
    border-right: none;
    border-bottom: 3px solid #C5A059;
    padding: 1rem 0;
  }

  .admin-main {
    margin-left: 0;
  }

  .sidebar-footer {
    position: static;
    padding: 1rem 1.5rem;
  }

  .nav-menu {
    display: flex;
    overflow-x: auto;
  }

  .nav-menu li {
    flex-shrink: 0;
  }

  .nav-menu a {
    white-space: nowrap;
  }
}
</style>
