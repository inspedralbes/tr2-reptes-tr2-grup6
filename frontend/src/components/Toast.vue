<template>
  <Teleport to="body">
    <div class="toast-container">
      <transition-group name="toast-slide" tag="div">
        <div
          v-for="notification in toastStore.notifications"
          :key="notification.id"
          :class="['toast', `toast-${notification.type}`]"
        >
          <div class="toast-content">
            <div class="toast-icon">
              <i v-if="notification.type === 'success'" class="fas fa-check-circle"></i>
              <i v-else-if="notification.type === 'error'" class="fas fa-exclamation-circle"></i>
              <i v-else-if="notification.type === 'warning'" class="fas fa-exclamation-triangle"></i>
              <i v-else class="fas fa-info-circle"></i>
            </div>
            <span class="toast-message">{{ notification.message }}</span>
          </div>
          <button class="toast-close" @click="removeNotification(notification.id)">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </transition-group>
    </div>
  </Teleport>
</template>

<script setup>
import { useToastStore } from '../stores/toast'
import { watch } from 'vue'

const toastStore = useToastStore()

// Debug watcher
watch(() => toastStore.notifications.length, (newLength) => {
  console.log('🔔 Toast notifications updated:', newLength, toastStore.notifications)
})

const removeNotification = (id) => {
  toastStore.removeNotification(id)
}
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 200000;
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-width: 400px;
  pointer-events: none; /* Allow clicks to pass through container */
}

.toast {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  animation: slideIn 0.3s ease-out;
  min-width: 320px;
  pointer-events: auto; /* Re-enable clicks for toasts */
  background-color: white; /* Default background */
}

.toast-content {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.toast-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
  display: flex;
  align-items: center;
}

.toast-message {
  font-size: 0.95rem;
  font-weight: 500;
  line-height: 1.4;
  word-break: break-word;
  color: #333;
}

.toast-close {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  margin-left: 12px;
  flex-shrink: 0;
  opacity: 0.7;
  transition: opacity 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
}

.toast-close:hover {
  opacity: 1;
}

/* Success Toast */
.toast-success {
  background-color: #D4EDDA;
  border: 1px solid #C3E6CB;
}

.toast-success .toast-icon {
  color: #28A745;
}

.toast-success .toast-message {
  color: #155724;
}

.toast-success .toast-close {
  color: #155724;
}

/* Error Toast */
.toast-error {
  background-color: #F8D7DA;
  border: 1px solid #F5C6CB;
}

.toast-error .toast-icon {
  color: #DC3545;
}

.toast-error .toast-message {
  color: #721C24;
}

.toast-error .toast-close {
  color: #721C24;
}

/* Warning Toast */
.toast-warning {
  background-color: #FFF3CD;
  border: 1px solid #FFEAA7;
}

.toast-warning .toast-icon {
  color: #FFC107;
}

.toast-warning .toast-message {
  color: #856404;
}

.toast-warning .toast-close {
  color: #856404;
}

/* Info Toast */
.toast-info {
  background-color: #D1ECF1;
  border: 1px solid #BEE5EB;
}

.toast-info .toast-icon {
  color: #17A2B8;
}

.toast-info .toast-message {
  color: #0C5460;
}

.toast-info .toast-close {
  color: #0C5460;
}

/* Animations */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(400px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.toast-slide-enter-active {
  animation: slideIn 0.3s ease-out;
}

.toast-slide-leave-active {
  animation: slideIn 0.3s ease-in reverse;
}

@media (max-width: 640px) {
  .toast-container {
    top: 10px;
    right: 10px;
    left: 10px;
    max-width: none;
  }

  .toast {
    min-width: auto;
  }
}
</style>
