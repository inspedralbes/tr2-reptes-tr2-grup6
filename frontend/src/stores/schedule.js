import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'

export const useScheduleStore = defineStore('schedule', () => {
  const sessions = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchSessions = async (teacherId) => {
    loading.value = true
    error.value = null
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      const res = await client.get('/api/sessions', { params: { teacher_id: teacherId } })
      if (res.data.success) sessions.value = res.data.data
      else error.value = res.data.message
    } catch (err) {
      error.value = err.response?.data?.message || 'Error obtenint sessions'
    } finally {
      loading.value = false
    }
  }

  const createSession = async (payload) => {
    loading.value = true
    error.value = null
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      const res = await client.post('/api/sessions', payload)
      if (res.data.success) sessions.value.push(res.data.data)
      else error.value = res.data.message
      return res.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error creant sessió'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  const updateSession = async (id, payload) => {
    loading.value = true
    error.value = null
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      const res = await client.put(`/api/sessions/${id}`, payload)
      if (res.data.success) {
        const idx = sessions.value.findIndex(s => s.id === id)
        if (idx >= 0) sessions.value[idx] = res.data.data
      } else error.value = res.data.message
      return res.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error actualitzant sessió'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  return { sessions, loading, error, fetchSessions, createSession, updateSession }
})
