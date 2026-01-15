<template>
  <!-- Dropdown -->
  <div 
    v-if="showDropdown"
    class="notification-dropdown" 
    :class="placementClass"
    v-click-outside="closeDropdown"
  >
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
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Carregant notificacions...</p>
      </div>
      
      <div v-else-if="notifications.length === 0" class="empty-state">
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
import { computed, defineEmits, defineProps } from 'vue'
import { useNotificationStore } from '../stores/notificationStore'
import { useRouter } from 'vue-router'

const props = defineProps({
    showDropdown: {
        type: Boolean,
        default: false
    },
    placement: {
        type: String,
        default: 'bottom' // 'bottom' | 'top' | 'top-right'
    }
})

const emit = defineEmits(['close'])
const router = useRouter()
const notificationStore = useNotificationStore()

const notifications = computed(() => notificationStore.notifications)
const unreadNotifications = computed(() => notificationStore.unreadNotifications)
const loading = computed(() => notificationStore.loading)

// Computed class for placement
const placementClass = computed(() => `placement-${props.placement}`)

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
  
  return date.toLocaleDateString('ca-ES', { day: 'numeric', month: 'short' })
}

async function handleNotificationClick(notification) {
  if (!notification.llegida) await notificationStore.markAsRead(notification.id)
  if (notification.sollicitud_id) router.push('/mis-solicitudes')
  closeDropdown()
}

async function markAllAsRead() {
  await notificationStore.markAllAsRead()
}

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

<style scoped>
.notification-dropdown {
  position: absolute;
  width: 360px;
  max-width: 90vw;
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  animation: fadeIn 0.2s ease-out;
  border: 1px solid #E5E7EB;
}

/* Placement styles */
.placement-bottom {
    top: calc(100% + 0.5rem);
    right: 0;
    transform-origin: top right;
}

.placement-top {
    bottom: calc(100% + 0.5rem);
    right: 0;
    transform-origin: bottom right;
}

.placement-top-right {
    bottom: 100%;
    left: 0;
    margin-bottom: 0.5rem;
    transform-origin: bottom left;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}

.dropdown-header {
  padding: 1rem;
  border-bottom: 1px solid #E5E7EB;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.dropdown-header h3 { font-size: 1rem; font-weight: 700; color: #0F172A; margin: 0; }
.mark-all-btn { color: #3B82F6; font-size: 0.75rem; font-weight: 600; cursor: pointer; background: none; border: none; }
.mark-all-btn:hover { text-decoration: underline; }

.notifications-list { max-height: 400px; overflow-y: auto; }
.empty-state { padding: 2rem; text-align: center; color: #6B7280; }
.empty-icon { font-size: 2rem; display: block; margin-bottom: 0.5rem; }

.notification-item {
  padding: 1rem;
  border-bottom: 1px solid #F3F4F6;
  display: flex;
  gap: 0.75rem;
  cursor: pointer;
  transition: background-color 0.2s;
}
.notification-item:hover { background-color: #F9FAFB; }
.notification-item.unread { background-color: #EFF6FF; }
.notification-content h4 { font-size: 0.875rem; font-weight: 600; color: #0F172A; margin: 0 0 0.1rem 0; }
.notification-content p { font-size: 0.8rem; color: #6B7280; margin: 0; line-height: 1.3; }
.notification-time { font-size: 0.7rem; color: #9CA3AF; margin-top: 0.25rem; display: block; }
.unread-dot { width: 8px; height: 8px; background-color: #3B82F6; border-radius: 50%; margin-top: 0.25rem; }

@media (max-width: 768px) {
  .notification-dropdown {
    width: fixed;
    left: 1rem;
    right: 1rem; 
    width: auto;
  }
}
</style>
