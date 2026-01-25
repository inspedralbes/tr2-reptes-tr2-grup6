import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth.js'
import { useRealtimeStore } from './realtime.js'

export const useSlotStore = defineStore('slot', () => {
  const slots = ref([])
  const selectedSlot = ref(null)
  const lockedSlot = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const activeWorkshopId = ref(null)
  const realtimeSubscriptions = []

  const realtimeStore = useRealtimeStore()

  const updateSlotStatus = (slotId, status) => {
    const index = slots.value.findIndex(s => s.id === slotId)
    if (index >= 0) {
      slots.value[index] = { ...slots.value[index], status }
    }
  }

  const bindRealtime = (workshopId) => {
    if (!workshopId) return

    activeWorkshopId.value = workshopId
    realtimeStore.connect()
    realtimeStore.joinWorkshop(workshopId)

    // Netejar subscripcions anteriors
    while (realtimeSubscriptions.length) {
      const unsubscribe = realtimeSubscriptions.pop()
      if (unsubscribe) unsubscribe()
    }

    realtimeSubscriptions.push(
      realtimeStore.on('slotLocked', (payload) => {
        if (!payload?.slotId) return
        updateSlotStatus(payload.slotId, 'locked')
      })
    )

    realtimeSubscriptions.push(
      realtimeStore.on('slotReleased', (payload) => {
        if (!payload?.slotId) return
        updateSlotStatus(payload.slotId, 'available')
      })
    )

    realtimeSubscriptions.push(
      realtimeStore.on('slotBooked', (payload) => {
        if (!payload?.slotId) return
        updateSlotStatus(payload.slotId, 'booked')
      })
    )
  }
  
  /**
   * Obtenir slots disponibles d'un taller
   */
  const fetchSlots = async (workshop_id) => {
    loading.value = true
    error.value = null
    
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      
      const response = await client.get(`/api/slots/workshop/${workshop_id}`)
      
      if (response.data.success) {
        slots.value = response.data.data
        bindRealtime(workshop_id)
        return { success: true, data: response.data.data }
      }
      
      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error obtenint slots'
      error.value = errorMsg
      console.error('Fetch slots error:', err)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Blocar un slot per evitar double booking
   */
  const lockSlot = async (slot_id) => {
    loading.value = true
    error.value = null
    
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      
      const response = await client.post('/api/slots/lock', { slot_id })
      
      if (response.data.success) {
        selectedSlot.value = slot_id
        lockedSlot.value = {
          slot_id: slot_id,
          token: response.data.lock_token,
          expires_at: Date.now() + (response.data.expires_in * 1000)
        }
        
        // Iniciar timer per a desbloquejament automàtic
        startLockTimer(response.data.expires_in)

        // Notificar en temps real a altres clients
        if (activeWorkshopId.value) {
          realtimeStore.emitSlotLock(slot_id, activeWorkshopId.value)
        }
        
        return { success: true, lock_token: response.data.lock_token }
      }
      
      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error bloquejant slot'
      error.value = errorMsg
      console.error('Lock slot error:', err)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Confirmar booking d'un slot
   */
  const bookSlot = async (params = {}) => {
    const slotId = params.slot_id || (lockedSlot.value && lockedSlot.value.slot_id)
    const token = params.lock_token || (lockedSlot.value && lockedSlot.value.token)
    
    if (!slotId || !token) {
      error.value = 'No hi ha slot o token de bloqueig'
      return { success: false, error: error.value }
    }
    
    loading.value = true
    error.value = null
    
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      
      const response = await client.post('/api/slots/book', {
        slot_id: slotId,
        lock_token: token
      })
      
      if (response.data.success) {
        // Actualitzar slot al store
        const index = slots.value.findIndex(s => s.id === slotId)
        if (index >= 0) {
          slots.value[index].status = 'booked'
        }
        
        const booked_slot = response.data.slot
        lockedSlot.value = null
        selectedSlot.value = null

        // Broadcast en temps real
        if (activeWorkshopId.value) {
          realtimeStore.emitSlotBook(slotId, activeWorkshopId.value)
        }
        
        return { success: true, slot: booked_slot }
      }
      
      error.value = response.data.message
      return { success: false, error: response.data.message }
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Error reservant slot'
      error.value = errorMsg
      console.error('Book slot error:', err)
      return { success: false, error: errorMsg }
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Desbloqueja un slot
   */
  const unlockSlot = async (token = null) => {
    const slotId = lockedSlot.value && lockedSlot.value.slot_id
    const lockToken = token || (lockedSlot.value && lockedSlot.value.token)
    
    if (!slotId || !lockToken) return
    
    try {
      const authStore = useAuthStore()
      const client = authStore.getApiClient()
      
      await client.post('/api/slots/unlock', {
        slot_id: slotId,
        lock_token: lockToken
      })
      
      lockedSlot.value = null
      selectedSlot.value = null

      if (activeWorkshopId.value) {
        realtimeStore.emitSlotUnlock(slotId, activeWorkshopId.value)
      }
    } catch (err) {
      console.error('Unlock slot error:', err)
    }
  }
  
  /**
   * Filtrar slots disponibles
   */
  const getAvailableSlots = () => {
    return slots.value.filter(s => s.status === 'available' && s.available_spots > 0)
  }
  
  /**
   * Filtrar slots per data
   */
  const getSlotsByDate = (date) => {
    if (!date) return []
    return slots.value.filter(s => {
      const slotDate = s.date || s.start_time.substring(0, 10)
      return slotDate === date && s.available_spots > 0
    })
  }
  
  /**
   * Calcular % ocupació d'un slot
   */
  const getSlotOccupancy = (slot) => {
    if (!slot || !slot.capacity) return 0
    const used = slot.capacity - (slot.available_spots || 0)
    return Math.round((used / slot.capacity) * 100)
  }
  
  /**
   * Timer per desbloquejament automàtic
   */
  const startLockTimer = (duration) => {
    const timer = setInterval(() => {
      if (lockedSlot.value && Date.now() > lockedSlot.value.expires_at) {
        unlockSlot()
        clearInterval(timer)
      }
    }, 1000)
  }
  
  /**
   * Clear state
   */
  const clearSelection = async () => {
    if (lockedSlot.value) {
      await unlockSlot()
    }
    selectedSlot.value = null
  }
  
  return {
    slots,
    selectedSlot,
    lockedSlot,
    loading,
    error,
    fetchSlots,
    lockSlot,
    bookSlot,
    unlockSlot,
    getAvailableSlots,
    getSlotsByDate,
    getSlotOccupancy,
    clearSelection
  }
})