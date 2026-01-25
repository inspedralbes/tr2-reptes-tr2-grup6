<?php
namespace Controllers;

require_once __DIR__ . '/../../config/Database.php';

use Database;
use Services\AllocationAlgorithm;
use PDO;

class DebugController {
    
    public function checkAllocations() {
        try {
            $db = \Database::getInstance();
            $pdo = $db->getPDO();

            $output = [];
            // Check Request 4
            $stmt = $pdo->query("SELECT * FROM requests WHERE id = 4");
            $output['request_4'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Execute (dry run logic or just debug)
            $result = AllocationAlgorithm::executeAllocation(1);
            $output['algo_result_period_1'] = $result;

            echo json_encode(['success' => true, 'debug_data' => $output]);
        } catch (\Exception $e) { 
            echo json_encode(['success' => false, 'error' => $e->getMessage()]); 
        }
    }

}
