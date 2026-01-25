<template>
  <div class="page-container">
    <!-- Header Premium -->
    <div class="header-section">
      <div class="header-content">
        <router-link to="/" class="btn-back">
          <i class="fas fa-arrow-left"></i> Tornar a l'Inici
        </router-link>
        <h1><i class="fas fa-images text-gold"></i> Galeria Pública ENGINY</h1>
        <p class="subtitle">Explora els projectes i activitats realitzades als nostres tallers tecnològics</p>
      </div>
     <div v-if="!activeAlbum" class="header-stats">
        <div class="stat-pill">
          <i class="fas fa-camera"></i>
          <span>{{ stats.total_images }} imatges</span>
        </div>
        <div class="stat-pill">
          <i class="fas fa-eye"></i>
          <span>{{ totalViews }} vistes</span>
        </div>
      </div>
    </div>

    <!-- Controls Bar -->
    <div v-if="!activeAlbum" class="controls-bar">
      <div class="filter-tabs">
        <button 
          :class="['tab-pill', { active: viewMode === 'featured' }]"
          @click="viewMode = 'featured'"
        >
          <i class="fas fa-star"></i> Destacades
        </button>
        <button 
          :class="['tab-pill', { active: viewMode === 'all' }]"
          @click="viewMode = 'all'"
        >
          <i class="fas fa-th-large"></i> Totes
        </button>
        <button 
          :class="['tab-pill', { active: viewMode === 'albums' }]"
          @click="viewMode = 'albums'"
        >
          <i class="fas fa-folder-open"></i> Àlbums
        </button>
      </div>

      <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input 
          type="text"
          v-model="searchQuery"
          placeholder="Cerca per títol, taller o centre..."
          class="search-input"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Carregant la galeria...</p>
    </div>

    <!-- IMAGES GRID -->
    <div v-else-if="viewMode !== 'albums'" class="gallery-grid">
      <div 
        v-for="image in filteredImages" 
        :key="image.id"
        class="image-card"
        @click="openLightbox(image)"
      >
        <div class="card-image-wrapper">
           <img 
            :src="getImageUrl(image.file_path)" 
            :alt="image.alt_text || image.title"
            class="card-img"
            loading="lazy"
          />
          <div class="overlay-gradient"></div>
          <div class="card-content">
            <h3 class="card-title">{{ image.title }}</h3>
            <span class="card-workshop"><i class="fas fa-hammer"></i> {{ image.workshop_title }}</span>
          </div>
           <div v-if="image.is_featured" class="badge-featured">
            <i class="fas fa-star"></i>
          </div>
        </div>
        <div class="card-footer">
          <div class="photographer">
            <i class="fas fa-camera-retro"></i> {{ image.photographer_name || 'Anònim' }}
          </div>
          <div class="views-count">
            <i class="far fa-eye"></i> {{ image.view_count }}
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredImages.length === 0" class="empty-state">
        <div class="empty-icon-circle">
          <i class="far fa-images"></i>
        </div>
        <h3>No s'han trobat imatges</h3>
        <p>Prova amb altres termes de cerca o filtres</p>
      </div>
    </div>

    <!-- ALBUMS GRID -->
    <div v-else-if="viewMode === 'albums' && !activeAlbum" class="albums-grid">
      <div 
        v-for="album in albums" 
        :key="album.id"
        class="album-card"
        @click="openAlbum(album)"
      >
        <div class="album-cover">
          <div class="album-icon-wrapper">
            <i class="fas fa-folder"></i>
          </div>
          <div class="album-overlay">
            <span class="img-count">{{ album.image_count }} fotos</span>
          </div>
        </div>
        <div class="album-body">
          <h3>{{ album.title }}</h3>
          <p class="album-desc">{{ album.description }}</p>
          <div class="album-meta">
            <span><i class="far fa-calendar"></i> {{ album.academic_year }}</span>
            <span><i class="far fa-eye"></i> {{ album.view_count }}</span>
          </div>
        </div>
      </div>
       <div v-if="albums.length === 0" class="empty-state">
        <div class="empty-icon-circle">
          <i class="far fa-folder-open"></i>
        </div>
        <h3>No hi ha àlbums disponibles</h3>
      </div>
    </div>

    <!-- LIGHTBOX -->
    <transition name="fade">
      <div v-if="lightboxImage" class="lightbox-overlay" @click="closeLightbox">
        <button class="btn-close-lightbox" @click="closeLightbox"><i class="fas fa-times"></i></button>
        
        <button v-if="currentImageIndex > 0" class="nav-btn prev" @click.stop="previousImage">
          <i class="fas fa-chevron-left"></i>
        </button>
        
        <button v-if="currentImageIndex < filteredImages.length - 1" class="nav-btn next" @click.stop="nextImage">
          <i class="fas fa-chevron-right"></i>
        </button>

        <div class="lightbox-modal" @click.stop>
          <div class="lightbox-media">
             <img 
              :src="getImageUrl(lightboxImage.file_path)" 
              :alt="lightboxImage.title"
              class="lightbox-img"
            />
          </div>
          <div class="lightbox-details">
            <div class="details-header">
              <h2>{{ lightboxImage.title }}</h2>
              <div class="details-actions">
                <button @click="downloadImage" class="btn-icon" title="Descarregar">
                  <i class="fas fa-download"></i>
                </button>
                <button @click="shareImage" class="btn-icon" title="Compartir">
                  <i class="fas fa-share-alt"></i>
                </button>
              </div>
            </div>
            
            <p class="description">{{ lightboxImage.description || 'Sense descripció' }}</p>
            
            <div class="meta-grid">
               <div class="meta-item">
                <label>Taller</label>
                <span>{{ lightboxImage.workshop_title }}</span>
              </div>
              <div class="meta-item">
                <label>Centre</label>
                <span>{{ lightboxImage.center_name }}</span>
              </div>
              <div class="meta-item">
                <label>Autoria</label>
                <span>{{ lightboxImage.photographer_name }}</span>
              </div>
               <div class="meta-item">
                <label>Data</label>
                <span>{{ formatDate(lightboxImage.taken_at || lightboxImage.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- ALBUM DETAIL VIEW (In-Page Hero Design) -->
    <transition name="fade">
      <div v-if="activeAlbum" class="album-detail-view animate-fade-in relative z-10 w-full">
         
          <!-- Album Header Hero -->
          <div class="album-hero">
              <div class="hero-content">
                   <button 
                    @click="closeAlbum" 
                    class="btn-back-hero"
                  >
                    <div class="icon-circle">
                       <i class="fas fa-arrow-left"></i>
                    </div>
                    <span>Tornar a la Galeria</span>
                  </button>
  
                  <div class="hero-title-block">
                      <div class="hero-text">
                          <h2>{{ activeAlbum.title }}</h2>
                          <p>{{ activeAlbum.description }}</p>
                      </div>
                      <div class="hero-badges">
                          <span class="badge-hero">
                              <i class="far fa-calendar-alt text-gold mr-2"></i> {{ activeAlbum.academic_year }}
                          </span>
                          <span class="badge-hero">
                              <i class="far fa-images text-gold mr-2"></i> {{ albumImages.length }} imatges
                          </span>
                      </div>
                  </div>
              </div>
          </div>
  
        <!-- Grid de Fotos de l'Àlbum -->
        <div class="album-grid-container">
          <div class="gallery-grid">
              <div 
                v-for="(image, index) in albumImages" 
                :key="image.id"
                class="image-card group"
                @click="openLightbox(image)"
              >
                <div class="card-image-wrapper">
                    <img 
                      :src="getImageUrl(image.file_path)" 
                      class="card-img"
                      loading="lazy"
                    >
                    <!-- Overlay Content -->
                    <div class="overlay-gradient">
                       <div class="card-content">
                           <h3 class="card-title">{{ image.title }}</h3>
                           <div class="card-meta-row">
                               <span class="author"><i class="fas fa-camera"></i> {{ image.photographer_name || 'Anònim' }}</span>
                               <span class="views text-gold"><i class="far fa-eye"></i> {{ image.view_count }}</span>
                           </div>
                       </div>
                    </div>
                </div>
              </div>
          </div>
  
          <!-- Empty State -->
          <div v-if="albumImages.length === 0 && !loadingAlbum" class="empty-state-card">
              <div class="empty-icon-circle">
                  <i class="far fa-images"></i>
              </div>
              <p>Aquest àlbum encara no té imatges.</p>
          </div>
        </div>
  
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

// State (HMR Trigger)
const loading = ref(false);
const loadingAlbum = ref(false);
const images = ref([]);
const albums = ref([]);
const viewMode = ref('all'); // Changed from 'featured'
const searchQuery = ref('');
const lightboxImage = ref(null);
const currentImageIndex = ref(0);
const activeAlbum = ref(null);
const albumImages = ref([]);

const stats = ref({
  total_images: 0,
  total_views: 0,
  public_images: 0,
  featured_images: 0
});

// Computed
const filteredImages = computed(() => {
  let result = [...images.value];
  
  // Filter by search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(img => 
      img.title.toLowerCase().includes(query) || 
      (img.workshop_title && img.workshop_title.toLowerCase().includes(query)) ||
      (img.center_name && img.center_name.toLowerCase().includes(query))
    );
  }

  // Sort by Views if Featured
  if (viewMode.value === 'featured') {
      result.sort((a, b) => (parseInt(b.view_count) || 0) - (parseInt(a.view_count) || 0));
  }
  
  return result;
});

// Dynamic Total Views (Sums currently loaded images + adds any not loaded if pagination existed, but here we load all)
// Better: Use stats.total_views as base but updated with local changes? 
// Simplest: Sum of images.value if we have all images. If paginated, we'd need to add local increments.
// Given we fetch all:
const totalViews = computed(() => {
  return images.value.reduce((sum, img) => sum + (parseInt(img.view_count) || 0), 0);
});

// Display logic
const displayedItems = computed(() => {
  if (activeTab.value === 'albums') return albums.value; // Implement album filtering if needed
  return filteredImages.value;
});

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000';

// Methods
const loadGallery = async () => {
  loading.value = true;
  try {
    const params = {};
    if (viewMode.value === 'featured') params.is_featured = 'true';
    
    // Clear mock timeout, rely on API
    const response = await axios.get(`${API_BASE}/api/gallery`, { params });
    if(response.data.success) {
      images.value = response.data.data;
    }

  } catch (error) {
    console.error('Error loading gallery:', error);
  } finally {
    loading.value = false;
  }
};

import { watch } from 'vue';
watch(viewMode, () => {
  activeAlbum.value = null; // Close album when switching tabs
  if (viewMode.value !== 'albums') {
     loadGallery();
  }
});

const loadAlbums = async () => {
  try {
     const response = await axios.get(`${API_BASE}/api/gallery/albums`);
     if(response.data.success) albums.value = response.data.data;
  } catch (error) { console.error(error); }
};

const loadStats = async () => {
  try {
     const response = await axios.get(`${API_BASE}/api/gallery/stats`);
     if(response.data.success) stats.value = response.data.data;
  } catch (error) { console.error(error); }
};

const openLightbox = async (image) => {
  lightboxImage.value = image;
  currentImageIndex.value = filteredImages.value.findIndex(img => img.id === image.id);
  document.body.style.overflow = 'hidden';
};

// Increment view on image change (Open or Navigate)
const incrementViewCount = async (image) => {
  if (!image) return;
  try {
    await axios.post(`${API_BASE}/api/gallery/${image.id}/view`);
    // Optimistic update
    image.view_count = (parseInt(image.view_count) || 0) + 1;
  } catch (err) {
    console.error('Failed to increment view', err);
  }
};

watch(lightboxImage, (newVal) => {
  if (newVal) {
    incrementViewCount(newVal);
  }
});

const closeLightbox = () => {
  lightboxImage.value = null;
  document.body.style.overflow = '';
};

const previousImage = () => {
  if (currentImageIndex.value > 0) {
    currentImageIndex.value--;
    lightboxImage.value = filteredImages.value[currentImageIndex.value];
  }
};

const nextImage = () => {
  if (currentImageIndex.value < filteredImages.value.length - 1) {
    currentImageIndex.value++;
    lightboxImage.value = filteredImages.value[currentImageIndex.value];
  }
};

const openAlbum = async (album) => {
  activeAlbum.value = album;
  loadingAlbum.value = true;
  // Removed body overflow hidden because Album View is full page content, not a modal
  
  // Filter images belonging to this album (by Workshop Title match)
  // Since we don't have direct relation ID in frontend list effectively, we match by title
  // Backend groups by workshop name.
  try {
     // Ensure we have current images
     if (images.value.length === 0) await loadGallery();

     albumImages.value = images.value.filter(img => 
        img.workshop_title === album.title || 
        img.workshop_title === album.title_original // Fallback if encoded differently
     );
     
     // Fallback if empty (e.g. mismatching strings), show all for workshop ID if we had it
     // Current mock/controller uses title grouping.
     
  } catch (e) {
      console.error(e);
  } finally {
      loadingAlbum.value = false;
  }
};

const closeAlbum = () => {
  activeAlbum.value = null;
  document.body.style.overflow = '';
};

const downloadImage = () => {
    // Logic
};

const shareImage = () => {
    // Logic
};

const getImageUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    const baseURL = import.meta.env.VITE_API_URL || 'http://localhost:8000';
    return `${baseURL}${path}`;
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('ca-ES', { day: 'numeric', month: 'long', year: 'numeric' });
};

onMounted(() => {
  loadGallery();
  loadStats();
  loadAlbums();
});
</script>

<style scoped>
/* Styling Light Mode (Reverted) */
.page-container {
  min-height: 100vh;
  background-color: #f8fafc;
  padding: 0 0 2rem 0;
  font-family: 'Inter', sans-serif;
  color: #1e293b;
}

/* Header */
.header-section {
  background: white;
  padding: 2.5rem 2rem;
  border-bottom: 3px solid #C5A059;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.header-content h1 {
  font-size: 2.2rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  display: flex; align-items: center; gap: 1rem;
}

.text-gold { color: #C5A059; }

.subtitle {
  color: #64748b;
  font-size: 1.1rem;
  margin: 0.5rem 0 0 0;
  max-width: 600px;
}

.header-stats {
    display: flex; gap: 1rem; align-items: center;
}

.stat-pill {
    background: #f8fafc;
    padding: 0.6rem 1.2rem;
    border-radius: 50px;
    font-size: 0.9rem;
    color: #475569;
    font-weight: 600;
    display: flex; align-items: center; gap: 0.8rem;
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
}
.stat-pill i { color: #C5A059; font-size: 1.1rem; }
.stat-pill:hover { background: white; border-color: #C5A059; transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }

/* Controls */
.controls-bar {
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.filter-tabs {
    display: flex;
    background: white;
    padding: 0.4rem;
    border-radius: 50px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.tab-pill {
    padding: 0.6rem 1.5rem;
    border: none; border-radius: 50px;
    background: transparent;
    color: #64748b;
    font-weight: 600; cursor: pointer; transition: all 0.3s;
    display: flex; align-items: center; gap: 0.5rem;
}

.tab-pill:hover { color: #0f172a; }
.tab-pill.active { background: #0f172a; color: #C5A059; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15); }

.search-wrapper { position: relative; width: 300px; }
.search-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.search-input {
    width: 100%;
    padding: 0.8rem 1rem 0.8rem 2.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 50px;
    background: white;
    color: #1e293b;
    transition: all 0.3s;
}
.search-input:focus { outline: none; border-color: #C5A059; box-shadow: 0 0 0 3px rgba(197, 160, 89, 0.1); background: white; }

/* Gallery Grid */
.gallery-grid {
    max-width: 1400px; margin: 0 auto; padding: 0 2rem;
    display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
}

.image-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    border: 1px solid #f1f5f9;
}

.image-card:hover { transform: translateY(-7px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); border-color: #C5A059; }

.card-image-wrapper {
    position: relative;
    aspect-ratio: 4/3;
    overflow: hidden;
}

.card-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
.image-card:hover .card-img { transform: scale(1.05); }

.overlay-gradient {
    position: absolute; bottom: 0; left: 0; right: 0; height: 75%;
    background: linear-gradient(to top, rgba(15, 23, 42, 0.9) 0%, rgba(15, 23, 42, 0.6) 40%, transparent 100%);
    opacity: 0.9; transition: opacity 0.3s;
    display: flex; flex-direction: column; justify-content: flex-end;
    padding: 1.5rem;
}
.image-card:hover .overlay-gradient { opacity: 1; }

.card-content {
    background: transparent;
}

.card-title { color: white; }
.card-workshop { color: #f1f5f9; }

.card-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #f1f5f9;
    background: white;
    color: #64748b;
}

/* Albums */
.albums-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2rem;
    max-width: 1400px; margin: 0 auto; padding: 0 2rem;
}

.album-card {
    background: white; border-radius: 16px; overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.3s;
    border: none;
}
.album-card:hover { transform: translateY(-7px); border: 1px solid #C5A059; box-shadow: 0 15px 30px rgba(0,0,0,0.1); }

.album-cover {
    height: 200px;
    background: #f1f5f9;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    color: #94a3b8;
    position: relative;
    transition: all 0.3s;
}
.album-card:hover .album-cover { background: #e2e8f0; color: #C5A059; }

.album-body { padding: 1.5rem; background: white; }
.album-body h3 { margin: 0 0 0.5rem 0; color: #0f172a; font-size: 1.2rem; }
.album-desc { color: #64748b; font-size: 0.9rem; line-height: 1.4; margin-bottom: 1rem; }
.album-meta { display: flex; gap: 1rem; font-size: 0.85rem; color: #94a3b8; }

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #94a3b8;
  text-decoration: none;
  font-weight: 500;
  margin-bottom: 1rem;
  transition: color 0.2s;
}
.btn-back:hover { color: #C5A059; }

/* Lightbox */
.lightbox-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.95);
    z-index: 1000;
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(5px);
}

.lightbox-modal {
    background: white;
    width: 90vw; max-width: 1200px; height: 85vh;
    border-radius: 12px;
    display: flex;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.lightbox-media {
    flex: 2;
    background: black;
    display: flex; align-items: center; justify-content: center;
}
.lightbox-img { max-width: 100%; max-height: 100%; object-fit: contain; }

.lightbox-details {
    flex: 1;
    padding: 2rem;
    background: white;
    display: flex; flex-direction: column;
    overflow-y: auto;
    min-width: 350px;
}

.details-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; }
.details-header h2 { margin: 0; font-size: 1.5rem; color: #0f172a; }
.btn-icon { background: #f1f5f9; border: none; width: 40px; height: 40px; border-radius: 50%; color: #64748b; cursor: pointer; transition: all 0.2s; margin-left: 0.5rem; }
.btn-icon:hover { background: #e2e8f0; color: #0f172a; }

.meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #f1f5f9; }
.meta-item label { display: block; font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.3rem; }
.meta-item span { font-weight: 500; color: #334155; }

.btn-close-lightbox { position: absolute; top: 2rem; right: 2rem; background: transparent; border: none; color: white; font-size: 2rem; cursor: pointer; opacity: 0.7; }
.btn-close-lightbox:hover { opacity: 1; }

.nav-btn { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.1); border: none; color: white; width: 60px; height: 60px; border-radius: 50%; font-size: 1.5rem; cursor: pointer; transition:all 0.2s; }
.nav-btn:hover { background: rgba(255,255,255,0.2); }
.prev { left: 2rem; }
.next { right: 2rem; }

/* Empty States */
.empty-state { text-align: center; padding: 4rem; color: #94a3b8; grid-column: 1/-1; }
.empty-icon-circle { width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.5rem; color: #cbd5e1; }

/* Animations */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.up-enter-active, .up-leave-active { transition: transform 0.3s ease-out; }
.up-enter-from, .up-leave-to { transform: translateY(100%); }

.album-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 900; }
.album-modal-container { position: absolute; bottom: 0; left: 0; right: 0; top: 50px; background: #f8fafc; border-radius: 24px 24px 0 0; display: flex; flex-direction: column; overflow: hidden; }
.album-modal-header { padding: 1.5rem 2rem; background: white; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 2rem; }
.album-modal-body { flex: 1; overflow-y: auto; padding: 2rem; }
.btn-back { border: none; background: transparent; font-weight: 600; color: #64748b; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; }
.btn-back:hover { color: #C5A059; }

/* Album Hero Specifics */
.album-hero {
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    padding: 4rem 0 2rem 0;
    margin-bottom: 2rem;
}

.hero-content {
    max-width: 1400px; margin: 0 auto; padding: 0 2rem;
}

.btn-back-hero {
    display: flex; align-items: center; gap: 0.8rem;
    background: none; border: none; cursor: pointer;
    font-weight: 500; color: #64748b; margin-bottom: 1.5rem;
    transition: color 0.2s;
}
.btn-back-hero:hover { color: #854d0e; } /* Dark Gold */

.icon-circle {
    width: 32px; height: 32px; border-radius: 50%;
    background: white; border: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: center;
    color: #C5A059;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.hero-title-block {
    display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 2rem;
}

.hero-text h2 {
    font-size: 2.5rem; font-weight: 800; color: #1e293b; margin: 0 0 0.5rem 0;
    letter-spacing: -0.02em;
}
.hero-text p { color: #64748b; font-size: 1.1rem; max-width: 700px; margin: 0; }

.hero-badges { display: flex; gap: 1rem; }
.badge-hero {
    background: #f8fafc; padding: 0.6rem 1.2rem; border-radius: 50px;
    border: 1px solid transparent; color: #64748b; font-size: 0.9rem; font-weight: 500;
    display: flex; align-items: center;
    transition: all 0.2s;
}
.badge-hero:hover { background: #f1f5f9; color: #0f172a; }

.album-grid-container {
    max-width: 1400px; margin: 0 auto; padding: 0 2rem 4rem 2rem;
}

.card-meta-row {
    display: flex; justify-content: space-between; align-items: center;
    margin-top: 0.5rem; font-size: 0.8rem; color: #dadada;
}
.author { display: flex; align-items: center; gap: 0.3rem; }
.views { display: flex; align-items: center; gap: 0.3rem; font-weight: bold; }

.empty-state-card {
    background: white; border: 2px dashed #e2e8f0; border-radius: 16px;
    padding: 3rem; text-align: center; color: #94a3b8;
}


</style>
