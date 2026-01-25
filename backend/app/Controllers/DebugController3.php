<?php
namespace Controllers;

use Services\Database;
use Services\AllocationAlgorithmV2;
use PDO;

class DebugController3 {
    
    public function checkAllocations() {
        try {
            // Execute algorithm V2
            // Period is ignored in V2
            $result = AllocationAlgorithmV2::executeAllocation(1);
            echo json_encode(['success' => true, 'execution_result' => $result]);
        } catch (\Exception $e) { 
            echo json_encode(['success' => false, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]); 
        }
    }
}
