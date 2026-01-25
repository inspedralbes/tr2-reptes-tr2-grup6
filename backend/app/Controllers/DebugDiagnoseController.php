<?php
namespace Controllers;

require_once __DIR__ . '/../Services/Database.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Allocation.php';
require_once __DIR__ . '/../Models/WorkshopSlot.php'; 

use Models\User;
use Models\Allocation;

class DebugDiagnoseController {
    public function run() {
        echo "<h1>Diagnosis Start v3</h1>";
        try {
            // 1. Check User 11
            echo "Step 1: Find User 11...<br>";
            $userId = 11; 
            
            $db = \Services\Database::getInstance();
            if (!$db) { echo "DB Connection Failed<br>"; return; }
            
            $user = User::findById($userId);
            
            if (!$user) {
                echo "User 11 NOT FOUND in DB users table.<br>";
            } else {
                echo "User 11 Found.<br>";
                echo "Role: " . ($user->role ?? 'N/A') . "<br>";
                echo "User CenterID: " . ($user->center_id ?? 'NULL') . "<br>";
            }
            
            // 2. Check Teacher Record explicitly
            echo "Step 2: Check Teacher Table...<br>";
            $email = $user ? $user->email : 'a@a.a'; // email from user 'a'
            $teacher = $db->fetchOne("SELECT * FROM teachers WHERE email = ?", [$email]);
            
            if ($teacher) {
                echo "Teacher Record Found via email '$email'.<br>";
                echo "Teacher ID: " . $teacher['id'] . "<br>";
                echo "Teacher CenterID: " . ($teacher['center_id'] ?? 'NULL') . "<br>";
            } else {
                echo "Teacher Record NOT FOUND for email '$email'.<br>";
            }
            
            // 3. Simulate Fallback Logic
            echo "Step 3: Simulating AllocationController Logic...<br>";
            $userCenterId = $user ? $user->center_id : null;
            if (!$userCenterId && $teacher) {
                $userCenterId = $teacher['center_id'];
                echo "Fallback applied. CenterID is now: $userCenterId<br>";
            } else {
                echo "No fallback needed or possible. CenterID: " . ($userCenterId ?? 'NULL') . "<br>";
            }
            
            $requestedCenterId = 7;
            
            if ($requestedCenterId && $userCenterId == $requestedCenterId) {
                echo "Logic Match! ($userCenterId == $requestedCenterId). Querying Allocation::findByCenter...<br>";
                $allocations = Allocation::findByCenter($userCenterId);
                echo "Allocations Count: " . count($allocations) . "<br>";
                
                if (count($allocations) > 0) {
                    echo "<pre>";
                    print_r($allocations[0]);
                    echo "</pre>";
                } else {
                    echo "Allocations array is empty.<br>";
                }
            } else {
                echo "Logic Mismatch. UserCenterId ($userCenterId) != Requested ($requestedCenterId).<br>";
            }
            
        } catch (\Exception $e) {
            echo "Exception: " . $e->getMessage() . "<br>" . $e->getTraceAsString();
        }
    }
}
