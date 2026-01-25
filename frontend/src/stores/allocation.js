import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'

export const useAllocationStore = defineStore('allocation', () => {
  const allocations = ref([])
  const loading = ref(false)
  const error = ref(null)
  
  /**
   * Obtenir assignacions de l'usuari autenticat
   */
  const fetchAllocations = async () => {
    loading.value = true
    error.value = null
    
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      
      const response = await client.get('/api/allocations')
      
      if (response.data.success) {
        allocations.value = response.data.data
        return { success: true, data: response.data.data }
      }
      
      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error obtenint assignacions'
      error.value = errorMsg
      console.error('Fetch allocations error:', err)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Obtenir assignacions pendents de l'usuari
   */
  const getPendingAllocations = () => {
    return allocations.value.filter(a => a.status === 'active')
  }
  
  /**
   * Obtenir assignacions completades
   */
  const getCompletedAllocations = () => {
    return allocations.value.filter(a => a.status === 'completed')
  }
  
  /**
   * Actualitzar una assignació
   */
  const updateAllocation = async (id, updates) => {
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      
      const response = await client.put(`/api/allocations/${id}`, updates)
      
      if (response.data.success) {
        // Actualitzar store local
        const index = allocations.value.findIndex(a => a.id === id)
        if (index >= 0) {
          allocations.value[index] = { ...allocations.value[index], ...updates }
        }
        return { success: true }
      }
      
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error actualitzant assignació'
      console.error('Update allocation error:', err)
      return { success: false, error: errorMsg }
    }
  }
  
  return {
    allocations,
    loading,
    error,
    fetchAllocations,
    getPendingAllocations,
    getCompletedAllocations,
    updateAllocation
  }
})