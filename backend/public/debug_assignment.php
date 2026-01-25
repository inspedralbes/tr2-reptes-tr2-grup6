<?php
// backend/public/debug_assignment.php
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../config/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT id, workshop_id, center_id, slot_id FROM workshop_assignments LIMIT 5");
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'count' => count($assignments),
        'assignments' => $assignments
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
