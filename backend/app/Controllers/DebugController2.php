<?php
namespace Controllers;

use Services\Database;
use Services\AllocationAlgorithm;
use PDO;

class DebugController2 {
    
    public function checkAllocations() {
        try {
            $db = \Services\Database::getInstance();
            $pdo = $db->getPDO();

            $output = [];
            // Check Request 4
            $stmt = $pdo->query("SELECT * FROM requests WHERE id = 4");
            $output['request_4'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Execute algorithm
            // Ensure we use period from request if possible, or 1
            $period = 1;
            if ($output['request_4'] && isset($output['request_4']['period_id'])) {
                $period = $output['request_4']['period_id'];
            }
            $output['period_target'] = $period;

            $result = AllocationAlgorithm::executeAllocation($period);
            $output['algo_result'] = $result;

            echo json_encode(['success' => true, 'debug_data_v2' => $output]);
        } catch (\Exception $e) { 
            echo json_encode(['success' => false, 'error' => $e->getMessage()]); 
        }
    }
}
