/**
 * FASES TEMPORALS CONTROLLER
 * Sistema de control de fases de KAIROS
 * 
 * FASES DEFINIDES:
 * - Fase 1: Presentació (8 agost - 30 setembre)
 * - Fase 2: Sol·licituds (1 octubre - 23 desembre)
 * - Fase 3: Asignació (24 desembre - 31 gener)
 * - Fase 4: Agendament Docent (1 febrer - 31 maig)
 * - Fase 5: Realització (1 juny - 30 juny)
 */

class PhasesController {
  constructor() {
    // Definir les fases amb dates
    this.phases = {
      1: {
        id: 1,
        name: 'Presentació',
        description: 'Centres educatius explorer tallers disponibles (mode lectura)',
        startDate: new Date('2025-08-08'),
        endDate: new Date('2025-09-30'),
        status: 'pending', // upcoming, active, completed
        features: {
          marketplace_view: true,      // Pots veure tallers
          add_to_cart: true,           // Pots afegir a carret
          submit_requests: false,      // NO pots enviar sol·licituds
          view_allocations: false,     // NO pots veure assignacions
          schedule_teachers: false,    // NO pots agendament
          feedback: false              // NO pots donar feedback
        }
      },
      2: {
        id: 2,
        name: 'Sol·licituds',
        description: 'Centres educatius envien sol·licituds de tallers',
        startDate: new Date('2025-10-01'),
        endDate: new Date('2025-12-23'),
        status: 'pending',
        features: {
          marketplace_view: true,      // Pots veure tallers
          add_to_cart: true,           // Pots afegir a carret
          submit_requests: true,       // Pots enviar sol·licituds ✅
          view_allocations: false,     // NO pots veure assignacions
          schedule_teachers: false,    // NO pots agendament
          feedback: false              // NO pots donar feedback
        }
      },
      3: {
        id: 3,
        name: 'Asignació',
        description: 'Algoritme assigna tallers automàticament',
        startDate: new Date('2025-12-24'),
        endDate: new Date('2026-01-31'),
        status: 'pending',
        features: {
          marketplace_view: true,      // Pots veure tallers
          add_to_cart: false,          // NO pots afegir a carret
          submit_requests: false,      // NO pots enviar sol·licituds (ja tancada)
          view_allocations: true,      // Pots veure assignacions ✅
          schedule_teachers: false,    // NO pots agendament
          feedback: false              // NO pots donar feedback
        }
      },
      4: {
        id: 4,
        name: 'Agendament Docent',
        description: 'Docents ageneden dates/horaris específics per cada taller',
        startDate: new Date('2026-02-01'),
        endDate: new Date('2026-05-31'),
        status: 'pending',
        features: {
          marketplace_view: true,
          add_to_cart: false,
          submit_requests: false,
          view_allocations: true,
          schedule_teachers: true,     // Docents ageneden ✅
          feedback: false
        }
      },
      5: {
        id: 5,
        name: 'Realització',
        description: 'Tallers es realitzen. Recollida de feedback i QR',
        startDate: new Date('2026-06-01'),
        endDate: new Date('2026-06-30'),
        status: 'pending',
        features: {
          marketplace_view: true,
          add_to_cart: false,
          submit_requests: false,
          view_allocations: true,
          schedule_teachers: true,
          feedback: true               // Docents enregistren feedback ✅
        }
      }
    };

    // Cargar estat actual de phases des de sessió
    this.currentPhase = this.calculateCurrentPhase();
    this.updatePhaseStatus();
  }

  /**
   * Calcular quina és la fase actual basada en la data
   */
  calculateCurrentPhase() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    for (const [phaseId, phase] of Object.entries(this.phases)) {
      const startDate = new Date(phase.startDate);
      const endDate = new Date(phase.endDate);
      startDate.setHours(0, 0, 0, 0);
      endDate.setHours(23, 59, 59, 999);

      if (today >= startDate && today <= endDate) {
        return parseInt(phaseId);
      }
    }

    // Si no hi ha fase activa, retornar la més recent passada o la següent
    for (const [phaseId, phase] of Object.entries(this.phases)) {
      if (new Date(phase.endDate) < today) {
        return parseInt(phaseId); // Fase completada
      }
    }

    return 1; // Per defecte, fase 1
  }

  /**
   * Actualitzar status de totes les fases (upcoming, active, completed)
   */
  updatePhaseStatus() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    Object.values(this.phases).forEach(phase => {
      const startDate = new Date(phase.startDate);
      const endDate = new Date(phase.endDate);
      startDate.setHours(0, 0, 0, 0);
      endDate.setHours(23, 59, 59, 999);

      if (today < startDate) {
        phase.status = 'upcoming';
      } else if (today > endDate) {
        phase.status = 'completed';
      } else {
        phase.status = 'active';
      }
    });
  }

  /**
   * Obtenir informació de la fase actual
   */
  getCurrentPhase() {
    return this.phases[this.currentPhase];
  }

  /**
   * Obtenir totes les fases amb status actualitzat
   */
  getAllPhases() {
    this.updatePhaseStatus();
    return Object.values(this.phases);
  }

  /**
   * Comprovar si una funcionalitat està disponible a la fase actual
   */
  isFeatureAvailable(featureName) {
    const currentPhase = this.getCurrentPhase();
    if (!currentPhase) return false;

    return currentPhase.features[featureName] === true;
  }

  /**
   * Obtenir totes les funcionalitats disponibles en la fase actual
   */
  getAvailableFeatures() {
    const currentPhase = this.getCurrentPhase();
    if (!currentPhase) return {};

    const available = {};
    Object.entries(currentPhase.features).forEach(([feature, isAvailable]) => {
      if (isAvailable) {
        available[feature] = true;
      }
    });

    return available;
  }

  /**
   * Obtenir el missatge de fase actual per mostrar a usuaris
   */
  getPhaseMessage() {
    const currentPhase = this.getCurrentPhase();
    if (!currentPhase) {
      return {
        phase: 'unknown',
        message: 'Fase desconeguda',
        subMessage: ''
      };
    }

    const endDate = new Date(currentPhase.endDate);
    const today = new Date();
    const daysLeft = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));

    return {
      phase: currentPhase.id,
      phaseName: currentPhase.name,
      message: `${currentPhase.name} (Fase ${currentPhase.id})`,
      subMessage: daysLeft > 0 ? `Termina en ${daysLeft} dies` : 'Fase completada',
      status: currentPhase.status,
      daysLeft: daysLeft,
      description: currentPhase.description
    };
  }

  /**
   * Obtenir informació d'una fase específica
   */
  getPhase(phaseId) {
    return this.phases[phaseId] || null;
  }

  /**
   * Validar si es pot accedir a una funcionalitat
   */
  canAccess(featureName, userRole = 'center') {
    // Alguns features són específics de rol
    if (featureName === 'schedule_teachers' && userRole !== 'teacher') {
      return false;
    }

    return this.isFeatureAvailable(featureName);
  }

  /**
   * Obtenir stats de les fases
   */
  getPhaseStats() {
    const currentPhase = this.getCurrentPhase();
    const allPhases = this.getAllPhases();

    return {
      currentPhaseId: this.currentPhase,
      currentPhaseName: currentPhase?.name || 'Unknown',
      totalPhases: Object.keys(this.phases).length,
      completedPhases: allPhases.filter(p => p.status === 'completed').length,
      activePhases: allPhases.filter(p => p.status === 'active').length,
      upcomingPhases: allPhases.filter(p => p.status === 'upcoming').length
    };
  }

  /**
   * Simular canvi de fase (per testing)
   */
  setCurrentDate(date) {
    // NOMÉS PER DESENVOLUPAMENT
    if (process.env.NODE_ENV === 'development') {
      // Implement custom date for testing
      this.currentPhase = this.calculateCurrentPhase();
      this.updatePhaseStatus();
      return this.getCurrentPhase();
    }
    return null;
  }

  /**
   * Obtenir roadmap de totes les fases
   */
  getRoadmap() {
    const phases = this.getAllPhases();
    return phases.map(phase => ({
      id: phase.id,
      name: phase.name,
      description: phase.description,
      startDate: phase.startDate,
      endDate: phase.endDate,
      status: phase.status,
      daysRemaining: this.getDaysRemaining(phase.endDate),
      featureCount: Object.values(phase.features).filter(f => f).length
    }));
  }

  /**
   * Helper: Calcular dies restants
   */
  getDaysRemaining(endDate) {
    const today = new Date();
    const end = new Date(endDate);
    const days = Math.ceil((end - today) / (1000 * 60 * 60 * 24));
    return days > 0 ? days : 0;
  }

  /**
   * Actualitzar dates d'una fase (Admin només)
   */
  updatePhaseDates(phaseId, startDate, endDate) {
    const phase = this.phases[phaseId];
    if (!phase) {
      return {
        success: false,
        message: `Fase ${phaseId} no trobada`
      };
    }

    // Validar dates
    const start = new Date(startDate);
    const end = new Date(endDate);

    if (start >= end) {
      return {
        success: false,
        message: 'La data d\'inici ha de ser anterior a la data de fi'
      };
    }

    // Validar que no hi hagi solapaments amb altres fases
    for (const [id, otherPhase] of Object.entries(this.phases)) {
      if (parseInt(id) === phaseId) continue;

      const otherStart = new Date(otherPhase.startDate);
      const otherEnd = new Date(otherPhase.endDate);

      // Check overlap
      if (
        (start >= otherStart && start <= otherEnd) ||
        (end >= otherStart && end <= otherEnd) ||
        (start <= otherStart && end >= otherEnd)
      ) {
        return {
          success: false,
          message: `Solapament amb ${otherPhase.name} (${otherStart.toLocaleDateString()} - ${otherEnd.toLocaleDateString()})`,
          conflictPhase: otherPhase.id
        };
      }
    }

    // Actualitzar dates
    phase.startDate = start;
    phase.endDate = end;

    // Recalcular fase actual i status
    this.currentPhase = this.calculateCurrentPhase();
    this.updatePhaseStatus();

    return {
      success: true,
      message: `Fase ${phase.name} actualitzada correctament`,
      data: phase
    };
  }

  /**
   * Obtenir configuració de totes les dates per edició
   */
  getPhaseDatesConfig() {
    return Object.values(this.phases).map(phase => ({
      id: phase.id,
      name: phase.name,
      startDate: phase.startDate.toISOString().split('T')[0],
      endDate: phase.endDate.toISOString().split('T')[0],
      status: phase.status
    }));
  }
}

// Exportar com a singleton (ES modules)
export default new PhasesController();
