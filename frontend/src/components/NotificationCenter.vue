<template>
  <div class="notification-center">
    <!-- Notification Bell Icon -->
    <div class="notification-bell" @click="togglePanel">
      <i class="fas fa-bell icon"></i>
      <span v-if="unreadCount > 0" class="badge">{{ unreadCount }}</span>
    </div>

    <!-- Notification Panel -->
    <transition name="slide-down">
      <div v-if="showPanel" class="notification-panel" @click.stop>
        <div class="panel-header">
          <h3><i class="fas fa-inbox"></i> Notificacions</h3>
          <button @click="markAllAsRead" class="btn-link" v-if="notifications.length > 0">
            Marcar tot com llegit
          </button>
        </div>

        <div class="panel-body">
          <div v-if="notifications.length === 0" class="empty-state">
            <div class="empty-icon"><i class="fas fa-bell-slash"></i></div>
            <p>No tens notificacions noves</p>
          </div>

          <div
            v-for="notif in sortedNotifications"
            :key="notif.id"
            :class="['notification-item', { unread: !notif.read }]"
            @click="markAsRead(notif.id)"
          >
            <div class="notif-icon-wrapper" :class="notif.type">
                <i :class="getIconClass(notif.type)"></i>
            </div>
            <div class="notif-content">
              <h4>{{ notif.title }}</h4>
              <p>{{ notif.message }}</p>
              <span class="notif-time">{{ formatTime(notif.timestamp) }}</span>
            </div>
            <button @click.stop="removeNotification(notif.id)" class="btn-remove">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>

        <div class="panel-footer" v-if="notifications.length > 0">
          <button @click="clearAll" class="btn-clear text-danger">
            <i class="fas fa-trash-alt"></i> Esborrar tot
          </button>
        </div>
      </div>
    </transition>

    <!-- Toast Notifications -->
    <transition-group name="toast" tag="div" class="toast-container">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="['toast-notification', `toast-${toast.type}`]"
        @click="dismissToast(toast.id)"
      >
        <div class="toast-icon-wrapper" :class="toast.type">
            <i :class="getIconClass(toast.type)"></i>
        </div>
        <div class="toast-content">
          <h4>{{ toast.title }}</h4>
          <p>{{ toast.message }}</p>
        </div>
        <button @click.stop="dismissToast(toast.id)" class="toast-close"><i class="fas fa-times"></i></button>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRealtimeStore } from '../stores/realtime.js'

const realtimeStore = useRealtimeStore()

const showPanel = ref(false)
const notifications = ref([])
const toasts = ref([])
let notificationId = 0
let toastId = 0

const unreadCount = computed(() => {
  return notifications.value.filter(n => !n.read).length
})

const sortedNotifications = computed(() => {
  return [...notifications.value].sort((a, b) => b.timestamp - a.timestamp)
})

const togglePanel = () => {
  showPanel.value = !showPanel.value
}

const getIconClass = (type) => {
  const icons = {
    info: 'fas fa-info-circle',
    success: 'fas fa-check-circle',
    warning: 'fas fa-exclamation-triangle',
    error: 'fas fa-exclamation-circle',
    slot: 'fas fa-cube',
    booking: 'fas fa-calendar-check',
    assignment: 'fas fa-graduation-cap',
    request: 'fas fa-file-signature'
  }
  return icons[type] || 'fas fa-bell'
}

const formatTime = (timestamp) => {
  const now = Date.now()
  const diff = now - timestamp
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 1) return 'Ara mateix'
  if (minutes < 60) return `Fa ${minutes} min`
  if (hours < 24) return `Fa ${hours}h`
  return `Fa ${days} dies`
}

const addNotification = (title, message, type = 'info', shouldShowToast = true) => {
  const notification = {
    id: ++notificationId,
    title,
    message,
    type,
    timestamp: Date.now(),
    read: false
  }

  notifications.value.unshift(notification)

  // Limit to 50
  if (notifications.value.length > 50) {
    notifications.value = notifications.value.slice(0, 50)
  }

  // Show Toast
  if (shouldShowToast) {
    showToast({ title, message, type })
  }
}

const showToast = ({ title, message, type = 'info', duration = 5000 }) => {
  const toast = {
    id: ++toastId,
    title,
    message,
    type
  }

  toasts.value.push(toast)

  // Auto-dismiss
  setTimeout(() => {
    dismissToast(toast.id)
  }, duration)
}

const dismissToast = (id) => {
  const index = toasts.value.findIndex(t => t.id === id)
  if (index >= 0) {
    toasts.value.splice(index, 1)
  }
}

const markAsRead = (id) => {
  const notification = notifications.value.find(n => n.id === id)
  if (notification) {
    notification.read = true
  }
}

const markAllAsRead = () => {
  notifications.value.forEach(n => { n.read = true })
}

const removeNotification = (id) => {
  const index = notifications.value.findIndex(n => n.id === id)
  if (index >= 0) {
    notifications.value.splice(index, 1)
  }
}

const clearAll = () => {
  notifications.value = []
  showPanel.value = false
}

// Event listeners de realtime
let unsubscribers = []

onMounted(() => {
  // Subscriure's a events de realtime
  unsubscribers.push(
    realtimeStore.on('notification', (payload) => {
      addNotification(
        payload.title || 'Nova notificació',
        payload.message || '',
        payload.type || 'info'
      )
    })
  )

  unsubscribers.push(
    realtimeStore.on('slotLocked', (payload) => {
      if (payload.slotId) {
        addNotification(
          'Slot bloquejat',
          `El slot ${payload.slotId} ha estat bloquejat`,
          'slot',
          false
        )
      }
    })
  )

  unsubscribers.push(
    realtimeStore.on('slotBooked', (payload) => {
      if (payload.slotId) {
        addNotification(
          'Slot reservat',
          `El slot ${payload.slotId} ha estat reservat`,
          'booking'
        )
      }
    })
  )

  unsubscribers.push(
    realtimeStore.on('requestCreated', (payload) => {
      addNotification(
        'Nova sol·licitud',
        payload.message || 'S\'ha creat una nova sol·licitud',
        'request'
      )
    })
  )

  unsubscribers.push(
    realtimeStore.on('assignmentExecuted', (payload) => {
      addNotification(
        'Assignació executada',
        payload.message || 'S\'ha executat l\'assignació de tallers',
        'assignment'
      )
    })
  )

  // Tancar panel en clic fora
  document.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  unsubscribers.forEach(unsub => unsub())
  document.removeEventListener('click', handleOutsideClick)
})

const handleOutsideClick = (event) => {
  if (!event.target.closest('.notification-center')) {
    showPanel.value = false
  }
}

// Exposar mètodes per ús extern
defineExpose({
  addNotification,
  showToast
})
</script>

<style scoped>
.notification-center {
  position: relative;
  display: flex;
  align-items: center;
}

/* Bell Icon */
.notification-bell {
  position: relative;
  cursor: pointer;
  padding: 10px;
  border-radius: 50%;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8; 
}

.notification-bell:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #C5A059; /* Brand Gold */
  transform: scale(1.05);
}

.notification-bell .icon {
  font-size: 1.25rem;
}

.notification-bell .badge {
  position: absolute;
  top: 4px;
  right: 4px;
  background: #EF4444;
  color: white;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 2px 5px;
  border-radius: 10px;
  min-width: 16px;
  text-align: center;
  border: 1px solid #1E293B; 
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Notification Panel */
.notification-panel {
  position: absolute;
  top: 55px;
  right: -10px;
  width: 380px;
  max-height: 500px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid #f1f5f9;
}

.panel-header {
  padding: 16px;
  background: #f8fafc;
  border-bottom: 1px solid #E2E8F0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.panel-header h3 {
  margin: 0;
  font-size: 1rem;
  color: #0F172A;
  font-weight: 700;
  display: flex; align-items: center; gap: 0.5rem;
}

.btn-link {
  background: none;
  border: none;
  color: #C5A059; /* Brand Gold */
  font-size: 0.8rem;
  cursor: pointer;
  font-weight: 600;
  transition: color 0.2s;
}

.btn-link:hover {
  color: #d6af66;
  text-decoration: underline;
}

.panel-body {
  flex: 1;
  overflow-y: auto;
  max-height: 360px;
}

.empty-state {
  padding: 50px 20px;
  text-align: center;
  color: #94a3b8;
}
.empty-icon { font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5; }

/* Items */
.notification-item {
  display: flex;
  gap: 14px;
  padding: 16px;
  border-bottom: 1px solid #F1F5F9;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.notification-item:hover {
  background: #F8FAFC;
}

.notification-item.unread {
  background: #fffbef; /* Light Gold Tint */
}
.notification-item.unread::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: #C5A059;
}

.notif-icon-wrapper {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #64748b;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.notif-icon-wrapper.info { color: #3b82f6; background: #eff6ff; }
.notif-icon-wrapper.success { color: #10b981; background: #ecfdf5; }
.notif-icon-wrapper.warning { color: #f59e0b; background: #fffbeb; }
.notif-icon-wrapper.error { color: #ef4444; background: #fef2f2; }
.notif-icon-wrapper.assignment { color: #C5A059; background: #fffadb; }

.notif-content {
  flex: 1;
}

.notif-content h4 {
  margin: 0 0 4px 0;
  font-size: 0.9rem;
  color: #334155;
  font-weight: 600;
}

.notif-content p {
  margin: 0 0 4px 0;
  font-size: 0.85rem;
  color: #64748b;
  line-height: 1.4;
}

.notif-time {
  font-size: 0.75rem;
  color: #94A3B8;
}

.btn-remove {
  background: none;
  border: none;
  color: #cbd5e1;
  cursor: pointer;
  font-size: 1rem;
  padding: 0 4px;
  height: fit-content;
  transition: color 0.2s;
}

.btn-remove:hover {
  color: #EF4444;
}

.panel-footer {
  padding: 12px 16px;
  background: #f8fafc;
  border-top: 1px solid #E2E8F0;
  text-align: center;
}

.btn-clear {
  background: transparent;
  border: none;
  color: #64748b;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 0.5rem;
  width: 100%;
  padding: 8px;
  border-radius: 6px;
  transition: background 0.2s;
}
.btn-clear:hover { background: #e2e8f0; color: #0f172a; }
.btn-clear.text-danger:hover { color: #ef4444; background: #fee2e2; }

/* Toast Notifications */
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 2000;
  display: flex;
  flex-direction: column;
  gap: 12px;
  pointer-events: none;
}

.toast-notification {
  display: flex;
  gap: 12px;
  padding: 16px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  max-width: 360px;
  pointer-events: all;
  cursor: pointer;
  border-left: 4px solid #3B82F6;
  animation: slideInRight 0.3s ease;
  align-items: flex-start;
}

.toast-info { border-left-color: #3B82F6; }
.toast-success { border-left-color: #10B981; }
.toast-warning { border-left-color: #F59E0B; }
.toast-error { border-left-color: #EF4444; }
.toast-assignment { border-left-color: #C5A059; }

.toast-icon-wrapper {
    width: 28px; height: 28px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
}
.toast-icon-wrapper.info { color: #3B82F6; }
.toast-icon-wrapper.success { color: #10B981; }
.toast-icon-wrapper.error { color: #EF4444; }

.toast-content {
  flex: 1;
}

.toast-content h4 {
  margin: 0 0 4px 0;
  font-size: 0.95rem;
  color: #0F172A;
  font-weight: 600;
}

.toast-content p {
  margin: 0;
  font-size: 0.85rem;
  color: #475569;
  line-height: 1.4;
}

.toast-close {
  background: none;
  border: none;
  color: #cbd5e1;
  cursor: pointer;
  font-size: 1rem;
  padding: 0;
}
.toast-close:hover { color: #64748B; }

/* Animations */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px) scale(0.98);
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

@keyframes slideInRight {
  from { opacity: 0; transform: translateX(100%); }
  to { opacity: 1; transform: translateX(0); }
}

@media (max-width: 768px) {
  .notification-panel {
    width: 100vw;
    right: -20px;
    max-width: none;
    top: 60px;
    border-radius: 0 0 12px 12px;
  }
}
</style>
