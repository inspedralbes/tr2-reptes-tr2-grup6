<template>
  <div class="notification-bell">
    <button 
      @click.stop="toggleDropdown" 
      class="bell-button"
      :class="{ 'has-notifications': unreadCount > 0 }"
      ref="bellButton"
    >
      <span class="bell-icon">🔔</span>
      <span v-if="unreadCount > 0" class="badge">{{ unreadCount }}</span>
    </button>
    
    <NotificationDropdown 
      v-if="showDropdown" 
      :showDropdown="showDropdown"
      :placement="placement"
      @close="showDropdown = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, defineProps } from 'vue'
import { useNotificationStore } from '../stores/notificationStore'
import NotificationDropdown from './NotificationDropdown.vue'

const props = defineProps({
  placement: {
    type: String,
    default: 'bottom'
  }
})

const notificationStore = useNotificationStore()
const showDropdown = ref(false)
const bellButton = ref(null)

const unreadCount = computed(() => notificationStore.unreadCount)

function toggleDropdown() {
  showDropdown.value = !showDropdown.value
  if (showDropdown.value) {
    notificationStore.fetchNotifications()
  }
}

onMounted(() => {
  notificationStore.startPolling(30000)
})

onUnmounted(() => {
  notificationStore.stopPolling()
})
</script>

<style scoped>
.notification-bell { position: relative; display: inline-block; }
.bell-button {
  position: relative;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 0.5rem;
  transition: background-color 0.2s;
}
.bell-button:hover { background-color: rgba(255, 255, 255, 0.1); }
.bell-icon { font-size: 1.5rem; display: block; filter: grayscale(1); transition: filter 0.3s; }
.bell-button:hover .bell-icon, .bell-button.has-notifications .bell-icon { filter: grayscale(0); }
.bell-button.has-notifications .bell-icon { animation: ring 3s ease-in-out infinite; }
@keyframes ring {
  0%, 100% { transform: rotate(0deg); }
  5% { transform: rotate(15deg); }
  10% { transform: rotate(-10deg); }
  15% { transform: rotate(5deg); }
  20% { transform: rotate(0deg); }
}
.badge {
  position: absolute;
  top: 0; right: 0;
  background-color: #EF4444;
  color: white;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.1rem 0.3rem;
  border-radius: 9999px;
  border: 2px solid #0F172A; /* Match sidebar bg for contrast separation */
  min-width: 1.25rem;
  text-align: center;
}
</style>
