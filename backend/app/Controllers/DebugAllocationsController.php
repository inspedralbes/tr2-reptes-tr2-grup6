<?php
namespace Controllers;

use Models\Allocation;

class DebugAllocationsController {
    public function check() {
        try {
            // Force center 7 check
            $centerId = 7;
            
            // Logic being tested:
            $allocations = Allocation::findByCenter($centerId);
            
            $data = [];
            foreach ($allocations as $allocation) {
                // Testing toArray mapping
                $data[] = $allocation->toArray();
            }
            
            echo json_encode([
                'success' => true,
                'count' => count($data),
                'allocations' => $data
            ]);
        } catch (\Exception $e) {
             echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
