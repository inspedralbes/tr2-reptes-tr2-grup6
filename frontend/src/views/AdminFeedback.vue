<template>
  <div class="admin-feedback-view">
    <div class="header-actions">
      <h1><i class="fas fa-comments"></i> Feedback dels Tallers</h1>
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input v-model="searchQuery" type="text" placeholder="Cercar per taller o centre..." />
      </div>
    </div>

    <!-- Stats Summary -->
    <div class="stats-cards">
      <div class="stat-card">
        <div class="stat-value">{{ feedbacks.length }}</div>
        <div class="stat-label">Total Valoracions</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ averageRating }}</div>
        <div class="stat-label">Mitjana Global</div>
      </div>
    </div>

    <!-- Feedback Table -->
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Data</th>
            <th>Taller</th>
            <th>Centre</th>
            <th>Valoració</th>
            <th>Recomana</th>
            <th>Accions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="feedback in filteredFeedbacks" :key="feedback.id">
            <td>{{ formatDate(feedback.created_at) }}</td>
            <td class="font-medium">{{ feedback.workshop_name || 'Desconegut' }}</td>
            <td class="text-muted">{{ feedback.center_name || 'Desconegut' }}</td>
            <td>
              <div class="star-rating">
                <span v-for="n in 5" :key="n" :class="{ filled: n <= feedback.overall_rating }">★</span>
              </div>
            </td>
            <td>
              <span class="badge" :class="feedback.would_recommend ? 'badge-success' : 'badge-danger'">
                {{ feedback.would_recommend ? 'Sí' : 'No' }}
              </span>
            </td>
            <td>
              <button class="btn-icon" @click="viewDetails(feedback)" title="Veure Detalls">
                <i class="fas fa-eye"></i>
              </button>
            </td>
          </tr>
          <tr v-if="filteredFeedbacks.length === 0">
            <td colspan="6" class="empty-state">
              No s'han trobat valoracions
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Details Modal -->
    <div v-if="selectedFeedback" class="modal-overlay" @click.self="selectedFeedback = null">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Detalls de la Valoració</h2>
          <button class="btn-close" @click="selectedFeedback = null">×</button>
        </div>
        <div class="modal-body">
          <div class="details-grid">
            <div class="detail-item">
              <label>Punts Forts</label>
              <p>{{ selectedFeedback.positive_aspects || '-' }}</p>
            </div>
            <div class="detail-item">
              <label>A millorar</label>
              <p>{{ selectedFeedback.negative_aspects || '-' }}</p>
            </div>
            <div class="detail-item">
              <label>Suggeriments</label>
              <p>{{ selectedFeedback.suggestions || '-' }}</p>
            </div>
            <div class="detail-item">
              <label>Comentaris Generals</label>
              <p>{{ selectedFeedback.general_comments || '-' }}</p>
            </div>
          </div>
          <div class="ratings-breakdown">
            <h3>Desglossament</h3>
            <ul>
              <li>Qualitat Contingut: <strong>{{ selectedFeedback.content_quality }}/5</strong></li>
              <li>Docent: <strong>{{ selectedFeedback.teacher_performance }}/5</strong></li>
              <li>Organització: <strong>{{ selectedFeedback.organization }}/5</strong></li>
              <li>Relevància: <strong>{{ selectedFeedback.relevance }}/5</strong></li>
            </ul>
          </div>
          
          <div class="attendance-info" v-if="selectedFeedback.expected_attendees || selectedFeedback.actual_attendees">
            <h3>Assistència</h3>
            <div class="attendance-grid">
              <div class="stat-bubble">
                <span class="bubble-label">Esperats</span>
                <span class="bubble-value">{{ selectedFeedback.expected_attendees || '-' }}</span>
              </div>
              <div class="stat-bubble">
                <span class="bubble-label">Reals</span>
                <span class="bubble-value">{{ selectedFeedback.actual_attendees || '-' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const feedbacks = ref([]);
const searchQuery = ref('');
const selectedFeedback = ref(null);

const fetchFeedbacks = async () => {
  try {
    const client = authStore.getApiClient();
    const response = await client.get('/api/admin/feedback');
    if (response.data.success) {
      feedbacks.value = response.data.data;
    }
  } catch (error) {
    console.error('Error fetching feedbacks:', error);
  }
};

const filteredFeedbacks = computed(() => {
  if (!searchQuery.value) return feedbacks.value;
  const q = searchQuery.value.toLowerCase();
  return feedbacks.value.filter(f => 
    (f.workshop_name?.toLowerCase().includes(q)) || 
    (f.center_name?.toLowerCase().includes(q))
  );
});

const averageRating = computed(() => {
  if (feedbacks.value.length === 0) return 0;
  const sum = feedbacks.value.reduce((acc, curr) => acc + parseInt(curr.overall_rating), 0);
  return (sum / feedbacks.value.length).toFixed(1);
});

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('ca-ES', {
    day: '2-digit', month: '2-digit', year: 'numeric'
  });
};

const viewDetails = (feedback) => {
  selectedFeedback.value = feedback;
};

onMounted(() => {
  fetchFeedbacks();
});
</script>

<style scoped>
.admin-feedback-view {
  padding: 20px;
}

.header-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.header-actions h1 {
  font-size: 1.8rem;
  color: #0F172A;
  margin: 0;
}

.search-box {
  position: relative;
}

.search-box input {
  padding: 10px 10px 10px 40px;
  border: 1px solid #E2E8F0;
  border-radius: 8px;
  width: 300px;
  font-size: 0.95rem;
}

.search-box i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #94A3B8;
}

.stats-cards {
  display: flex;
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.05);
  flex: 1;
  text-align: center;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 700;
  color: #0F172A;
}

.stat-label {
  color: #64748B;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.05);
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th, .data-table td {
  padding: 16px;
  text-align: left;
  border-bottom: 1px solid #F1F5F9;
}

.data-table th {
  background: #F8FAFC;
  font-weight: 600;
  color: #475569;
}

.star-rating {
  color: #E2E8F0;
}

.star-rating .filled {
  color: #D4AF37;
}

.badge {
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
}

.badge-success { background: #DCFCE7; color: #166534; }
.badge-danger { background: #FEE2E2; color: #991B1B; }

.btn-icon {
  background: none;
  border: none;
  color: #64748B;
  cursor: pointer;
  font-size: 1.1rem;
  transition: color 0.2s;
}

.btn-icon:hover { color: #0F172A; }

.empty-state {
  text-align: center;
  padding: 40px;
  color: #94A3B8;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px rgba(0,0,0,0.1);
}

.modal-header {
  padding: 20px;
  border-bottom: 1px solid #E2E8F0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 { margin: 0; font-size: 1.5rem; }

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #64748B;
}

.modal-body { padding: 24px; }

.detail-item { margin-bottom: 20px; }
.detail-item label {
  display: block;
  font-weight: 600;
  color: #0F172A;
  margin-bottom: 6px;
}
.detail-item p {
  margin: 0;
  color: #475569;
  background: #F8FAFC;
  padding: 12px;
  border-radius: 8px;
}

.ratings-breakdown {
  margin-top: 30px;
  border-top: 1px solid #E2E8F0;
  padding-top: 20px;
}

.ratings-breakdown ul {
  list-style: none;
  padding: 0;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.attendance-info {
  margin-top: 24px;
  border-top: 1px solid #E2E8F0;
  padding-top: 20px;
}

.attendance-grid {
  display: flex;
  gap: 20px;
  margin-top: 12px;
}

.stat-bubble {
  background: #F1F5F9;
  border-radius: 12px;
  padding: 12px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 100px;
}

.bubble-label {
  font-size: 0.8rem;
  color: #64748B;
  font-weight: 600;
  text-transform: uppercase;
}

.bubble-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0F172A;
}
</style>
