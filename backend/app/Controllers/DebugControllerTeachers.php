<?php
namespace Controllers;

use Services\Database;
use PDO;

class DebugControllerTeachers {
    
    public function check() {
        try {
            $centerId = 7;
            $db = Database::getInstance();
            $pdo = $db->getPDO();
            
            $output = ['msg' => 'Teachers Check'];
            
            // Teachers assigned to Center 7
            $stmt = $pdo->prepare("SELECT id, email, full_name, center_id FROM users WHERE center_id = ? AND role = 'teacher'");
            $stmt->execute([$centerId]);
            $output['teachers_center_7'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // All Teachers to see if they are orphans
            $stmt = $pdo->query("SELECT id, email, full_name, center_id FROM users WHERE role = 'teacher'");
            $output['all_teachers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($output);
        } catch (\Exception $e) { 
            echo json_encode(['error' => $e->getMessage()]); 
        }
    }
}
