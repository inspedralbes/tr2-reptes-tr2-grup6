import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'

export const useRequestStore = defineStore('request', () => {
  const requests = ref([])
  const loading = ref(false)
  const error = ref(null)
  
  /**
   * Obtenir sol·licituds de l'usuari autenticat
   */
  const fetchRequests = async () => {
    loading.value = true
    error.value = null
    
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      
      const response = await client.get('/api/requests')
      
      if (response.data.success) {
        requests.value = response.data.data
        return { success: true, data: response.data.data }
      }
      
      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error obtenint sol·licituds'
      error.value = errorMsg
      console.error('Fetch requests error:', err)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Crear nova sol·licitud per a un taller
   */
  const createRequest = async (workshop_id, center_id, priority = 1) => {
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      
      const response = await client.post('/api/requests', {
        workshop_id,
        center_id,
        priority
      })
      
      if (response.data.success) {
        // Afegir nova sol·licitud al store
        requests.value.push(response.data.data)
        return { success: true, request: response.data.data }
      }
      
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error creant sol·licitud'
      console.error('Create request error:', err)
      return { success: false, error: errorMsg }
    }
  }
  
  /**
   * Eliminar una sol·licitud
   */
  const deleteRequest = async (id) => {
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      
      const response = await client.delete(`/api/requests/${id}`)
      
      if (response.data.success) {
        // Eliminar del store
        requests.value = requests.value.filter(r => r.id !== id)
        return { success: true }
      }
      
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error eliminant sol·licitud'
      console.error('Delete request error:', err)
      return { success: false, error: errorMsg }
    }
  }
  
  /**
   * Obtenir sol·licituds pendents
   */
  const getPendingRequests = () => {
    return requests.value.filter(r => r.status === 'pending')
  }
  
  /**
   * Obtenir sol·licituds assignades
   */
  const getAllocatedRequests = () => {
    return requests.value.filter(r => r.status === 'allocated')
  }
  
  /**
   * Obtenir sol·licituds rebutjades
   */
  const getRejectedRequests = () => {
    return requests.value.filter(r => r.status === 'rejected')
  }
  
  return {
    requests,
    loading,
    error,
    fetchRequests,
    createRequest,
    deleteRequest,
    getPendingRequests,
    getAllocatedRequests,
    getRejectedRequests
  }
})