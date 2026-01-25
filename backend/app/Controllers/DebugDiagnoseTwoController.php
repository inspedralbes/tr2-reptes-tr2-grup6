<?php
namespace Controllers;

// require_once removed - relying on autoloader or index.php inclusions

use Models\User;
use Models\Allocation;

class DebugDiagnoseTwoController {
    public function run() {
        echo "<h1>Diagnosis V4 Simplified</h1>";
        echo "Starting...<br>";
        
        try {
            // Check if User class exists
            if (class_exists('Models\User')) {
                echo "User Class Exists<br>";
            } else {
                echo "User Class MISSING<br>";
            }

            // 1. Check User 11
            $userId = 11; 
            echo "Finding User $userId...<br>";
            
            $db = \Services\Database::getInstance();
            $user = User::findById($userId);
            
            if (!$user) {
                echo "User NOT FOUND<br>";
            } else {
                echo "User Found. Role: " . ($user->role ?? 'N/A') . "<br>";
                echo "CenterID: " . ($user->center_id ?? 'NULL') . "<br>";
            }
            
            // Check Teacher Table
            $email = $user ? $user->email : 'a@a.a';
            $teacher = $db->fetchOne("SELECT * FROM teachers WHERE email = ?", [$email]);
            
            if ($teacher) {
                echo "Teacher Found. CenterID: " . ($teacher['center_id'] ?? 'NULL') . "<br>";
            } else { echo "Teacher NOT Found.<br>"; }

            // Final check
             $userCenterId = $user ? $user->center_id : null;
             if (!$userCenterId && $teacher) $userCenterId = $teacher['center_id'];
             
             echo "Final CenterID: " . ($userCenterId ?? 'NULL') . "<br>";
             $alloc = Allocation::findByCenter(7);
             echo "Allocations for 7: " . count($alloc) . "<br>";

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
