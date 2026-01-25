import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'

export const useCenterStore = defineStore('center', () => {
  const centers = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchCenters = async () => {
    loading.value = true
    error.value = null
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      const res = await client.get('/api/centers')
      if (res.data.success) centers.value = res.data.data
      else error.value = res.data.message
    } catch (err) {
      error.value = err.response?.data?.message || 'Error obtenint centres'
    } finally {
      loading.value = false
    }
  }

  const createCenter = async (payload) => {
    loading.value = true
    error.value = null
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      const res = await client.post('/api/centers', payload)
      if (res.data.success) centers.value.push(res.data.data)
      else error.value = res.data.message
      return res.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error creant centre'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  const updateCenter = async (id, payload) => {
    loading.value = true
    error.value = null
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      const res = await client.put(`/api/centers/${id}`, payload)
      if (res.data.success) {
        const idx = centers.value.findIndex(c => c.id === id)
        if (idx >= 0) centers.value[idx] = res.data.data
      } else error.value = res.data.message
      return res.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error actualitzant centre'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  const deleteCenter = async (id) => {
    loading.value = true
    error.value = null
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      const res = await client.delete(`/api/centers/${id}`)
      if (res.data.success) centers.value = centers.value.filter(c => c.id !== id)
      else error.value = res.data.message
      return res.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error eliminant centre'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  return { centers, loading, error, fetchCenters, createCenter, updateCenter, deleteCenter }
})
