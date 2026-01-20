<script setup>
import { ref, onMounted } from 'vue';
import MainLayout from '../layouts/MainLayout.vue';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const solicitudes = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const res = await fetch(`http://localhost:8000/api/mis_solicitudes.php?centre_id=${auth.user.id}`);
        solicitudes.value = await res.json();
    } catch (error) {
        console.error("Error cargando solicitudes", error);
    } finally {
        loading.value = false;
    }
});

const getEstatColor = (estat) => {
    if(estat === 'pendent') return 'bg-yellow-100 text-yellow-800';
    if(estat === 'assignada') return 'bg-blue-100 text-blue-800';
    if(estat === 'realitzada') return 'bg-green-100 text-green-800';
    return 'bg-red-100 text-red-800';
};

const getEstatLabel = (estat) => {
    if(estat === 'pendent') return '⏳ Pendent de revisió';
    if(estat === 'assignada') return '✅ Assignada';
    if(estat === 'realitzada') return '🎉 Realitzada';
    return '❌ Rebutjada';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ca-ES');
};
</script>

<template>
  <MainLayout>
    <div class="max-w-7xl mx-auto">
        <header class="mb-8">
            <h2 class="text-3xl font-bold text-kairos-navy">Les Meves Sol·licituds</h2>
            <p class="text-gray-500 mt-2">Visualitza l'estat de totes les teves sol·licituds de tallers.</p>
        </header>

        <div v-if="loading" class="text-center py-20 text-gray-400">
            Carregant sol·licituds...
        </div>

        <div v-else-if="solicitudes.length === 0" class="bg-blue-50 border border-blue-200 rounded-lg p-8 text-center">
            <p class="text-blue-800 font-semibold">No tens sol·licituds de moment.</p>
            <p class="text-blue-600 mt-2">Torna al catàleg i sol·licita algun taller!</p>
        </div>

        <div v-else class="space-y-4">
            <div 
                v-for="sol in solicitudes" 
                :key="sol.id"
                class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition"
            >
                <div class="flex flex-col md:flex-row">
                    <!-- Imagen -->
                    <div class="md:w-48 h-48 bg-gray-200 flex-shrink-0">
                        <img :src="sol.taller.imatge" :alt="sol.taller.nom" class="w-full h-full object-cover">
                    </div>

                    <!-- Contenido -->
                    <div class="flex-1 p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">{{ sol.taller.nom }}</h3>
                                    <p class="text-sm text-gray-500 mt-1">{{ sol.taller.categoria }}</p>
                                </div>
                                <span 
                                    :class="getEstatColor(sol.estat)"
                                    class="px-3 py-1 text-sm font-bold rounded-lg whitespace-nowrap ml-4"
                                >
                                    {{ getEstatLabel(sol.estat) }}
                                </span>
                            </div>

                            <p class="text-gray-600 text-sm mt-3">{{ sol.taller.descripcio }}</p>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t">
                                <div>
                                    <p class="text-xs text-gray-500 font-semibold">Alumnes</p>
                                    <p class="text-lg font-bold text-kairos-navy">{{ sol.nombre_alumnes }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 font-semibold">Solicitat</p>
                                    <p class="text-sm text-gray-700">{{ formatDate(sol.data_creacio) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 font-semibold">Observacions</p>
                                    <p class="text-sm text-gray-700">{{ sol.comentaris || '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 font-semibold">ID Sol·licitud</p>
                                    <p class="text-sm font-mono text-gray-700">#{{ sol.id }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </MainLayout>
</template>
