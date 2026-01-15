<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const email = ref('');
const password = ref('');
const errorMsg = ref('');
const loading = ref(false);

const handleLogin = async () => {
    loading.value = true;
    errorMsg.value = '';
    
    try {
        await auth.login(email.value, password.value);
    } catch (e) {
        errorMsg.value = e.message;
    } finally {
        loading.value = false;
    }
};
</script>

<template>
  <div class="min-h-screen flex">
    
    <!-- Panel Izquierdo (Solo en pantallas grandes) -->
    <div class="hidden lg:flex w-1/2 bg-kairos-navy text-white flex-col justify-center items-center p-12 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-full opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
        
        <div class="relative z-10 text-center">
            <h1 class="text-6xl font-bold mb-4 text-kairos-gold font-serif">KAIROS</h1>
            <p class="text-xl text-blue-200">L'encaix perfecte entre centre i taller.</p>
        </div>
    </div>

    <!-- Panel Derecho (Formulario) -->
    <div class="w-full lg:w-1/2 flex items-center justify-center bg-kairos-surface p-8">
        <div class="w-full max-w-md bg-white p-8 rounded-2xl shadow-xl">
            
            <div class="mb-8 text-center lg:text-left">
                <h2 class="text-3xl font-bold text-kairos-navy">Benvingut</h2>
                <p class="text-gray-500">Introdueix les teves credencials per accedir.</p>
            </div>

            <form @submit.prevent="handleLogin" class="space-y-6">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correu Electrònic</label>
                    <input 
                        v-model="email"
                        type="email" 
                        required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-kairos-blue focus:border-transparent outline-none transition"
                        placeholder="admin@kairos.cat"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contrasenya</label>
                    <input 
                        v-model="password"
                        type="password" 
                        required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-kairos-blue focus:border-transparent outline-none transition"
                        placeholder="••••••••"
                    >
                </div>

                <div v-if="errorMsg" class="p-3 bg-red-50 text-red-600 rounded-lg text-sm flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"></path></svg>
                    {{ errorMsg }}
                </div>

                <button 
                    type="submit" 
                    :disabled="loading"
                    class="w-full py-3 px-4 bg-kairos-navy hover:bg-slate-800 text-white font-bold rounded-lg transition transform active:scale-95 disabled:opacity-50 flex justify-center"
                >
                    <span v-if="!loading">Accedir</span>
                    <span v-else>Carregant...</span>
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-500">
                No tens compte? <a href="#" class="text-kairos-blue hover:underline">Registrar centre</a>
            </div>
        </div>
    </div>
  </div>
</template>
