<template>
  <div class="page-container">
    <!-- Header Premium -->
    <div class="header-section">
      <div class="header-content">
        <router-link to="/center-dashboard" class="btn-back-dash mb-2">
          <i class="fas fa-arrow-left"></i> Tornar al Dashboard
        </router-link>
        <h1><i class="fas fa-qrcode text-gold"></i> Escaneig de Codis QR</h1>
        <p class="subtitle">Registra l'assistència als tallers escanejant els codis</p>
      </div>
    </div>

    <!-- Main Card -->
    <div class="scanner-card">
      <!-- Tabs Style -->
      <div class="nav-tabs">
        <button 
          :class="['nav-link', { active: activeTab === 'scan' }]"
          @click="activeTab = 'scan'"
        >
          <i class="fas fa-camera"></i> Escanejar
        </button>
        <button 
          :class="['nav-link', { active: activeTab === 'history' }]"
          @click="activeTab = 'history'"
        >
          <i class="fas fa-history"></i> Historial
        </button>
        <button 
          :class="['nav-link', { active: activeTab === 'generate' }]"
          @click="activeTab = 'generate'"
          v-if="isAdmin"
        >
          <i class="fas fa-magic"></i> Generar QR
        </button>
      </div>

      <!-- Tab Content: SCAN -->
      <div v-if="activeTab === 'scan'" class="tab-pane fade-in">
        <!-- Mode Switcher -->
        <div class="mode-switcher">
          <button 
            :class="['mode-btn', { active: scanMode === 'camera' }]"
            @click="scanMode = 'camera'"
          >
            <i class="fas fa-video"></i> Càmera
          </button>
          <button 
            :class="['mode-btn', { active: scanMode === 'manual' }]"
            @click="scanMode = 'manual'"
          >
            <i class="fas fa-keyboard"></i> Manual
          </button>
        </div>

        <!-- CAMERA MODE -->
        <div v-if="scanMode === 'camera'" class="camera-section">
          <div class="scanner-viewport">
            <video ref="videoElement" autoplay playsinline class="video-feed"></video>
            <canvas ref="canvasElement" style="display: none;"></canvas>
            
            <div class="scanner-overlay-ui">
              <div class="scan-frame">
                <div class="corner tl"></div>
                <div class="corner tr"></div>
                <div class="corner bl"></div>
                <div class="corner br"></div>
              </div>
              <div class="scan-hint">
                <i class="fas fa-crop-alt"></i> Enquadra el codi QR
              </div>
            </div>
            
             <div v-if="!isCameraActive" class="camera-placeholder">
              <i class="fas fa-camera-slash"></i>
              <p>Càmera desactivada</p>
            </div>
          </div>

          <div class="controls mt-4">
             <button 
              v-if="!isCameraActive"
              @click="startCamera"
              class="btn-cta primary"
            >
              <i class="fas fa-play"></i> Activar Càmera
            </button>
            <button 
              v-else
              @click="stopCamera"
              class="btn-cta secondary"
            >
              <i class="fas fa-stop"></i> Aturar
            </button>
          </div>

           <div v-if="cameraError" class="alert-box error mt-3">
            <i class="fas fa-exclamation-triangle"></i> {{ cameraError }}
          </div>
        </div>

        <!-- MANUAL MODE -->
        <div v-if="scanMode === 'manual'" class="manual-section">
          <div class="form-card">
            <div class="form-group">
              <label>Codi QR</label>
              <div class="input-wrapper">
                <i class="fas fa-qrcode icon-left"></i>
                <input 
                  type="text"
                  v-model="manualQRCode"
                  placeholder="Ex: QR-A1B2C3..."
                  class="form-input"
                  @keyup.enter="scanManualQR"
                />
              </div>
            </div>

            <div class="form-group">
              <label>Tipus d'Acció</label>
              <div class="input-wrapper">
                <i class="fas fa-exchange-alt icon-left"></i>
                <select v-model="scanType" class="form-select">
                  <option value="check_in">Entrada (Check-In)</option>
                  <option value="check_out">Sortida (Check-Out)</option>
                  <option value="verification">Només Verificar</option>
                </select>
              </div>
            </div>

            <button 
              @click="scanManualQR"
              :disabled="!manualQRCode || isScanning"
              class="btn-cta primary full-width"
            >
              <i :class="isScanning ? 'fas fa-spinner fa-spin' : 'fas fa-check'"></i>
              {{ isScanning ? 'Processant...' : 'Registrar Assistència' }}
            </button>
          </div>
        </div>

        <!-- RESULT CARD -->
        <div v-if="scanResult" class="result-card" :class="scanResult.success ? 'success' : 'error'">
          <div class="result-icon-wrapper">
            <i :class="scanResult.success ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
          </div>
          <div class="result-body">
            <h3>{{ scanResult.success ? 'Operació Exitosa' : 'Error en l\'Escaneig' }}</h3>
            <p>{{ scanResult.message }}</p>
            
            <div v-if="scanResult.data" class="result-meta">
              <div class="meta-row">
                <span class="meta-label">Taller:</span>
                <span class="meta-val">{{ scanResult.data.qr_data?.workshop_title }}</span>
              </div>
              <div class="meta-row">
                <span class="meta-label">Tipus:</span>
                 <span :class="['badge-scan', scanResult.data.scan?.scan_type]">
                  {{ getScanTypeName(scanResult.data.scan?.scan_type) }}
                </span>
              </div>
            </div>
          </div>
          <button @click="scanResult = null" class="btn-close-result"><i class="fas fa-times"></i></button>
        </div>
      </div>

      <!-- Tab Content: HISTORY -->
      <div v-if="activeTab === 'history'" class="tab-pane fade-in">
        <div class="filters-bar">
          <div class="search-group">
            <i class="fas fa-search search-icon"></i>
            <input 
              type="text"
              v-model="searchQuery"
              placeholder="Cercar per taller..."
              class="form-search"
            />
          </div>
          <select v-model="filterType" class="form-filter">
            <option value="">Tots els tipus</option>
            <option value="check_in">Entrada</option>
            <option value="check_out">Sortida</option>
          </select>
        </div>

        <div v-if="loading" class="loading-state">
           <div class="spinner"></div>
           <p>Carregant historial...</p>
        </div>

        <div v-else-if="filteredScans.length === 0" class="empty-state">
          <div class="empty-icon"><i class="fas fa-clipboard-list"></i></div>
          <p>No hi ha registres recents</p>
        </div>

        <div v-else class="scans-list">
          <div 
            v-for="scan in filteredScans" 
            :key="scan.id"
            class="scan-item"
          >
            <div class="scan-left">
              <div class="scan-icon-container" :class="scan.scan_type">
                <i :class="getScanTypeIcon(scan.scan_type)"></i>
              </div>
              <div class="scan-info">
                <h4>{{ scan.qr_code }}</h4>
                <p class="scan-time"><i class="far fa-clock"></i> {{ formatDateTime(scan.scanned_at) }}</p>
              </div>
            </div>
            <div class="scan-right">
              <span :class="['badge-scan', scan.scan_type]">
                {{ getScanTypeName(scan.scan_type) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab Content: GENERATE (Admin) -->
      <div v-if="activeTab === 'generate' && isAdmin" class="tab-pane fade-in">
        <div class="admin-panel">
            <!-- Existing logic for Generate stays mostly same but styled -->
            <!-- ... (Keeping it simple for now as user is Center Coord usually) ... -->
            <p class="text-center text-muted">Panell d'Admin: Generació de QRs (implementació prèvia)</p>
             <div class="form-group">
              <label>Assignació</label>
              <select v-model="selectedAssignment" class="form-select">
                <option value="">Selecciona una assignació...</option>
                <option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">
                  {{ assignment.workshop_title }}
                </option>
              </select>
            </div>
            <button @click="generateQR" class="btn-cta primary" :disabled="isGenerating || !selectedAssignment">
                <i v-if="isGenerating" class="fas fa-spinner fa-spin"></i>
                {{ isGenerating ? 'Generant...' : 'Generar QR' }}
            </button>
            
            <div v-if="generatedQR" class="qr-result-box mt-3 text-center">
                <h4>Codi QR Generat:</h4>
                <img :src="generatedQR.image" alt="QR Code" class="qr-image-generated" />
                <div class="qr-code-text">
                    <strong>Codi:</strong> 
                    <code class="qr-code-string">{{ generatedQR.code }}</code>
                    <button @click="copyToClipboard(generatedQR.code)" class="btn-copy" title="Copiar">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <p class="text-muted text-sm mt-2">Pots projectar-lo o imprimir-lo per als alumnes.</p>
            </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import axios from 'axios';

const authStore = useAuthStore();
const router = useRouter();
const isAdmin = computed(() => ['admin', 'center_coord'].includes(authStore.user?.role));

// Tabs
const activeTab = ref('scan');

// Scanner
const scanMode = ref('manual'); 
const manualQRCode = ref('');
const scanType = ref('check_in');
const isScanning = ref(false);
const scanResult = ref(null);

// Camera
const videoElement = ref(null);
const canvasElement = ref(null);
const isCameraActive = ref(false);
const cameraError = ref('');
let cameraStream = null;
let scanInterval = null;

// History
const scans = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const filterType = ref('');

// Generate (Mocked/Simplified for UI revamp focus)
const selectedAssignment = ref('');
const assignments = ref([]);
const isGenerating = ref(false);
const generatedQR = ref(null);

// Computed
const filteredScans = computed(() => {
  let filtered = [...scans.value];
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(scan => 
      scan.qr_code?.toLowerCase().includes(query) ||
      scan.assignment_id?.toString().includes(query)
    );
  }
  if (filterType.value) {
    filtered = filtered.filter(scan => scan.scan_type === filterType.value);
  }
  return filtered;
});

// Methods
const startCamera = async () => {
  try {
    cameraError.value = '';
    const stream = await navigator.mediaDevices.getUserMedia({ 
      video: { facingMode: 'environment' } 
    });
    cameraStream = stream;
    videoElement.value.srcObject = stream;
    isCameraActive.value = true;
    scanInterval = setInterval(() => { scanFromCamera(); }, 500);
  } catch (error) {
    cameraError.value = 'No s\'ha pogut accedir a la càmera. Comprova permisos.';
  }
};

const stopCamera = () => {
  if (cameraStream) {
    cameraStream.getTracks().forEach(track => track.stop());
    cameraStream = null;
  }
  if (scanInterval) {
    clearInterval(scanInterval);
    scanInterval = null;
  }
  isCameraActive.value = false;
};

const scanFromCamera = () => {
  // Logic placeholder
};

const scanManualQR = async () => {
  if (!manualQRCode.value || isScanning.value) return;
  isScanning.value = true;
  scanResult.value = null;
  
  try {
    const client = authStore.getApiClient();
    const response = await client.post('/api/qr/scan', {
      qr_code: manualQRCode.value,
      scan_type: scanType.value,
      user_id: authStore.user?.id
    });
    
    if (response.data.success) {
        scanResult.value = {
            success: true,
            message: response.data.message,
            data: response.data.data
        };
        
        // Reload history
        loadScans(); 
        
        // If check-out, redirect to feedback form after short delay
        if (scanType.value === 'check_out') {
            setTimeout(() => {
                const assignmentId = response.data.data.qr_data.assignment_id;
                const workshopId = response.data.data.qr_data.workshop_id;
                // Redirect to feedback form
                router.push({ 
                    name: 'FeedbackForm', 
                    params: { assignmentId: assignmentId },
                    query: { workshopId: workshopId }
                });
            }, 1500);
        }
    } else {
        throw new Error(response.data.message || 'Error desconegut');
    }
    
    manualQRCode.value = '';
    
  } catch (error) {
    scanResult.value = {
      success: false,
      message: error.response?.data?.message || error.message || 'Error connectant amb el servidor',
    };
  } finally {
    isScanning.value = false;
  }
};

const loadScans = async () => {
  loading.value = true;
  try {
    const client = authStore.getApiClient();
    const response = await client.get('/api/qr/history');
    if (response.data.success) {
        scans.value = response.data.data;
    }
  } catch (error) {
    console.error('Error loading history:', error);
  } finally {
    loading.value = false;
  }
};

const loadAssignments = async () => {
  try {
    const client = authStore.getApiClient();
    const params = {};
    
    // If center coord, filter by center. If admin, maybe all?
    if (authStore.user?.center_id) {
        params.assigned_center_id = authStore.user.center_id;
    }
    
    const res = await client.get('/api/allocations', { params });
    if (res.data?.success) {
        assignments.value = res.data.data.map(a => ({
            id: a.id,
            workshop_title: `${a.workshop_name} - ${new Date(a.slot_date).toLocaleDateString()}`
        }));
    }
  } catch (error) {
    console.error('Error loading assignments:', error);
  }
};

// Watch for tab change to load data
import { watch } from 'vue';
watch(activeTab, (val) => {
    if (val === 'generate' && assignments.value.length === 0) {
        loadAssignments();
    }
});

const generateQR = async () => {
    if (!selectedAssignment.value || isGenerating.value) return;
    
    isGenerating.value = true;
    generatedQR.value = null;

    try {
        const client = authStore.getApiClient();
        const response = await client.post('/api/qr/generate', { 
            allocation_id: selectedAssignment.value 
        });
        
        if (response.data?.success) {
            generatedQR.value = {
                image: response.data.data.qr_image,
                code: response.data.data.qr_code
            };
        } else {
             alert('Error: ' + response.data.message);
        }
       
    } catch (e) {
        console.error(e);
        alert('Error connectant amb servidor');
    } finally {
        isGenerating.value = false;
    }
};

const getScanTypeIcon = (type) => {
  const map = { check_in: 'fas fa-sign-in-alt', check_out: 'fas fa-sign-out-alt', verification: 'fas fa-check' };
  return map[type] || 'fas fa-qrcode';
};

const getScanTypeName = (type) => {
  const map = { check_in: 'Entrada', check_out: 'Sortida', verification: 'Verificació' };
  return map[type] || type;
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('ca-ES', { day: '2-digit', month: '2-digit', hour: '2-digit', minute:'2-digit' });
};

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text).then(() => {
    alert('Codi copiat!');
  }).catch(() => {
    alert('Error copiant el codi');
  });
};

onMounted(() => {
  loadScans();
});

onUnmounted(() => {
  stopCamera();
});
</script>

<style scoped>
.page-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 2rem;
  font-family: 'Inter', sans-serif;
  color: #1e293b;
}

/* Header */
.header-section {
  margin-bottom: 2rem;
  border-bottom: 3px solid #C5A059;
  padding-bottom: 1.5rem;
}

.header-content h1 {
  font-size: 2.2rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.text-gold { color: #C5A059; }

.subtitle { color: #64748b; font-size: 1.1rem; margin-top: 0.5rem; }

.btn-back-dash {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9rem;
  transition: color 0.2s;
}
.btn-back-dash:hover { color: #C5A059; }
.mb-2 { margin-bottom: 0.5rem; }

/* Main Card */
.scanner-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  border: 1px solid #f1f5f9;
}

/* Tabs */
.nav-tabs {
  display: flex;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.nav-link {
  flex: 1;
  padding: 1rem;
  border: none;
  background: transparent;
  cursor: pointer;
  color: #64748b;
  font-weight: 600;
  border-bottom: 3px solid transparent;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.nav-link:hover { background: #f1f5f9; color: #334155; }
.nav-link.active { color: #C5A059; border-bottom-color: #C5A059; background: white; }

.tab-pane { padding: 2rem; animation: fadeIn 0.3s ease; }

@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Controls */
.mode-switcher {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 2rem;
  background: #f1f5f9;
  padding: 0.5rem;
  border-radius: 12px;
  width: fit-content;
  margin-left: auto;
  margin-right: auto;
}

.mode-btn {
  padding: 0.6rem 1.5rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  color: #64748b;
  background: transparent;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.mode-btn.active { background: white; color: #0f172a; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }

/* Camera */
.camera-section { text-align: center; }
.scanner-viewport {
  position: relative;
  width: 100%;
  max-width: 500px;
  height: 350px;
  background: #000;
  margin: 0 auto;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.video-feed { width: 100%; height: 100%; object-fit: cover; }

.camera-placeholder {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  color: rgba(255,255,255,0.3);
}

.camera-placeholder i { font-size: 3rem; margin-bottom: 1rem; }

.scanner-overlay-ui {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  z-index: 10;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}

.scan-frame {
  width: 250px; height: 250px;
  border: 2px solid rgba(255,255,255,0.5);
  border-radius: 20px;
  position: relative;
  box-shadow: 0 0 0 4000px rgba(0,0,0,0.6);
}

.corner { position: absolute; width: 20px; height: 20px; border: 4px solid #C5A059; }
.tl { top: -2px; left: -2px; border-right: none; border-bottom: none; border-radius: 6px 0 0 0; }
.tr { top: -2px; right: -2px; border-left: none; border-bottom: none; border-radius: 0 6px 0 0; }
.bl { bottom: -2px; left: -2px; border-right: none; border-top: none; border-radius: 0 0 0 6px; }
.br { bottom: -2px; right: -2px; border-left: none; border-top: none; border-radius: 0 0 6px 0; }

.scan-hint {
  margin-top: 1.5rem;
  background: rgba(0,0,0,0.6);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-size: 0.9rem;
  backdrop-filter: blur(4px);
}

/* Manual Form */
.manual-section { max-width: 500px; margin: 0 auto; }
.form-card { padding: 1rem; }

.form-group { margin-bottom: 1.5rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; }

.input-wrapper { position: relative; }
.icon-left { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.form-input, .form-select {
  width: 100%;
  padding: 0.8rem 1rem 0.8rem 2.8rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  background: #f8fafc;
  transition: all 0.2s;
}
.form-input:focus, .form-select:focus { outline: none; border-color: #C5A059; background: white; box-shadow: 0 0 0 3px rgba(197,160,89,0.1); }

/* Buttons */
.btn-cta {
  border: none;
  padding: 0.8rem 1.5rem;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.2s;
}
.btn-cta.primary { background: #C5A059; color: white; }
.btn-cta.primary:hover { background: #b8905f; transform: translateY(-2px); }
.btn-cta:disabled { opacity: 0.7; cursor: not-allowed; }
.btn-cta.secondary { background: #94a3b8; color: white; }
.btn-cta.full-width { width: 100%; }

/* Alerts */
.alert-box { padding: 1rem; border-radius: 8px; font-size: 0.95rem; }
.alert-box.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

/* Results */
.result-card {
  margin-top: 2rem;
  padding: 1.5rem;
  border-radius: 12px;
  display: flex;
  gap: 1.5rem;
  position: relative;
  border: 1px solid transparent;
}
.result-card.success { background: #f0fdf4; border-color: #bbf7d0; }
.result-card.error { background: #fef2f2; border-color: #fecaca; }

.result-icon-wrapper { font-size: 2.5rem; }
.result-card.success .result-icon-wrapper { color: #166534; }
.result-card.error .result-icon-wrapper { color: #991b1b; }

.result-body h3 { margin: 0 0 0.5rem 0; font-size: 1.1rem; }
.result-meta { margin-top: 1rem; background: rgba(255,255,255,0.5); padding: 1rem; border-radius: 8px; }
.meta-row { display: flex; justify-content: space-between; margin-bottom: 0.4rem; font-size: 0.9rem; }
.meta-label { color: #64748b; font-weight: 500; }
.meta-val { font-weight: 600; }

.btn-close-result { position: absolute; top: 1rem; right: 1rem; background: transparent; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b; }

/* History List */
.filters-bar { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
.search-group { flex: 2; position: relative; }
.search-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.form-search { width: 100%; padding: 0.8rem 1rem 0.8rem 2.8rem; border: 1px solid #e2e8f0; border-radius: 50px; background: white; }
.form-filter { flex: 1; border-radius: 50px; border: 1px solid #e2e8f0; padding-left: 1rem; }

.scans-list { display: flex; flex-direction: column; gap: 1rem; }
.scan-item { background: white; padding: 1rem; border-radius: 12px; border: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s; }
.scan-item:hover { border-color: #C5A059; transform: translateX(5px); }
.scan-left { display: flex; gap: 1rem; align-items: center; }
.scan-icon-container { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
.scan-icon-container.check_in { background: #dcfce7; color: #166534; }
.scan-icon-container.check_out { background: #ffedd5; color: #9a3412; }

.scan-info h4 { margin: 0; font-size: 1rem; color: #0f172a; }
.scan-time { margin: 0.2rem 0 0; font-size: 0.85rem; color: #64748b; }

.badge-scan { padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.8rem; font-weight: 600; }
.badge-scan.check_in { background: #dcfce7; color: #166534; }
.badge-scan.check_out { background: #ffedd5; color: #9a3412; }

/* Empty States & Loading */
.empty-state { text-align: center; padding: 3rem; color: #94a3b8; }
.empty-icon { font-size: 3rem; margin-bottom: 1rem; }
.loading-state { text-align: center; padding: 2rem; }
.spinner { border: 3px solid #f3f3f3; border-top: 3px solid #C5A059; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

/* QR Code Display */
.qr-image-generated { max-width: 300px; border: 2px solid #e2e8f0; border-radius: 8px; margin: 1rem auto; }
.qr-code-text { margin-top: 1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
.qr-code-string { background: #f1f5f9; padding: 0.5rem 1rem; border-radius: 6px; font-family: monospace; font-size: 0.9rem; color: #0F172A; }
.btn-copy { background: #C5A059; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
.btn-copy:hover { background: #b08d4d; }
.qr-result-box { background: #f8fafc; padding: 2rem; border-radius: 12px; border: 1px solid #e2e8f0; }
.mt-3 { margin-top: 1rem; }
.text-center { text-align: center; }
.text-muted { color: #64748b; }
.text-sm { font-size: 0.875rem; }

</style>
