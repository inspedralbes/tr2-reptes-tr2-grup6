<?php
// backend/public/debug_check_feedback.php
require_once __DIR__ . '/../config/Database.php';

try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT * FROM workshop_feedback");
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'count' => count($feedbacks),
        'rows' => $feedbacks
    ]);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
