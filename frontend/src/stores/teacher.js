import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'

export const useTeacherStore = defineStore('teacher', () => {
  const teachers = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchTeachers = async () => {
    loading.value = true
    error.value = null
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      const res = await client.get('/api/teachers')
      if (res.data.success) teachers.value = res.data.data
      else error.value = res.data.message
    } catch (err) {
      error.value = err.response?.data?.message || 'Error obtenint docents'
    } finally {
      loading.value = false
    }
  }

  const createTeacher = async (payload) => {
    loading.value = true
    error.value = null
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      const res = await client.post('/api/teachers', payload)
      if (res.data.success) teachers.value.push(res.data.data)
      else error.value = res.data.message
      return res.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error creant docent'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  const updateTeacher = async (id, payload) => {
    loading.value = true
    error.value = null
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      const res = await client.put(`/api/teachers/${id}`, payload)
      if (res.data.success) {
        const idx = teachers.value.findIndex(t => t.id === id)
        if (idx >= 0) teachers.value[idx] = res.data.data
      } else error.value = res.data.message
      return res.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error actualitzant docent'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  const deleteTeacher = async (id) => {
    loading.value = true
    error.value = null
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      const res = await client.delete(`/api/teachers/${id}`)
      if (res.data.success) teachers.value = teachers.value.filter(t => t.id !== id)
      else error.value = res.data.message
      return res.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error eliminant docent'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  return { teachers, loading, error, fetchTeachers, createTeacher, updateTeacher, deleteTeacher }
})
