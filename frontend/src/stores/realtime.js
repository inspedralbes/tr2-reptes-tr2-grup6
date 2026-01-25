import { defineStore } from 'pinia'
import { ref } from 'vue'
import realtimeClient from '../services/realtimeClient.js'
import { useAuthStore } from './auth.js'

export const useRealtimeStore = defineStore('realtime', () => {
  const status = ref('disconnected') // disconnected | connecting | connected | error
  const socketId = ref(null)
  const lastError = ref(null)
  const lastEvent = ref(null)

  // Subscripcions simples sense dependre de libs externes
  const listeners = {
    slotLocked: [],
    slotReleased: [],
    slotBooked: [],
    notification: [],
    requestCreated: [],
    assignmentExecuted: []
  }

  const notify = (key, payload) => {
    if (!listeners[key]) return
    listeners[key].forEach((cb) => cb(payload))
    lastEvent.value = { key, payload, at: Date.now() }
  }

  const on = (key, cb) => {
    if (!listeners[key]) return () => {}
    listeners[key].push(cb)
    return () => {
      listeners[key] = listeners[key].filter((fn) => fn !== cb)
    }
  }

  const connect = () => {
    if (status.value === 'connected' || status.value === 'connecting') return realtimeClient.getSocket()

    const authStore = useAuthStore()
    const userId = authStore.user?.id || null

    status.value = 'connecting'
    lastError.value = null

    const socket = realtimeClient.connect(userId)

    // Events bàsics
    socket.on('connect', () => {
      status.value = 'connected'
      socketId.value = socket.id
      if (userId) {
        socket.emit('user:join', userId)
      }
    })

    socket.on('disconnect', () => {
      status.value = 'disconnected'
      socketId.value = null
    })

    socket.on('connect_error', (err) => {
      status.value = 'error'
      lastError.value = err?.message || 'Error de connexió'
    })

    // Events de slots
    socket.on('slot:locked', (payload) => notify('slotLocked', payload))
    socket.on('slot:lock:released', (payload) => notify('slotReleased', payload))
    socket.on('slot:booked', (payload) => notify('slotBooked', payload))

    // Notificacions
    socket.on('notification:received', (payload) => notify('notification', payload))
    socket.on('request:created', (payload) => notify('requestCreated', payload))
    socket.on('assignment:executed', (payload) => notify('assignmentExecuted', payload))

    return socket
  }

  const disconnect = () => {
    realtimeClient.disconnect()
    status.value = 'disconnected'
    socketId.value = null
  }

  /**
   * Unir-se a sala d'un taller per rebre només events d'aquest taller
   */
  const joinWorkshop = (workshopId) => {
    const socket = realtimeClient.getSocket()
    if (!socket || !workshopId) return
    socket.emit('workshop:join', workshopId)
  }

  /**
   * Emits d'ajuda
   */
  const emitSlotLock = (slotId, workshopId = null, allocationId = null) => {
    const authStore = useAuthStore()
    realtimeClient.emit('slot:lock', {
      slotId,
      workshopId,
      allocationId,
      userId: authStore.user?.id || null
    })
  }

  const emitSlotUnlock = (slotId, workshopId = null) => {
    realtimeClient.emit('slot:unlock', { slotId, workshopId })
  }

  const emitSlotBook = (slotId, workshopId = null, allocationId = null) => {
    const authStore = useAuthStore()
    realtimeClient.emit('slot:book', {
      slotId,
      workshopId,
      allocationId,
      userId: authStore.user?.id || null
    })
  }

  const emitNotification = (payload) => {
    realtimeClient.emit('notification:send', payload)
  }

  const emitRequestCreated = (payload) => {
    realtimeClient.emit('request:created', payload)
  }

  const emitAssignmentExecuted = (payload) => {
    realtimeClient.emit('assignment:executed', payload)
  }

  return {
    status,
    socketId,
    lastError,
    lastEvent,
    connect,
    disconnect,
    on,
    joinWorkshop,
    emitSlotLock,
    emitSlotUnlock,
    emitSlotBook,
    emitNotification,
    emitRequestCreated,
    emitAssignmentExecuted
  }
})
