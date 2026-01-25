<?php
namespace Controllers;

use Services\Database;

class AdminController {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getPDO();
    }
    
    /**
     * Obtenir estadístiques globals
     * GET /api/admin/stats
     */
    public function stats() {
        try {
            // Total Tallers
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM workshops");
            $totalWorkshops = $stmt->fetch()['count'];
            
            // Total Sol·licituds
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM requests");
            $totalRequests = $stmt->fetch()['count'];
            
            // Total Assignacions
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM allocations");
            $totalAllocations = $stmt->fetch()['count'];
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'totalWorkshops' => $totalWorkshops,
                    'totalRequests' => $totalRequests,
                    'totalAllocations' => $totalAllocations,
                    'pendingAllocations' => 0 // TODO: Calcular pendents real
                ]
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtenir estadístiques: ' . $e->getMessage()
            ]);
        }
    }
}
