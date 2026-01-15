<script setup>
import { ref, onMounted, computed } from 'vue';
import MainLayout from '../layouts/MainLayout.vue';
import ModalSolicitud from '../components/ModalSolicitud.vue';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const sectors = ref([]);
const tallers = ref([]);
const loading = ref(true);

// Estado de navegación
const sectorSeleccionado = ref(null); // Si es null, vemos sectores. Si tiene ID, vemos talleres.
const showModal = ref(false);
const tallerParaSolicitar = ref(null);

// Cargar Datos Iniciales
onMounted(async () => {
    try {
        const [resSec, resTal] = await Promise.all([
            fetch('http://localhost:8000/api/sectors.php'),
            fetch('http://localhost:8000/api/tallers.php')
        ]);
        sectors.value = await resSec.json();
        tallers.value = await resTal.json();
    } catch (error) {
        console.error("Error cargando datos", error);
    } finally {
        loading.value = false;
    }
});

// Filtrar talleres por el sector seleccionado
const tallersDelSector = computed(() => {
    if (!sectorSeleccionado.value) return [];
    return tallers.value.filter(t => t.sector_id === sectorSeleccionado.value.id);
});

// Iconos (Mapeo rápido de texto a emojis para prototipo)
const getIcon = (iconName) => {
    const map = {
        'agriculture': '🌱', 
        'precision_manufacturing': '🏭', 
        'engineering': '⚙️',
        'bolt': '⚡', 
        'construction': '🏗️', 
        'computer': '💻',
        'science': '🧪', 
        'business_center': '💼', 
        'people': '🤝',
        'palette': '🎨', 
        'fitness_center': '🩺'
    };
    return map[iconName] || '📚';
};

const abrirSolicitud = (taller) => {
    tallerParaSolicitar.value = taller;
    showModal.value = true;
};

const cerrarModal = () => {
    showModal.value = false;
    tallerParaSolicitar.value = null;
};
</script>

<template>
  <MainLayout>
    <div class="max-w-7xl mx-auto">
        
        <header class="mb-8">
            <div v-if="!sectorSeleccionado">
                <h2 class="text-3xl font-bold text-kairos-navy">Sectors Professionals ENGINY</h2>
                <p class="text-gray-500 mt-2">Explora els àmbits professionals i descobreix les activitats disponibles.</p>
            </div>
            <div v-else class="flex items-center gap-4">
                <button @click="sectorSeleccionado = null" class="text-sm bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded-lg transition font-medium">
                    ← Tornar als sectors
                </button>
                <div>
                    <h2 class="text-3xl font-bold" :style="{ color: sectorSeleccionado.color }">
                        {{ sectorSeleccionado.nom }}
                    </h2>
                    <p class="text-sm text-gray-500">{{ tallersDelSector.length }} activitats disponibles</p>
                </div>
            </div>
        </header>

        <!-- Vista de Sectores (Mosaico) -->
        <div v-if="!sectorSeleccionado && !loading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <div v-for="sec in sectors" :key="sec.id" 
                @click="sectorSeleccionado = sec"
                class="cursor-pointer bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 border-t-4 p-6 flex flex-col items-center text-center group"
                :style="{ borderColor: sec.color }"
            >
                <div class="text-5xl mb-4 w-20 h-20 flex items-center justify-center rounded-full group-hover:scale-110 transition-transform duration-300"
                     :style="{ backgroundColor: sec.color + '20' }">
                    {{ getIcon(sec.icona) }}
                </div>
                <h3 class="font-bold text-gray-800 group-hover:text-kairos-navy transition text-sm leading-tight">
                    {{ sec.nom }}
                </h3>
                <p class="text-xs text-gray-400 mt-2 group-hover:text-kairos-gold transition">
                    Veure activitats →
                </p>
            </div>
        </div>

        <!-- Vista de Talleres del Sector -->
        <div v-else-if="sectorSeleccionado && !loading">
            <div v-if="tallersDelSector.length === 0" class="text-center py-20 bg-white rounded-xl shadow-sm border border-gray-200">
                <p class="text-xl text-gray-400 mb-2">📭 Encara no hi ha tallers disponibles</p>
                <p class="text-sm text-gray-500">Aquest sector s'està preparant. Torna aviat!</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="taller in tallersDelSector" :key="taller.id" 
                     class="bg-white rounded-xl shadow-sm hover:shadow-lg transition border border-gray-100 overflow-hidden flex flex-col">
                    
                    <div class="h-48 bg-gradient-to-br from-gray-100 to-gray-200 relative overflow-hidden">
                        <img v-if="taller.imatge" :src="taller.imatge" class="w-full h-full object-cover" :alt="taller.nom">
                        <div v-else class="w-full h-full flex items-center justify-center text-6xl">
                            {{ getIcon(sectorSeleccionado.icona) }}
                        </div>
                        <span class="absolute top-3 right-3 bg-white/95 backdrop-blur-sm px-3 py-1 text-xs font-bold rounded-full shadow-lg"
                              :style="{ color: sectorSeleccionado.color }">
                            Modalitat {{ taller.modalitat }}
                        </span>
                    </div>
                    
                    <div class="p-5 flex-1 flex flex-col">
                        <h3 class="text-lg font-bold text-kairos-navy mb-3 line-clamp-2">{{ taller.nom }}</h3>
                        
                        <p class="text-sm text-gray-600 line-clamp-3 mb-4 flex-1">{{ taller.descripcio }}</p>

                        <div class="space-y-2 mb-4 text-xs text-gray-500">
                            <div class="flex items-center gap-2">
                                <span>⏱️</span>
                                <span>{{ taller.durada_minuts }} minuts</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span>👥</span>
                                <span>Màx. {{ taller.capacitat_max }} alumnes</span>
                            </div>
                        </div>

                        <button @click="abrirSolicitud(taller)" 
                            class="mt-auto w-full py-3 bg-kairos-navy text-white font-bold rounded-lg hover:bg-opacity-90 transition flex items-center justify-center gap-2">
                            <span>📝</span>
                            <span>Sol·licitar Plaça</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-20">
            <p class="text-gray-400 text-lg">Carregant sectors...</p>
        </div>
    </div>

    <ModalSolicitud 
        v-if="showModal && tallerParaSolicitar"
        :isOpen="showModal" 
        :taller="tallerParaSolicitar"
        @close="cerrarModal"
    />
  </MainLayout>
</template>
