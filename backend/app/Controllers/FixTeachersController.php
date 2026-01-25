<?php
namespace Controllers;

use Services\Database;

class FixTeachersController {
    
    public function fix() {
        try {
            $db = Database::getInstance();
            $pdo = $db->getPDO();
            
            // Fix teachers for center 7 (sdasd)
            // IDs identified: 11 (a), 12 (asda)
            $count = $pdo->exec("UPDATE users SET center_id = 7 WHERE id IN (11, 12)");
            
            echo json_encode(['success' => true, 'updated' => $count]);
            
            // Force Opcache reset just in case
            if (function_exists('opcache_reset')) opcache_reset();
            
        } catch (\Exception $e) { 
            echo json_encode(['error' => $e->getMessage()]); 
        }
    }
}
