import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAuthStore } from './auth'

export const usePhaseStore = defineStore('phase', () => {
  const phases = ref([
    {
      id: 1,
      name: 'Publicació i Oferta',
      code: 'PUBLICATION',
      description: 'Administradors configuren tallers. Centres poden veure en mode lectura.',
      start_date: '2025-08-08',
      end_date: '2025-09-30',
      allowed_roles: ['admin'],
      readonly_roles: ['center_coord']
    },
    {
      id: 2,
      name: 'Demanda (Cistella)',
      code: 'DEMAND',
      description: 'Centres seleccionen tallers desitjats. No garanteix plaça.',
      start_date: '2025-10-01',
      end_date: '2025-12-23',
      allowed_roles: ['admin', 'center_coord'],
      readonly_roles: []
    },
    {
      id: 3,
      name: 'Assignació',
      code: 'ASSIGNMENT',
      description: 'Sistema executa algoritme de distribució equitativa.',
      start_date: '2025-12-24',
      end_date: '2026-01-15',
      allowed_roles: ['admin'],
      readonly_roles: ['center_coord']
    },
    {
      id: 4,
      name: 'Calendari',
      code: 'SCHEDULING',
      description: 'Docents seleccionen dia i hora exactes dels tallers assignats.',
      start_date: '2026-01-16',
      end_date: '2026-03-31',
      allowed_roles: ['admin', 'center_coord', 'teacher'],
      readonly_roles: []
    },
    {
      id: 5,
      name: 'Execució',
      code: 'EXECUTION',
      description: 'Realització dels tallers. Feedback actiu.',
      start_date: '2025-11-01',
      end_date: '2026-06-30',
      allowed_roles: ['admin', 'center_coord', 'teacher'],
      readonly_roles: []
    }
  ])

  const currentPhase = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const fetchCurrentPhase = async () => {
    loading.value = true
    error.value = null

    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()

      console.log('📡 Fetching current phase from API...')
      const response = await client.get('/api/phases/current')
      const data = response.data

      console.log('API Response:', data)

      if (data.success && data.data?.phase) {
        // Map backend response to our store format
        const backendPhase = data.data.phase
        currentPhase.value = {
          id: backendPhase.id,
          name: backendPhase.name,
          code: getPhaseCodeFromId(backendPhase.id),
          description: backendPhase.description,
          start_date: backendPhase.startDate,
          end_date: backendPhase.endDate,
          status: backendPhase.status
        }
        console.log('✅ Current phase updated:', currentPhase.value)
      } else {
        // No active phase - use first phase as fallback
        currentPhase.value = phases.value[0]
        console.log('⚠️ No active phase found, using fallback:', currentPhase.value)
      }
    } catch (e) {
      console.error('❌ Error fetching current phase:', e)
      error.value = e.response?.data?.message || e.message
      // Fallback to hardcoded phases on error
      const today = new Date().toISOString().split('T')[0]
      const active = phases.value.find(p => today >= p.start_date && today <= p.end_date)
      currentPhase.value = active || phases.value[0]
      console.log('⚠️ Using fallback phase due to error:', currentPhase.value)
    } finally {
      loading.value = false
    }
  }

  // Helper to map phase ID to code
  const getPhaseCodeFromId = (id) => {
    const codeMap = {
      1: 'PUBLICATION',
      2: 'DEMAND',
      3: 'ASSIGNMENT',
      4: 'SCHEDULING',
      5: 'EXECUTION',
      6: 'FEEDBACK'
    }
    return codeMap[id] || 'UNKNOWN'
  }

  const updatePhase = async (phaseCode) => {
    const phase = phases.value.find(p => p.code === phaseCode)
    if (phase) {
      currentPhase.value = phase
    }
  }

  /**
   * Validar si un rol pot accedir a una acció en la fase actual
   */
  const canAccess = (action = 'read') => {
    if (!currentPhase.value) return false

    const authStore = useAuthStore()
    const userRole = authStore.user?.role

    if (!userRole) return false

    // Admin sempre pot
    if (userRole === 'admin') return true

    // Normalize roles (handle 'center' vs 'center_coord' mismatch from API)
    const checkRole = (list) => {
      return list.some(r => r === userRole || (r === 'center' && userRole === 'center_coord'))
    }

    // Comprovar si està en allowed_roles
    if (action === 'write') {
      return checkRole(currentPhase.value.allowed_roles)
    }

    // Lectura: allowed_roles o readonly_roles
    return checkRole(currentPhase.value.allowed_roles) ||
      checkRole(currentPhase.value.readonly_roles)
  }

  /**
   * Obtenir estat de la fase (upcoming, active, past)
   */
  const getPhaseStatus = (phase) => {
    const today = new Date().toISOString().split('T')[0]

    if (today < phase.start_date) return 'upcoming'
    if (today > phase.end_date) return 'past'
    return 'active'
  }

  /**
   * Computed: només fases actives
   */
  const activePhases = computed(() => {
    return phases.value.filter(p => getPhaseStatus(p) === 'active')
  })

  /**
   * Computed: nom de la fase actual
   */
  const currentPhaseName = computed(() => {
    return currentPhase.value?.name || 'Cap fase activa'
  })

  /**
   * Computed: codi de la fase actual
   */
  const currentPhaseCode = computed(() => {
    return currentPhase.value?.code || null
  })

  return {
    // State
    phases,
    currentPhase,
    loading,
    error,
    // Computed
    activePhases,
    currentPhaseName,
    currentPhaseCode,
    // Actions
    fetchCurrentPhase,
    updatePhase,
    canAccess,
    getPhaseStatus
  }
})
