<?php
namespace Controllers;

use Services\Database;
use PDO;

class DebugControllerCenter2 {
    
    public function check() {
        try {
            $centerId = 7;
            $db = Database::getInstance();
            $pdo = $db->getPDO();
            
            $output = ['msg' => 'V2'];
            
            // Count Real (Simulating CenterController)
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM requests WHERE center_id = ?");
            $stmt->execute([$centerId]);
            $output['simulated_stats_count'] = $stmt->fetchColumn();

            // Recent Bookings (Requests)
            $sql = 'SELECT r.id, w.name as workshop_name, r.created_at, r.status, r.updated_at
                    FROM requests r
                    JOIN workshops w ON w.id = r.workshop_id
                    WHERE r.center_id = ?
                    ORDER BY r.created_at DESC
                    LIMIT 5';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$centerId]);
            $output['recent'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($output);
        } catch (\Exception $e) { 
            echo json_encode(['error' => $e->getMessage()]); 
        }
    }
}
