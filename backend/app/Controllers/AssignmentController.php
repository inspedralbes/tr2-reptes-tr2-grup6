<?php
/**
 * Controlador d'Assignacions (Algoritme)
 * Path: /backend/app/Controllers/AssignmentController.php
 * 
 * Controlador per executar l'algoritme d'assignació de tallers
 */

namespace Controllers;

class AssignmentController {
    
    /**
     * Executar l'algoritme d'assignació per un període
     * POST /api/assignment/execute
     * 
     * Body: { "period_id": 1 }
     */
    public function execute() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validar entrada
            if (!isset($input['period_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Camp obligatori: period_id'
                ]);
                return;
            }
            
            // Verificar permís (només admin)
            $token = \Utils\JwtHandler::getTokenFromHeader();
            
            if ($token) {
                $jwtHandler = new \Utils\JwtHandler();
                $user_data = $jwtHandler->decode($token);
            }
            
            if (!$user_data || $user_data['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accés denegat. Es requereix rol admin.'
                ]);
                return;
            }
            
            // Executar algoritme
            $result = \Services\AllocationAlgorithm::executeAllocation($input['period_id']);
            
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error executant algoritme: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtenir analytics de l'assignació
     * GET /api/assignment/analytics/:period_id
     */
    public function analytics($period_id) {
        try {
            // Verificar permís (admin)
            $token = \Utils\JwtHandler::getTokenFromHeader();
            
            if ($token) {
                $jwtHandler = new \Utils\JwtHandler();
                $user_data = $jwtHandler->decode($token);
            }
            
            if (!$user_data || !in_array($user_data['role'], ['admin', 'director'])) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accés denegat'
                ]);
                return;
            }
            
            // Obtenir analytics
            $analytics = \Services\AllocationAlgorithm::getAnalytics($period_id);
            
            if (!$analytics) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'No hi ha dades per aquest període'
                ]);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $analytics
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Desfer assignació (reset per reexecutar)
     * POST /api/assignment/reset/:period_id
     */
    public function reset($period_id) {
        try {
            // Verificar permís (admin)
            $token = \Utils\JwtHandler::getTokenFromHeader();
            
            if ($token) {
                $jwtHandler = new \Utils\JwtHandler();
                $user_data = $jwtHandler->decode($token);
            }
            
            if (!$user_data || $user_data['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accés denegat'
                ]);
                return;
            }
            
            // Reset assignacions
            $db = \Services\Database::getInstance();
            
            // Obtenir assignacions aquest període
            $sql = "SELECT a.id FROM allocations a 
                    JOIN requests r ON a.request_id = r.id 
                    WHERE r.period_id = ? AND a.status = 'active'";
            
            $allocations = $db->query($sql, [$period_id]);
            
            $deleted = 0;
            foreach ($allocations as $alloc) {
                $allocation = \Models\Allocation::findById($alloc['id']);
                if ($allocation && $allocation->delete()) {
                    $deleted++;
                    
                    // Retornar sol·licitud a estat pending
                    $request = \Models\Request::findById($allocation->request_id);
                    if ($request) {
                        $request->setStatus('pending');
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Assignacions reset',
                'deleted' => $deleted
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}