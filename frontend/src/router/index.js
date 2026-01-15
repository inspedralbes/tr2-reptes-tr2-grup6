import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import MisSolicitudesView from '../views/MisSolicitudesView.vue'
import AdminSolicitudesView from '../views/AdminSolicitudesView.vue'
import DocenteDashboard from '../views/DocenteDashboard.vue'
import AdminTalleresView from '../views/AdminTalleresView.vue'
import ProfessorAgendaView from '../views/ProfessorAgendaView.vue'
import AdminUsersView from '../views/AdminUsersView.vue'
import AdminStatsView from '../views/AdminStatsView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardView,
      meta: { requiresAuth: true }
    },
    {
      path: '/mis-solicitudes',
      name: 'mis-solicitudes',
      component: MisSolicitudesView,
      meta: { requiresAuth: true }
    },
    {
      path: '/admin/solicitudes',
      name: 'admin-solicitudes',
      component: AdminSolicitudesView,
      meta: { requiresAuth: true }
    },
    {
      path: '/admin/talleres',
      name: 'admin-talleres',
      component: AdminTalleresView,
      meta: { requiresAuth: true }
    },
    {
      path: '/admin/users',
      name: 'admin-users',
      component: AdminUsersView,
      meta: { requiresAuth: true }
    },
    {
      path: '/admin/stats',
      name: 'admin-stats',
      component: AdminStatsView,
      meta: { requiresAuth: true }
    },
    {
      path: '/docente',
      name: 'docente',
      component: DocenteDashboard,
      meta: { requiresAuth: true }
    },
    {
      path: '/professor/agenda',
      name: 'prof-agenda',
      component: ProfessorAgendaView,
      meta: { requiresAuth: true }
    },
    {
      path: '/',
      redirect: '/login'
    }
  ]
})

// Guardián de navegación (Middleware)
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login');
  } else {
    next();
  }
});

export default router
