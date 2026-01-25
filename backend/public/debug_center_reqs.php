<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/Config.php';

use Services\Database;

$centerId = 7; // ID del centro 'sdasd'

try {
    $db = Database::getInstance();
    $pdo = $db->getPDO();

    echo "--- CHECKING CENTER ID $centerId ---\n";

    // 1. Check Requests associated with this center
    echo "\n[Requests]\n";
    $stmt = $pdo->query("SELECT id, workshop_id, status FROM requests WHERE center_id = $centerId");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($requests) > 0) {
        foreach ($requests as $r) {
            echo "Req ID: {$r['id']} | Workshop: {$r['workshop_id']} | Status: {$r['status']}\n";
        }
    } else {
        echo "No requests found for center $centerId.\n";
    }

    // 2. Check Allocations associated with this center
    echo "\n[Allocations]\n";
    $stmt = $pdo->query("SELECT id, request_id, assigned_center_id, assigned_teacher_id, status FROM allocations WHERE assigned_center_id = $centerId");
    $allocs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($allocs) > 0) {
        foreach ($allocs as $a) {
            echo "Alloc ID: {$a['id']} | Req ID: {$a['request_id']} | Teacher: {$a['assigned_teacher_id']} | Status: {$a['status']}\n";
        }
    } else {
        echo "No allocations found for center $centerId.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
