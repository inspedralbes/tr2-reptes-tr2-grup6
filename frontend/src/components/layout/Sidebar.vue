<script setup>
import { useAuthStore } from '../../stores/auth';
import { useRouter } from 'vue-router';
import NotificationBell from '../NotificationBell.vue';
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);

const auth = useAuthStore();
const router = useRouter();

const handleLogout = () => {
    auth.logout();
    router.push('/login');
};
</script>

<template>
    <!-- Mobile Overlay -->
    <div v-if="isOpen" 
         class="fixed inset-0 bg-black/50 z-30 md:hidden transition-opacity" 
         @click="$emit('close')">
    </div>

    <!-- Sidebar -->
    <aside 
        class="bg-kairos-navy text-white flex flex-col shadow-xl h-full fixed top-0 left-0 z-40 w-64 transform transition-transform duration-300 ease-in-out md:translate-x-0"
        :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <!-- Logo -->
        <div class="p-6 text-center border-b border-gray-700 flex justify-between items-center md:block">
            <div>
                <h1 class="text-3xl font-bold text-kairos-gold font-serif tracking-wider">KAIROS</h1>
                <p class="text-xs text-gray-400 mt-1 uppercase tracking-widest">Gestió ENGINY</p>
            </div>
            <!-- Mobile Close Button -->
            <button @click="$emit('close')" class="md:hidden text-gray-400 hover:text-white">
                ✕
            </button>
        </div>

        <!-- Navigation (Scrollable) -->
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto custom-scrollbar">
            <!-- Role: Centre (2) -->
            <div v-if="auth.user?.rol === 2" class="space-y-1">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase mb-2 mt-2">Centre Educatiu</p>
                
                <router-link to="/dashboard" 
                    @click="$emit('close')"
                    class="nav-item" 
                    active-class="active">
                    <span class="icon">📚</span>
                    <span class="label">Catàleg de Tallers</span>
                </router-link>

                <router-link to="/mis-solicitudes" 
                    @click="$emit('close')"
                    class="nav-item"
                    active-class="active">
                    <span class="icon">📋</span>
                    <span class="label">Les Meves Sol·licituds</span>
                </router-link>

                <router-link to="/alumnes" 
                    @click="$emit('close')"
                    class="nav-item"
                    active-class="active">
                    <span class="icon">👨‍🎓</span>
                    <span class="label">Gestió d'Alumnes</span>
                </router-link>
            </div>

            <!-- Role: Professor (3) or Admin -->
            <div v-if="auth.user && (auth.user.rol === 3 || auth.isAdmin)" class="space-y-1">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase mb-2 mt-4">Professorat</p>
                <router-link to="/professor/agenda" 
                    @click="$emit('close')"
                    class="nav-item"
                    active-class="active">
                    <span class="icon">🗓️</span>
                    <span class="label">La Meva Agenda</span>
                </router-link>
            </div>

            <!-- Role: Admin (1) -->
            <div v-if="auth.isAdmin" class="space-y-1">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase mb-2 mt-6 pt-4 border-t border-gray-700">Administració</p>

                <router-link to="/admin/stats" 
                    @click="$emit('close')"
                    class="nav-item"
                    active-class="active">
                    <span class="icon">📊</span>
                    <span class="label">Analítica</span>
                </router-link>

                <router-link to="/admin/solicitudes" 
                    @click="$emit('close')"
                    class="nav-item"
                    active-class="active">
                    <span class="icon">⚡</span>
                    <span class="label">Peticions</span>
                </router-link>

                <router-link to="/admin/talleres" 
                    @click="$emit('close')"
                    class="nav-item"
                    active-class="active">
                    <span class="icon">⚙️</span>
                    <span class="label">Catàleg</span>
                </router-link>

                <router-link to="/admin/users" 
                    @click="$emit('close')"
                    class="nav-item"
                    active-class="active">
                    <span class="icon">👥</span>
                    <span class="label">Usuaris</span>
                </router-link>
            </div>
        </nav>

        <!-- User Profile (Fixed Bottom) -->
        <div class="p-4 border-t border-gray-700 bg-black/20 relative">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center min-w-0">
                    <div class="w-10 h-10 rounded-lg bg-kairos-blue flex items-center justify-center font-bold text-lg shadow-lg flex-shrink-0">
                        {{ auth.user?.nom?.charAt(0) || 'U' }}
                    </div>
                    <div class="ml-3 overflow-hidden">
                        <p class="text-sm font-bold truncate text-white">{{ auth.user?.nom }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ auth.user?.email }}</p>
                    </div>
                </div>
                <!-- Bell with TOP placement -->
                <NotificationBell placement="top-right" />
            </div>
            <button @click="handleLogout" class="w-full py-2 px-4 rounded border border-red-500/30 text-sm text-red-300 hover:bg-red-500/10 hover:text-red-100 transition duration-200 flex items-center justify-center gap-2">
                <span>🚪</span> Tancar Sessió
            </button>
        </div>
    </aside>
</template>

<style scoped>
.nav-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    transition: all 0.2s;
    color: #94A3B8; /* Slate 400 */
}
.nav-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
}
.nav-item.active {
    background-color: #C5A059; /* Kairos Gold */
    color: #0F172A; /* Kairos Navy */
    font-weight: 700;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
.nav-item .icon {
    font-size: 1.25rem;
    margin-right: 0.75rem;
    transition: transform 0.2s;
}
.nav-item:hover .icon, .nav-item.active .icon {
    transform: scale(1.1);
}
</style>
