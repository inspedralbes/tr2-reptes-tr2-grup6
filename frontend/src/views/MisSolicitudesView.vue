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
        console.error(error);
    } finally {
        loading.value = false;
    }
});

// Función para el color del badge según estado
const getStatusColor = (estat) => {
    switch(estat) {
        case 'pendent': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'assignada': return 'bg-green-100 text-green-800 border-green-200';
        case 'rebutjada': return 'bg-red-100 text-red-800 border-red-200';
        case 'realitzada': return 'bg-blue-100 text-blue-800 border-blue-200';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getStatusLabel = (estat) => {
    const labels = {
        'pendent': '⏳ Pendent',
        'assignada': '✅ Assignada',
        'rebutjada': '❌ Rebutjada',
        'realitzada': '🎉 Realitzada'
    };
    return labels[estat] || estat;
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ca-ES');
};
</script>

<template>
  <MainLayout>
    <div class="max-w-6xl mx-auto">
        <header class="mb-8">
            <h2 class="text-3xl font-bold text-kairos-navy">📋 Les Meves Sol·licituds</h2>
            <p class="text-gray-500 mt-2">Consulta l'estat de totes les teves sol·licituds de tallers.</p>
        </header>

        <div v-if="loading" class="text-center py-10 text-gray-500">
            Carregant històric...
        </div>

        <div v-else-if="solicitudes.length === 0" class="text-center py-20 bg-white rounded-xl shadow-sm border border-gray-200">
            <p class="text-xl text-gray-400 mb-4">Encara no has fet cap sol·licitud.</p>
            <router-link to="/dashboard" class="inline-block px-4 py-2 bg-kairos-blue text-white rounded-lg hover:bg-blue-600 transition font-medium">
                ➜ Explorar Catàleg de Tallers
            </router-link>
        </div>

        <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Activitat</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Data Preferent</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Detalls</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estat</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="sol in solicitudes" :key="sol.id" class="hover:bg-gray-50 transition">
                        
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 h-12 w-12 rounded-lg overflow-hidden bg-gray-200">
                                    <img v-if="sol.imatge_url" class="h-12 w-12 object-cover" :src="sol.imatge_url" :alt="sol.nom_taller">
                                    <div v-else class="h-12 w-12 flex items-center justify-center text-gray-400">📚</div>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900">{{ sol.nom_taller }}</div>
                                    <div class="text-xs text-gray-500">Sol·licitat: {{ formatDate(sol.data_creacio) }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <div v-if="sol.data_preferent" class="font-medium">{{ formatDate(sol.data_preferent) }}</div>
                            <div v-else class="text-gray-400 italic">No especificada</div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <div class="flex items-center gap-1">
                                <span class="text-lg">👥</span>
                                <span class="font-semibold">{{ sol.nombre_alumnes }}</span>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border" 
                                  :class="getStatusColor(sol.estat)">
                                {{ getStatusLabel(sol.estat) }}
                            </span>
                        </td>

                    </tr>
                </tbody>
            </table>
        </div>
    </div>
  </MainLayout>
</template>
