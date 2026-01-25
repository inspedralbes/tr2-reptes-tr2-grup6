<template>
  <div class="center-request-container">
    <div class="request-header">
      <h1>Solicitud d'Accés a KAIROS</h1>
      <p class="subtitle">Centre Educatiu - Programa ENGINY</p>
    </div>

    <div v-if="success" class="success-container">
      <div class="success-card">
        <div class="success-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <h2>Sol·licitud enviada correctament</h2>
        <p class="success-message">El teu centre educatiu ha estat registrat a la llista d'espera.</p>
        <p class="success-submessage">L'equip administratiu revisarà la teva sol·licitud i rebrà un correu electrònic amb les credencials d'accés.</p>
        <router-link to="/" class="btn-back-home">
          <i class="fas fa-home"></i> Tornar a l'inici
        </router-link>
      </div>
    </div>

    <form v-else @submit.prevent="handleSubmit">
      <h3 class="section-title">Dades del Centre</h3>

      <div class="mb-3">
        <label for="center_name" class="form-label">Nom del Centre *</label>
        <input 
          v-model="form.center_name" 
          type="text" 
          class="form-control" 
          id="center_name" 
          required
          :disabled="loading"
          placeholder="Institut Joan Miró"
        >
      </div>

      <div class="mb-3">
        <label for="center_code" class="form-label">Codi del Centre *</label>
        <input 
          v-model="form.center_code" 
          type="text" 
          class="form-control" 
          id="center_code" 
          required
          :disabled="loading"
          placeholder="08012345"
        >
      </div>

      <div class="mb-3">
        <label for="address" class="form-label">Adreça Completa *</label>
        <input 
          v-model="form.address" 
          type="text" 
          class="form-control" 
          id="address" 
          required
          :disabled="loading"
          placeholder="Carrer Major, 123"
        >
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="city" class="form-label">Ciutat *</label>
          <input 
            v-model="form.city" 
            type="text" 
            class="form-control" 
            id="city" 
            required
            :disabled="loading"
            placeholder="Barcelona"
          >
        </div>

        <div class="col-md-6 mb-3">
          <label for="postal_code" class="form-label">Codi Postal *</label>
          <input 
            v-model="form.postal_code" 
            type="text" 
            class="form-control" 
            id="postal_code" 
            required
            :disabled="loading"
            placeholder="08001"
          >
        </div>
      </div>

      <h3 class="section-title mt-4">Persona de Contacte</h3>

      <div class="mb-3">
        <label for="contact_name" class="form-label">Nom Complet *</label>
        <input 
          v-model="form.contact_name" 
          type="text" 
          class="form-control" 
          id="contact_name" 
          required
          :disabled="loading"
          placeholder="Maria Garcia López"
        >
      </div>

      <div class="mb-3">
        <label for="contact_email" class="form-label">Correu Electrònic *</label>
        <input 
          v-model="form.contact_email" 
          type="email" 
          class="form-control" 
          id="contact_email" 
          required
          :disabled="loading"
          placeholder="contacte@centre.edu"
        >
        <small class="text-muted">A aquest correu s'enviaran les credencials d'accés</small>
      </div>

      <div class="mb-3">
        <label for="contact_phone" class="form-label">Telèfon de Contacte *</label>
        <input 
          v-model="form.contact_phone" 
          type="tel" 
          class="form-control" 
          id="contact_phone" 
          required
          :disabled="loading"
          placeholder="93 123 45 67"
        >
      </div>

      <div class="mb-3">
        <label for="contact_position" class="form-label">Càrrec</label>
        <input 
          v-model="form.contact_position" 
          type="text" 
          class="form-control" 
          id="contact_position" 
          :disabled="loading"
          placeholder="Director/a, Cap d'Estudis, etc."
        >
      </div>

      <h3 class="section-title mt-4">Informació Addicional</h3>

      <div class="mb-3">
        <label for="student_count" class="form-label">Nombre aproximat d'alumnes ESO</label>
        <input 
          v-model="form.student_count" 
          type="number" 
          class="form-control" 
          id="student_count" 
          :disabled="loading"
          placeholder="250"
        >
      </div>

      <div class="mb-3">
        <label for="notes" class="form-label">Observacions o comentaris</label>
        <textarea 
          v-model="form.notes" 
          class="form-control" 
          id="notes" 
          rows="4"
          :disabled="loading"
          placeholder="Informació addicional que vulguis compartir..."
        ></textarea>
      </div>

      <div v-if="error" class="alert alert-danger">
        {{ error }}
      </div>

      <button type="submit" class="btn btn-primary w-100" :disabled="loading">
        {{ loading ? 'Enviant sol·licitud...' : 'Enviar Sol·licitud' }}
      </button>

      <div class="text-center mt-3">
        <router-link to="/login" class="btn btn-link text-muted">
          Tornar a l'inici de sessió
        </router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const form = ref({
  center_name: '',
  center_code: '',
  address: '',
  city: '',
  postal_code: '',
  contact_name: '',
  contact_email: '',
  contact_phone: '',
  contact_position: '',
  student_count: null,
  notes: ''
})

const loading = ref(false)
const error = ref(null)
const success = ref(false)

const handleSubmit = async () => {
  error.value = null
  loading.value = true

  try {
    const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000'
    
    const response = await axios.post(`${API_BASE}/api/center-requests`, form.value)
    
    if (response.data.success) {
      success.value = true
    } else {
      error.value = response.data.message || 'Error al enviar la sol·licitud'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al enviar la sol·licitud. Si us plau, intenta-ho més tard.'
    console.error('Error:', err)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.center-request-container {
  min-height: 100vh;
  background: #F8F9FA;
  padding: 0;
}

.request-header {
  background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
  position: relative;
  color: white;
  padding: 80px 20px;
  text-align: center;
  margin-bottom: 0;
  border-bottom: 4px solid #C5A059;
  box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.request-header::after {
  content: '';
  position: absolute;
  bottom: -12px;
  left: 50%;
  transform: translateX(-50%) rotate(45deg);
  width: 24px;
  height: 24px;
  background: #C5A059;
  border: 4px solid white;
}

.request-header h1 {
  color: #C5A059;
  font-family: 'Playfair Display', serif;
  font-weight: 700;
  font-size: 3.5rem;
  margin-bottom: 20px;
  letter-spacing: -1px;
  text-shadow: 0 4px 10px rgba(197, 160, 89, 0.2);
}

.subtitle {
  color: #E2E8F0;
  font-family: 'Playfair Display', serif;
  font-size: 1.25rem;
  font-weight: 400;
  margin: 0;
  letter-spacing: 1px;
  text-transform: uppercase;
  opacity: 0.9;
}

form {
  max-width: 800px;
  margin: 0 auto;
  padding: 60px 40px;
}

.section-title {
  color: #0F172A;
  font-family: 'Playfair Display', serif !important;
  font-size: 1.3rem;
  font-weight: 700;
  margin-bottom: 25px;
  margin-top: 40px;
  padding-bottom: 12px;
  border-bottom: 3px solid #C5A059;
}

.section-title:first-of-type {
  margin-top: 0;
}

.form-label {
  font-weight: 600;
  color: #0F172A;
  margin-bottom: 8px;
  font-size: 0.95rem;
  font-family: 'Playfair Display', serif !important;
}

.form-control {
  border: 2px solid #E9ECEF;
  border-radius: 8px;
  padding: 14px 16px;
  transition: all 0.3s ease;
  font-size: 1rem;
  font-family: 'Playfair Display', serif !important;
}

.form-control:focus {
  border-color: #C5A059;
  box-shadow: 0 0 0 4px rgba(197, 160, 89, 0.1);
  outline: none;
}

.form-control::placeholder {
  color: #ADB5BD;
  font-family: 'Playfair Display', serif !important;
  opacity: 0.6;
}

.text-muted {
  color: #6C757D;
  font-size: 0.875rem;
}

.btn-primary {
  background: linear-gradient(135deg, #C5A059 0%, #d4af37 100%);
  border: none;
  padding: 16px 32px;
  font-weight: 700;
  border-radius: 8px;
  transition: all 0.3s ease;
  font-size: 1.1rem;
  font-family: 'Playfair Display', serif !important;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  margin-top: 20px;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #d4af37 0%, #e8c04c 100%);
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(197, 160, 89, 0.3);
}

.btn-primary:disabled {
  background: #ADB5BD;
  cursor: not-allowed;
  transform: none;
}

.success-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
}

.success-card {
  background: white;
  border-radius: 16px;
  padding: 3rem 2.5rem;
  max-width: 600px;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.success-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #C5A059 0%, #d4af37 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 2rem;
  animation: scaleIn 0.6s ease-out 0.2s both;
}

@keyframes scaleIn {
  from {
    transform: scale(0);
  }
  to {
    transform: scale(1);
  }
}

.success-icon i {
  font-size: 2.5rem;
  color: white;
}

.success-card h2 {
  color: #0F172A;
  font-size: 2rem;
  font-weight: 800;
  margin-bottom: 1rem;
}

.success-message {
  color: #1f2937;
  font-size: 1.15rem;
  font-weight: 600;
  margin-bottom: 1rem;
  line-height: 1.6;
}

.success-submessage {
  color: #6b7280;
  font-size: 1rem;
  line-height: 1.7;
  margin-bottom: 2rem;
}

.btn-back-home {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  background: linear-gradient(135deg, #C5A059 0%, #d4af37 100%);
  color: white;
  padding: 1rem 2.5rem;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 700;
  font-size: 1.05rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(197, 160, 89, 0.3);
}

.btn-back-home:hover {
  background: linear-gradient(135deg, #d4af37 0%, #e8c04c 100%);
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(197, 160, 89, 0.4);
}

.btn-back-home i {
  font-size: 1.1rem;
}

.alert {
  border-radius: 12px;
  padding: 30px;
  margin: 40px auto;
  max-width: 600px;
  text-align: center;
}

.alert-success {
  background: #D1FAE5;
  border: 2px solid #10B981;
}

.alert-success h3 {
  color: #065F46;
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 15px;
}

.alert-success p {
  color: #065F46;
  line-height: 1.7;
  margin-bottom: 10px;
}

.alert-danger {
  background: #FEE2E2;
  border: 2px solid #EF4444;
  color: #991B1B;
  padding: 16px;
  margin-bottom: 25px;
  border-radius: 8px;
}

.w-100 {
  width: 100%;
}

.mt-3 {
  margin-top: 20px;
}

.text-center {
  text-align: center;
}

@media (max-width: 768px) {
  .request-header {
    padding: 40px 20px;
  }

  .request-header h1 {
    font-size: 2rem;
  }

  form {
    padding: 40px 20px;
  }
}
</style>
