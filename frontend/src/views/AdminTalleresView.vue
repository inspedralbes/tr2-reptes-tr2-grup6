<script setup>
import { ref, onMounted } from 'vue';
import MainLayout from '../layouts/MainLayout.vue';

const tallers = ref([]);
const sectors = ref([]);
const showModal = ref(false);
const isLoading = ref(true);

const defaultImage = 'https://images.unsplash.com/photo-1581092160562-40aa08e78837?auto=format&fit=crop&w=800&q=80';

const form = ref({
    nom: '', 
    descripcio: '', 
    modalitat: 'A', 
    durada: 60, 
    capacitat: 25,
    sector_id: '', 
    data: new Date().toISOString().split('T')[0],
    imatge: defaultImage
});

// Cargar Talleres
const fetchTalleres = async () => {
    isLoading.value = true;
    try {
        const res = await fetch('http://localhost:8000/api/tallers.php');
        tallers.value = await res.json();
    } catch (error) {
        console.error('Error cargando talleres:', error);
    } finally {
        isLoading.value = false;
    }
};

// Cargar Sectores
const fetchSectors = async () => {
    try {
        const res = await fetch('http://localhost:8000/api/sectors.php');
        sectors.value = await res.json();
    } catch (error) {
        console.error('Error cargando sectores:', error);
    }
};

onMounted(() => {
    fetchTalleres();
    fetchSectors();
});

// Crear Taller
const crearTaller = async () => {
    try {
        const res = await fetch('http://localhost:8000/api/admin_talleres.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(form.value)
        });
        const data = await res.json();
        
        if(data.success) {
            showModal.value = false;
            await fetchTalleres();
            resetForm();
        } else {
            alert(data.message || 'Error al crear');
        }
    } catch (e) {
        alert("Error de connexió");
    }
};

const resetForm = () => {
    form.value = { 
        nom: '', 
        descripcio: '', 
        modalitat: 'A', 
        durada: 60, 
        capacitat: 25, 
        sector_id: '', 
        data: new Date().toISOString().split('T')[0],
        imatge: defaultImage 
    };
};

// Eliminar Taller
const eliminarTaller = async (id) => {
    if(!confirm("Segur que vols eliminar aquest taller del catàleg?")) return;
    try {
        await fetch(`http://localhost:8000/api/admin_talleres.php?id=${id}`, {
            method: 'DELETE'
        });
        fetchTalleres();
    } catch (error) {
        alert("Error al eliminar");
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'Sense data';
    const date = new Date(dateStr);
    return new Intl.DateTimeFormat('ca-ES', { day: '2-digit', month: 'short', year: 'numeric' }).format(date);
};

const getModalitatLabel = (mod) => {
    const labels = { 'A': 'Centre ve', 'B': 'Profe va', 'C': 'Online' };
    return labels[mod] || mod;
};
</script>

<template>
  <MainLayout>
    <div class="max-w-7xl mx-auto space-y-8 animate-in fade-in duration-500">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div>
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Gestió del Catàleg</h2>
                <p class="text-slate-500 mt-1">Administra i organitza els tallers disponibles per als centres.</p>
            </div>
            <button @click="showModal = true" class="bg-indigo-600 text-white font-bold px-6 py-3 rounded-xl hover:bg-indigo-700 transition-all shadow-md hover:shadow-lg flex items-center gap-2 group">
                <span class="text-xl group-hover:rotate-90 transition-transform duration-300">+</span> Nou Taller
            </button>
        </div>

        <!-- Grid de Talleres -->
        <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div v-for="i in 3" :key="i" class="bg-white rounded-2xl h-80 animate-pulse border border-gray-100"></div>
        </div>

        <div v-else-if="tallers.length === 0" class="text-center py-20 bg-white rounded-2xl border border-dashed border-gray-300">
            <div class="text-5xl mb-4">🎨</div>
            <h3 class="text-xl font-bold text-gray-800">No hi ha tallers encara</h3>
            <p class="text-gray-500">Comença creant el primer taller per al catàleg.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div v-for="t in tallers" :key="t.id" class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 flex flex-col overflow-hidden">
                
                <!-- Card Header Image -->
                <div class="relative h-48 overflow-hidden">
                    <img :src="t.imatge" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="imatge taller">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 right-4 flex justify-between items-end">
                        <span class="px-3 py-1 bg-white/90 backdrop-blur-sm text-indigo-700 text-xs font-bold rounded-full shadow-sm">
                            {{ getModalitatLabel(t.modalitat) }}
                        </span>
                        <div class="flex gap-2">
                             <button @click="eliminarTaller(t.id)" class="bg-white/90 backdrop-blur-sm text-red-600 p-2 rounded-lg hover:bg-red-50 transition shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-6 flex-1 flex flex-col">
                    <div class="flex items-center gap-2 mb-2">
                        <span v-if="t.sector" :style="{ backgroundColor: t.sector.color + '20', color: t.sector.color }" class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded">
                           {{ t.sector.nom }}
                        </span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2 leading-tight">{{ t.nom }}</h3>
                    <p class="text-gray-600 text-sm line-clamp-2 mb-4">{{ t.descripcio }}</p>
                    
                    <div class="mt-auto space-y-3">
                        <div class="flex items-center text-sm text-slate-500 gap-2 bg-slate-50 p-2 rounded-lg">
                            <span class="text-lg">📅</span>
                            <span class="font-medium">{{ formatDate(t.data) }}</span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 pt-3 border-t border-gray-50">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-400 uppercase font-bold">Durada</span>
                                <span class="text-sm font-semibold text-gray-700">{{ t.durada_minuts }} min</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-400 uppercase font-bold">Capacitat</span>
                                <span class="text-sm font-semibold text-gray-700">{{ t.capacitat_max }} alumnes</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
                <div class="bg-white rounded-3xl w-full max-w-2xl shadow-2xl transform transition-all animate-in zoom-in-95 duration-200 flex flex-col max-h-[90vh]">
                    
                    <!-- Header (Fixed) -->
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-slate-50 rounded-t-3xl flex-none">
                        <div>
                            <h3 class="text-2xl font-bold text-slate-900">Crear Nou Taller</h3>
                            <p class="text-sm text-slate-500 uppercase tracking-widest font-bold mt-1">Configuració del catàleg</p>
                        </div>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 p-2 hover:bg-white rounded-full transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Scrollable Content -->
                    <div class="overflow-y-auto p-6 md:p-8 flex-1">
                        <form @submit.prevent="crearTaller" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="text-sm font-bold text-slate-700 mb-2 block">Nom del Taller</label>
                                    <input v-model="form.nom" placeholder="Ej: Introducció a la Robòtica" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none" required>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-sm font-bold text-slate-700 mb-2 block">Descripció</label>
                                    <textarea v-model="form.descripcio" placeholder="Descripció detallada per als centres..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none" rows="3"></textarea>
                                </div>
                                
                                <div>
                                    <label class="text-sm font-bold text-slate-700 mb-2 block">Modalitat</label>
                                    <select v-model="form.modalitat" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all outline-none">
                                        <option value="A">🚗 Mod A (Centre ve)</option>
                                        <option value="B">🏫 Mod B (Profe va)</option>
                                        <option value="C">💻 Mod C (Online)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-slate-700 mb-2 block">Sector Professional</label>
                                    <select v-model.number="form.sector_id" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all outline-none" required>
                                        <option value="" disabled>Selecciona un sector...</option>
                                        <option v-for="sec in sectors" :key="sec.id" :value="sec.id">
                                            {{ sec.nom }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-slate-700 mb-2 block">Data del Taller</label>
                                    <input type="date" v-model="form.data" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all outline-none" required>
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-slate-700 mb-2 block">Durada (minuts)</label>
                                    <input type="number" v-model.number="form.durada" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all outline-none">
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-slate-700 mb-2 block">Capacitat Màxima</label>
                                    <input type="number" v-model.number="form.capacitat" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all outline-none">
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-slate-700 mb-2 block">URL Imatge</label>
                                    <input v-model="form.imatge" placeholder="https://images.unsplash.com/..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all outline-none text-sm">
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                                <button type="button" @click="showModal = false" class="px-6 py-3 text-slate-500 font-bold hover:bg-slate-100 rounded-xl transition-colors">Cancel·lar</button>
                                <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-700 shadow-md hover:shadow-lg transition-all">Guardar Taller</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
  </MainLayout>
</template>

<style scoped>
.animate-in {
    animation-duration: 0.5s;
    animation-fill-mode: both;
}
@keyframes fade-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.fade-in {
    animation-name: fade-in;
}

/* Scrollbar personalizado */
::-webkit-scrollbar {
  width: 8px;
}
::-webkit-scrollbar-track {
  background: #f1f5f9;
}
::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
