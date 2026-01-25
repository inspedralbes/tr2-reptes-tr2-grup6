<template>
  <div class="workshop-filters">
    <div class="filter-section">
      <label class="filter-title">
        <i class="icon">📋</i> Modalitat
      </label>
      <div class="filter-options">
        <label class="filter-checkbox" v-for="mod in modalities" :key="mod">
          <input 
            type="checkbox" 
            :value="mod" 
            v-model="selectedModalities"
            @change="applyFilters"
          >
          <span class="checkbox-text">
            Modalitat {{ mod }}
          </span>
        </label>
      </div>
    </div>

    <div class="filter-section">
      <label class="filter-title">
        <i class="icon">🏷️</i> Categoria
      </label>
      <div class="filter-options">
        <label class="filter-checkbox" v-for="cat in categories" :key="cat">
          <input 
            type="checkbox" 
            :value="cat" 
            v-model="selectedCategories"
            @change="applyFilters"
          >
          <span class="checkbox-text">
            {{ cat }}
          </span>
        </label>
      </div>
    </div>

    <div class="filter-section">
      <label class="filter-title">
        <i class="icon">⭐</i> Disponibilitat
      </label>
      <div class="filter-options">
        <label class="filter-checkbox">
          <input 
            type="checkbox" 
            v-model="onlyAvailable"
            @change="applyFilters"
          >
          <span class="checkbox-text">
            Només amb places disponibles
          </span>
        </label>
      </div>
    </div>

    <button @click="resetFilters" class="btn-reset" v-if="hasActiveFilters">
      🔄 Netejar filtres
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const emit = defineEmits(['filter'])

const modalities = ref(['A', 'B', 'C'])
const categories = ref([
  'Robòtica i IA',
  'Web i Apps',
  'Dades i Ciberseguretat',
  'Màquines i Electrònica',
  'Disseny Digital',
  'Jocs i Realitat Virtual'
])

const selectedModalities = ref([])
const selectedCategories = ref([])
const onlyAvailable = ref(false)

const hasActiveFilters = computed(() => {
  return selectedModalities.value.length > 0 || 
         selectedCategories.value.length > 0 || 
         onlyAvailable.value
})

const applyFilters = () => {
  emit('filter', {
    modalities: selectedModalities.value,
    categories: selectedCategories.value,
    onlyAvailable: onlyAvailable.value
  })
}

const resetFilters = () => {
  selectedModalities.value = []
  selectedCategories.value = []
  onlyAvailable.value = false
  applyFilters()
}
</script>

<style scoped>
.workshop-filters {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.filter-section {
  margin-bottom: 24px;
}

.filter-section:last-of-type {
  margin-bottom: 0;
}

.filter-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  color: #0F172A;
  margin-bottom: 12px;
  cursor: pointer;
  user-select: none;
}

.icon {
  font-size: 1.1rem;
}

.filter-options {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.filter-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  user-select: none;
  padding: 6px 0;
}

.filter-checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #3B82F6;
}

.checkbox-text {
  color: #475569;
  font-size: 0.95rem;
  transition: color 0.2s ease;
}

.filter-checkbox:hover .checkbox-text {
  color: #0F172A;
}

.filter-checkbox input[type="checkbox"]:checked + .checkbox-text {
  color: #3B82F6;
  font-weight: 500;
}

.btn-reset {
  width: 100%;
  padding: 10px 16px;
  margin-top: 20px;
  border: none;
  border-radius: 6px;
  background: #F8FAFC;
  color: #475569;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid #E2E8F0;
}

.btn-reset:hover {
  background: #E2E8F0;
  color: #0F172A;
}

@media (max-width: 768px) {
  .workshop-filters {
    padding: 16px;
  }

  .filter-options {
    gap: 8px;
  }
}
</style>


