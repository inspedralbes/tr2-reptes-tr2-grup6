import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000'

export const useWorkshopStore = defineStore('workshop', () => {
  const workshops = ref([])
  const workshop = ref(null)
  const loading = ref(false)
  const error = ref(null)
  
  /**
   * Obtenir llista de tallers amb filtres opcionals
   */
  const fetchWorkshops = async (filters = {}) => {
    loading.value = true
    error.value = null
    
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      
      let url = '/api/workshops'
      const params = new URLSearchParams()
      
      if (filters.modality) params.append('modality', filters.modality)
      if (filters.category) params.append('category', filters.category)
      
      if (params.toString()) url += '?' + params.toString()
      
      const response = await client.get(url)
      
      if (response.data.success) {
        workshops.value = response.data.data
        return { success: true, data: response.data.data }
      }
      
      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || err.message || 'Error obtenint tallers'
      error.value = errorMsg
      console.error('Fetch workshops error:', err)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Obtenir detalls d'un taller specific
   */
  const fetchWorkshop = async (id) => {
    loading.value = true
    error.value = null
    
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      
      const response = await client.get(`/api/workshops/${id}`)
      
      if (response.data.success) {
        workshop.value = response.data.data
        return { success: true, data: response.data.data }
      }
      
      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error obtenint detalls del taller'
      error.value = errorMsg
      console.error('Fetch workshop error:', err)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Crear sol·licitud per un taller
   */
  const requestWorkshop = async (workshop_id, center_id, priority = 1) => {
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      
      const response = await client.post('/api/requests', {
        workshop_id,
        center_id,
        priority
      })
      
      if (response.data.success) {
        return { success: true, request_id: response.data.data.id }
      }
      
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error creant sol·licitud'
      console.error('Request workshop error:', err)
      return { success: false, error: errorMsg }
    }
  }
  
  return {
    workshops,
    workshop,
    loading,
    error,
    fetchWorkshops,
    fetchWorkshop,
    requestWorkshop
  }
})
