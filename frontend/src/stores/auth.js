import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

// API base URL
const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000'

console.log('🔧 API_BASE configurado:', API_BASE)
console.log('🔧 VITE_API_URL:', import.meta.env.VITE_API_URL)

// Crear instancia axios amb config per defecte
const api = axios.create({
  baseURL: API_BASE,
  headers: {
    'Content-Type': 'application/json'
  }
})

// Interceptor per gestionar errors 401 (Token caducat/invàlid)
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response && error.response.status === 401) {
      console.warn('⚠️ Sessió caducada o invàlida (401). Tancant sessió...')
      localStorage.removeItem('kairos_token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// Recover token on init
const token = localStorage.getItem('kairos_token')
if (token) {
  api.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('kairos_token'))
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!token.value)

  /**
   * Login d'usuari amb email i contrasenya
   */
  const login = async (email, password) => {
    loading.value = true
    error.value = null

    const payload = { email, password }
    console.log('🔐 Intentando login con:', { email, url: `${API_BASE}/api/auth/login`, payload })
    console.log('🔐 Payload JSON:', JSON.stringify(payload))

    try {
      const response = await api.post('/api/auth/login', payload)

      console.log('✅ Login exitoso:', response.data)

      if (response.data.success) {
        token.value = response.data.token
        user.value = response.data.user
        localStorage.setItem('kairos_token', token.value)

        // Actualitzar header per futures peticions
        api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`

        return { success: true, user: response.data.user }
      }

      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error en el login'
      error.value = errorMsg
      console.error('❌ Login error:', err)
      console.error('❌ Error response:', err.response)
      console.error('❌ Error response data:', err.response?.data)
      console.error('❌ Error request:', err.request)
      console.error('❌ Error config:', err.config)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }

  /**
   * Registre de nou usuari
   */
  const register = async (email, password, full_name, center_id = null) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/api/auth/register', {
        email,
        password,
        full_name,
        center_id,
        role: 'student'
      })

      if (response.data.success) {
        return { success: true, user: response.data.data }
      }

      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error en el registre'
      error.value = errorMsg
      console.error('Register error:', err)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }

  /**
   * Verificar token i carregar dades d'usuari
   */
  const verify = async () => {
    if (!token.value) return false

    try {
      api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
      const response = await api.get('/api/auth/verify')

      if (response.data.success) {
        user.value = response.data.user
        return true
      }

      // Token invalid
      logout()
      return false
    } catch (err) {
      console.error('Verify error:', err)
      logout()
      return false
    }
  }

  /**
   * Logout
   */
  const logout = () => {
    user.value = null
    token.value = null
    error.value = null
    localStorage.removeItem('kairos_token')
    delete api.defaults.headers.common['Authorization']
  }

  /**
   * Obtenir instancia API configurada amb token
   */
  const getApiClient = () => {
    if (token.value) {
      api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
    }
    return api
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    login,
    register,
    verify,
    logout,
    getApiClient
  }
})
