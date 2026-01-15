// frontend/src/stores/auth.js
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';

export const useAuthStore = defineStore('auth', () => {
    const router = useRouter();
    
    // Estado (State)
    // Intentamos recuperar del localStorage por si refresca la página
    const user = ref(JSON.parse(localStorage.getItem('user')) || null);
    const token = ref(localStorage.getItem('token') || null);
    const returnUrl = ref(null);

    // Getters
    const isAuthenticated = computed(() => !!token.value);
    const isAdmin = computed(() => user.value?.rol === 1); // 1 = Admin

    // Acciones (Actions)
    async function login(email, password) {
        try {
            // URL del backend PHP con Docker
            const response = await fetch('http://localhost:8000/api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Error en el login');
            }

            // Guardar datos en Pinia y LocalStorage
            user.value = data.user;
            token.value = data.token;
            
            localStorage.setItem('user', JSON.stringify(data.user));
            localStorage.setItem('token', data.token);

            // Redirigir según ROL
            if (data.user.rol === 1) {
                router.push('/admin/solicitudes'); // Admin -> Panel Admin
            } else if (data.user.rol === 3) {
                router.push('/docente'); // Profesor -> Su Agenda
            } else {
                router.push('/dashboard'); // Centro -> Catálogo
            }

        } catch (error) {
            console.error(error);
            throw error; // Lanzamos el error para que la Vista lo muestre
        }
    }

    function logout() {
        user.value = null;
        token.value = null;
        localStorage.removeItem('user');
        localStorage.removeItem('token');
        router.push('/login');
    }

    return { user, token, isAuthenticated, isAdmin, login, logout };
});
