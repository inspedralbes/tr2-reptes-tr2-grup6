<?php
require_once __DIR__ . '/config/Database.php';

try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT * FROM teachers");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total teachers: " . count($teachers) . "\n";
    print_r($teachers);
    
    $stmt2 = $db->query("SELECT * FROM centers");
    $centers = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "Total centers: " . count($centers) . "\n";
    print_r($centers);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
