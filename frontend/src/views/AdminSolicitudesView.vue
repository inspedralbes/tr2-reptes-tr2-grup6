<script setup>
import { ref, onMounted } from 'vue';
import MainLayout from '../layouts/MainLayout.vue';

const solicitudes = ref([]);
const professores = ref([]);
const loading = ref(true);

// Variables para el Modal de Asignación Manual
const showAssignModal = ref(false);
const selectedSolicitudId = ref(null);
const selectedProfesor = ref('');

// Variables para el Modal de Asignación Automática (Algoritmo)
const showAlgorithmModal = ref(false);
const algorithmRunning = ref(false);
const algorithmResult = ref(null);
const prioridades = ref([]);

// Cargar datos
const fetchData = async () => {
    try {
        const [resSol, resProf] = await Promise.all([
            fetch('http://localhost:8000/api/admin_sollicituds.php'),
            fetch('http://localhost:8000/api/professors.php')
        ]);
        solicitudes.value = await resSol.json();
        professores.value = await resProf.json();
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);

// ========== ASIGNACIÓN MANUAL ==========

// Abrir modal al hacer clic en Aprobar
const abrirAsignacion = (id) => {
    selectedSolicitudId.value = id;
    selectedProfesor.value = '';
    showAssignModal.value = true;
};

// Confirmar Asignación Manual
const confirmarAsignacion = async () => {
    if(!selectedProfesor.value) {
        alert("Selecciona un professor!");
        return;
    }

    try {
        const res = await fetch('http://localhost:8000/api/admin_sollicituds.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                id: selectedSolicitudId.value, 
                estat: 'assignada',
                professor_id: parseInt(selectedProfesor.value)
            })
        });
        const data = await res.json();
        
        if(data.success) {
            showAssignModal.value = false;
            fetchData();
            alert("✅ Taller assignat correctament!");
        } else {
            alert("❌ Error: " + data.message);
        }
    } catch (error) {
        alert("Error al servidor");
        console.error(error);
    }
};

// ========== ASIGNACIÓN INTELIGENTE (ALGORITMO) ==========

// Abrir modal de algoritmo
const abrirAlgoritmo = async () => {
    showAlgorithmModal.value = true;
    algorithmRunning.value = true;
    algorithmResult.value = null;
    
    try {
        const res = await fetch('http://localhost:8000/api/admin_assignacion.php?action=calcular_prioridades');
        const data = await res.json();
        
        if (data.success) {
            prioridades.value = data.resultado;
        } else {
            alert("Error: " + data.error);
        }
    } catch (error) {
        alert("Error cargando prioridades");
        console.error(error);
    } finally {
        algorithmRunning.value = false;
    }
};

// Ejecutar algoritmo de asignación
const ejecutarAlgoritmo = async () => {
    if (!confirm("¿Ejecutar asignación inteligente? Esto asignará automáticamente todas las solicitudes pendientes.")) {
        return;
    }
    
    algorithmRunning.value = true;
    
    try {
        const res = await fetch('http://localhost:8000/api/admin_assignacion.php?action=ejecutar_asignacion', {
            method: 'POST'
        });
        const data = await res.json();
        
        if (data.success) {
            algorithmResult.value = data;
            fetchData();
            alert(`✅ Asignación completada!\n✅ Asignadas: ${data.asignaciones_creadas}\n❌ Rebutjades: ${data.rechazadas}`);
        } else {
            alert("Error: " + data.error);
        }
    } catch (error) {
        alert("Error ejecutando algoritmo");
        console.error(error);
    } finally {
        algorithmRunning.value = false;
    }
};

// Rechazar Directamente
const rechazar = async (id) => {
    if(!confirm("Segur que vols rebutjar aquesta sol·licitud?")) return;

    try {
        const res = await fetch('http://localhost:8000/api/admin_sollicituds.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, estat: 'rebutjada' })
        });
        const data = await res.json();
        
        if(data.success) {
            fetchData();
            alert("❌ Sol·licitud rebutjada");
        }
    } catch (error) {
        alert("Error al servidor");
    }
};

const getStatusColor = (estat) => {
    switch(estat) {
        case 'pendent': return 'bg-yellow-100 text-yellow-800';
        case 'assignada': return 'bg-green-100 text-green-800';
        case 'rebutjada': return 'bg-red-100 text-red-800';
        case 'realitzada': return 'bg-blue-100 text-blue-800';
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
    <div class="max-w-7xl mx-auto">
      <header class="mb-8">
        <h2 class="text-3xl font-bold text-kairos-navy">Gestió de Sol·licituds</h2>
        <p class="text-gray-500 mt-2">Revisa, aprova o rebutja les sol·licituds dels centres educatius.</p>
      </header>

      <!-- Botón Algoritmo Inteligente -->
      <div class="mb-6 flex gap-2">
        <button @click="abrirAlgoritmo" class="px-6 py-3 bg-kairos-blue text-white font-bold rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
          🤖 Asignació Intel·ligent (Algoritmo)
        </button>
      </div>

      <div v-if="loading" class="text-center py-20 text-gray-400">
        Carregant sol·licituds...
      </div>

      <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Centre</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Taller</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Alumnes</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Data Pref.</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Estat</th>
              <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">Accions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="sol in solicitudes" :key="sol.id" class="hover:bg-gray-50 transition">
              
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-semibold text-gray-900">{{ sol.nom_centre }}</div>
                <div class="text-xs text-gray-500">{{ formatDate(sol.data_creacio) }}</div>
              </td>

              <td class="px-6 py-4">
                <div class="text-sm font-semibold text-gray-900">{{ sol.nom_taller }}</div>
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-50 text-blue-700 mt-1">
                  {{ sol.modalitat === 'A' ? 'Centre ve' : sol.modalitat === 'B' ? 'Professor va' : 'Online' }}
                </span>
              </td>

              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-semibold text-gray-900">{{ sol.nombre_alumnes }}</div>
              </td>

              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-600">{{ sol.data_preferent ? formatDate(sol.data_preferent) : '—' }}</div>
              </td>

              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full uppercase"
                    :class="getStatusColor(sol.estat)">
                  {{ getStatusLabel(sol.estat) }}
                </span>
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-right">
                <div v-if="sol.estat === 'pendent'" class="flex justify-end gap-2">
                    <button @click="abrirAsignacion(sol.id)" 
                        class="px-3 py-1 text-sm font-medium text-white bg-green-600 rounded hover:bg-green-700 transition">
                        Aprovar i Assignar
                    </button>
                    <button @click="rechazar(sol.id)" 
                        class="px-3 py-1 text-sm font-medium text-white bg-red-600 rounded hover:bg-red-700 transition">
                        Rebutjar
                    </button>
                </div>
                <div v-else class="text-xs text-gray-400 font-medium">
                  Processada
                </div>
              </td>

            </tr>
          </tbody>
        </table>
        
        <div v-if="solicitudes.length === 0" class="p-10 text-center text-gray-500">
            No hi ha sol·licituds pendents.
        </div>
      </div>

      <!-- Modal de Asignación Manual -->
      <div v-if="showAssignModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-xl shadow-2xl w-96">
            <h3 class="text-lg font-bold text-kairos-navy mb-4">Assignar Professor</h3>
            
            <p class="text-sm text-gray-600 mb-3">Qui impartirà aquest taller?</p>
            
            <select v-model="selectedProfesor" class="w-full p-3 border border-gray-300 rounded-lg mb-6 focus:outline-none focus:border-kairos-blue">
                <option value="" disabled>Selecciona un expert...</option>
                <option v-for="prof in professores" :key="prof.id" :value="prof.id">
                    {{ prof.nom_complet }}
                </option>
            </select>

            <div class="flex justify-end gap-2">
                <button @click="showAssignModal = false" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    Cancel·lar
                </button>
                <button @click="confirmarAsignacion" class="px-4 py-2 bg-kairos-blue text-white rounded-lg font-bold hover:bg-blue-700 transition">
                    Confirmar
                </button>
            </div>
        </div>
      </div>

      <!-- Modal de Algoritmo Inteligente -->
      <div v-if="showAlgorithmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-xl shadow-2xl w-2xl max-h-96 overflow-y-auto">
            <h3 class="text-lg font-bold text-kairos-navy mb-4">🤖 Asignació Intel·ligent</h3>
            
            <div v-if="algorithmRunning && !algorithmResult" class="text-center py-8">
              <p class="text-gray-600 mb-4">Analitzant prioritats...</p>
              <div class="inline-block animate-spin">⏳</div>
            </div>

            <div v-else-if="prioridades.length > 0 && !algorithmResult">
              <p class="text-sm text-gray-600 mb-4">
                Es van a assignar {{ prioridades.filter(p => p.puntuacion_total).length }} sol·licituds segons l'algoritme:
              </p>
              <ul class="space-y-2 max-h-64 overflow-y-auto mb-6">
                <li v-for="sol in prioridades.slice(0, 10)" :key="sol.sollicitud_id" class="text-sm bg-gray-50 p-3 rounded">
                  <strong>{{ sol.centre_nom }}</strong> → {{ sol.taller_nom }}
                  <span class="text-xs text-gray-500 ml-2">(Puntuació: {{ sol.puntuacion_total }})</span>
                </li>
              </ul>

              <div class="flex justify-end gap-2">
                <button @click="showAlgorithmModal = false" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    Cancel·lar
                </button>
                <button @click="ejecutarAlgoritmo" class="px-4 py-2 bg-kairos-blue text-white rounded-lg font-bold hover:bg-blue-700 transition">
                    ✅ Executar Asignació
                </button>
              </div>
            </div>

            <div v-else-if="algorithmResult">
              <div class="bg-green-50 border-2 border-green-300 p-4 rounded-lg mb-4">
                <p class="text-sm text-green-900">
                  ✅ <strong>Asignació completada!</strong>
                </p>
                <p class="text-sm text-green-700 mt-2">
                  Assignades: <strong class="text-lg">{{ algorithmResult.asignaciones_creadas }}</strong>
                </p>
                <p class="text-sm text-green-700">
                  Rebutjades: <strong class="text-lg">{{ algorithmResult.rechazadas }}</strong>
                </p>
              </div>

              <button @click="showAlgorithmModal = false" class="w-full px-4 py-2 bg-kairos-blue text-white rounded-lg font-bold hover:bg-blue-700 transition">
                Tancar
              </button>
            </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>
