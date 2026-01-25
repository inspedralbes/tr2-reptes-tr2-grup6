<template>
  <div class="home-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="hero-overlay"></div>
      
      <!-- Ambient Background Animation -->
      <div class="hero-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
      </div>

      <div class="container hero-content">
        <h1 class="hero-title">
          <span 
            v-for="(letter, index) in ['K','A','I','R','O','S']" 
            :key="index" 
            class="shine-letter" 
            :style="{ animationDelay: index * 0.2 + 's' }"
          >{{ letter }}</span>
        </h1>
        <p class="hero-subtitle">Programa ENGINY - Generalitat de Catalunya</p>
        <p class="hero-description">
          Plataforma centralitzada per a la gestió eficient de tallers vocacionals,
          connectant centres educatius amb oportunitats formatives d'excel·lència.
        </p>
        <div class="hero-actions">
          <router-link to="/login" class="btn-hero-primary">
            Accedir a la plataforma
          </router-link>
          <router-link to="/solicitud-centro" class="btn-hero-secondary">
            Sol·licitar accés
          </router-link>
          <router-link to="/gallery" class="btn-hero-gallery">
            <i class="fas fa-images"></i> Galeria Pública
          </router-link>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
      <div class="container">
        <h2 class="section-title">Com funciona KAIROS</h2>
        <p class="section-subtitle">
          Un sistema integral que optimitza la gestió de recursos formatius
        </p>

        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-bullhorn"></i>
            </div>
            <h3>Fase 1: Publicació</h3>
            <p>
              Els tallers es publiquen al catàleg amb informació detallada
              sobre continguts, requisits i disponibilitat.
            </p>
          </div>

          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-edit"></i>
            </div>
            <h3>Fase 2: Sol·licitud</h3>
            <p>
              Els centres educatius seleccionen i sol·liciten els tallers
              que millor s'adapten a les seves necessitats.
            </p>
          </div>

          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-cogs"></i>
            </div>
            <h3>Fase 3: Assignació Intel·ligent</h3>
            <p>
              L'algoritme KAIROS assigna els tallers de forma equitativa,
              optimitzant recursos i prioritzant l'accés universal.
            </p>
          </div>

          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <h3>Fase 4: Planificació</h3>
            <p>
              Els docents coordinen les dates específiques i gestionen
              la logística dels tallers assignats.
            </p>
          </div>

          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-star"></i>
            </div>
            <h3>Fase 5: Realització</h3>
            <p>
              Execució dels tallers amb seguiment en temps real i
              eines de gestió d'assistència i avaluació.
            </p>
          </div>

          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-chart-line"></i>
            </div>
            <h3>Avaluació i Feedback</h3>
            <p>
              Recopilació de dades i feedback per millorar contínuament
              la qualitat del programa ENGINY.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits-section">
      <div class="container">
        <h2 class="section-title">Avantatges de KAIROS</h2>
        
        <div class="benefits-grid">
          <div class="benefit-item">
            <div class="benefit-check"></div>
            <div class="benefit-content">
              <h4>Gestió Centralitzada</h4>
              <p>Una única plataforma per gestionar tot el programa ENGINY</p>
            </div>
          </div>

          <div class="benefit-item">
            <div class="benefit-check"></div>
            <div class="benefit-content">
              <h4>Accés Equitatiu</h4>
              <p>Algoritme que garanteix igualtat d'oportunitats per a tots els centres</p>
            </div>
          </div>

          <div class="benefit-item">
            <div class="benefit-check"></div>
            <div class="benefit-content">
              <h4>Transparència Total</h4>
              <p>Seguiment en temps real de sol·licituds, assignacions i resultats</p>
            </div>
          </div>

          <div class="benefit-item">
            <div class="benefit-check"></div>
            <div class="benefit-content">
              <h4>Optimització de Recursos</h4>
              <p>Màxima eficiència en l'assignació i gestió dels tallers</p>
            </div>
          </div>

          <div class="benefit-item">
            <div class="benefit-check"></div>
            <div class="benefit-content">
              <h4>Escalabilitat i Flexibilitat</h4>
              <p>Sistema adaptat per créixer amb les necessitats del programa ENGINY</p>
            </div>
          </div>

          <div class="benefit-item">
            <div class="benefit-check"></div>
            <div class="benefit-content">
              <h4>Suport i Formació</h4>
              <p>Acompanyament integral per als centres i docents participants</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="home-footer">
      <div class="container">
        <p>© 2026 KAIROS - Programa ENGINY | Generalitat de Catalunya</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { onMounted } from "vue"
import { useRouter } from "vue-router"
import { useAuthStore } from "@/stores/auth"

const router = useRouter()
const authStore = useAuthStore()

// Redirigir a los usuarios autenticados a su dashboard correspondiente
onMounted(() => {
  if (authStore.isAuthenticated && authStore.user) {
    const user = authStore.user
    if (user.role === "center_coord") {
      router.push("/center-dashboard")
    } else if (user.role === "admin") {
      router.push("/admin")
    } else if (user.role === "teacher") {
      router.push("/teacher/schedule")
    }
  }
})
</script>

<style scoped>
.home-page {
  width: 100%;
  margin: 0;
  padding: 0;
  background: #FFFFFF;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* HERO SECTION */
.hero-section {
  background: radial-gradient(circle at 50% 50%, #1a2940 0%, #0F172A 100%);
  color: white;
  padding: 120px 20px;
  position: relative;
  overflow: hidden;
}

.hero-overlay {
  /* Clean overlay */
  position: absolute; inset: 0;
  background: rgba(15, 23, 42, 0.4); 
  z-index: 0;
}

.hero-blobs {
  position: absolute; inset: 0; overflow: hidden; pointer-events: none; z-index: 0;
}

.blob {
  position: absolute; border-radius: 50%; filter: blur(90px); opacity: 0.5;
  will-change: transform;
}

.blob-1 {
  width: 600px; height: 600px; background: #C5A059;
  top: -20%; left: -10%;
  animation: floatOrb1 25s infinite alternate ease-in-out;
}

.blob-2 {
  width: 700px; height: 700px; background: #3b82f6;
  bottom: -20%; right: -10%;
  animation: floatOrb2 30s infinite alternate ease-in-out;
}

@keyframes floatOrb1 {
  0% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(100px, 50px) scale(1.1); }
  100% { transform: translate(200px, -50px) scale(0.9); }
}

@keyframes floatOrb2 {
  0% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(-100px, -50px) scale(1.1); }
  100% { transform: translate(-200px, 50px) scale(0.95); }
}

.hero-content {
  max-width: 1200px;
  margin: 0 auto;
  text-align: center;
  position: relative;
  z-index: 1;
}

.hero-title {
  font-size: 7rem; /* Increased from 5rem */
  font-weight: 800;
  color: #C5A059;
  margin-bottom: 20px;
  letter-spacing: 0.1em;
  text-shadow: 0 4px 20px rgba(197, 160, 89, 0.3);
  display: flex;
  justify-content: center;
  gap: 0.2rem;
}

.shine-letter {
  display: inline-block;
  animation: shine 3s ease-in-out infinite; 
}

@keyframes shine {
  0%, 100% {
    color: #C5A059;
    text-shadow: 0 4px 20px rgba(197, 160, 89, 0.3);
  }
  50% {
    color: #ffdb70; /* Brighter Gold */
    text-shadow: 0 0 25px rgba(255, 219, 112, 0.6), 0 0 10px rgba(255, 219, 112, 0.4);
    /* Removed transform: scale */
  }
}

.hero-subtitle {
  font-size: 1.4rem;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 25px;
  font-weight: 600;
}

.hero-description {
  font-size: 1.15rem;
  color: rgba(255, 255, 255, 0.85);
  max-width: 800px;
  margin: 0 auto 50px;
  line-height: 1.7;
}

.hero-actions {
  display: flex;
  gap: 20px;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-hero-primary,
.btn-hero-secondary {
  padding: 16px 40px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1.05rem;
  text-decoration: none;
  transition: all 0.3s ease;
  display: inline-block;
}

.btn-hero-primary {
  background: linear-gradient(135deg, #C5A059 0%, #d4af37 100%);
  color: white;
}

.btn-hero-primary:hover {
  background: linear-gradient(135deg, #d4af37 0%, #e8c04c 100%);
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(197, 160, 89, 0.5);
}

.btn-hero-secondary {
  background: transparent;
  color: white;
  border: 2px solid white;
}

.btn-hero-secondary:hover {
  background: white;
  color: #0F172A;
  transform: translateY(-3px);
}

.btn-hero-gallery {
  padding: 16px 40px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1.05rem;
  text-decoration: none;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.1);
  color: #C5A059;
  border: 1px solid #C5A059;
  backdrop-filter: blur(5px);
}

.btn-hero-gallery:hover {
  background: #C5A059;
  color: white;
  transform: translateY(-3px);
  box-shadow: 0 4px 15px rgba(197, 160, 89, 0.3);
}

/* FEATURES SECTION */
.features-section {
  padding: 90px 20px;
  background: #F8FAFC;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.section-title {
  font-size: 2.5rem;
  color: #1a2332;
  text-align: center;
  margin-bottom: 15px;
  font-weight: 700;
}

.section-subtitle {
  text-align: center;
  color: #6C757D;
  font-size: 1.15rem;
  margin-bottom: 60px;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 30px;
}

.feature-card {
  background: white;
  padding: 35px;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  border: 1px solid #E9ECEF;
}

.feature-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
  border-color: #C5A059;
}

.feature-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #C5A059 0%, #d4af37 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  margin-bottom: 20px;
}

.feature-card h3 {
  color: #0F172A;
  font-size: 1.4rem;
  margin-bottom: 15px;
  font-weight: 700;
}

.feature-card p {
  color: #6C757D;
  line-height: 1.7;
  margin: 0;
}

/* BENEFITS SECTION */
.benefits-section {
  padding: 90px 20px 90px 20px;
  background: white;
  margin-bottom: -1px;
}

.benefits-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 35px;
  max-width: 1000px;
  margin: 0 auto;
}

.benefit-item {
  display: flex;
  gap: 20px;
  align-items: flex-start;
}

.benefit-check {
  width: 24px;
  height: 24px;
  background: #10B981;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
  font-weight: bold;
  position: relative;
}

.benefit-check::after {
  content: "✓";
  font-size: 14px;
}

.benefit-content h4 {
  color: #1a2332;
  font-size: 1.2rem;
  margin-bottom: 8px;
  font-weight: 600;
}

.benefit-content p {
  color: #6C757D;
  line-height: 1.6;
  margin: 0;
}

/* FOOTER */
.home-footer {
  background: #1a2332;
  color: rgba(255, 255, 255, 0.7);
  padding: 30px 20px;
  text-align: center;
  margin: -1px 0 0 0;
  width: 100%;
  border-top: none;
  position: relative;
  z-index: 10;
}

.home-footer p {
  margin: 0;
  font-size: 0.95rem;
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .hero-title {
    font-size: 3rem;
  }

  .hero-subtitle {
    font-size: 1.1rem;
  }

  .hero-description {
    font-size: 1rem;
  }

  .section-title {
    font-size: 2rem;
  }

  .features-grid {
    grid-template-columns: 1fr;
  }

  .benefits-grid {
    grid-template-columns: 1fr;
  }
}
</style>



