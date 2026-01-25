<?php
namespace Controllers;

require_once __DIR__ . '/../app/Services/Database.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Allocation.php';
require_once __DIR__ . '/../app/Models/Workshop.php';

use Models\Allocation;
use Models\User;

class DebugAllocations {
    public function check() {
        // Mock User Teacher ID ??? (User 'a' id ??)
        // Let's assume we pass center_id=7 via GET params logic we implemented
        
        $centerId = 7;
        
        // Use logic from AllocationController
        $allocations = Allocation::findByCenter($centerId);
        
        $data = [];
        foreach ($allocations as $allocation) {
            $data[] = $allocation->toArray();
        }
        
        echo json_encode([
            'count' => count($data),
            'first_item' => !empty($data) ? $data[0] : null,
            'has_slot_date' => !empty($data) && isset($data[0]['slot_date'])
        ]);
    }
}

(new DebugAllocations())->check();
