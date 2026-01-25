import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'

export const useCartStore = defineStore('cart', () => {
  const items = ref([])
  const loading = ref(false)
  const error = ref(null)

  /**
   * Carregar cistella des de localStorage
   */
  const loadCart = () => {
    try {
      const saved = localStorage.getItem('kairos_cart')
      if (saved) {
        items.value = JSON.parse(saved)
      }
    } catch (err) {
      console.error('Error loading cart:', err)
    }
  }

  /**
   * Guardar cistella a localStorage
   */
  const saveCart = () => {
    try {
      localStorage.setItem('kairos_cart', JSON.stringify(items.value))
    } catch (err) {
      console.error('Error saving cart:', err)
    }
  }

  /**
   * Afegir item a la cistella
   */
  const addItem = (workshop) => {
    const exists = items.value.find(item => item.id === workshop.id)
    if (exists) {
      return { success: false, error: 'Ja està a la cistella' }
    }

    items.value.push({
      id: workshop.id,
      name: workshop.name,
      description: workshop.description,
      image: workshop.image || (Array.isArray(workshop.images) && workshop.images[0]) || null,
      hours: workshop.duration_hours,
      days: workshop.duration_days,
      duration: workshop.duration,
      addedAt: new Date().toISOString()
    })

    saveCart()
    return { success: true }
  }

  /**
   * Eliminar item de la cistella
   */
  const removeItem = (workshopId) => {
    items.value = items.value.filter(item => item.id !== workshopId)
    saveCart()
  }

  /**
   * Buidar cistella
   */
  const clearCart = () => {
    items.value = []
    localStorage.removeItem('kairos_cart')
  }

  /**
   * Enviar sol·licituds al backend
   */
  const submitRequests = async () => {
    console.log('🛒 [Store] submitRequests cridat. Items:', items.value)
    if (items.value.length === 0) {
      return { success: false, error: 'La cistella està buida' }
    }

    loading.value = true
    error.value = null

    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()

      const requests = items.value.map(item => ({
        workshop_id: item.id,
        priority: 1
      }))

      console.log('📡 [Store] Enviant POST a /api/cart/submit amb:', requests)

      const response = await client.post('/api/cart/submit', { requests })

      console.log('📥 [Store] Resposta del servidor:', response.data)

      if (response.data.success) {
        return { success: true, data: response.data.data }
      }

      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      console.error('🔥 [Store] Error en submitRequests:', err)
      const errorMsg = err.response?.data?.message || 'Error enviant sol·licituds'
      error.value = errorMsg
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    items,
    loading,
    error,
    // Actions
    loadCart,
    saveCart,
    addItem,
    removeItem,
    clearCart,
    submitRequests
  }
})
