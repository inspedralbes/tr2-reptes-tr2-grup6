<?php
namespace Controllers;

use Services\Database;
use PDO;

class DebugCenterController {
    
    public function checkStats() {
        try {
            $db = Database::getInstance();
            $pdo = $db->getPDO();

            $email = 'bryannrg10@gmail.com';
            $output = ['user_email' => $email];

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $output['error'] = 'User not found';
                echo json_encode($output);
                return;
            }

            $output['user'] = $user;
            $centerId = $user['center_id'];

            if (!$centerId) {
                $output['error'] = 'User has no center_id';
                echo json_encode($output);
                return;
            }

            // 1. Requests
            $stmt = $pdo->query("SELECT COUNT(*) FROM requests WHERE center_id = $centerId");
            $output['total_requests'] = $stmt->fetchColumn();

            // 2. Teachers
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE center_id = $centerId AND role = 'teacher'");
            $output['total_teachers'] = $stmt->fetchColumn();
            
            echo json_encode(['success' => true, 'data' => $output]);

        } catch (\Exception $e) { 
            echo json_encode(['success' => false, 'error' => $e->getMessage()]); 
        }
    }
}
