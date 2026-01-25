<?php
/**
 * Controlador de Check de Salut
 * Path: /backend/app/Controllers/HealthController.php
 */

namespace Controllers;

class HealthController {
    
    /**
     * Verificar que l'aplicació està funcionant
     * GET /api/health
     */
    public function check() {
        echo json_encode([
            'success' => true,
            'message' => 'KAIROS API - Tot funcionant correctament ✓',
            'version' => '1.0.0',
            'timestamp' => date('c')
        ]);
    }
}
