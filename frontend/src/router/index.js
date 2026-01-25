import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('../views/Home.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/Login.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/solicitud-centro',
    name: 'CenterRequest',
    component: () => import('../views/CenterRequest.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/cambiar-password',
    name: 'ChangePassword',
    component: () => import('../views/ChangePassword.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('../views/Dashboard.vue'),
    meta: { requiresAuth: true },
    beforeEnter: (to, from, next) => {
      // Redirigir automàticament segons el rol
      const authStore = useAuthStore()
      const user = authStore.user

      if (user?.role === 'center_coord') {
        next('/center-dashboard')
      } else if (user?.role === 'admin') {
        next('/admin')
      } else if (user?.role === 'teacher') {
        next('/teacher/schedule')
      } else {
        next()
      }
    }
  },
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: '',
        name: 'AdminDashboard',
        component: () => import('../views/AdminDashboard.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'workshops',
        name: 'AdminWorkshops',
        component: () => import('../views/AdminWorkshops.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'workshops/create',
        name: 'CreateWorkshop',
        component: () => import('../views/CreateWorkshop.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'workshops/:id/edit',
        name: 'EditWorkshop',
        component: () => import('../views/EditWorkshop.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'requests',
        name: 'AdminRequests',
        component: () => import('../views/AdminRequests.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'allocations',
        name: 'AdminAllocations',
        component: () => import('../views/AdminAllocations.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'phases',
        name: 'AdminPhases',
        component: () => import('../views/AdminPhases.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'centers',
        name: 'AdminCenters',
        component: () => import('../views/AdminCenters.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'algorithm',
        name: 'AdminAlgorithm',
        component: () => import('../views/AdminAlgorithm.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'algorithm/analytics',
        name: 'AdminAlgorithmAnalytics',
        component: () => import('../views/AdminAlgorithmAnalytics.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'history',
        name: 'AdminHistory',
        component: () => import('../views/AdminHistory.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'teachers',
        name: 'AdminTeachers',
        component: () => import('../views/AdminTeachers.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'center-requests',
        name: 'AdminCenterRequests',
        component: () => import('../views/AdminCenterRequests.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'gallery',
        name: 'AdminGallery',
        component: () => import('../views/AdminGallery.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
      },
      {
        path: 'feedback',
        name: 'AdminFeedback',
        component: () => import('../views/AdminFeedback.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
      }
    ]
  },
  {
    path: '/marketplace',
    name: 'CenterMarketplace',
    component: () => import('../views/CenterMarketplace.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/center-dashboard',
    name: 'CenterDashboard',
    component: () => import('../views/CenterDashboard.vue'),
    meta: { requiresAuth: true, requiresCenter: true }
  },
  {
    path: '/center/teachers',
    name: 'CenterTeachers',
    component: () => import('../views/CenterTeachers.vue'),
    meta: { requiresAuth: true, requiresCenter: true }
  },
  {
    path: '/teacher/scheduler',
    name: 'TeacherScheduler',
    component: () => import('../views/TeacherScheduler.vue'),
    meta: { requiresAuth: true, requiresTeacher: true }
  },
  {
    path: '/teacher/schedule',
    name: 'TeacherSchedule',
    component: () => import('../views/TeacherSchedule.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/gallery',
    name: 'PublicGallery',
    component: () => import('../views/PublicGallery.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/qr-scanner',
    name: 'QRScanner',
    component: () => import('../components/QRScanner.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/feedback/:assignmentId',
    name: 'FeedbackForm',
    component: () => import('../components/FeedbackForm.vue'),
    meta: { requiresAuth: true },
    props: route => ({
      assignmentId: parseInt(route.params.assignmentId),
      workshopId: parseInt(route.query.workshopId || 0)
    })
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: { template: '<div style="text-align: center; padding: 50px;"><h1>404 - Página no encontrada</h1><router-link to="/">Volver al inicio</router-link></div>' },
    meta: { requiresAuth: false }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Manejador de errores de ruta
router.onError((error) => {
  console.error('🚨 Router error:', error)
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  console.log(`📍 Navegando a: ${to.path}`)

  // Si requiere autenticación y no está autenticado, ir a login
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    console.log('🔐 Requiere autenticación, redirigiendo a login')
    next('/login')
    return
  }

  // Si está en login y ya está autenticado, redirigir según rol
  if (to.path === '/login' && authStore.isAuthenticated) {
    const user = authStore.user
    if (user?.role === 'admin') {
      next('/admin')
    } else if (user?.role === 'center') {
      next('/center-dashboard')
    } else if (user?.role === 'teacher') {
      next('/teacher/schedule')
    } else {
      next('/dashboard')
    }
    return
  }

  // Si requiere cambio de contraseña obligatorio
  if (authStore.isAuthenticated && authStore.user?.force_password_change && to.path !== '/cambiar-password') {
    next('/cambiar-password')
    return
  }

  // Si requiere admin y no es admin, denegar acceso
  if (to.meta.requiresAdmin && authStore.user?.role !== 'admin') {
    next('/dashboard')
    return
  }

  // Si requiere teacher y no es teacher, denegar acceso
  if (to.meta.requiresTeacher && authStore.user?.role !== 'teacher') {
    next('/dashboard')
    return
  }

  // Si requiere center y no es center, denegar acceso
  if (to.meta.requiresCenter && authStore.user?.role !== 'center_coord') {
    next('/dashboard')
    return
  }

  next()
})

export default router
