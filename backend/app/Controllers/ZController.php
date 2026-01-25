<?php
namespace Controllers;

class ZController {
    public function run() {
        echo "A|";
        $user = \Models\User::findById(11);
        echo "USER:" . $user->center_id . "|";
        
        $allocs = \Models\Allocation::findByCenter(7);
        echo "ALLOC_COUNT:" . count($allocs) . "|";
        
        if (count($allocs) > 0) {
            echo "FIRST_SLOT:" . ($allocs[0]->slot_date ?? 'NULL') . "|";
        }
    }
}
