// frontend/src/stores/notificationStore.js
// Store Pinia per gestionar notificacions

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useNotificationStore = defineStore('notifications', () => {
  const notifications = ref([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const pollingInterval = ref(null)

  // Computed
  const unreadNotifications = computed(() => 
    notifications.value.filter(n => !n.llegida)
  )

  // Actions
  async function fetchNotifications(onlyUnread = false) {
    try {
      loading.value = true
      const url = onlyUnread 
        ? 'http://localhost:8000/api/notificacions.php?no_llegides=1'
        : 'http://localhost:8000/api/notificacions.php'
      
      const response = await fetch(url, {
        credentials: 'include'
      })
      
      if (!response.ok) throw new Error('Error al carregar notificacions')
      
      const data = await response.json()
      
      if (data.success) {
        notifications.value = data.notificacions || []
      }
    } catch (error) {
      console.error('Error fetching notifications:', error)
    } finally {
      loading.value = false
    }
  }

  async function fetchUnreadCount() {
    try {
      const response = await fetch('http://localhost:8000/api/notificacions.php?count=1', {
        credentials: 'include'
      })
      
      if (!response.ok) throw new Error('Error al comptar notificacions')
      
      const data = await response.json()
      
      if (data.success) {
        unreadCount.value = data.total
      }
    } catch (error) {
      console.error('Error fetching unread count:', error)
    }
  }

  async function markAsRead(notificationId) {
    try {
      const response = await fetch(`http://localhost:8000/api/notificacions.php?id=${notificationId}`, {
        method: 'PUT',
        credentials: 'include'
      })
      
      if (!response.ok) throw new Error('Error al marcar com a llegida')
      
      const data = await response.json()
      
      if (data.success) {
        // Actualitzar localment
        const notification = notifications.value.find(n => n.id === notificationId)
        if (notification) {
          notification.llegida = true
        }
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch (error) {
      console.error('Error marking as read:', error)
    }
  }

  async function markAllAsRead() {
    try {
      const response = await fetch('http://localhost:8000/api/notificacions.php?marcar_totes=1', {
        method: 'PUT',
        credentials: 'include'
      })
      
      if (!response.ok) throw new Error('Error al marcar totes com a llegides')
      
      const data = await response.json()
      
      if (data.success) {
        // Actualitzar localment
        notifications.value.forEach(n => n.llegida = true)
        unreadCount.value = 0
      }
    } catch (error) {
      console.error('Error marking all as read:', error)
    }
  }

  function startPolling(intervalMs = 30000) {
    // Polling cada 30 segons per defecte
    if (pollingInterval.value) {
      stopPolling()
    }
    
    // Fetch inicial
    fetchUnreadCount()
    
    // Configurar interval
    pollingInterval.value = setInterval(() => {
      fetchUnreadCount()
    }, intervalMs)
  }

  function stopPolling() {
    if (pollingInterval.value) {
      clearInterval(pollingInterval.value)
      pollingInterval.value = null
    }
  }

  return {
    notifications,
    unreadCount,
    loading,
    unreadNotifications,
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    startPolling,
    stopPolling
  }
})
