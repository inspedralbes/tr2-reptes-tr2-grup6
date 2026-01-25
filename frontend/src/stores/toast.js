import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const notifications = ref([])
  let nextId = 1

  const addNotification = (message, type = 'info', duration = 4000) => {
    const id = nextId++
    const notification = { id, message, type }

    notifications.value.push(notification)

    if (duration > 0) {
      setTimeout(() => {
        removeNotification(id)
      }, duration)
    }

    return id
  }

  const removeNotification = (id) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }

  const success = (message, duration = 3000) => addNotification(message, 'success', duration)
  const error = (message, duration = 4000) => addNotification(message, 'error', duration)
  const warning = (message, duration = 4000) => addNotification(message, 'warning', duration)
  const info = (message, duration = 3000) => addNotification(message, 'info', duration)

  return {
    notifications,
    addNotification,
    removeNotification,
    success,
    error,
    warning,
    info
  }
})
