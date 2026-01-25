<?php
require_once __DIR__ . '/app/Services/Database.php';

use Services\Database;

// Mock database connection if needed or use existing
try {
    $db = Database::getInstance();
    
    echo "--- REQUESTS TABLE ---\n";
    $stmt = $db->query("SHOW CREATE TABLE requests");
    print_r($stmt[0]);
    
    echo "\n--- ALLOCATIONS TABLE ---\n";
    $stmt = $db->query("SHOW CREATE TABLE allocations");
    print_r($stmt[0]);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
