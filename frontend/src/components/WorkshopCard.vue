<template>
  <div class="workshop-card">
    <div class="card-header" :style="{ borderTopColor: modalityColor }">
      <span class="badge" :style="{ backgroundColor: modalityColor }">
        {{ workshop.modality }}
      </span>
      <span class="capacity-badge">
        📍 {{ workshop.available_slots || 0 }}/{{ workshop.capacity }}
      </span>
    </div>

    <div class="card-body">
      <h5 class="workshop-title">{{ workshop.name }}</h5>
      <p class="workshop-description">{{ truncateText(workshop.description, 80) }}</p>

      <div class="workshop-meta">
        <div class="meta-item">
          <span class="meta-label">Categoria:</span>
          <span class="meta-value">{{ workshop.category }}</span>
        </div>
        <div class="meta-item">
          <span class="meta-label">Hores:</span>
          <span class="meta-value">{{ workshop.hours }}h</span>
        </div>
      </div>

      <div class="capacity-bar">
        <div class="bar-fill" :style="{ width: capacityPercent + '%' }"></div>
      </div>
      <p class="capacity-text">
        {{ capacityPercent === 100 ? 'TALLER PLE' : `${availablePercent}% places lliures` }}
      </p>
    </div>

    <div class="card-footer">
      <button 
        @click="selectWorkshop" 
        class="btn btn-sm btn-primary"
        :disabled="workshop.available_slots === 0"
      >
        {{ workshop.available_slots === 0 ? 'PLE' : 'VER DETALLS' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  workshop: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['select'])

// Calcular color per modalitat
const modalityColor = computed(() => {
  const colors = {
    A: '#3B82F6', // Blau
    B: '#C5A059', // Or
    C: '#10B981'  // Verd
  }
  return colors[props.workshop.modality] || '#6B7280'
})

// Calcular % capacitat
const capacityPercent = computed(() => {
  if (!props.workshop.capacity) return 0
  const used = props.workshop.capacity - (props.workshop.available_slots || 0)
  return Math.round((used / props.workshop.capacity) * 100)
})

// % places lliures
const availablePercent = computed(() => {
  return 100 - capacityPercent.value
})

// Truncar text
const truncateText = (text, length) => {
  return text.length > length ? text.substring(0, length) + '...' : text
}

// Emit select event
const selectWorkshop = () => {
  emit('select', props.workshop.id)
}
</script>

<style scoped>
.workshop-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.workshop-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

.card-header {
  padding: 12px 16px;
  border-top: 4px solid #3B82F6;
  background: #F8FAFC;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  color: white;
  font-weight: 600;
  font-size: 0.85rem;
}

.capacity-badge {
  font-size: 0.85rem;
  color: #475569;
  font-weight: 500;
}

.card-body {
  padding: 16px;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
}

.workshop-title {
  margin: 0 0 8px 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #0F172A;
  line-height: 1.4;
}

.workshop-description {
  margin: 0 0 12px 0;
  font-size: 0.9rem;
  color: #64748B;
  line-height: 1.5;
  flex-grow: 1;
}

.workshop-meta {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
  font-size: 0.85rem;
}

.meta-item {
  display: flex;
  gap: 4px;
}

.meta-label {
  color: #94A3B8;
  font-weight: 500;
}

.meta-value {
  color: #0F172A;
  font-weight: 600;
}

.capacity-bar {
  width: 100%;
  height: 6px;
  background: #E2E8F0;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 4px;
}

.bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #C5A059, #b08d47);
  transition: width 0.3s ease;
}

.capacity-text {
  margin: 0;
  font-size: 0.75rem;
  color: #64748B;
  text-align: right;
}

.card-footer {
  padding: 12px 16px;
  border-top: 1px solid #E2E8F0;
  background: #F8FAFC;
}

.btn {
  width: 100%;
  padding: 8px 12px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary {
  background: #C5A059;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #b08d47;
}

.btn:disabled {
  background: #CBD5E1;
  color: #94A3B8;
  cursor: not-allowed;
}
</style>


