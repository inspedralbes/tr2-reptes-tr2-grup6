<template>
  <div v-if="isOpen" class="modal-backdrop" @click="cancel">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
         <h3 :class="type === 'danger' ? 'text-danger' : 'text-gold'">
            <i :class="iconClass"></i> {{ title }}
         </h3>
      </div>
      
      <div class="modal-body">
         <p>{{ message }}</p>
      </div>

      <div class="modal-actions">
         <button class="btn-cancel" @click="cancel">{{ cancelText }}</button>
         <button :class="['btn-confirm', type]" @click="confirm">
            {{ confirmText }}
         </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    title: { type: String, default: 'Confirmar' },
    message: String,
    confirmText: { type: String, default: 'Acceptar' },
    cancelText: { type: String, default: 'Cancel·lar' },
    type: { type: String, default: 'primary' } // 'primary' (gold) or 'danger' (red)
});

const emit = defineEmits(['close', 'confirm']);

const iconClass = computed(() => {
    return props.type === 'danger' ? 'fas fa-exclamation-triangle' : 'fas fa-check-circle';
});

const cancel = () => emit('close');
const confirm = () => emit('confirm');
</script>

<style scoped>
.modal-backdrop {
    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
    display: flex; justify-content: center; align-items: center;
    z-index: 1000;
}

.modal-content {
    background: #1e293b; /* Dark Blue Theme */
    color: white;
    padding: 2rem;
    border-radius: 16px;
    width: 90%; max-width: 400px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
    border: 1px solid #334155;
    animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    text-align: center;
}

.modal-header h3 {
    margin: 0 0 1rem 0; font-size: 1.5rem; font-family: 'Outfit', sans-serif;
    display: flex; justify-content: center; gap: 0.5rem; align-items: center;
}

.text-gold { color: #C5A059; }
.text-danger { color: #ef4444; }

.modal-body p { 
    color: #cbd5e1; font-size: 1rem; line-height: 1.5; margin-bottom: 2rem; 
}

.modal-actions {
    display: flex; gap: 1rem; justify-content: center;
}

button {
    padding: 0.75rem 1.5rem; border-radius: 50px; 
    font-weight: 600; cursor: pointer; border: none;
    font-size: 0.95rem; transition: transform 0.2s;
}
button:active { transform: scale(0.95); }

.btn-cancel {
    background: transparent; border: 1px solid #475569; color: #94a3b8;
}
.btn-cancel:hover { background: #334155; color: white; }

.btn-confirm.primary {
    background: #C5A059; color: white;
}
.btn-confirm.primary:hover { background: #d6af66; box-shadow: 0 5px 15px rgba(197, 160, 89, 0.3); }

.btn-confirm.danger {
    background: #ef4444; color: white;
}
.btn-confirm.danger:hover { background: #dc2626; box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3); }

@keyframes popIn {
    0% { opacity: 0; transform: scale(0.9) translateY(20px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
