<template>
  <div class="workshop-catalog">
    <!-- Header -->
    <div class="catalog-header">
      <h1>Catàleg de Tallers</h1>
      <p class="subtitle">Explora els tallers disponibles del Programa ENGINY</p>
      <div v-if="workshopCount > 0" class="workshop-count">
        {{ workshopCount }} tallers disponibles
      </div>
    </div>

    <!-- Container -->
    <div class="catalog-container">
      <!-- Sidebar Filters -->
      <aside class="filters-sidebar">
        <WorkshopFilters @filter="handleFilters" />
      </aside>

      <!-- Main Content -->
      <main class="catalog-main">
        <!-- Loading State -->
        <div v-if="loading" class="loading-grid">
          <div v-for="i in 6" :key="i" class="skeleton-card"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-box">
          <p>{{ error }}</p>
          <button @click="fetchWorkshops" class="btn btn-primary">
            🔄 Reintentar
          </button>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredWorkshops.length === 0" class="empty-state">
          <p>❌ No s'han trobat tallers amb aquests filtres</p>
          <button @click="resetFilters" class="btn btn-secondary">
            🔄 Netejar filtres
          </button>
        </div>

        <!-- Workshops Grid -->
        <div v-else class="workshops-grid">
          <WorkshopCard 
            v-for="workshop in filteredWorkshops" 
            :key="workshop.id"
            :workshop="workshop"
            @select="selectWorkshop"
          />
        </div>
      </main>
    </div>

    <!-- Detail Modal -->
    <WorkshopDetail 
      v-if="selectedWorkshopId"
      :workshop-id="selectedWorkshopId"
      @close="selectedWorkshopId = null"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useWorkshopStore } from '@/stores/workshop'
import { useAuthStore } from '@/stores/auth'
import WorkshopCard from '@/components/WorkshopCard.vue'
import WorkshopFilters from '@/components/WorkshopFilters.vue'
import WorkshopDetail from '@/components/WorkshopDetail.vue'

const workshopStore = useWorkshopStore()
const authStore = useAuthStore()

const loading = ref(false)
const error = ref(null)
const selectedWorkshopId = ref(null)

const filters = ref({
  modalities: [],
  categories: [],
  onlyAvailable: false
})

// Get workshops from store
const workshops = computed(() => workshopStore.workshops)
const workshopCount = computed(() => workshops.value.length)

// Apply filters
const filteredWorkshops = computed(() => {
  return workshops.value.filter(ws => {
    // Filter by modality
    if (filters.value.modalities.length > 0) {
      if (!filters.value.modalities.includes(ws.modality)) {
        return false
      }
    }

    // Filter by category
    if (filters.value.categories.length > 0) {
      if (!filters.value.categories.includes(ws.category)) {
        return false
      }
    }

    // Filter by availability
    if (filters.value.onlyAvailable) {
      if (!ws.available_slots || ws.available_slots === 0) {
        return false
      }
    }

    return true
  })
})

// Fetch workshops on mount
const fetchWorkshops = async () => {
  loading.value = true
  error.value = null
  try {
    await workshopStore.fetchWorkshops()
  } catch (e) {
    error.value = 'Error carregant els tallers. Intenta-ho més tard.'
    console.error(e)
  } finally {
    loading.value = false
  }
}

// Handle filter changes
const handleFilters = (newFilters) => {
  filters.value = newFilters
}

// Reset filters
const resetFilters = () => {
  filters.value = {
    modalities: [],
    categories: [],
    onlyAvailable: false
  }
}

// Select workshop
const selectWorkshop = (workshopId) => {
  selectedWorkshopId.value = workshopId
}

// Load workshops on mount
onMounted(() => {
  if (workshops.value.length === 0) {
    fetchWorkshops()
  }
})
</script>

<style scoped>
.workshop-catalog {
  min-height: 100vh;
  background: linear-gradient(135deg, #F8FAFC 0%, #F0F4F8 100%);
  padding: 40px 20px;
}

.catalog-header {
  max-width: 1200px;
  margin: 0 auto 40px;
  text-align: center;
}

.catalog-header h1 {
  font-size: 2.5rem;
  font-weight: 700;
  color: #0F172A;
  margin: 0 0 12px 0;
}

.subtitle {
  font-size: 1.1rem;
  color: #64748B;
  margin: 0 0 16px 0;
}

.workshop-count {
  display: inline-block;
  padding: 8px 16px;
  background: white;
  border-radius: 20px;
  color: #3B82F6;
  font-weight: 600;
  font-size: 0.95rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.catalog-container {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 32px;
}

.filters-sidebar {
  position: sticky;
  top: 100px;
  height: fit-content;
}

.catalog-main {
  display: flex;
  flex-direction: column;
}

.loading-grid,
.workshops-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 24px;
}

.skeleton-card {
  background: white;
  border-radius: 8px;
  height: 380px;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.error-box,
.empty-state {
  background: white;
  border-radius: 8px;
  padding: 40px 20px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.error-box p,
.empty-state p {
  font-size: 1.1rem;
  color: #64748B;
  margin: 0 0 20px 0;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.95rem;
}

.btn-primary {
  background: #3B82F6;
  color: white;
}

.btn-primary:hover {
  background: #2563EB;
}

.btn-secondary {
  background: #E2E8F0;
  color: #0F172A;
}

.btn-secondary:hover {
  background: #CBD5E1;
}

@media (max-width: 1024px) {
  .catalog-container {
    grid-template-columns: 1fr;
  }

  .filters-sidebar {
    position: static;
  }

  .catalog-header h1 {
    font-size: 2rem;
  }
}

@media (max-width: 768px) {
  .workshop-catalog {
    padding: 24px 16px;
  }

  .catalog-header {
    margin-bottom: 24px;
  }

  .loading-grid,
  .workshops-grid {
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 16px;
  }

  .catalog-header h1 {
    font-size: 1.75rem;
  }

  .subtitle {
    font-size: 1rem;
  }
}
</style>


