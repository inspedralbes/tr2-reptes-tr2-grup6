<template>
  <div class="change-password-container">
    <div class="change-password-card">
      <div class="header">
        <h1>Canviar Contrasenya</h1>
        <p class="subtitle">És el teu primer accés. Si us plau, crea una contrasenya segura.</p>
      </div>

      <form @submit.prevent="handleSubmit">
        <div class="mb-3">
          <label for="new-password" class="form-label">Nova Contrasenya *</label>
          <input 
            v-model="form.new_password" 
            type="password" 
            class="form-control" 
            id="new-password" 
            required
            :disabled="loading"
            placeholder="Mínim 8 caràcters"
          >
          <small class="text-muted">Recomanem una contrasenya amb lletres, números i caràcters especials</small>
        </div>

        <div class="mb-3">
          <label for="confirm-password" class="form-label">Confirmar Contrasenya *</label>
          <input 
            v-model="form.confirm_password" 
            type="password" 
            class="form-control" 
            id="confirm-password" 
            required
            :disabled="loading"
            placeholder="Repeteix la contrasenya"
          >
        </div>

        <div v-if="error" class="alert alert-danger">
          {{ error }}
        </div>

        <button type="submit" class="btn btn-primary w-100" :disabled="loading">
          {{ loading ? 'Guardant...' : 'Guardar Contrasenya' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  new_password: '',
  confirm_password: ''
})

const loading = ref(false)
const error = ref(null)

const handleSubmit = async () => {
  error.value = null

  // Validaciones
  if (form.value.new_password.length < 8) {
    error.value = 'La contrasenya ha de tenir mínim 8 caràcters'
    return
  }

  if (form.value.new_password !== form.value.confirm_password) {
    error.value = 'Les contrasenyes no coincideixen'
    return
  }

  loading.value = true

  try {
    const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000'
    const token = authStore.token
    
    const response = await axios.post(
      `${API_BASE}/api/auth/change-password`, 
      { new_password: form.value.new_password },
      { headers: { Authorization: `Bearer ${token}` } }
    )
    
    if (response.data.success) {
      // Actualizar el usuario para quitar el flag de force_password_change
      authStore.user.force_password_change = false
      
      // Redirigir según rol
      const user = authStore.user
      if (user.role === 'admin') {
        router.push('/admin')
      } else if (user.role === 'center_coord') {
        router.push('/center-dashboard')
      } else if (user.role === 'teacher') {
        router.push('/teacher/schedule')
      } else {
        router.push('/dashboard')
      }
    } else {
      error.value = response.data.message || 'Error al canviar la contrasenya'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al canviar la contrasenya'
    console.error('Error:', err)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.change-password-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #0F172A 0%, #1a2940 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
}

.change-password-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  padding: 50px;
  max-width: 500px;
  width: 100%;
}

.header {
  text-align: center;
  margin-bottom: 35px;
}

.header h1 {
  color: #0F172A;
  font-weight: 700;
  font-size: 1.8rem;
  margin-bottom: 12px;
}

.subtitle {
  color: #6C757D;
  font-size: 1rem;
  line-height: 1.6;
}

.form-label {
  font-weight: 600;
  color: #0F172A;
  margin-bottom: 8px;
  font-size: 0.95rem;
}

.form-control {
  border: 2px solid #E9ECEF;
  border-radius: 8px;
  padding: 14px 16px;
  transition: all 0.3s ease;
  font-size: 1rem;
}

.form-control:focus {
  border-color: #3B82F6;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
  outline: none;
}

.form-control::placeholder {
  color: #ADB5BD;
}

.text-muted {
  color: #6C757D;
  font-size: 0.875rem;
  display: block;
  margin-top: 6px;
}

.btn-primary {
  background: #C5A059;
  border: none;
  padding: 16px;
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.3s ease;
  font-size: 1.05rem;
}

.btn-primary:hover:not(:disabled) {
  background: #2563EB;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 102, 204, 0.3);
}

.btn-primary:disabled {
  background: #ADB5BD;
  cursor: not-allowed;
  transform: none;
}

.alert-danger {
  background: #FEE2E2;
  border: 2px solid #EF4444;
  color: #991B1B;
  border-radius: 8px;
  padding: 14px;
  margin-bottom: 20px;
  font-weight: 500;
}

.w-100 {
  width: 100%;
}

.mb-3 {
  margin-bottom: 20px;
}

@media (max-width: 768px) {
  .change-password-card {
    padding: 35px 25px;
  }

  .header h1 {
    font-size: 1.5rem;
  }
}
</style>
