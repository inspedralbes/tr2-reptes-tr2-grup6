import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'
import { useAuthStore } from './auth'

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000'

export const useAdminStore = defineStore('admin', () => {
  const workshops = ref([])
  const requests = ref([])
  const allocations = ref([])
  const teachers = ref([])
  const stats = ref({
    totalWorkshops: 0,
    totalRequests: 0,
    totalAllocations: 0,
    pendingAllocations: 0
  })
  const loading = ref(false)
  const error = ref(null)

  const api = axios.create({
    baseURL: API_BASE,
    headers: {
      'Content-Type': 'application/json'
    }
  })

  /**
   * Obtenir totes les dades del admin
   */
  const fetchAdminData = async () => {
    loading.value = true
    error.value = null

    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()

      // Obtenir tallers
      const workshopsRes = await client.get('/api/workshops')
      if (workshopsRes.data.success) {
        workshops.value = workshopsRes.data.data
        stats.value.totalWorkshops = workshopsRes.data.total || 0
      }

      // Obtenir docents (teachers)
      const teachersRes = await client.get('/api/teachers')
      if (teachersRes.data.success) {
        teachers.value = teachersRes.data.data
      }

      // Obtenir sol·licituds
      const requestsRes = await client.get('/api/requests')
      if (requestsRes.data.success) {
        requests.value = requestsRes.data.data
        stats.value.totalRequests = requestsRes.data.total || 0
      }

      // Obtenir assignacions
      const allocRes = await client.get('/api/allocations')
      if (allocRes.data.success) {
        allocations.value = allocRes.data.data
        stats.value.totalAllocations = allocRes.data.total || 0
        stats.value.pendingAllocations = allocRes.data.data.filter(a => a.status === 'pending').length || 0
      }

      return { success: true }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error obtenint dades de admin'
      error.value = errorMsg
      console.error('Fetch admin data error:', err)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }

  /**
   * Crear nou taller
   */
  const createWorkshop = async (workshopData) => {
    loading.value = true
    error.value = null

    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()

      const response = await client.post('/api/workshops', workshopData)

      if (response.data.success) {
        workshops.value.push(response.data.data)
        stats.value.totalWorkshops++
        return { success: true, data: response.data.data }
      }

      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error creant taller'
      error.value = errorMsg
      console.error('Create workshop error:', err)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }

  /**
   * Actualitzar taller
   */
  const updateWorkshop = async (id, workshopData) => {
    loading.value = true
    error.value = null

    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()

      const response = await client.put(`/api/workshops/${id}`, workshopData)

      if (response.data.success) {
        const index = workshops.value.findIndex(w => w.id === id)
        if (index >= 0) {
          workshops.value[index] = response.data.data
        }
        return { success: true, data: response.data.data }
      }

      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error actualitzant taller'
      error.value = errorMsg
      console.error('Update workshop error:', err)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }

  /**
   * Eliminar taller
   */
  const deleteWorkshop = async (id) => {
    loading.value = true
    error.value = null

    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()

      const response = await client.delete(`/api/workshops/${id}`)

      if (response.data.success) {
        workshops.value = workshops.value.filter(w => w.id !== id)
        stats.value.totalWorkshops--
        return { success: true }
      }

      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error eliminant taller'
      error.value = errorMsg
      console.error('Delete workshop error:', err)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }

  /**
   * Executar algoritme d'assignació
   */
  const executeAllocation = async () => {
    loading.value = true
    error.value = null

    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()

      const response = await client.post('/api/assignment/execute', { period_id: 1 })

      if (response.data.success) {
        // Recarregar dades
        await fetchAdminData()
        return { success: true, message: response.data.message }
      }

      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error executant algoritme'
      error.value = errorMsg
      console.error('Execute allocation error:', err)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }

  /**
   * Actualitzar sol·licitud
   */
  const updateRequest = async (id, requestData) => {
    loading.value = true
    error.value = null

    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()

      const response = await client.put(`/api/requests/${id}`, requestData)

      if (response.data.success) {
        const index = requests.value.findIndex(r => r.id === id)
        if (index >= 0) {
          requests.value[index] = response.data.data
        }
        return { success: true, data: response.data.data }
      }

      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error actualitzant sol·licitud'
      error.value = errorMsg
      console.error('Update request error:', err)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }

  /**
   * Actualitzar assignació
   */
  const updateAllocation = async (id, allocationData) => {
    loading.value = true
    error.value = null

    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()

      const response = await client.put(`/api/allocations/${id}`, allocationData)

      if (response.data.success) {
        const index = allocations.value.findIndex(a => a.id === id)
        if (index >= 0) {
          allocations.value[index] = response.data.data
          // Actualizar stats
          stats.value.pendingAllocations = allocations.value.filter(a => a.status === 'pending').length
        }
        return { success: true, data: response.data.data }
      }

      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error actualitzant assignació'
      error.value = errorMsg
      console.error('Update allocation error:', err)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    workshops,
    requests,
    allocations,
    teachers,
    stats,
    loading,
    error,
    // Actions
    fetchAdminData,
    createWorkshop,
    updateWorkshop,
    deleteWorkshop,
    updateRequest,
    executeAllocation,
    updateAllocation
  }
})
