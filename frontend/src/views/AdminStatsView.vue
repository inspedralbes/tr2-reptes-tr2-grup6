<script setup>
import { ref, onMounted } from 'vue';
import MainLayout from '../layouts/MainLayout.vue';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const loaded = ref(false);
const kpis = ref({ solicitudes: 0, alumnos: 0, tasa_exito: 0 });
const chartRef = ref(null);
let chartInstance = null;

onMounted(async () => {
    try {
        const res = await fetch('http://localhost:8000/api/admin_stats.php');
        const data = await res.json();
        
        kpis.value = data.kpis;

        // Esperar a que el DOM esté listo
        await new Promise(resolve => setTimeout(resolve, 100));

        const ctx = chartRef.value?.getContext('2d');
        if (ctx) {
            if (chartInstance) {
                chartInstance.destroy();
            }

            chartInstance = new ChartJS(ctx, {
                type: 'bar',
                data: {
                    labels: data.sectores.map(s => s.nom),
                    datasets: [{
                        label: 'Peticions',
                        data: data.sectores.map(s => s.cantidad),
                        backgroundColor: data.sectores.map(s => s.color || '#3B82F6'),
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
        
        loaded.value = true;
    } catch (e) {
        console.error("Error cargando stats", e);
    }
});
</script>

<template>
  <MainLayout>
    <div class="max-w-7xl mx-auto">
        <h2 class="text-3xl font-bold text-kairos-navy mb-6">Impacte del Programa ENGINY</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-kairos-blue">
                <p class="text-sm font-bold text-gray-500 uppercase">Alumnes Orientats</p>
                <p class="text-4xl font-bold text-kairos-navy mt-2">{{ kpis.alumnos }}</p>
                <p class="text-xs text-green-600 mt-2">↑ Impacte Directe</p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-kairos-gold">
                <p class="text-sm font-bold text-gray-500 uppercase">Peticions Rebudes</p>
                <p class="text-4xl font-bold text-kairos-navy mt-2">{{ kpis.solicitudes }}</p>
                <p class="text-xs text-gray-400 mt-2">De centres educatius</p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-green-500">
                <p class="text-sm font-bold text-gray-500 uppercase">Percentatge d'Assignació</p>
                <p class="text-4xl font-bold text-gray-800 mt-2">{{ kpis.tasa_exito }}%</p>
                <p class="text-xs text-gray-400 mt-2">Capacitat de resposta</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow mb-8">
            <h3 class="text-lg font-bold text-gray-700 mb-4">Demanda per Sector Professional</h3>
            <div v-if="loaded" class="h-80">
                <canvas ref="chartRef"></canvas>
            </div>
            <div v-else class="h-80 flex items-center justify-center text-gray-400">
                Carregant dades visuals...
            </div>
        </div>

    </div>
  </MainLayout>
</template>
