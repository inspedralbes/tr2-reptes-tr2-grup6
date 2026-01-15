<template>
  <div class="notification-dropdown" v-click-outside="closeDropdown">
    <div class="dropdown-header">
      <h3>Notificacions</h3>
      <button 
        v-if="unreadNotifications.length > 0"
        @click="markAllAsRead" 
        class="mark-all-btn"
      >
        Marcar totes com a llegides
      </button>
    </div>
    
    <div class="notifications-list custom-scrollbar">
      <div 
        v-if="loading" 
        class="loading-state"
      >
        <div class="spinner"></div>
        <p>Carregant notificacions...</p>
      </div>
      
      <div 
        v-else-if="notifications.length === 0" 
        class="empty-state"
      >
        <span class="empty-icon">📭</span>
        <p>No tens notificacions</p>
      </div>
      
      <div 
        v-else
        v-for="notification in notifications" 
        :key="notification.id"
        class="notification-item"
        :class="{ 'unread': !notification.llegida }"
        @click="handleNotificationClick(notification)"
      >
        <div class="notification-icon">
          {{ getIcon(notification.tipus) }}
        </div>
        <div class="notification-content">
          <h4>{{ notification.titol }}</h4>
          <p>{{ notification.missatge }}</p>
          <span class="notification-time">{{ formatTime(notification.created_at) }}</span>
        </div>
        <div v-if="!notification.llegida" class="unread-dot"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useNotificationStore } from '../stores/notificationStore'
import { useRouter } from 'vue-router'

const emit = defineEmits(['close'])
const router = useRouter()
const notificationStore = useNotificationStore()

const notifications = computed(() => notificationStore.notifications)
const unreadNotifications = computed(() => notificationStore.unreadNotifications)
const loading = computed(() => notificationStore.loading)

function closeDropdown() {
  emit('close')
}

function getIcon(tipus) {
  const icons = {
    'assignacio': '🎉',
    'rebuig': '❌',
    'recordatori': '📅',
    'canvi': '🔄',
    'checklist': '📋'
  }
  return icons[tipus] || '📬'
}

function formatTime(timestamp) {
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now - date
  
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)
  
  if (minutes < 1) return 'Ara mateix'
  if (minutes < 60) return `Fa ${minutes} min`
  if (hours < 24) return `Fa ${hours}h`
  if (days < 7) return `Fa ${days} dies`
  
  return date.toLocaleDateString('ca-ES', { 
    day: 'numeric', 
    month: 'short' 
  })
}

async function handleNotificationClick(notification) {
  // Marcar com a llegida
  if (!notification.llegida) {
    await notificationStore.markAsRead(notification.id)
  }
  
  // Navegar a la sol·licitud si n'hi ha
  if (notification.sollicitud_id) {
    router.push('/mis-solicitudes')
  }
  
  closeDropdown()
}

async function markAllAsRead() {
  await notificationStore.markAllAsRead()
}

// Directiva personalitzada per tancar en clicar fora
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value()
      }
    }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent)
  }
}
</script>

<script>
export default {
  name: 'NotificationDropdown'
}
</script>

<style scoped>
.notification-dropdown {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  width: 400px;
  max-width: 90vw;
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown-header {
  padding: 1rem;
  border-bottom: 1px solid #E5E7EB;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dropdown-header h3 {
  font-size: 1.125rem;
  font-weight: 700;
  color: #0F172A;
  margin: 0;
}

.mark-all-btn {
  background: none;
  border: none;
  color: #3B82F6;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
  transition: background-color 0.2s;
}

.mark-all-btn:hover {
  background-color: #EFF6FF;
}

.notifications-list {
  max-height: 400px;
  overflow-y: auto;
}

.loading-state,
.empty-state {
  padding: 3rem 1rem;
  text-align: center;
  color: #6B7280;
}

.empty-icon {
  font-size: 3rem;
  display: block;
  margin-bottom: 0.5rem;
}

.notification-item {
  padding: 1rem;
  border-bottom: 1px solid #F3F4F6;
  display: flex;
  gap: 0.75rem;
  cursor: pointer;
  transition: background-color 0.2s;
  position: relative;
}

.notification-item:hover {
  background-color: #F9FAFB;
}

.notification-item.unread {
  background-color: #EFF6FF;
}

.notification-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-content h4 {
  font-size: 0.875rem;
  font-weight: 600;
  color: #0F172A;
  margin: 0 0 0.25rem 0;
}

.notification-content p {
  font-size: 0.875rem;
  color: #6B7280;
  margin: 0 0 0.5rem 0;
  line-height: 1.4;
}

.notification-time {
  font-size: 0.75rem;
  color: #9CA3AF;
}

.unread-dot {
  width: 8px;
  height: 8px;
  background-color: #3B82F6;
  border-radius: 50%;
  flex-shrink: 0;
  margin-top: 0.25rem;
}

@media (max-width: 768px) {
  .notification-dropdown {
    width: 100vw;
    max-width: 100vw;
    right: -1rem;
    border-radius: 0.75rem 0.75rem 0 0;
  }
}
</style>
