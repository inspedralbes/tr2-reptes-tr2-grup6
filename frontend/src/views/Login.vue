<template>
  <div class="login-page">
    <!-- Ambient Background Animation -->
    <div class="hero-blobs">
      <div class="blob blob-1"></div>
      <div class="blob blob-2"></div>
    </div>

    <div class="login-container">
      <router-link to="/" class="btn-back">
        <i class="fas fa-arrow-left"></i> Tornar a l'inici
      </router-link>
      
      <div class="login-header">
        <h2>Benvingut de nou</h2>
        <p>Accediu al panell de gestió d'ENGINY</p>
      </div>

      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="email">Correu electrònic</label>
          <input 
            v-model="email" 
            type="email" 
            id="email" 
            placeholder="correu@centre.cat"
            required
            :disabled="loading"
          >
        </div>

        <div class="form-group">
          <label for="password">Contrasenya</label>
          <input 
            v-model="password" 
            type="password" 
            id="password" 
            placeholder="••••••••"
            required
            :disabled="loading"
          >
        </div>

        <div class="form-footer">
          <a href="#" @click.prevent="showForgotPassword" class="forgot-link">
            Heu oblidat la contrasenya?
          </a>
        </div>



        <button type="submit" class="btn-primary" :disabled="loading">
          {{ loading ? 'Iniciant sessió...' : 'Iniciar sessió' }}
        </button>
      </form>

      <div class="register-link">
        Sou un centre educatiu nou?
        <router-link to="/solicitud-centro">Sol·liciteu accés</router-link>
      </div>
    </div>

    <!-- MODAL FORGOT PASSWORD -->
    <div v-if="showForgotModal" class="modal-overlay" @click.self="showForgotModal = false">
      <div class="modal-content">
        <h3>Recuperar contrasenya</h3>
        <p class="text-muted">Introdueix el teu correu electrònic i rebràs instruccions per recuperar la teva contrasenya.</p>
        
        <div class="form-group">
          <label for="forgot-email">Correu electrònic</label>
          <input 
            v-model="forgotEmail" 
            type="email" 
            id="forgot-email" 
            required
          >
        </div>
        
        <div v-if="forgotSuccess" class="alert alert-success">
          <i class="fas fa-check-circle"></i> S'han enviat les instruccions al teu correu electrònic.
        </div>
        
        <div v-if="forgotError" class="alert alert-danger">
          <i class="fas fa-exclamation-circle"></i> {{ forgotError }}
        </div>
        
        <div class="modal-actions">
          <button @click="handleForgotPassword" class="btn-primary" :disabled="forgotLoading">
            {{ forgotLoading ? 'Enviant...' : 'Enviar instruccions' }}
          </button>
          <button @click="showForgotModal = false" class="btn-secondary">
            Cancel·lar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const email = ref('')
const password = ref('')
const loading = ref(false)

// Forgot password
const showForgotModal = ref(false)
const forgotEmail = ref('')
const forgotLoading = ref(false)
const forgotSuccess = ref(false)
const forgotError = ref(null)

const loginError = ref(null)

/**
 * Handle login
 */
const handleLogin = async () => {
  loading.value = true
  loginError.value = null
  
  if (!email.value || !password.value) {
    loginError.value = 'Si us plau, completa tots els camps'
    loading.value = false
    return
  }
  
  const result = await authStore.login(email.value, password.value)
  
  if (result.success) {
    toastStore.success('Iniciat sessió correctament')
    
    // Verificar si es primer login y necesita cambiar contraseña
    if (result.user.force_password_change) {
      await nextTick()
      router.replace('/cambiar-password')
      return
    }
    
    // Redirigir según el rol
    const user = result.user
    await nextTick()
    
    if (user?.role === 'admin') {
      router.replace('/admin')
    } else if (user?.role === 'center_coord') {
      router.replace('/center-dashboard')
    } else if (user?.role === 'teacher') {
      router.replace('/teacher/schedule')
    } else {
      router.replace('/dashboard')
    }
  } else {
    loginError.value = result.error || 'Credencials incorrectes'
    toastStore.error(result.error || 'Error en el login')
  }
  
  loading.value = false
}

/**
 * Show forgot password modal
 */
const showForgotPassword = () => {
  showForgotModal.value = true
  forgotEmail.value = ''
  forgotSuccess.value = false
  forgotError.value = null
}

/**
 * Handle forgot password
 */
const handleForgotPassword = async () => {
  forgotError.value = null
  forgotSuccess.value = false
  forgotLoading.value = true
  
  if (!forgotEmail.value) {
    forgotError.value = 'Si us plau, introdueix el teu correu electrònic'
    forgotLoading.value = false
    return
  }
  
  // TODO: Implementar llamada a API para recuperación de contraseña
  setTimeout(() => {
    forgotSuccess.value = true
    forgotLoading.value = false
  }, 1000)
}
</script>

<style scoped>
.login-page {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100vh;
  width: 100%;
  background: radial-gradient(circle at 50% 50%, #1a2940 0%, #0F172A 100%);
  margin: 0;
  padding: 0;
  overflow: hidden;
  position: relative;
}

/* Blobs Animation */
.hero-blobs {
  position: absolute; inset: 0; overflow: hidden; pointer-events: none; z-index: 0;
}

.blob {
  position: absolute; border-radius: 50%; filter: blur(90px); opacity: 0.5;
  will-change: transform;
}

.blob-1 {
  width: 600px; height: 600px; background: #C5A059;
  top: -20%; left: -20%;
  animation: floatOrbBig1 20s infinite alternate ease-in-out;
}

.blob-2 {
  width: 700px; height: 700px; background: #3b82f6;
  bottom: -20%; right: -20%;
  animation: floatOrbBig2 25s infinite alternate ease-in-out;
}

@keyframes floatOrbBig1 {
  0% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(400px, 200px) scale(1.2); }
  100% { transform: translate(800px, 100px) scale(0.9); }
}

@keyframes floatOrbBig2 {
  0% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(-300px, -200px) scale(1.2); }
  100% { transform: translate(-700px, -100px) scale(0.9); }
}

.login-container {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 440px;
  background: #FFFFFF;
  padding: 50px;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #6C757D;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  padding: 0;
  border-radius: 8px;
  transition: all 0.2s ease;
  margin-bottom: 1.5rem;
  background: transparent;
}

.btn-back:hover {
  color: #C5A059;
  transform: translateX(-3px);
}

.btn-back i {
  font-size: 0.9rem;
}

.login-header {
  margin-bottom: 40px;
}

.login-header h2 {
  font-size: 2rem;
  color: #0F172A;
  font-weight: 700;
  margin-bottom: 10px;
}

.login-header p {
  color: #6C757D;
  font-size: 1.05rem;
}

.form-group {
  margin-bottom: 25px;
}

.form-group label {
  display: block;
  color: #495057;
  font-weight: 600;
  margin-bottom: 10px;
  font-size: 0.95rem;
}

.form-group input {
  width: 100%;
  padding: 14px 18px;
  border: 2px solid #E9ECEF;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: #F8F9FA;
}

.form-group input:focus {
  outline: none;
  border-color: #C5A059;
  background: #FFFFFF;
  box-shadow: 0 0 0 4px rgba(197, 160, 89, 0.1);
}

.form-group input:disabled {
  background: #E9ECEF;
  cursor: not-allowed;
}

.form-footer {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 25px;
}

.forgot-link {
  color: #C5A059;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  transition: color 0.2s ease;
}

.forgot-link:hover {
  color: #d4af37;
  text-decoration: underline;
}

.btn-primary {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, #C5A059 0%, #d4af37 100%);
  color: #FFFFFF;
  border: none;
  border-radius: 8px;
  font-size: 1.05rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #d4af37 0%, #e8c04c 100%);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(197, 160, 89, 0.4);
}

.btn-primary:disabled {
  background: #ADB5BD;
  cursor: not-allowed;
}

.error-message {
  margin-top: 20px;
  padding: 14px;
  background: #FFF3CD;
  border: 1px solid #FFC107;
  border-radius: 8px;
  color: #856404;
  font-size: 0.95rem;
}

.register-link {
  text-align: center;
  margin-top: 30px;
  color: #6C757D;
  font-size: 0.95rem;
}

.register-link a {
  color: #C5A059;
  text-decoration: none;
  font-weight: 600;
  margin-left: 5px;
}

.register-link a:hover {
  color: #d4af37;
  text-decoration: underline;
}

/* MODAL */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: #FFFFFF;
  border-radius: 12px;
  padding: 35px;
  max-width: 500px;
  width: 90%;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.modal-content h3 {
  color: #0F172A;
  margin-bottom: 15px;
  font-size: 1.5rem;
}

.text-muted {
  color: #6C757D;
  margin-bottom: 25px;
  line-height: 1.5;
}

.modal-actions {
  display: flex;
  gap: 12px;
  margin-top: 25px;
}

.btn-secondary {
  flex: 1;
  padding: 12px;
  background: #E9ECEF;
  color: #495057;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background: #DEE2E6;
}

.alert {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.alert-success {
  background: #D4EDDA;
  border: 1px solid #C3E6CB;
  color: #155724;
}

.alert-danger {
  background: #F8D7DA;
  border: 1px solid #F5C6CB;
  color: #721C24;
}

/* RESPONSIVE */
@media (max-width: 1024px) {
  .login-page {
    flex-direction: column;
  }
}

@media (max-width: 768px) {
  .login-header h2 {
    font-size: 1.6rem;
  }
}
</style>
