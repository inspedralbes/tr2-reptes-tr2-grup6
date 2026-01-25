<template>
  <div class="admin-gallery">
    <div class="header">
      <div class="header-content">
        <h1><i class="fas fa-images" style="color: #C5A059; margin-right: 12px;"></i> Galeria - Gestió</h1>
        <p class="subtitle">Gestiona les fotos pendents i les publicades</p>
      </div>
      <div class="header-actions">
        <!-- TABS -->
        <div class="tabs-group">
            <button 
                class="tab-btn" 
                :class="{ active: activeTab === 'pending' }" 
                @click="activeTab = 'pending'"
            >
                <i class="fas fa-hourglass-half"></i> Pendents
            </button>
             <button 
                class="tab-btn" 
                :class="{ active: activeTab === 'approved' }" 
                @click="activeTab = 'approved'"
            >
                <i class="fas fa-check-circle"></i> Publicades
            </button>
        </div>

         <button class="btn-select-all" @click="selectAll">
            <i class="fas fa-check-double"></i> 
            {{ selectedIds.length > 0 && selectedIds.length === currentList.length ? 'Deseleccionar' : 'Seleccionar Tot' }}
         </button>
      </div>
    </div>

    <div v-if="loading" class="loading">Carregant...</div>
    
    <div v-else-if="currentList.length === 0" class="empty-state">
      <div class="empty-icon"><i class="fas fa-box-open"></i></div>
      <h3>No hi ha fotos {{ activeTab === 'pending' ? 'pendents' : 'publicades' }}</h3>
      <p v-if="activeTab === 'approved'">Les fotos aprovades apareixeran aquí.</p>
      <p v-else>Tot al dia! No hi ha sol·licituds.</p>
    </div>

    <div v-else class="photos-grid">
      <div 
        v-for="photo in currentList" 
        :key="photo.id" 
        class="photo-card"
        :class="{ 'is-selected': selectedIds.includes(photo.id), 'is-featured': photo.is_featured == 1 }"
        @click="toggleSelection(photo.id)"
      >
        <div class="selection-overlay">
            <div class="checkbox-circle">
                <i class="fas fa-check" v-if="selectedIds.includes(photo.id)"></i>
            </div>
        </div>

        <!-- Featured Toggle (Only for Approved) -->
        <button 
            v-if="activeTab === 'approved'"
            class="star-toggle" 
            :class="{ active: photo.is_featured == 1 }"
            @click.stop="toggleFeatured(photo)"
            title="Destacar a la galeria"
        >
            <i class="fas fa-star"></i>
        </button>

        <div class="img-wrapper">
          <img :src="getImageUrl(photo.file_path)" :alt="photo.title" loading="lazy" />
          <div class="overlay-info">
             <span class="pill-badge">
               <i class="fas fa-user"></i> {{ photo.uploader_name?.split(' ')[0] }}
             </span>
          </div>
        </div>
        
        <div class="card-body">
          <div class="card-header">
             <span class="workshop-tag">{{ photo.workshop_title }}</span>
             <h4 class="card-title">{{ photo.title }}</h4>
          </div>
          <div class="meta-info">
            <i class="far fa-clock"></i> {{ formatDate(photo.created_at) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Floating Action Bar -->
    <transition name="slide-up">
        <div v-if="selectedIds.length > 0" class="floating-bar">
            <div class="selected-count">{{ selectedIds.length }}</div>
            <div class="bar-actions">
                <button class="btn-bar-reject" @click.stop="rejectSelected">
                    <i class="fas fa-trash-alt"></i> {{ activeTab === 'approved' ? 'Esborrar' : 'Rebutjar' }}
                </button>
                <button v-if="activeTab === 'pending'" class="btn-bar-approve" @click.stop="approveSelected">
                    <i class="fas fa-check"></i> Aprovar
                </button>
            </div>
        </div>
    </transition>

    <!-- Global Confirmation Modal -->
    <ConfirmModal 
        :isOpen="confirmModal.isOpen"
        :title="confirmModal.title"
        :message="confirmModal.message"
        :type="confirmModal.type"
        :confirmText="confirmModal.confirmText"
        @close="confirmModal.isOpen = false"
        @confirm="handleConfirm"
    />

  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import ConfirmModal from '../components/ConfirmModal.vue'; 
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const activeTab = ref('pending'); 
const pendingPhotos = ref([]);
const approvedPhotos = ref([]); 
const loading = ref(true);
const processing = ref(null);
const selectedIds = ref([]);
const currentList = computed(() => activeTab.value === 'pending' ? pendingPhotos.value : approvedPhotos.value);

// Modal State
const confirmModal = ref({
    isOpen: false,
    title: '',
    message: '',
    type: 'primary',
    confirmText: 'Confirmar',
    onConfirm: null
});

const openConfirm = ({ title, message, type, text, action }) => {
    confirmModal.value = {
        isOpen: true,
        title,
        message,
        type: type || 'primary',
        confirmText: text || 'Acceptar',
        onConfirm: action
    };
};

const handleConfirm = () => {
    if (confirmModal.value.onConfirm) confirmModal.value.onConfirm();
    confirmModal.value.isOpen = false;
};

const getImageUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    const baseURL = import.meta.env.VITE_API_URL || 'http://localhost:8000';
    return `${baseURL}${path}`;
};

const formatDate = (date) => new Date(date).toLocaleDateString('ca-ES', { 
    day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' 
});

const loadPending = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/admin/gallery/pending', {
             headers: { 'Authorization': `Bearer ${authStore.token}` }
        });
        if (response.data.success) {
            pendingPhotos.value = response.data.data;
        }
    } catch (error) {
        console.error("Error loading pending", error);
    } finally {
        if (activeTab.value === 'pending') loading.value = false;
    }
};

const loadApproved = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/gallery', {
             headers: { 'Authorization': `Bearer ${authStore.token}` }
        }); 
        if (response.data.success) {
            approvedPhotos.value = response.data.data;
        }
    } catch (error) {
        console.error("Error loading approved", error);
    } finally {
        if (activeTab.value === 'approved') loading.value = false;
    }
};

// Watch for tab changes to reload data
watch(activeTab, (newTab) => {
    selectedIds.value = [];
    if (newTab === 'pending') loadPending();
    else loadApproved();
});

const toggleSelection = (id) => {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter(itemId => itemId !== id);
    } else {
        selectedIds.value.push(id);
    }
};

const selectAll = () => {
    const list = currentList.value;
    if (selectedIds.value.length === list.length) {
        selectedIds.value = [];
    } else {
        selectedIds.value = list.map(p => p.id);
    }
};

const approveSelected = () => {
    openConfirm({
        title: 'Aprovar Fotos',
        message: `Estàs segur que vols aprovar ${selectedIds.value.length} fotos seleccionades?`,
        type: 'primary',
        text: 'Aprovar',
        action: async () => {
             processing.value = 'batch';
             for (const id of selectedIds.value) {
                try { 
                    await axios.post(`/api/admin/gallery/${id}/approve`, {}, {
                        headers: { 'Authorization': `Bearer ${authStore.token}` }
                    }); 
                } catch(e) {}
             }
             await loadPending();
             selectedIds.value = [];
             processing.value = null;
        }
    });
};

const rejectSelected = () => {
    openConfirm({
        title: activeTab.value === 'approved' ? 'Esborrar Fotos' : 'Rebutjar Fotos',
        message: activeTab.value === 'approved' 
           ? `Estàs segur que vols esborrar ${selectedIds.value.length} fotos permanentment?`
           : `Estàs segur que vols rebutjar les fotos seleccionades?`,
        type: 'danger',
        text: activeTab.value === 'approved' ? 'Esborrar' : 'Rebutjar',
        action: async () => {
             processing.value = 'batch';
             for (const id of selectedIds.value) {
                try { 
                    await axios.post(`/api/admin/gallery/${id}/reject`, {}, {
                        headers: { 'Authorization': `Bearer ${authStore.token}` }
                    }); 
                } catch(e) {}
             }
             if (activeTab.value === 'pending') await loadPending();
             else await loadApproved();
             selectedIds.value = [];
             processing.value = null;
        }
    });
};

const toggleFeatured = async (photo) => {
    try {
        const response = await axios.post(`/api/admin/gallery/${photo.id}/toggle-featured`, {}, {
             headers: { 'Authorization': `Bearer ${authStore.token}` }
        });
        if (response.data.success) {
            photo.is_featured = response.data.is_featured ? 1 : 0;
        }
    } catch (error) {
        console.error("Failed to toggle featured", error);
    }
};

onMounted(() => {
    loadPending();
});
</script>

<style scoped>
/* Professional & Clean Admin Gallery Styles */
.admin-gallery { 
    width: 100%; 
    max-width: none;
    margin: 0;
    font-family: 'Inter', sans-serif;
    background-color: transparent; 
    min-height: 100vh;
    padding-bottom: 120px;
}

.header {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex; justify-content: space-between; align-items: center;
}

.header-content h1 { 
    font-family: 'Outfit', sans-serif; 
    color: #0f172a; 
    font-size: 2rem; 
    font-weight: 700;
    margin: 0; 
    letter-spacing: -0.5px;
}

.subtitle { color: #64748b; font-size: 1rem; margin-top: 5px; }

.header-actions { display: flex; gap: 1rem; }

.tabs-group {
    background: #f1f5f9;
    padding: 0.3rem; 
    border-radius: 12px;
    display: flex; gap: 0.5rem;
    margin-right: 1.5rem;
}
.tab-btn {
    padding: 0.6rem 1.2rem;
    border: none; background: transparent;
    color: #64748b; font-weight: 600;
    border-radius: 8px; cursor: pointer;
    transition: all 0.2s;
    display: flex; align-items: center; gap: 0.5rem;
}
.tab-btn:hover { color: #0f172a; background: rgba(255,255,255,0.5); }
.tab-btn.active {
    background: white; color: #0f172a;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.btn-select-all {
    background: white; border: 1px solid #cbd5e1; color: #475569;
    padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600;
    cursor: pointer; transition: all 0.2s;
    display: flex; align-items: center; gap: 0.5rem;
}
.btn-select-all:hover { background: #f8fafc; border-color: #94a3b8; }

/* Grid Layout */
.photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    align-items: start;
}

/* Card Styling - DARK THEME INTEGRATION */
.photo-card {
    background: #1e293b; 
    border-radius: 16px; 
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    border: 2px solid transparent; 
    display: flex; flex-direction: column;
    transition: all 0.25s ease;
    cursor: pointer;
    position: relative;
    color: white; 
}

.photo-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);
    background: #253346; 
}

.photo-card.is-selected {
    border-color: #C5A059;
    background: #253346;
}

.photo-card.is-featured {
    border-color: #C5A059;
}

/* Checkbox Overlay */
.selection-overlay {
    position: absolute; top: 12px; right: 12px; z-index: 10;
}
.checkbox-circle {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: rgba(30, 41, 59, 0.6);
    border: 2px solid rgba(255,255,255,0.5);
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
    color: white;
}
.photo-card:hover .checkbox-circle { border-color: white; background: rgba(30, 41, 59, 0.8); }
.photo-card.is-selected .checkbox-circle {
    background: #C5A059; border-color: #C5A059; color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* STAR TOGGLE */
.star-toggle {
    position: absolute; top: 12px; left: 12px; z-index: 10;
    width: 32px; height: 32px;
    border-radius: 50%;
    border: none;
    background: rgba(30, 41, 59, 0.6);
    color: #94a3b8;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
    font-size: 1rem;
    backdrop-filter: blur(4px);
}
.star-toggle:hover { transform: scale(1.1); background: white; color: #C5A059; }
.star-toggle.active {
    background: white; color: #C5A059;
    box-shadow: 0 0 10px rgba(197, 160, 89, 0.5);
}

/* Image */
.img-wrapper {
    position: relative; 
    height: 220px; 
    background: #0f172a;
}
.img-wrapper img { 
    width: 100%; height: 100%; object-fit: cover; 
    transition: transform 0.5s;
}
.photo-card:hover .img-wrapper img { transform: scale(1.05); }

/* Badges */
.overlay-info {
    position: absolute; bottom: 10px; left: 10px; right: 10px;
    display: flex; justify-content: space-between;
    pointer-events: none;
    z-index: 5;
}

.pill-badge {
    background: rgba(15, 23, 42, 0.75); 
    color: #f1f5f9;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem; 
    font-weight: 500;
    backdrop-filter: blur(4px);
    display: flex; align-items: center; gap: 6px;
    border: 1px solid rgba(255,255,255,0.1);
}

/* Content */
.card-body { 
    padding: 1.25rem; 
    display: flex; flex-direction: column; 
    gap: 0.6rem;
}

.card-header { display: flex; flex-direction: column; gap: 0.3rem; }

.workshop-tag {
    color: #C5A059; 
    font-size: 0.75rem; 
    font-weight: 800; 
    letter-spacing: 0.5px;
    text-transform: uppercase;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.card-title {
    margin: 0; font-size: 1.15rem; font-weight: 700; 
    color: #f8fafc; 
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.meta-info {
    font-size: 0.8rem; color: #64748b;
    display: flex; align-items: center; gap: 0.5rem;
    margin-top: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid #334155; 
}

/* Floating Action Bar */
.floating-bar {
    position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%);
    background: #0f172a; 
    color: white;
    padding: 0.8rem 1rem; border-radius: 50px;
    display: flex; align-items: center; gap: 1.5rem;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
    z-index: 100;
    border: 1px solid #334155;
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.selected-count { 
    background: #334155; padding: 0.4rem 1rem; border-radius: 20px;
    font-weight: 600; font-size: 0.95rem;
}

.bar-actions { display: flex; gap: 0.8rem; }

.btn-bar-approve {
    background: #C5A059; color: white; padding: 0.6rem 1.5rem; border-radius: 30px;
    border: none; font-weight: 600; cursor: pointer; transition: all 0.2s;
    display: flex; align-items: center; gap: 0.5rem;
}
.btn-bar-approve:hover { background: #d6af66; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(197, 160, 89, 0.4); }

.btn-bar-reject {
    background: #334155; color: #ef4444; padding: 0.6rem 1.5rem; border-radius: 30px;
    border: 1px solid #ef4444; font-weight: 600; cursor: pointer; transition: all 0.2s;
    display: flex; align-items: center; gap: 0.5rem;
}
.btn-bar-reject:hover { background: #ef4444; color: white; transform: translateY(-2px); }

@keyframes slideUp {
    from { transform: translate(-50%, 150%); opacity: 0; }
    to { transform: translate(-50%, 0); opacity: 1; }
}

/* Empty State */
.empty-state { 
    text-align: center; padding: 4rem 2rem; 
    background: #1e293b; 
    border-radius: 16px; 
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
    max-width: 400px; margin: 4rem auto;
    color: white;
}
.empty-icon { 
    font-size: 3.5rem; color: #C5A059; margin-bottom: 1.5rem;
    animation: float 3s ease-in-out infinite; 
}

.empty-state h3 { 
    font-family: 'Outfit', sans-serif; font-size: 1.8rem; margin-bottom: 0.5rem; 
    color: #f8fafc;
    font-weight: 700;
}
.empty-state p { color: #94a3b8; font-size: 1.1rem; }

@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}
</style>
