<script setup>
import { ref, onMounted } from 'vue';
import MainLayout from '../layouts/MainLayout.vue';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const classes = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        // Llamamos a la API enviando el ID del usuario logueado
        const res = await fetch(`http://localhost:8000/api/meves_assignacions.php?id=${auth.user.id}`);
        classes.value = await res.json();
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
});

const getModalitatColor = (mod) => {
    if(mod === 'A') return 'bg-purple-100 text-purple-800';
    if(mod === 'B') return 'bg-blue-100 text-blue-800';
    return 'bg-green-100 text-green-800';
};

const getModalitatLabel = (mod) => {
    if(mod === 'A') return 'Centre ve al CAIB';
    if(mod === 'B') return 'Professor va al centre';
    return 'Online';
};

const formatDate = (date) => {
    if (!date) return 'Data per confirmar';
    return new Date(date).toLocaleDateString('ca-ES', { year: 'numeric', month: 'long', day: 'numeric' });
};
</script>

<template>
  <MainLayout>
    <div class="max-w-5xl mx-auto">
        <header class="mb-8">
            <h2 class="text-3xl font-bold text-kairos-navy">🗓️ La Meva Agenda Docent</h2>
            <p class="text-gray-500 mt-2">Benvingut/da, <strong>{{ auth.user.nom }}</strong>. Aquestes són les teves sessions assignades.</p>
        </header>

        <div v-if="loading" class="text-center py-20 text-gray-400">
            Carregant agenda...
        </div>

        <div v-else-if="classes.length === 0" class="bg-blue-50 border border-blue-200 rounded-xl p-12 text-center">
            <p class="text-2xl text-blue-600 font-semibold">📅 No tens tallers assignats encara</p>
            <p class="text-blue-500 mt-2">Aviat rebràs nous encàrrecs</p>
        </div>

        <div v-else class="space-y-4">
            <div v-for="item in classes" :key="item.id" 
                class="bg-white border-l-4 border-kairos-gold rounded-lg shadow-sm hover:shadow-lg transition p-6">
                
                <div class="flex flex-col md:flex-row justify-between items-start md:items-start gap-6">
                    <!-- Dades del Taller -->
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span :class="getModalitatColor(item.modalitat)" class="text-xs px-3 py-1 rounded-full font-bold uppercase">
                                {{ getModalitatLabel(item.modalitat) }}
                            </span>
                        </div>
                        <h3 class="text-2xl font-bold text-kairos-navy mb-3">{{ item.nom_taller }}</h3>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm text-gray-700">
                            <div>
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Centre Educatiu</p>
                                <p class="font-medium">{{ item.nom_centre }}</p>
                                <p class="text-xs text-gray-500">{{ item.email_centre }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Nombre d'Alumnes</p>
                                <p class="text-xl font-bold text-kairos-blue">{{ item.nombre_alumnes }}</p>
                            </div>
                        </div>

                        <div v-if="item.comentaris" class="mt-4 p-3 bg-gray-50 rounded text-sm text-gray-700 border-l-2 border-kairos-gold">
                            <p class="font-semibold text-gray-600 mb-1">💬 Observacions del Centre:</p>
                            <p>{{ item.comentaris }}</p>
                        </div>
                    </div>

                    <!-- Data i Accions -->
                    <div class="md:text-right">
                        <div class="bg-kairos-blue text-white p-4 rounded-lg inline-block mb-4">
                            <p class="text-xs font-semibold uppercase text-blue-100">Data Preferent</p>
                            <p class="text-2xl font-bold">{{ formatDate(item.data_preferent) }}</p>
                        </div>
                        <div class="flex flex-col gap-2">
                            <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium text-sm">
                                ✅ Confirmar Assitència
                            </button>
                            <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium text-sm">
                                📧 Contactar Centre
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </MainLayout>
</template>
