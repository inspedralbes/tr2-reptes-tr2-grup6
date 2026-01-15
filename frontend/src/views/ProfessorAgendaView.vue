<script setup>
import { ref, onMounted } from 'vue';
import MainLayout from '../layouts/MainLayout.vue';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const agenda = ref([]);
const loading = ref(true);

const fetchAgenda = async () => {
    loading.value = true;
    try {
        const res = await fetch(`http://localhost:8000/api/prof_agenda.php?prof_id=${auth.user.id}`);
        agenda.value = await res.json();
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchAgenda);

const marcarRealizada = async (item) => {
    if (!confirm(`Confirmar que el grup de ${item.nom_centre} ha assistit?`)) return;

    await fetch('http://localhost:8000/api/prof_agenda.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            assignacio_id: item.assignacio_id,
            estat: 'realitzada',
            observacions: 'Activitat completada sense incidències.'
        })
    });
    fetchAgenda();
};
</script>

<template>
  <MainLayout>
    <div class="max-w-5xl mx-auto">
        <h2 class="text-3xl font-bold text-kairos-navy mb-6">La Meva Agenda Docent</h2>

        <div v-if="loading" class="text-center py-10">Carregant classes...</div>

        <div v-else class="space-y-6">
            <div v-for="item in agenda" :key="item.assignacio_id" 
                 class="bg-white rounded-xl shadow border-l-8 overflow-hidden relative"
                 :class="item.estat_execucio === 'realitzada' ? 'border-green-500 opacity-75' : 'border-kairos-blue'">
                
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div class="flex flex-col items-center mr-6 min-w-[80px]">
                            <span class="text-3xl font-bold text-gray-800">{{ item.data_realitzacio?.split('-')[2] }}</span>
                            <span class="text-sm uppercase font-bold text-gray-500">
                                {{ new Date(item.data_realitzacio).toLocaleString('ca-ES', { month: 'short' }) }}
                            </span>
                            <span class="mt-2 bg-gray-100 px-2 py-1 rounded text-sm font-mono">{{ item.hora_realitzacio?.substring(0,5) }}h</span>
                        </div>

                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-kairos-navy">{{ item.nom_taller }}</h3>
                            <p class="text-lg text-gray-700 font-semibold mb-1">{{ item.nom_centre }}</p>
                            
                            <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-600">
                                <span class="bg-blue-50 text-blue-800 px-2 py-1 rounded">👥 {{ item.curs_grup }} ({{ item.nombre_alumnes }} pax)</span>
                                <span class="bg-gray-100 px-2 py-1 rounded">📍 Modalitat {{ item.modalitat }}</span>
                            </div>

                            <div v-if="item.necessitats_especifiques" class="mt-4 bg-red-50 border border-red-200 p-3 rounded-lg flex items-start gap-3">
                                <span class="text-xl">⚠️</span>
                                <div>
                                    <p class="text-xs font-bold text-red-700 uppercase">Atenció a la diversitat</p>
                                    <p class="text-sm text-red-800">{{ item.necessitats_especifiques }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="ml-4 flex flex-col gap-2">
                            <div v-if="item.estat_execucio === 'programada'">
                                <button @click="marcarRealizada(item)" 
                                    class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 shadow font-bold transition">
                                    ✅ Confirmar Assistència
                                </button>
                                <button class="w-full px-4 py-2 bg-red-100 text-red-600 rounded hover:bg-red-200 text-xs mt-2">
                                    Reportar Incidència
                                </button>
                            </div>
                            <div v-else>
                                <span class="px-4 py-2 bg-gray-100 text-gray-500 font-bold rounded block text-center">
                                    Ja realitzada
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div v-if="agenda.length === 0" class="text-center py-20 bg-gray-50 rounded-xl">
            <p class="text-gray-400">No tens tallers assignats properament.</p>
        </div>
    </div>
  </MainLayout>
</template>
