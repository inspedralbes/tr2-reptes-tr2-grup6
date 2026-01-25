
<template>
  <div class="page-container">
    <!-- HEADER ADMIN STYLE -->
    <div class="page-header" ref="topHeader">
      <div class="header-title">
        <router-link to="/center-dashboard" class="btn-back-dash mb-2">
          <i class="fas fa-arrow-left"></i> Tornar al Dashboard
        </router-link>
        <h1><i class="fas fa-book-open text-gold"></i> Catàleg de Tallers ENGINY</h1>
        <p class="subtitle">Explora l'oferta disponible i afegeix-la a la teva cistella</p>
      </div>
      
      <div class="header-actions">
        <!-- TODAY'S DATE BADGE -->
        <div class="today-badge">
           <i class="fas fa-calendar-day"></i>
           <span>Avui: {{ formattedToday }}</span>
        </div>

        <!-- REFRESH BUTTON -->
        <button 
          @click="manualRefresh" 
          class="btn-refresh"
          :disabled="isRefreshing"
          title="Refrescar dades de fase"
        >
          <i class="fas fa-sync-alt" :class="{ 'fa-spin': isRefreshing }"></i>
        </button>

        <!-- PHASE INDICATOR -->
        <div :class="['phase-badge', `phase-${phaseStore.currentPhaseCode?.toLowerCase()}`]">
          <i class="fas fa-calendar-alt"></i>
          <span>{{ phaseStore.currentPhase ? `Fase ${phaseStore.currentPhase.id}: ${phaseStore.currentPhase.name}` : phaseStore.currentPhaseName }}</span>
        </div>

        <!-- CART BUTTON (CTA) -->
        <button 
          v-if="canAddToCart"
          class="btn-cta" 
          @click="showCart = true"
        >
          <span class="icon-stack">
            <i class="fas fa-shopping-cart"></i>
            <span class="count-badge" v-if="cartStore.items.length > 0">{{ cartStore.items.length }}</span>
          </span>
          La meva Cistella
        </button>
      </div>
    </div>

    <!-- FILTERS SECTION (Card Style) -->
    <div class="card filter-card mb-4">
      <div class="filter-row">
        <div class="search-group">
          <i class="fas fa-search search-icon"></i>
          <input 
            type="text" 
            v-model="searchQuery" 
            class="form-control"
            placeholder="Buscar tallers per nom o descripció..."
          />
        </div>
        <div class="select-group">
          <select v-model="filterTheme" class="form-select">
            <option value="">Totes les temàtiques</option>
            <option v-for="opt in themeOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
          <select v-model="filterCourse" class="form-select">
            <option value="">Tots els cursos</option>
            <option v-for="opt in courseOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
          <select v-model="filterModality" class="form-select">
            <option value="">Totes les modalitats</option>
            <option value="A">Presencial</option>
            <option value="B">Semipresencial</option>
            <option value="C">Online</option>
          </select>
          <select v-model="filterAmbit" class="form-select">
            <option value="">Tots els àmbits</option>
            <option value="Artístic">Artístic</option>
            <option value="Industrial">Industrial</option>
            <option value="Tecnològic">Tecnològic</option>
            <option value="Hosteleria">Hosteleria</option>
            <option value="Sostenibilitat">Sostenibilitat</option>
            <option value="Esport i Lleure">Esport i Lleure</option>
            <option value="Imatge Personal">Imatge Personal</option>
            <option value="Humanitats">Humanitats</option>
            <option value="Serveis a la Comunitat">Serveis a la Comunitat</option>
          </select>
        </div>
      </div>
    </div>

    <!-- LOADING -->
    <div v-if="workshopStore.loading" class="loading-state">
      <div class="spinner-border text-gold"></div>
      <p class="mt-3 text-muted">Carregant tallers...</p>
    </div>

    <!-- WORKSHOPS GRID -->
    <div v-else>
      <div class="workshops-grid">
        <div 
          v-for="workshop in paginatedWorkshops" 
          :key="workshop.id"
          class="card workshop-card"
          @click="viewDetails(workshop)"
        >
          <div class="card-header-image">
            <img 
              :src="getWorkshopImage(workshop)" 
              :alt="workshop.name" 
              @error="$event.target.src = 'https://ui-avatars.com/api/?name=' + workshop.name + '&background=random&size=400'"
            />
            <div class="workshop-overlay" v-if="workshop.available_slots">
              <span class="badge-overlay">{{ workshop.available_slots }} places</span>
            </div>
            <!-- Added Course Badge on Image -->
            <div class="course-badge" v-if="workshop.course">
              {{ workshop.course }}
            </div>
          </div>

          <div class="card-body">
            <h3 class="card-title">{{ workshop.name }}</h3>
            <p class="card-text text-muted description-clamp">{{ workshop.description }}</p>

            <div class="workshop-tags">
              <span v-if="workshop.theme" class="tag tag-navy">
                <i :class="themeIcons[workshop.theme] || 'fas fa-tag'"></i> 
                {{ themeLabels[workshop.theme] || workshop.theme }}
              </span>
              <span v-if="workshop.modality" class="tag tag-blue">
                <i class="fas fa-signal"></i> {{ getModalityLabel(workshop.modality) }}
              </span>
            </div>

            <div class="workshop-meta">
              <span class="meta-item">
                <i class="fas fa-clock text-gold"></i> {{ workshop.duration_hours }}h ({{ workshop.duration_days }} dies)
              </span>
              <span class="meta-item">
                <i class="fas fa-chalkboard-teacher text-gold"></i> {{ workshop.provider_name || 'Sense proveïdor' }}
              </span>
            </div>

            <div class="workshop-footer">
              <button 
                v-if="canAddToCart"
                @click.stop="addToCart(workshop)"
                :disabled="isInCart(workshop.id)"
                :class="['btn-action', isInCart(workshop.id) ? 'btn-success' : 'btn-outline']"
              >
                <i :class="isInCart(workshop.id) ? 'fas fa-check' : 'fas fa-plus'"></i>
                {{ isInCart(workshop.id) ? 'Afegit' : 'Afegir' }}
              </button>
              <button 
                v-else
                @click.stop="viewDetails(workshop)"
                class="btn-action btn-outline"
              >
                <i class="fas fa-eye"></i> Detalls
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="filteredWorkshops.length === 0" class="empty-state">
        <div class="empty-icon text-muted"><i class="fas fa-inbox"></i></div>
        <h3>No s'han trobat tallers</h3>
        <p>Prova amb altres criteris de cerca</p>
      </div>

      <!-- PAGINATION CONTROLS -->
      <div v-if="totalPages > 1" class="pagination-controls mt-4">
        <button 
          class="btn-page" 
          :disabled="currentPage === 1" 
          @click="currentPage--"
        >
          <i class="fas fa-chevron-left"></i> Anterior
        </button>
        
        <span class="page-info">
          Pàgina {{ currentPage }} de {{ totalPages }}
        </span>
        
        <button 
          class="btn-page" 
          :disabled="currentPage === totalPages" 
          @click="currentPage++"
        >
          Següent <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>

    <!-- CART MODAL -->
    <div v-if="showCart" class="modal-overlay" @click="showCart = false">
      <div class="modal-content cart-modal" @click.stop>
        <div class="modal-header">
          <h2><i class="fas fa-shopping-cart text-gold"></i> La Teva Cistella</h2>
          <button @click="showCart = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <div v-if="cartStore.items.length === 0" class="empty-cart">
            <p>La cistella està buida</p>
          </div>

          <div v-else class="cart-items">
            <TransitionGroup name="list">
              <div 
                v-for="item in cartStore.items" 
                :key="item.id"
                class="cart-item"
              >
                <div class="cart-item-img">
                  <img :src="item.image || `https://picsum.photos/seed/${item.id}/100/100`" alt="">
                </div>
                <div class="cart-item-info">
                  <h4>{{ item.name }}</h4>
                  <p class="text-secondary">
                    <i class="fas fa-clock"></i> {{ item.hours }}h • {{ item.days || '—' }} dies
                  </p>
                </div>
                <button 
                  @click="removeFromCart(item.id)"
                  class="btn-remove"
                  title="Eliminar"
                >
                  <i class="fas fa-trash-alt"></i>
                </button>
              </div>
            </TransitionGroup>
          </div>
        </div>

        <div class="modal-footer">
          <div class="cart-summary">
            <strong>Total:</strong> {{ cartStore.items.length }} tallers
          </div>
          <button 
            @click="submitCart"
            :disabled="cartStore.items.length === 0 || submitting"
            class="btn-cta"
          >
            <i :class="submitting ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'"></i>
            {{ submitting ? 'Enviant...' : 'Enviar Sol·licitud' }}
          </button>
        </div>
      </div>
    </div>

    <!-- DETAIL MODAL -->
    <div v-if="selectedWorkshop" class="modal-overlay" @click="selectedWorkshop = null">
      <div class="modal-content detail-modal" @click.stop>
        <div class="modal-header">
          <h2><i class="fas fa-info-circle text-gold"></i> {{ selectedWorkshop.name }}</h2>
          <button @click="selectedWorkshop = null" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <img 
            :src="getWorkshopImage(selectedWorkshop)" 
            :alt="selectedWorkshop.name"
            class="detail-image"
          />
          
          <div class="detail-content">
            <div class="detail-section">
              <h3>Descripció</h3>
              <p>{{ selectedWorkshop.description }}</p>
            </div>

            <!-- New Comprehensive Info Grid -->
            <div class="detail-grid extended-grid">
              
              <!-- CATEGORIZATION -->
              <div class="info-group">
                <h4><i class="fas fa-tags text-gold"></i> Detalls</h4>
                <div class="info-row">
                  <span class="label">Modalitat:</span>
                  <span class="value">{{ getModalityLabel(selectedWorkshop.modality) }}</span>
                </div>
                <div class="info-row">
                  <span class="label">Àmbit:</span>
                  <span class="value">{{ selectedWorkshop.ambit }}</span>
                </div>
                <div class="info-row">
                  <span class="label">Temàtica:</span>
                  <span class="value">{{ themeLabels[selectedWorkshop.theme] || selectedWorkshop.theme || '—' }}</span>
                </div>
                <div class="info-row">
                  <span class="label">Curs:</span>
                  <span class="value">{{ courseLabels[selectedWorkshop.course] || selectedWorkshop.course || '—' }}</span>
                </div>
                <div class="info-row" v-if="selectedWorkshop.location">
                   <span class="label">Ubicació:</span>
                   <span class="value">{{ selectedWorkshop.location }}</span>
                </div>
              </div>

              <!-- DATES & DURATION -->
              <div class="info-group">
                <h4><i class="fas fa-calendar-alt text-gold"></i> Calendari</h4>
                <div class="info-row">
                  <span class="label">Dates:</span>
                  <span class="value">
                    {{ formatDate(selectedWorkshop.start_date) }} - {{ formatDate(selectedWorkshop.end_date) }}
                  </span>
                </div>
                <!-- Added Horari -->
                <div class="info-row" v-if="selectedWorkshop.time_slots && selectedWorkshop.time_slots.length">
                  <span class="label">Horari:</span>
                  <span class="value">{{ Array.isArray(selectedWorkshop.time_slots) ? selectedWorkshop.time_slots.join(', ') : selectedWorkshop.time_slots }}</span>
                </div>
                <div class="info-row">
                  <span class="label">Duració:</span>
                  <span class="value">{{ selectedWorkshop.duration_hours }}h ({{ selectedWorkshop.duration_days }} dies)</span>
                </div>
                <div class="info-row" v-if="selectedWorkshop.hours_per_day">
                  <span class="label">Intensitat:</span>
                  <span class="value">{{ selectedWorkshop.hours_per_day }}h / dia</span>
                </div>
                <div class="info-row" v-if="selectedWorkshop.allowed_days && selectedWorkshop.allowed_days.length">
                  <span class="label">Dies:</span>
                  <span class="value">{{ Array.isArray(selectedWorkshop.allowed_days) ? selectedWorkshop.allowed_days.join(', ') : selectedWorkshop.allowed_days }}</span>
                </div>
              </div>

              <!-- INSTRUCTOR & PROVIDER -->
              <div class="info-group">
                <h4><i class="fas fa-user-tie text-gold"></i> Docència</h4>
                <div class="info-row">
                  <span class="label">Instructor:</span>
                  <span class="value">{{ selectedWorkshop.instructor || 'Pendent d\'assignar' }}</span>
                </div>
                <div class="info-row">
                  <span class="label">Proveïdor:</span>
                  <span class="value">{{ selectedWorkshop.provider_name || 'Intern' }}</span>
                </div>
                 <div class="info-row" v-if="selectedWorkshop.provider_contact">
                  <span class="label">Contacte:</span>
                  <span class="value">{{ selectedWorkshop.provider_contact }}</span>
                </div>
              </div>

              <!-- CAPACITY -->
              <div class="info-group">
                <h4><i class="fas fa-users text-gold"></i> Capacitat</h4>
                <div class="info-row">
                  <span class="label">Places:</span>
                  <span class="value">{{ selectedWorkshop.available_slots }} disponibles</span>
                </div>
                <div class="info-row">
                  <span class="label">Màxim:</span>
                  <span class="value">{{ selectedWorkshop.capacity || selectedWorkshop.max_capacity }} alumnes</span>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="modal-footer">
           <button @click="selectedWorkshop = null" class="btn-secondary">
            Tancar
          </button>
          <button 
            v-if="canAddToCart"
            @click="addToCart(selectedWorkshop); selectedWorkshop = null"
            :disabled="isInCart(selectedWorkshop.id)"
            class="btn-cta"
          >
             <i :class="isInCart(selectedWorkshop.id) ? 'fas fa-check' : 'fas fa-plus'"></i>
            {{ isInCart(selectedWorkshop.id) ? 'A la cistella' : 'Afegir a la cistella' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useWorkshopStore } from '@/stores/workshop'
import { usePhaseStore } from '@/stores/phase'
import { useCartStore } from '@/stores/cart'
import { useToast } from '@/composables/useToast'

const workshopStore = useWorkshopStore()
const phaseStore = usePhaseStore()
const cartStore = useCartStore()
const toast = useToast()

const formattedToday = computed(() => {
  return new Intl.DateTimeFormat('ca-ES', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  }).format(new Date())
})

const themeLabels = {
  arts_esceniques: 'Arts Escèniques',
  bricolatge_construccio: 'Bricolatge i Construcció',
  gastronomia: 'Gastronomia',
  moda_textil: 'Moda i Tèxtil',
  mobilitat: 'Mobilitat i Transport',
  esports_nautics: 'Esports Nàutics',
  cura_personal: 'Cura Personal',
  arts_plastiques: 'Arts Plàstiques',
  tecnologia: 'Tecnologia Digital',
  energies_renovables: 'Energies Renovables',
  jocs: 'Jocs i Gamificació',
  historia: 'Història',
  audiovisual: 'Audiovisual',
  social: 'Serveis a la Comunitat',
  jardineria: 'Jardineria'
}

const themeIcons = {
  arts_esceniques: 'fas fa-theater-masks',
  bricolatge_construccio: 'fas fa-hammer',
  gastronomia: 'fas fa-utensils',
  moda_textil: 'fas fa-tshirt',
  mobilitat: 'fas fa-bicycle',
  esports_nautics: 'fas fa-water',
  cura_personal: 'fas fa-spa',
  arts_plastiques: 'fas fa-palette',
  tecnologia: 'fas fa-laptop-code',
  energies_renovables: 'fas fa-solar-panel',
  jocs: 'fas fa-dice',
  historia: 'fas fa-monument',
  audiovisual: 'fas fa-film',
  social: 'fas fa-hands-helping',
  jardineria: 'fas fa-leaf'
}

const courseLabels = {
  '3r ESO': '3r ESO',
  '4t ESO': '4t ESO',
  '1r Batxillerat': '1r Batx',
  '2n Batxillerat': '2n Batx',
  'Cicles Formatius': 'Cicles Formatius',
  'Tots': 'Tots'
}

const courseIcons = {
  '3r ESO': 'fas fa-graduation-cap',
  '4t ESO': 'fas fa-graduation-cap',
  '1r Batxillerat': 'fas fa-university',
  '2n Batxillerat': 'fas fa-university',
  'Cicles Formatius': 'fas fa-briefcase',
  'Tots': 'fas fa-users'
}

const themeOptions = Object.keys(themeLabels).map(key => ({
  value: key,
  label: themeLabels[key]
}))

const courseOptions = [
  { value: '', label: 'Tots els cursos' },
  { value: '3r ESO', label: courseLabels['3r ESO'] },
  { value: '4t ESO', label: courseLabels['4t ESO'] },
  { value: '1r Batxillerat', label: courseLabels['1r Batxillerat'] },
  { value: '2n Batxillerat', label: courseLabels['2n Batxillerat'] },
  { value: 'Cicles Formatius', label: courseLabels['Cicles Formatius'] }
]



const searchQuery = ref('')
const filterTheme = ref('')
const filterCourse = ref('')
const filterModality = ref('')
const filterAmbit = ref('')
const showCart = ref(false)
const selectedWorkshop = ref(null)
const submitting = ref(false)
const isRefreshing = ref(false)

const canAddToCart = computed(() => {
  return phaseStore.currentPhaseCode === 'DEMAND'
})

const ITEMS_PER_PAGE = 12
const currentPage = ref(1)

const filteredWorkshops = computed(() => {
  let results = workshopStore.workshops

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    results = results.filter(w => 
      w.name.toLowerCase().includes(query) ||
      w.description.toLowerCase().includes(query)
    )
  }

  if (filterTheme.value) results = results.filter(w => w.theme === filterTheme.value)
  if (filterCourse.value) results = results.filter(w => w.course === filterCourse.value)
  if (filterModality.value) results = results.filter(w => w.modality === filterModality.value)
  if (filterAmbit.value) results = results.filter(w => w.ambit === filterAmbit.value)

  // Reset page if filters change results length (simple way to detect filter change effect)
  return results
})

// Watch filters to reset page
// Actually, better to do it in the watcher or simple computed side-effect if we were using a watcher for filters. 
// Since filteredWorkshops is computed, we can't easily watch "filters" unless we watch them individually.
// Let's watch filteredWorkshops length changes to reset page 1, or just watch the filter refs.
import { watch } from 'vue'
watch([searchQuery, filterTheme, filterCourse, filterModality, filterAmbit], () => {
  currentPage.value = 1
})

const topHeader = ref(null)

watch(currentPage, () => {
  if (topHeader.value) {
    topHeader.value.scrollIntoView({ behavior: 'smooth' })
  }
})

const totalPages = computed(() => Math.ceil(filteredWorkshops.value.length / ITEMS_PER_PAGE))

const paginatedWorkshops = computed(() => {
  const start = (currentPage.value - 1) * ITEMS_PER_PAGE
  const end = start + ITEMS_PER_PAGE
  return filteredWorkshops.value.slice(start, end)
})

const getPhaseHint = () => {
  const hints = {
    PUBLICATION: 'Catàleg visible (No obert a sol·licituds)',
    DEMAND: 'Fase de sol·licituds oberta',
    ASSIGNMENT: 'Processant assignacions...',
    SCHEDULING: 'Podeu agendar les fitxes assignades',
    EXECUTION: 'Tallers en curs'
  }
  return hints[phaseStore.currentPhaseCode] || ''
}

const getWorkshopImage = (workshop) => {
  const firstGalleryImage = Array.isArray(workshop.images) && workshop.images.length > 0
    ? workshop.images[0]
    : null
  return firstGalleryImage || workshop.image || `https://picsum.photos/seed/${workshop.id}/400/300`
}

const isInCart = (workshopId) => {
  return cartStore.items.some(item => item.id === workshopId)
}

const formatDate = (dateString) => {
  if (!dateString) return '—'
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('ca-ES', { day: '2-digit', month: 'short', year: 'numeric' }).format(date)
}

const getModalityLabel = (code) => {
  const map = { 'A': 'Presencial', 'B': 'Semipresencial', 'C': 'Online' }
  return map[code] || code || '—'
}

const addToCart = (workshop) => {
  if (!canAddToCart.value) {
    toast.warning('No pots afegir tallers fora de la fase de demanda')
    return
  }

  cartStore.addItem(workshop)
  toast.success(`${workshop.name} afegit a la cistella`)
}

const removeFromCart = (workshopId) => {
  cartStore.removeItem(workshopId)
  toast.info('Taller eliminat de la cistella')
}

const viewDetails = (workshop) => {
  selectedWorkshop.value = workshop
}

const submitCart = async () => {
  console.log('🚀 Iniciant enviament de sol·licitud (submitCart)...')
  if (cartStore.items.length === 0) {
    console.warn('⚠️ La cistella està buida, no s\'envia res.')
    return
  }

  submitting.value = true
  
  try {
    console.log(`📦 Enviant ${cartStore.items.length} items al backend...`)
    const result = await cartStore.submitRequests()
    console.log('🔄 Resultat rebut del store:', result)
    
    if (result.success) {
      console.log('✅ Sol·licitud enviada amb èxit!')
      toast.success('Sol·licitud enviada correctament!')
      showCart.value = false
      cartStore.clearCart()
    } else {
      console.error('❌ Error enviant sol·licitud:', result.error)
      toast.error(result.error || 'Error enviant la sol·licitud')
    }
  } catch (error) {
    console.error('🔥 Excepció no controlada al submitCart:', error)
    toast.error('Error de connexió: ' + (error.message || 'Desconegut'))
  } finally {
    submitting.value = false
    console.log('🏁 Procés de submitCart finalitzat.')
  }
}

const manualRefresh = async () => {
  if (isRefreshing.value) return
  
  isRefreshing.value = true
  try {
    console.log('🔄 Manual refresh triggered')
    await phaseStore.fetchCurrentPhase()
    await workshopStore.fetchWorkshops()
    toast.success('Dades actualitzades!')
  } catch (error) {
    console.error('Error refreshing:', error)
    toast.error('Error actualitzant les dades')
  } finally {
    isRefreshing.value = false
  }
}

onMounted(async () => {
  await phaseStore.fetchCurrentPhase()
  await workshopStore.fetchWorkshops()
  cartStore.loadCart()
  
  // Auto-refresh phase every 30 seconds to detect changes
  const phaseRefreshInterval = setInterval(async () => {
    console.log('🔄 Auto-checking for phase updates...')
    await phaseStore.fetchCurrentPhase()
  }, 30000) // 30 seconds
  
  // Cleanup interval on component unmount
  onUnmounted(() => {
    clearInterval(phaseRefreshInterval)
  })
})
</script>

<style scoped>
/* PAGE LAYOUT (Matches AdminCenters.vue) */
.page-container {
  padding: 2rem 2.5rem;
  background: #f4f6fb;
  min-height: 100vh;
}

/* HEADER */
.page-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding-bottom: 1.2rem;
  border-bottom: 3px solid #c59d32;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.header-title h1 {
  margin: 0;
  color: #1f2937;
  font-size: 2.2rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.subtitle {
  margin: 0.35rem 0 0;
  color: #6b7280;
  font-size: 1rem;
  font-weight: 500;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1; /* Grow to fill space */
}

.today-badge {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.6rem 1.2rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 50px;
  font-size: 0.95rem;
  font-weight: 600;
  color: #64748b;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.today-badge i {
  color: #C5A059;
}

/* REFRESH BUTTON */
.btn-refresh {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 50%;
  color: #C5A059;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.btn-refresh:hover:not(:disabled) {
  background: #C5A059;
  color: white;
  transform: scale(1.05);
  box-shadow: 0 4px 8px rgba(197, 160, 89, 0.3);
}

.btn-refresh:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-refresh i {
  font-size: 1rem;
}

/* ACTIONS & BUTTONS */
.btn-cta {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  background: linear-gradient(135deg, #d6b14c 0%, #b88923 100%);
  color: #fff;
  border: none;
  padding: 0.85rem 1.4rem;
  border-radius: 12px;
  font-weight: 700;
  font-size: 0.98rem;
  cursor: pointer;
  box-shadow: 0 10px 24px rgba(184, 137, 35, 0.35);
  transition: all 0.2s ease;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  margin-left: auto; /* Push to far right */
}

.btn-cta:hover { 
  transform: translateY(-2px); 
  box-shadow: 0 14px 30px rgba(184,137,35,0.4); 
}

.btn-cta:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.icon-stack {
  position: relative;
  display: flex;
  align-items: center;
}

.count-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #1f2937;
  color: #fff;
  font-size: 0.65rem;
  font-weight: 800;
  padding: 2px 5px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.2);
}

/* PHASE BADGE */
.phase-badge {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.6rem 1.2rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 50px;
  font-size: 0.95rem;
  font-weight: 700;
  color: #64748b;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.phase-demand { 
  background: linear-gradient(135deg, #fffbf0 0%, #fff6d9 100%);
  color: #b08d4b; 
  border: 1px solid #e6c888;
  box-shadow: 0 4px 12px rgba(197, 160, 89, 0.15);
}

.phase-scheduling {
  border: 1px solid #C5A059;
  background: linear-gradient(135deg, #C5A059 0%, #b08d4b 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(184, 137, 35, 0.3);
}

.phase-publication { 
  background: white;
  color: #C5A059; 
  border: 1px solid #C5A059; 
}

/* FILTERS CARD */
.filter-card {
  background: #fff;
  border-radius: 16px;
  padding: 1.25rem;
  border: 1px solid #e2e8f0;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
  display: flex;
  align-items: center;
}

.filter-row {
  display: flex;
  gap: 1.5rem;
  align-items: center;
  width: 100%;
}

.search-group {
  flex: 1.5;
  position: relative;
}

.search-icon {
  position: absolute;
  left: 1.2rem;
  top: 50%;
  transform: translateY(-50%);
  color: #c59d32; /* Gold icon */
  font-size: 1.1rem;
}

.form-control, .form-select {
  width: 100%;
  padding: 0.9rem 1rem 0.9rem 3rem;
  border: 2px solid #f1f5f9;
  border-radius: 12px;
  font-size: 0.95rem;
  background: #f8fafc;
  transition: all 0.2s ease;
  color: #334155;
  font-weight: 500;
}

.select-group {
  display: flex;
  gap: 1rem;
  flex: 2;
}

.form-select {
  padding-left: 1.2rem;
  padding-right: 3rem;
  cursor: pointer;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 1rem center;
  background-size: 1.5em 1.5em;
  background-repeat: no-repeat;
  appearance: none;
}

.form-control:focus, .form-select:focus {
  outline: none;
  border-color: #c59d32;
  background: #fff;
  box-shadow: 0 4px 12px rgba(197, 157, 50, 0.1);
}

.form-control::placeholder {
  color: #94a3b8;
  font-weight: 400;
}

/* GRID & CARDS */
/* GRID & CARDS - GOLD EDITION */
.workshops-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 2rem;
  padding: 1rem 0 3rem 0;
}

.workshop-card {
  background: #ffffff;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  transition: all 0.4s ease;
  display: flex;
  flex-direction: column;
  position: relative;
  height: 100%;
}

.workshop-card:hover {
  transform: translateY(-8px);
  border-color: #c59d32; /* Gold Border */
  box-shadow: 0 20px 25px -5px rgba(197, 157, 50, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.card-header-image {
  position: relative;
  height: 200px;
  background: #1e293b;
  overflow: hidden;
}

.card-header-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
  opacity: 0.95;
}

.workshop-card:hover .card-header-image img {
  transform: scale(1.08);
  opacity: 1;
}

.card-header-image::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(to bottom, transparent 60%, rgba(15, 23, 42, 0.6));
  pointer-events: none;
}

.workshop-overlay {
  position: absolute;
  top: 1rem;
  right: 1rem;
  z-index: 2;
}

.badge-overlay {
  background: #c59d32; /* Gold Background */
  color: #ffffff;
  padding: 4px 10px;
  border-radius: 4px; /* More angular/formal */
  font-size: 0.75rem;
  font-weight: 700;
  box-shadow: 0 4px 6px rgba(0,0,0,0.2);
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.course-badge {
  position: absolute;
  top: 1rem;
  left: 1rem;
  background: #1e293b; /* Dark Slate */
  color: #c59d32; /* Gold Text */
  border: 1px solid #c59d32;
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 700;
  z-index: 2;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.card-body {
  padding: 1.5rem;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.card-title {
  font-family: 'Playfair Display', serif; /* Or keep sans if unavailable, but serif fits luxury */
  font-size: 1.35rem;
  margin: 0 0 0.75rem 0;
  color: #1e293b;
  font-weight: 700;
  line-height: 1.3;
}

.description-clamp {
  color: #64748b;
  font-size: 0.95rem;
  line-height: 1.6;
  margin-bottom: 1.5rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
}

.workshop-tags {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}

.tag {
  font-size: 0.7rem;
  padding: 4px 10px;
  border-radius: 2px;
  font-weight: 600;
  text-transform: uppercase;
  display: flex;
  align-items: center;
  gap: 6px;
  letter-spacing: 0.05em;
}

.tag-navy { 
  background: #f8fafc; 
  color: #475569; 
  border: 1px solid #cbd5e1;
}
.tag-blue { 
  background: #fffbeb; /* Light Amber/Start */
  color: #b45309; /* Dark Amber */
  border: 1px solid #fcd34d; 
}

.workshop-meta {
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.85rem;
  color: #475569;
  font-weight: 500;
  margin-bottom: 1.5rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.meta-item i {
  color: #c59d32;
}

/* FOOTER BUTTONS - GOLD THEME */
.workshop-footer {
  display: flex;
  gap: 12px;
}

.btn-action {
  flex: 1;
  padding: 10px 16px;
  border-radius: 4px; /* More sleek/square */
  border: none;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-family: 'Inter', sans-serif;
}

.btn-success {
  background: #c59d32; /* GOLD MAIN */
  color: white;
  border: 1px solid #b45309;
  box-shadow: 0 2px 4px rgba(197, 157, 50, 0.2);
}
.btn-success:hover {
  background: #b45309; /* Darker Gold/Bronze */
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(197, 157, 50, 0.3);
}

.btn-outline {
  background: transparent;
  border: 1px solid #cbd5e1;
  color: #64748b;
}
.btn-outline:hover {
  border-color: #c59d32;
  color: #c59d32;
  background: #fffbeb;
}

/* MODALS */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.modal-content {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 650px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  background: #fbf7ef; /* Kairos cream bg */
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 16px 16px 0 0;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.25rem;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-close {
  background: transparent;
  border: none;
  font-size: 1.25rem;
  color: #9ca3af;
  cursor: pointer;
}

.btn-close:hover { color: #1f2937; }

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
  border-radius: 0 0 16px 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

/* CART MODAL PREMIUM STYLES */
.cart-modal {
  max-width: 500px;
}

.cart-items {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-height: 400px;
  overflow-y: auto;
  padding-right: 0.5rem;
}

.cart-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  transition: all 0.2s ease;
}

.cart-item:hover {
  background: #fff;
  border-color: #c59d32;
  box-shadow: 0 4px 12px rgba(197, 157, 50, 0.1);
  transform: translateX(4px);
}

.cart-item-img {
  width: 60px;
  height: 60px;
  border-radius: 8px;
  overflow: hidden;
  flex-shrink: 0;
  border: 1px solid #e2e8f0;
}

.cart-item-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cart-item-info {
  flex: 1;
}

.cart-item-info h4 {
  margin: 0 0 0.25rem 0;
  font-size: 1rem;
  color: #1e293b;
  font-weight: 700;
  line-height: 1.2;
}

.cart-item-info p {
  margin: 0;
  font-size: 0.85rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.btn-remove {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: #fee2e2;
  color: #ef4444;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-left: auto; /* Ensure it stays on the right */
}

.btn-remove:hover {
  background: #ef4444;
  color: white;
  transform: rotate(8deg);
}

.empty-cart {
  text-align: center;
  padding: 3rem 1rem;
  color: #94a3b8;
}

.cart-summary {
  font-size: 1rem;
  color: #475569;
}

/* Modal Transitions */
.list-enter-active,
.list-leave-active {
  transition: all 0.4s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

/* Detail Modal Specifics */
.detail-image {
  width: 100%;
  height: 250px;
  object-fit: cover;
  border-radius: 10px;
  margin-bottom: 2rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.detail-section h3 {
  font-size: 1.2rem;
  color: #1f2937;
  margin-bottom: 0.75rem;
  font-weight: 700;
  border-bottom: 2px solid #f3f4f6;
  padding-bottom: 0.5rem;
}

.detail-content p {
  line-height: 1.6;
  color: #4b5563;
  margin-bottom: 2rem;
}

/* Extended Grid Layout */
.extended-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 2rem;
  background: #f8fafc;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.info-group h4 {
  font-size: 1rem;
  color: #1f2937;
  margin: 0 0 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 700;
}

.info-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.6rem;
  font-size: 0.95rem;
  border-bottom: 1px dashed #e2e8f0;
  padding-bottom: 0.4rem;
}

.info-row:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.info-row .label {
  color: #64748b;
  font-weight: 500;
}

.info-row .value {
  color: #1f2937;
  font-weight: 600;
  text-align: right;
}

.btn-secondary {
  background: #f3f4f6;
  color: #4b5563;
  border: none;
  padding: 0.75rem 1.2rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}

.btn-secondary:hover { background: #e5e7eb; }

/* Responsive */
@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-actions { width: 100%; justify-content: space-between; }
  .filter-row { flex-direction: column; }
  .extended-grid { grid-template-columns: 1fr; gap: 1.5rem; }
}

/* PAGINATION CONTROLS */
.pagination-controls {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1.5rem;
  padding: 1rem 0;
}

.btn-page {
  background: #fff;
  border: 1px solid #e2e8f0;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  color: #64748b;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-page:hover:not(:disabled) {
  background: #f8fafc;
  color: #c59d32;
  border-color: #c59d32;
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  font-weight: 600;
  color: #475569;
}
</style>
