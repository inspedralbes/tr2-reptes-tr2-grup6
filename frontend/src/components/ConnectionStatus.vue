<template>
  <!-- Component ocult temporalment - socket.io no crítico per a funcionalitats principals -->
  <!-- <div v-if="showStatus" :class="['connection-status', `status-${status}`]">
    <div class="status-indicator">
      <span class="status-dot"></span>
      <span class="status-text">{{ statusText }}</span>
    </div>
    <button v-if="canReconnect" @click="handleReconnect" class="btn-reconnect">
      🔄 Reconnectar
    </button>
  </div> -->
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRealtimeStore } from '../stores/realtime.js'

const realtimeStore = useRealtimeStore()

const showStatus = ref(true)
const autoHideTimer = ref(null)

const status = computed(() => realtimeStore.status)

const statusText = computed(() => {
  const texts = {
    disconnected: 'Reconnectant...',
    connecting: 'Connectant...',
    connected: 'Connectat',
    error: 'Reconnectant...'
  }
  return texts[status.value] || 'Desconegut'
})

const canReconnect = computed(() => {
  // No mostrar botó de reconnexió manual - el socket ho fa automàticament
  return false
})

const handleReconnect = () => {
  // Socket.io ja reconnecta automàticament amb reconnection: true
  realtimeStore.connect()
}

// Auto-amagar quan està connectat
const scheduleAutoHide = () => {
  if (autoHideTimer.value) {
    clearTimeout(autoHideTimer.value)
  }
  
  if (status.value === 'connected') {
    autoHideTimer.value = setTimeout(() => {
      showStatus.value = false
    }, 3000)
  }
}

// Watcher manual per status
let lastStatus = status.value
const checkStatusChange = () => {
  if (lastStatus !== status.value) {
    lastStatus = status.value
    showStatus.value = true
    scheduleAutoHide()
  }
}

let interval = null

onMounted(() => {
  // Connectar automàticament
  if (status.value === 'disconnected') {
    realtimeStore.connect()
  }

  // Polling per detectar canvis (simple)
  interval = setInterval(checkStatusChange, 500)
  
  scheduleAutoHide()
})

onUnmounted(() => {
  if (autoHideTimer.value) {
    clearTimeout(autoHideTimer.value)
  }
  if (interval) {
    clearInterval(interval)
  }
})
</script>

<style scoped>
.connection-status {
  position: fixed;
  bottom: 20px;
  left: 20px;
  padding: 12px 20px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 1500;
  animation: slideInLeft 0.3s ease;
  font-size: 0.9rem;
  font-weight: 600;
}

.status-disconnected {
  background: #FEE2E2;
  color: #991B1B;
  border-left: 4px solid #EF4444;
}

.status-connecting {
  background: #FEF3C7;
  color: #92400E;
  border-left: 4px solid #F59E0B;
}

.status-connected {
  background: #D1FAE5;
  color: #065F46;
  border-left: 4px solid #10B981;
}

.status-error {
  background: #FEE2E2;
  color: #991B1B;
  border-left: 4px solid #EF4444;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  animation: pulse 2s ease infinite;
}

.status-disconnected .status-dot {
  background: #EF4444;
}

.status-connecting .status-dot {
  background: #F59E0B;
}

.status-connected .status-dot {
  background: #10B981;
}

.status-error .status-dot {
  background: #EF4444;
}

.btn-reconnect {
  padding: 6px 12px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 600;
  transition: all 0.2s ease;
}

.btn-reconnect:hover {
  background: white;
  transform: translateY(-1px);
}

@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

@media (max-width: 768px) {
  .connection-status {
    bottom: 10px;
    left: 10px;
    right: 10px;
    font-size: 0.85rem;
  }
}
</style>



