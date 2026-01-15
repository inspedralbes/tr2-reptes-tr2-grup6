<script setup>
import { ref, onMounted, computed } from 'vue';
import MainLayout from '../layouts/MainLayout.vue';
import ModalSolicitud from '../components/ModalSolicitud.vue'; // Importamos el modal
import { useAuthStore } from '../stores/auth'; // Necesitamos saber quién es el usuario

const auth = useAuthStore();
const tallers = ref([]);
const loading = ref(true);
const filtro = ref('');

// Estado para el Modal
const showModal = ref(false);
const tallerSeleccionat = ref({});

// Cargar talleres de la API
onMounted(async () => {
    try {
        const res = await fetch('http://localhost:8000/api/tallers.php');
        tallers.value = await res.json();
    } catch (error) {
        console.error("Error cargando talleres", error);
    } finally {
        loading.value = false;
    }
});

// Abrir Modal
const obrirSolicitud = (taller) => {
    console.log('Abriendo modal para:', taller);
    tallerSeleccionat.value = taller;
    showModal.value = true;
};

// Enviar Solicitud a la API
const enviarSolicitud = async (datosFormulario) => {
    try {
        const payload = {
            centre_id: auth.user.id, // ID del usuario logueado
            taller_id: datosFormulario.taller_id,
            nombre_alumnes: datosFormulario.alumnes,
            data_preferent: datosFormulario.data,
            comentaris: datosFormulario.comentaris
        };

        const res = await fetch('http://localhost:8000/api/sollicitud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (data.success) {
            alert("✅ Sol·licitud enviada correctament! Els coordinadors la revisaran.");
            showModal.value = false;
        } else {
            alert("❌ Error: " + data.message);
        }

    } catch (error) {
        console.error("Error enviando solicitud", error);
        alert("Error de connexió amb el servidor");
    }
};

// Filtrar por nombre
const tallersFiltrats = computed(() => {
    return tallers.value.filter(t => 
        t.nom.toLowerCase().includes(filtro.value.toLowerCase())
    );
});

// Función para obtener color de modalidad
const getModalitatColor = (mod) => {
    if(mod === 'A') return 'bg-purple-100 text-purple-800'; // Centre ve
    if(mod === 'B') return 'bg-blue-100 text-blue-800';     // Profe va
    return 'bg-green-100 text-green-800';                   // Online
};
</script>

<template>
  <MainLayout>
    <div class="max-w-7xl mx-auto">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-kairos-navy">Catàleg de Tallers</h2>
                <p class="text-gray-500">Explora les activitats disponibles per al programa ENGINY.</p>
            </div>
            <div>
                <input 
                    v-model="filtro"
                    type="text" 
                    placeholder="Buscar activitat..." 
                    class="px-4 py-2 border rounded-lg shadow-sm focus:ring-2 focus:ring-kairos-gold outline-none"
                >
            </div>
        </header>

        <div v-if="loading" class="text-center py-20 text-gray-400">
            Carregant tallers...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div 
                v-for="taller in tallersFiltrats" 
                :key="taller.id"
                class="bg-white rounded-xl shadow-sm hover:shadow-md transition border border-gray-100 overflow-hidden flex flex-col"
            >
                <div class="h-48 bg-gray-200 relative">
                    <img :src="taller.imatge" alt="Taller" class="w-full h-full object-cover">
                    <span 
                        class="absolute top-2 right-2 px-2 py-1 text-xs font-bold rounded uppercase tracking-wider"
                        :class="getModalitatColor(taller.modalitat)"
                    >
                        Modalitat {{ taller.modalitat }}
                    </span>
                </div>

                <div class="p-5 flex-1 flex flex-col">
                    <div class="mb-2">
                        <span class="text-xs font-semibold text-kairos-blue uppercase">
                            {{ taller.categoria.nom }}
                        </span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ taller.nom }}</h3>
                    <p class="text-gray-600 text-sm line-clamp-3 mb-4 flex-1">
                        {{ taller.descripcio }}
                    </p>
                    
                    <div class="mt-auto pt-4 border-t flex justify-between items-center">
                        <span class="text-xs text-gray-500 font-medium">
                            ⏱ {{ taller.durada_minuts }} min
                        </span>
                        <button 
                            @click="obrirSolicitud(taller)" 
                            class="px-4 py-2 bg-kairos-navy text-white text-sm font-bold rounded-lg hover:bg-kairos-gold hover:text-kairos-navy transition"
                        >
                            Sol·licitar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Solicitud -->
        <ModalSolicitud 
            :isOpen="showModal" 
            :taller="tallerSeleccionat"
            @close="showModal = false"
            @confirm="enviarSolicitud"
        />
    </div>
  </MainLayout>
</template>
