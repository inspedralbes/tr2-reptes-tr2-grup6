<script setup>
import { ref, onMounted } from 'vue';
import MainLayout from '../layouts/MainLayout.vue';

const tallers = ref([]);
const sectors = ref([]);
const showModal = ref(false);
const defaultImage = 'https://images.unsplash.com/photo-1531297461136-82bf96091d6a?auto=format&fit=crop&w=900&q=80';
const form = ref({
    nom: '', descripcio: '', modalitat: 'A', durada: 60, capacitat: 25,
    sector_id: '', imatge: defaultImage
});

// Cargar Talleres
const fetchTalleres = async () => {
    const res = await fetch('http://localhost:8000/api/tallers.php');
    tallers.value = await res.json();
};

// Cargar Sectores
const fetchSectors = async () => {
    try {
        const res = await fetch('http://localhost:8000/api/sectors.php');
        const data = await res.json();
        sectors.value = data;
        console.log('Sectores cargados:', sectors.value);
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
            alert("✅ Taller creat!");
            showModal.value = false;
            await fetchTalleres();
            form.value = { nom: '', descripcio: '', modalitat: 'A', durada: 60, capacitat: 25, sector_id: '', imatge: defaultImage };
        } else {
            alert(data.message || 'Error al crear');
        }
    } catch (e) {
        alert("Error de connexió");
    }
};

// Eliminar Taller
const eliminarTaller = async (id) => {
    if(!confirm("Segur que vols eliminar aquest taller del catàleg?")) return;
    await fetch(`http://localhost:8000/api/admin_talleres.php?id=${id}`, {
        method: 'DELETE'
    });
    fetchTalleres();
};
</script>

<template>
  <MainLayout>
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-kairos-navy">Gestió del Catàleg</h2>
            <button @click="showModal = true" class="bg-kairos-gold text-kairos-navy font-bold px-4 py-2 rounded-lg hover:brightness-110 flex items-center gap-2">
                <span>+</span> Nou Taller
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="t in tallers" :key="t.id" class="bg-white rounded-xl shadow border border-gray-200 p-4 flex flex-col relative group">
                
                <button @click="eliminarTaller(t.id)" class="absolute top-2 right-2 bg-red-100 text-red-600 p-2 rounded-full hover:bg-red-200 opacity-0 group-hover:opacity-100 transition">
                    🗑
                </button>

                <div class="flex items-center gap-4 mb-4">
                    <img :src="t.imatge" class="w-16 h-16 rounded-lg object-cover bg-gray-100" alt="imatge taller">
                    <div>
                        <h3 class="font-bold text-gray-800 leading-tight">{{ t.nom }}</h3>
                        <span class="text-xs text-gray-500">{{ t.categoria?.nom || t.sector?.nom || 'Sense sector' }} | Mod {{ t.modalitat }}</span>
                    </div>
                </div>
                <p class="text-sm text-gray-600 line-clamp-2">{{ t.descripcio }}</p>
                <div class="mt-auto pt-4 border-t flex justify-between text-sm text-gray-500">
                    <span>{{ t.durada_minuts }} min</span>
                    <span>Max: {{ t.capacitat_max }} alumnes</span>
                </div>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="text-xl font-bold text-kairos-navy mb-4">Nou Taller</h3>
                
                <form @submit.prevent="crearTaller" class="space-y-4">
                    <div>
                        <label class="text-xs text-gray-500 font-bold uppercase block mb-1">Nom del Taller</label>
                        <input v-model="form.nom" placeholder="Nom del Taller" class="w-full p-2 border rounded" required>
                    </div>

                    <div>
                        <label class="text-xs text-gray-500 font-bold uppercase block mb-1">Descripció</label>
                        <textarea v-model="form.descripcio" placeholder="Descripció breu..." class="w-full p-2 border rounded" rows="3"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-500 font-bold uppercase block mb-1">Modalitat</label>
                            <select v-model="form.modalitat" class="w-full p-2 border rounded">
                                <option value="A">Mod A (Centre ve)</option>
                                <option value="B">Mod B (Profe va)</option>
                                <option value="C">Mod C (Online)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 font-bold uppercase block mb-1">Sector Professional</label>
                            <select v-model.number="form.sector_id" class="w-full p-2 border rounded" required>
                                <option value="" disabled>Selecciona un sector...</option>
                                <option v-for="sec in sectors" :key="sec.id" :value="sec.id">
                                    {{ sec.nom }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-500 font-bold uppercase block mb-1">Durada (minuts)</label>
                            <input type="number" v-model.number="form.durada" placeholder="Minuts" class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 font-bold uppercase block mb-1">Capacitat Màxima</label>
                            <input type="number" v-model.number="form.capacitat" placeholder="Capacitat Max" class="w-full p-2 border rounded">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-gray-500 font-bold uppercase block mb-1">Imatge URL</label>
                        <input v-model="form.imatge" placeholder="URL Imatge (https://...)" class="w-full p-2 border rounded text-sm">
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-gray-500 hover:bg-gray-100 rounded">Cancel·lar</button>
                        <button type="submit" class="bg-kairos-navy text-white px-4 py-2 rounded font-bold">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
  </MainLayout>
</template>
