<script setup>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();

const handleLogout = () => {
    auth.logout();
    router.push('/login');
};
</script>

<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <aside class="w-64 bg-kairos-navy text-white flex flex-col shadow-xl">
      <div class="p-6 text-center border-b border-gray-700">
        <h1 class="text-2xl font-bold text-kairos-gold font-serif">KAIROS</h1>
        <p class="text-xs text-gray-400 mt-1">Gestió ENGINY</p>
      </div>

      <nav class="flex-1 p-4 space-y-2">
        <div v-if="auth.user?.rol === 2">
            <router-link to="/dashboard" 
                class="flex items-center p-3 rounded-lg hover:bg-white/10 transition" 
                active-class="bg-kairos-gold text-kairos-navy font-bold">
                <span>📚 Catàleg de Tallers</span>
            </router-link>

            <router-link to="/mis-solicitudes" 
                class="flex items-center p-3 rounded-lg hover:bg-white/10 transition"
                active-class="bg-kairos-gold text-kairos-navy font-bold">
                <span>📋 Les Meves Sol·licituds</span>
            </router-link>
        </div>

        <div v-if="auth.user && (auth.user.rol === 3 || auth.isAdmin)">
          <router-link to="/professor/agenda" 
            class="flex items-center p-3 rounded-lg hover:bg-white/10 transition"
            active-class="bg-kairos-gold text-kairos-navy font-bold">
            <span>🗓️ La Meva Agenda</span>
          </router-link>
        </div>

        <div v-if="auth.isAdmin" class="mt-6 pt-6 border-t border-gray-700">
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase mb-2">Administració</p>

            <router-link to="/admin/stats" 
                class="flex items-center p-3 rounded-lg hover:bg-white/10 transition"
                active-class="bg-kairos-gold text-kairos-navy font-bold">
                <span>📊 Analítica i Impacte</span>
            </router-link>

            <router-link to="/admin/solicitudes" 
                class="flex items-center p-3 rounded-lg hover:bg-white/10 transition"
                active-class="bg-kairos-gold text-kairos-navy font-bold">
                <span>⚡ Gestió Peticions</span>
            </router-link>

          <router-link to="/admin/talleres" 
            class="flex items-center p-3 rounded-lg hover:bg-white/10 transition"
            active-class="bg-kairos-gold text-kairos-navy font-bold">
            <span>⚙️ Gestió Catàleg</span>
          </router-link>

          <router-link to="/admin/users" 
            class="flex items-center p-3 rounded-lg hover:bg-white/10 transition"
            active-class="bg-kairos-gold text-kairos-navy font-bold">
            <span>👥 Usuaris i Centres</span>
          </router-link>
        </div>
      </nav>

      <div class="p-4 border-t border-gray-700">
        <div class="flex items-center mb-4">
            <div class="w-8 h-8 rounded-full bg-kairos-blue flex items-center justify-center font-bold">
                {{ auth.user?.nom?.charAt(0) || 'U' }}
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium">{{ auth.user?.nom }}</p>
                <p class="text-xs text-gray-400">Centre Educatiu</p>
            </div>
        </div>
        <button @click="handleLogout" class="w-full text-sm text-red-300 hover:text-red-100 text-left">
          Tancar Sessió
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-8">
      <slot></slot>
    </main>
  </div>
</template>
