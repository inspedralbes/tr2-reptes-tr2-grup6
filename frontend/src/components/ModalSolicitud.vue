<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';

const props = defineProps({ 
    isOpen: Boolean, 
    taller: Object 
});
const emit = defineEmits(['close']);
const auth = useAuthStore();

const form = ref({
    alumnes: 20,
    curs_grup: '',
    preferencia_dates: '',
    necessitats: '',
});

const enviando = ref(false);

const enviar = async () => {
    enviando.value = true;
    try {
        const payload = {
            centre_id: auth.user.id,
            taller_id: props.taller.id,
            nombre_alumnes: form.value.alumnes,
            curs_grup: form.value.curs_grup,
            preferencia_dates: form.value.preferencia_dates,
            necessitats_especifiques: form.value.necessitats,
            comentaris: form.value.necessitats // Legacy field
        };

        const res = await fetch('http://localhost:8000/api/sollicitud.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        
        const data = await res.json();
        if(data.success) {
            alert("✅ Sol·licitud registrada! El servei de coordinació revisarà la teva petició.");
            form.value = { alumnes: 20, curs_grup: '', preferencia_dates: '', necessitats: '' };
            emit('close');
        } else {
            alert("❌ Error: " + (data.message || 'No s\'ha pogut enviar'));
        }
    } catch (e) {
        console.error(e);
        alert("❌ Error de connexió amb el servidor");
    } finally {
        enviando.value = false;
    }
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
        
        <div class="bg-gradient-to-r from-kairos-navy to-kairos-blue text-white p-5">
            <h3 class="font-bold text-xl">📝 Inscripció al Taller</h3>
            <p class="text-sm opacity-90 mt-1">{{ taller.nom }}</p>
        </div>

        <div class="p-6 overflow-y-auto flex-1">
            <form @submit.prevent="enviar" class="space-y-5">
                
                <!-- Perfil del Grup -->
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <h4 class="text-xs font-bold uppercase text-blue-900 mb-3 flex items-center gap-2">
                        <span>👥</span>
                        <span>Perfil del Grup</span>
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Curs i Grup *</label>
                            <input v-model="form.curs_grup" placeholder="Ex: 4t ESO A" required
                                class="w-full p-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-kairos-gold focus:border-kairos-gold outline-none transition">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nº Alumnes *</label>
                            <input type="number" v-model.number="form.alumnes" min="5" max="60" required
                                class="w-full p-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-kairos-gold focus:border-kairos-gold outline-none transition">
                        </div>
                    </div>
                </div>

                <!-- Preferència de Calendari -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1 flex items-center gap-2">
                        <span>📅</span>
                        <span>Preferència de Calendari *</span>
                    </label>
                    <p class="text-xs text-gray-500 mb-2">
                        Indica quins dies, horaris o trimestres us van millor (no cal data exacta).
                    </p>
                    <input v-model="form.preferencia_dates" required 
                        placeholder="Ex: Dimarts o dijous matí, 1r trimestre..."
                        class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-kairos-gold focus:border-kairos-gold outline-none transition">
                </div>

                <!-- Necessitats Específiques -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1 flex items-center gap-2">
                        <span>♿</span>
                        <span>Necessitats Específiques / Inclusió</span>
                    </label>
                    <p class="text-xs text-gray-500 mb-2">
                        Informa'ns si hi ha alumnes amb mobilitat reduïda, NEE, al·lèrgies, etc. (Opcional)
                    </p>
                    <textarea v-model="form.necessitats" rows="3" 
                        placeholder="Descriu aquí qualsevol necessitat especial del grup..."
                        class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-kairos-gold focus:border-kairos-gold outline-none transition resize-none"></textarea>
                </div>

                <!-- Info adicional -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <p class="text-xs text-yellow-800">
                        <strong>📌 Nota:</strong> Després d'enviar la sol·licitud, el servei de coordinació es posarà en contacte per confirmar la data definitiva i assignar un referent.
                    </p>
                </div>

                <!-- Botones -->
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" @click="$emit('close')" 
                        class="px-5 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition font-medium">
                        Cancel·lar
                    </button>
                    <button type="submit" :disabled="enviando" 
                        class="px-5 py-2 bg-kairos-navy text-white font-bold rounded-lg hover:bg-opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <span v-if="enviando">⏳</span>
                        <span v-else>✅</span>
                        <span>{{ enviando ? 'Enviant...' : 'Confirmar Inscripció' }}</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
  </div>
</template>
