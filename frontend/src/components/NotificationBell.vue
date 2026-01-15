<template>
  <div class="notification-bell">
    <button 
      @click="toggleDropdown" 
      class="bell-button"
      :class="{ 'has-notifications': unreadCount > 0 }"
    >
      <span class="bell-icon">🔔</span>
      <span v-if="unreadCount > 0" class="badge">{{ unreadCount }}</span>
    </button>
    
    <NotificationDropdown 
      v-if="showDropdown" 
      @close="showDropdown = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useNotificationStore } from '../stores/notificationStore'
import NotificationDropdown from './NotificationDropdown.vue'

const notificationStore = useNotificationStore()
const showDropdown = ref(false)

const unreadCount = computed(() => notificationStore.unreadCount)

function toggleDropdown() {
  showDropdown.value = !showDropdown.value
  if (showDropdown.value) {
    notificationStore.fetchNotifications()
  }
}

// Iniciar polling quan es munta el component
onMounted(() => {
  notificationStore.startPolling(30000) // Cada 30 segons
})

// Aturar polling quan es desmunta
onUnmounted(() => {
  notificationStore.stopPolling()
})
</script>

<script>
import { computed } from 'vue'
export default {
  name: 'NotificationBell'
}
</script>

<style scoped>
.notification-bell {
  position: relative;
}

.bell-button {
  position: relative;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 0.5rem;
  transition: background-color 0.2s;
}

.bell-button:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.bell-icon {
  font-size: 1.5rem;
  display: block;
}

.bell-button.has-notifications .bell-icon {
  animation: ring 2s ease-in-out infinite;
}

@keyframes ring {
  0%, 100% { transform: rotate(0deg); }
  10%, 30% { transform: rotate(-10deg); }
  20%, 40% { transform: rotate(10deg); }
  50% { transform: rotate(0deg); }
}

.badge {
  position: absolute;
  top: 0;
  right: 0;
  background-color: #EF4444;
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.125rem 0.375rem;
  border-radius: 9999px;
  min-width: 1.25rem;
  text-align: center;
}
</style>
