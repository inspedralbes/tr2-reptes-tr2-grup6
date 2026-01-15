<script setup>
import { ref, onMounted } from 'vue';
import MainLayout from '../layouts/MainLayout.vue';

const users = ref([]);
const showModal = ref(false);

const form = ref({
    nom: '',
    email: '',
    password: '',
    rol: 2
});

const fetchUsers = async () => {
    const res = await fetch('http://localhost:8000/api/admin_users.php');
    users.value = await res.json();
};

onMounted(fetchUsers);

const crearUsuario = async () => {
    try {
        const res = await fetch('http://localhost:8000/api/admin_users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(form.value)
        });
        const data = await res.json();
        if (data.success) {
            alert('✅ Usuari creat!');
            showModal.value = false;
            form.value = { nom: '', email: '', password: '', rol: 2 };
            fetchUsers();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (e) {
        alert('Error de connexió');
    }
};

const eliminarUsuario = async (id, nombre) => {
    if (!confirm(`Segur que vols eliminar l'accés a "${nombre}"?`)) return;
    const res = await fetch(`http://localhost:8000/api/admin_users.php?id=${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) fetchUsers();
    else alert(data.message);
};

const getRoleBadge = (rolName) => {
    if (rolName === 'admin') return 'bg-purple-100 text-purple-800';
    if (rolName === 'professor') return 'bg-blue-100 text-blue-800';
    return 'bg-green-100 text-green-800';
};
</script>

<template>
  <MainLayout>
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-kairos-navy">Gestió d'Usuaris</h2>
                <p class="text-gray-500">Administra els accessos dels Centres i Entitats.</p>
            </div>
            <button @click="showModal = true" class="bg-kairos-navy text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-900 flex items-center gap-2">
                <span>+</span> Nou Usuari
            </button>
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-bold">
                    <tr>
                        <th class="p-4 border-b">ID</th>
                        <th class="p-4 border-b">Nom / Centre</th>
                        <th class="p-4 border-b">Email (Usuari)</th>
                        <th class="p-4 border-b">Rol</th>
                        <th class="p-4 border-b text-right">Accions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="u in users" :key="u.id" class="hover:bg-gray-50 transition">
                        <td class="p-4 text-gray-400">#{{ u.id }}</td>
                        <td class="p-4 font-bold text-gray-800">{{ u.nom_complet }}</td>
                        <td class="p-4 text-gray-600 font-mono text-sm">{{ u.email }}</td>
                        <td class="p-4">
                            <span :class="`px-2 py-1 rounded-full text-xs font-bold ${getRoleBadge(u.nom_rol)}`">
                                {{ u.nom_rol }}
                            </span>
                        </td>
                        <td class="p-4 text-right">
                            <button @click="eliminarUsuario(u.id, u.nom_complet)" class="text-red-400 hover:text-red-600 px-2">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl w-full max-w-md p-6">
                <h3 class="text-xl font-bold text-kairos-navy mb-4">Donar d'alta Usuari</h3>
                
                <form @submit.prevent="crearUsuario" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Nom del Centre o Professor</label>
                        <input v-model="form.nom" placeholder="Ex: Institut Salvador Espriu" class="w-full p-2 border rounded" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Email d'accés</label>
                        <input type="email" v-model="form.email" placeholder="contacte@institut.cat" class="w-full p-2 border rounded" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Contrasenya</label>
                        <input type="password" v-model="form.password" placeholder="******" class="w-full p-2 border rounded" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Tipus de Compte (Rol)</label>
                        <select v-model="form.rol" class="w-full p-2 border rounded bg-white">
                            <option :value="2">🏫 Centre (Origen)</option>
                            <option :value="3">👨‍🏫 Professor / Entitat (Destí)</option>
                            <option :value="1">⚙️ Administrador</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t mt-4">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-gray-500">Cancel·lar</button>
                        <button type="submit" class="bg-kairos-navy text-white px-4 py-2 rounded font-bold">Crear Usuari</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
  </MainLayout>
</template>
